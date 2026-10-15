import logging
import re
import unicodedata
from collections import defaultdict
from difflib import SequenceMatcher
from typing import Any, Literal

from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field

from header_mapper import HeaderMapper
from pdf_parser import parse_file

logger = logging.getLogger(__name__)

# --- Legacy Masterlist Verifier Types (v3.0.0 Compatibility) ---
EnrollmentStatus = Literal["enrolled", "not_enrolled", "needs_review"]
CorStatus = Literal["cor_printed", "no_cor_printed", "needs_review"]
QualificationStatus = Literal["qualified", "not_qualified", "needs_review"]
MatchStatus = Literal["matched", "possible_match", "unmatched", "ambiguous", "inconsistent"]


class MasterlistRecordIn(BaseModel):
    row_id: int
    student_id_number: str | None = None
    student_name: str | None = None
    campus_id: int | None = None


class RegistrarStudentIn(BaseModel):
    id: int
    student_id_number: str | None = None
    student_name: str | None = None
    first_name: str | None = None
    last_name: str | None = None
    campus_id: int | None = None
    campus: str | None = None
    enrollment_status: str = "enrolled"
    cor_printed: bool | None = None
    course: str | None = None
    year_level: str | None = None


class VerifyMasterlistRequest(BaseModel):
    records: list[MasterlistRecordIn] = Field(default_factory=list, max_length=500)
    registrar_students: list[RegistrarStudentIn] = Field(default_factory=list)


class MasterlistRecordOut(BaseModel):
    row_id: int
    match_status: MatchStatus
    enrollment_status: EnrollmentStatus
    cor_status: CorStatus
    qualification_status: QualificationStatus
    matched_student_id: int | None = None
    similarity_score: float | None = None
    campus_id: int | None = None
    remarks: str


class VerificationSummary(BaseModel):
    total_records: int
    enrolled_count: int
    not_enrolled_count: int
    cor_printed_count: int
    no_cor_printed_count: int
    qualified_count: int
    not_qualified_count: int
    needs_review_count: int


class VerifyMasterlistResponse(BaseModel):
    service_version: str = "4.0.0"
    summary: VerificationSummary
    records: list[MasterlistRecordOut]


# --- Grantee Verification Pipeline Types (v4.0.0) ---
GranteeVerificationStatus = Literal["GREEN_VERIFIED", "AMBER_CONFLICT", "RED_UNMATCHED"]
GranteeMatchTier = Literal["EXACT_ID", "EXACT_NAME", "FUZZY_NAME", "MANUAL_OVERRIDE"]


class GranteeRecordIn(BaseModel):
    row_id: int
    student_id: str | None = None
    student_id_number: str | None = None
    student_name: str | None = None
    first_name: str | None = None
    middle_name: str | None = None
    last_name: str | None = None
    campus: str | None = None
    campus_id: int | None = None
    program: str | None = None
    scholarship_name: str | None = None
    scholarship_program: str | None = None
    source_pdf_name: str | None = None
    is_incomplete_name: bool = False


class CandidateMatch(BaseModel):
    registrar_student_id: int
    student_name: str
    student_id_number: str | None = None
    campus_id: int | None = None
    score: float


class GranteeRecordOut(BaseModel):
    row_id: int
    verification_status: GranteeVerificationStatus
    match_tier: GranteeMatchTier | None = None
    confidence_score: float | None = None
    matched_registrar_student_id: int | None = None
    resolved_campus_id: int | None = None
    resolved_campus: str | None = None
    resolved_program: str | None = None
    conflict_reason: str | None = None
    candidate_matches: list[CandidateMatch] | None = None
    remarks: str
    source_pdf_name: str | None = None
    scholarship_program: str | None = None
    enrollment_status: str | None = None


class GranteeVerificationSummary(BaseModel):
    total_records: int
    green_count: int
    amber_count: int
    red_count: int


class VerifyGranteesRequest(BaseModel):
    records: list[GranteeRecordIn] = Field(default_factory=list, max_length=500)
    registrar_students: list[RegistrarStudentIn] = Field(default_factory=list)
    existing_grantee_student_ids: list[int] = Field(default_factory=list)


class VerifyGranteesResponse(BaseModel):
    service_version: str = "4.0.0"
    summary: GranteeVerificationSummary
    records: list[GranteeRecordOut]


# --- App Initialization ---
app = FastAPI(title="ScholarSync Masterlist Verifier", version="4.0.0")


@app.get("/health-check")
def health_check() -> dict[str, str]:
    return {"status": "ok", "service": "masterlist-verifier", "version": "4.0.0"}


def _process_single_file(file_bytes: bytes, filename: str) -> dict[str, Any]:
    """Parse and normalize a single file's bytes."""
    file_type, raw_headers, raw_rows = parse_file(file_bytes, filename)
    if not raw_rows:
        return {
            "success": False,
            "error": f"No tabular data found in {file_type.upper()} file.",
            "file_type": file_type,
            "raw_headers": raw_headers,
            "normalized_headers": [],
            "unmapped_headers": [],
            "missing_fields": [],
            "rows": [],
            "parse_warnings": [f"{filename}: extracted 0 rows."],
        }

    normalized_headers, unmapped_headers, missing_fields = HeaderMapper.normalize_headers(raw_headers)
    rows: list[dict[str, Any]] = []
    parse_warnings: list[str] = []

    for idx, r in enumerate(raw_rows, start=1):
        norm_row = HeaderMapper.normalize_row(r, normalized_headers, raw_headers)
        norm_row["row_number"] = idx
        norm_row.setdefault("source_pdf_name", filename)
        if not norm_row.get("scholarship_program"):
            norm_row["scholarship_program"] = norm_row.get("scholarship_name") or "TES-UniFAST"

        # Check for completely missing student name
        if not norm_row.get("student_name") and not norm_row.get("first_name"):
            parse_warnings.append(f"{filename} Row {idx}: missing student name.")

        rows.append(norm_row)

    return {
        "success": True,
        "file_type": file_type,
        "raw_headers": raw_headers,
        "normalized_headers": normalized_headers,
        "unmapped_headers": unmapped_headers,
        "missing_fields": missing_fields,
        "rows": rows,
        "total_rows": len(rows),
        "parse_warnings": parse_warnings,
    }


# --- Phase 2: File Parsing Endpoints ---
@app.post("/parse-file")
async def parse_file_endpoint(file: UploadFile = File(...)):
    """Parse a single uploaded PDF, XLSX, or CSV file and return normalized tabular data."""
    try:
        file_bytes = await file.read()
        if not file_bytes:
            raise HTTPException(status_code=400, detail="Uploaded file is empty.")

        filename = file.filename or "unknown.csv"
        result = _process_single_file(file_bytes, filename)

        if not result["success"]:
            return JSONResponse(
                status_code=422,
                content={
                    "success": False,
                    "error": result["error"],
                    "detail": "The parser extracted 0 data rows.",
                },
            )

        return result

    except ValueError as ve:
        return JSONResponse(
            status_code=400,
            content={"success": False, "error": str(ve)},
        )
    except Exception as e:
        logger.exception("File parsing error")
        return JSONResponse(
            status_code=500,
            content={"success": False, "error": "Internal error during file parsing.", "detail": str(e)},
        )


@app.post("/parse-files")
async def parse_files_endpoint(files: list[UploadFile] = File(...)):
    """Batch upload and parse 1 to N PDF, XLSX, or CSV documents simultaneously."""
    if not files:
        raise HTTPException(status_code=400, detail="No files uploaded.")

    all_rows: list[dict[str, Any]] = []
    all_warnings: list[str] = []
    processed_files: list[str] = []
    file_types: list[str] = []
    missing_fields_set: set[str] = set()

    current_row_id = 1

    try:
        for f in files:
            file_bytes = await f.read()
            if not file_bytes:
                all_warnings.append(f"Skipped empty file: {f.filename}")
                continue

            filename = f.filename or f"document_{current_row_id}.pdf"
            processed_files.append(filename)

            res = _process_single_file(file_bytes, filename)
            if not res["success"]:
                all_warnings.extend(res.get("parse_warnings", []))
                continue

            file_types.append(res["file_type"])
            all_warnings.extend(res.get("parse_warnings", []))
            for mf in res.get("missing_fields", []):
                missing_fields_set.add(mf)

            for row in res["rows"]:
                row_copy = dict(row)
                row_copy["row_number"] = current_row_id
                all_rows.append(row_copy)
                current_row_id += 1

        if not all_rows:
            return JSONResponse(
                status_code=422,
                content={
                    "success": False,
                    "error": "No tabular data found across uploaded files.",
                    "detail": f"Processed {len(processed_files)} files but extracted 0 data rows.",
                    "warnings": all_warnings,
                },
            )

        return {
            "success": True,
            "files_processed": processed_files,
            "total_files": len(processed_files),
            "file_types": list(set(file_types)),
            "rows": all_rows,
            "total_rows": len(all_rows),
            "missing_fields": list(missing_fields_set),
            "parse_warnings": all_warnings,
        }

    except Exception as e:
        logger.exception("Batch file parsing error")
        return JSONResponse(
            status_code=500,
            content={"success": False, "error": "Internal error during batch file parsing.", "detail": str(e)},
        )


# --- Phase 3: POST /verify-grantees ---
@app.post("/verify-grantees", response_model=VerifyGranteesResponse)
def verify_grantees(payload: VerifyGranteesRequest) -> VerifyGranteesResponse:
    """3-Tier verification and conflict detection of grantee records against SIS students.

    Tier 1: Exact Student ID or exact normalized name match.
    Tier 2: Fuzzy Typo match (SequenceMatcher >= 0.72) within matching surnames.
    Tier 3: Incomplete / Surname-only candidate matching.
    Conflict Engine: Detects cross-category double-dipping, duplicate entries, unenrollment, and No-COR status.
    """
    existing_grantee_ids = set(payload.existing_grantee_student_ids)

    # 1. Index registrar students
    by_student_id: dict[str, list[RegistrarStudentIn]] = defaultdict(list)
    by_exact_name: dict[str, list[RegistrarStudentIn]] = defaultdict(list)
    by_surname: dict[str, list[RegistrarStudentIn]] = defaultdict(list)
    by_campus: dict[int | None, list[RegistrarStudentIn]] = defaultdict(list)

    for s in payload.registrar_students:
        s_id = normalize_value(s.student_id_number)
        if s_id:
            by_student_id[s_id].append(s)

        s_name = normalize_name(s.student_name)
        if s_name:
            by_exact_name[s_name].append(s)

        # Derive surname from explicit field or full name
        surname = s.last_name
        if not surname and s.student_name:
            split_res = HeaderMapper.split_full_name(s.student_name)
            surname = split_res.get("last_name")
        if surname:
            norm_sur = normalize_name(surname)
            if norm_sur:
                by_surname[norm_sur].append(s)

        by_campus[s.campus_id].append(s)

    # 2. Pre-scan for intra-batch and cross-category conflicts
    ident_to_records: dict[str, list[GranteeRecordIn]] = defaultdict(list)
    for rec in payload.records:
        ident = normalize_value(rec.student_id or rec.student_id_number)
        if not ident:
            ident = normalize_name(rec.student_name or f"{rec.last_name or ''} {rec.first_name or ''}")
        if ident:
            ident_to_records[ident].append(rec)

    results: list[GranteeRecordOut] = []

    for record in payload.records:
        rec_id = normalize_value(record.student_id or record.student_id_number)
        rec_prog = record.scholarship_program or record.scholarship_name or "TES-UniFAST"
        rec_source = record.source_pdf_name

        full_name = record.student_name
        rec_first = record.first_name
        rec_surname = record.last_name
        is_incomplete = record.is_incomplete_name

        if full_name and (not rec_surname or not rec_first):
            split_res = HeaderMapper.split_full_name(full_name)
            if not rec_surname:
                rec_surname = split_res.get("last_name")
            if not rec_first:
                rec_first = split_res.get("first_name")
            if split_res.get("is_incomplete"):
                is_incomplete = True

        if not full_name and (rec_surname or rec_first):
            full_name = " ".join(p for p in [rec_surname, rec_first] if p)
            if not rec_first:
                is_incomplete = True

        rec_name = normalize_name(full_name)
        norm_rec_surname = normalize_name(rec_surname) if rec_surname else None

        ident = rec_id or rec_name

        # Check for cross-category double-dipping in the uploaded batch
        if ident and len(ident_to_records.get(ident, [])) > 1:
            batch_records = ident_to_records[ident]
            programs = set(r.scholarship_program or r.scholarship_name for r in batch_records if r.scholarship_program or r.scholarship_name)
            if len(programs) > 1:
                results.append(
                    GranteeRecordOut(
                        row_id=record.row_id,
                        verification_status="AMBER_CONFLICT",
                        match_tier="EXACT_NAME" if rec_name else "EXACT_ID",
                        confidence_score=1.0,
                        conflict_reason="DOUBLE_GRANT",
                        remarks=f"Cross-category conflict: Student appears across multiple scholarship programs ({', '.join(sorted(programs))}). Administrative hold.",
                        source_pdf_name=rec_source,
                        scholarship_program=rec_prog,
                    )
                )
                continue

        # --- Tier 1: Exact Student ID Match ---
        if rec_id and rec_id in by_student_id:
            candidates = by_student_id[rec_id]
            if len(candidates) == 1:
                student = candidates[0]
                norm_status = normalize_value(student.enrollment_status).replace(" ", "_")
                if norm_status in ("unenrolled", "not_enrolled", "withdrawn"):
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="RED_UNMATCHED",
                            match_tier="EXACT_ID",
                            confidence_score=1.0,
                            matched_registrar_student_id=student.id,
                            resolved_campus_id=student.campus_id,
                            resolved_campus=student.campus,
                            resolved_program=student.course,
                            conflict_reason="UNENROLLED",
                            remarks="Ineligible: Student is officially unenrolled in SIS.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="UNENROLLED",
                        )
                    )
                elif norm_status in ("no_cor_printed", "no_cor") or student.cor_printed is False:
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="AMBER_CONFLICT",
                            match_tier="EXACT_ID",
                            confidence_score=1.0,
                            matched_registrar_student_id=student.id,
                            resolved_campus_id=student.campus_id,
                            resolved_campus=student.campus,
                            resolved_program=student.course,
                            conflict_reason="NO_COR_PRINTED",
                            remarks="Administrative hold: Student enrolled without printed COR.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="NO_COR_PRINTED",
                        )
                    )
                elif student.id in existing_grantee_ids:
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="AMBER_CONFLICT",
                            match_tier="EXACT_ID",
                            confidence_score=1.0,
                            matched_registrar_student_id=student.id,
                            resolved_campus_id=student.campus_id,
                            resolved_campus=student.campus,
                            resolved_program=student.course,
                            conflict_reason="DOUBLE_GRANT",
                            remarks="Double-grant conflict: Student already holds an active scholarship.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="ENROLLED",
                        )
                    )
                else:
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="GREEN_VERIFIED",
                            match_tier="EXACT_ID",
                            confidence_score=1.0,
                            matched_registrar_student_id=student.id,
                            resolved_campus_id=student.campus_id,
                            resolved_campus=student.campus,
                            resolved_program=student.course,
                            conflict_reason=None,
                            remarks=f"Exact student ID match. Enrolled: {student.enrollment_status}.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="ENROLLED",
                        )
                    )
                continue
            else:
                cand_list = [
                    CandidateMatch(
                        registrar_student_id=c.id,
                        student_name=c.student_name or "",
                        student_id_number=c.student_id_number,
                        campus_id=c.campus_id,
                        score=1.0,
                    )
                    for c in candidates
                ]
                results.append(
                    GranteeRecordOut(
                        row_id=record.row_id,
                        verification_status="AMBER_CONFLICT",
                        match_tier="EXACT_ID",
                        confidence_score=1.0,
                        conflict_reason="MULTI_MATCH",
                        candidate_matches=cand_list,
                        remarks="Multiple students found with the same student ID. Chairman review required.",
                        source_pdf_name=rec_source,
                        scholarship_program=rec_prog,
                    )
                )
                continue

        # --- Tier 1: Exact Name Match ---
        if rec_name and rec_name in by_exact_name:
            candidates = by_exact_name[rec_name]
            if record.campus_id:
                campus_candidates = [c for c in candidates if c.campus_id == record.campus_id]
                if campus_candidates:
                    candidates = campus_candidates

            if len(candidates) == 1:
                student = candidates[0]
                norm_status = normalize_value(student.enrollment_status).replace(" ", "_")
                if norm_status in ("unenrolled", "not_enrolled", "withdrawn"):
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="RED_UNMATCHED",
                            match_tier="EXACT_NAME",
                            confidence_score=0.98,
                            matched_registrar_student_id=student.id,
                            resolved_campus_id=student.campus_id,
                            resolved_campus=student.campus,
                            resolved_program=student.course,
                            conflict_reason="UNENROLLED",
                            remarks="Ineligible: Student is officially unenrolled in SIS.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="UNENROLLED",
                        )
                    )
                elif norm_status in ("no_cor_printed", "no_cor") or student.cor_printed is False:
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="AMBER_CONFLICT",
                            match_tier="EXACT_NAME",
                            confidence_score=0.98,
                            matched_registrar_student_id=student.id,
                            resolved_campus_id=student.campus_id,
                            resolved_campus=student.campus,
                            resolved_program=student.course,
                            conflict_reason="NO_COR_PRINTED",
                            remarks="Administrative hold: Student enrolled without printed COR.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="NO_COR_PRINTED",
                        )
                    )
                elif student.id in existing_grantee_ids:
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="AMBER_CONFLICT",
                            match_tier="EXACT_NAME",
                            confidence_score=0.98,
                            matched_registrar_student_id=student.id,
                            resolved_campus_id=student.campus_id,
                            resolved_campus=student.campus,
                            resolved_program=student.course,
                            conflict_reason="DOUBLE_GRANT",
                            remarks="Exact name match, but student already has an active scholarship.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="ENROLLED",
                        )
                    )
                else:
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="GREEN_VERIFIED",
                            match_tier="EXACT_NAME",
                            confidence_score=0.98,
                            matched_registrar_student_id=student.id,
                            resolved_campus_id=student.campus_id,
                            resolved_campus=student.campus,
                            resolved_program=student.course,
                            conflict_reason=None,
                            remarks="Confident exact name match with SIS records.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="ENROLLED",
                        )
                    )
                continue
            elif len(candidates) > 1:
                cand_list = [
                    CandidateMatch(
                        registrar_student_id=c.id,
                        student_name=c.student_name or "",
                        student_id_number=c.student_id_number,
                        campus_id=c.campus_id,
                        score=0.98,
                    )
                    for c in candidates
                ]
                results.append(
                    GranteeRecordOut(
                        row_id=record.row_id,
                        verification_status="AMBER_CONFLICT",
                        match_tier="EXACT_NAME",
                        confidence_score=0.98,
                        conflict_reason="MULTI_MATCH",
                        candidate_matches=cand_list,
                        remarks="Multiple students found with the same name across campuses.",
                        source_pdf_name=rec_source,
                        scholarship_program=rec_prog,
                    )
                )
                continue

        # --- Tier 3: Incomplete / Surname-Only Match ---
        # If the source entry has no first name, match all students sharing that surname
        if is_incomplete and norm_rec_surname in by_surname:
            cands = by_surname[norm_rec_surname]
            cand_list = [
                CandidateMatch(
                    registrar_student_id=c.id,
                    student_name=c.student_name or "",
                    student_id_number=c.student_id_number,
                    campus_id=c.campus_id,
                    score=0.70,
                )
                for c in cands[:5]
            ]
            results.append(
                GranteeRecordOut(
                    row_id=record.row_id,
                    verification_status="AMBER_CONFLICT",
                    match_tier=None,
                    confidence_score=0.70,
                    conflict_reason="INCOMPLETE_NAME",
                    candidate_matches=cand_list,
                    remarks=f"Incomplete source entry: First name missing. Found {len(cands)} candidate(s) sharing surname '{record.last_name or rec_surname}'.",
                    source_pdf_name=rec_source,
                    scholarship_program=rec_prog,
                )
            )
            continue

        # --- Tier 2: Fuzzy Typo Match (SequenceMatcher >= 0.72) ---
        # Prioritize matching within same surname pool if available, else campus pool or all students
        if rec_name and not is_incomplete:
            pool = by_surname.get(norm_rec_surname, []) if norm_rec_surname else []
            if not pool:
                pool = by_campus.get(record.campus_id, []) if record.campus_id else payload.registrar_students

            scored: list[tuple[float, RegistrarStudentIn]] = []
            for st in pool:
                st_norm = normalize_name(st.student_name)
                if not st_norm:
                    continue
                score = SequenceMatcher(None, rec_name, st_norm).ratio()
                if score >= 0.72:
                    scored.append((score, st))

            # If surname pool didn't yield >= 0.72, search full campus/global pool
            if not scored and pool is not payload.registrar_students:
                fallback_pool = by_campus.get(record.campus_id, []) if record.campus_id else payload.registrar_students
                for st in fallback_pool:
                    st_norm = normalize_name(st.student_name)
                    if not st_norm:
                        continue
                    score = SequenceMatcher(None, rec_name, st_norm).ratio()
                    if score >= 0.72:
                        scored.append((score, st))

            scored.sort(key=lambda x: x[0], reverse=True)

            if scored:
                top_score, top_student = scored[0]
                is_ambiguous = len(scored) > 1 and (scored[0][0] - scored[1][0] < 0.05)
                cand_list = [
                    CandidateMatch(
                        registrar_student_id=st.id,
                        student_name=st.student_name or "",
                        student_id_number=st.student_id_number,
                        campus_id=st.campus_id,
                        score=round(sc, 4),
                    )
                    for sc, st in scored[:5]
                ]

                norm_status = normalize_value(top_student.enrollment_status).replace(" ", "_")

                if top_student.id in existing_grantee_ids:
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="AMBER_CONFLICT",
                            match_tier="FUZZY_NAME",
                            confidence_score=round(top_score, 4),
                            matched_registrar_student_id=top_student.id,
                            resolved_campus_id=top_student.campus_id,
                            resolved_campus=top_student.campus,
                            resolved_program=top_student.course,
                            conflict_reason="DOUBLE_GRANT",
                            candidate_matches=cand_list,
                            remarks=f"Possible match ({top_score:.0%}), but student already has an active scholarship.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                        )
                    )
                    continue

                if norm_status in ("unenrolled", "not_enrolled", "withdrawn"):
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="RED_UNMATCHED",
                            match_tier="FUZZY_NAME",
                            confidence_score=round(top_score, 4),
                            matched_registrar_student_id=top_student.id,
                            resolved_campus_id=top_student.campus_id,
                            resolved_campus=top_student.campus,
                            resolved_program=top_student.course,
                            conflict_reason="UNENROLLED",
                            remarks=f"Ineligible: Matched student ({top_student.student_name}) is officially unenrolled.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="UNENROLLED",
                        )
                    )
                    continue

                if norm_status in ("no_cor_printed", "no_cor") or top_student.cor_printed is False:
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="AMBER_CONFLICT",
                            match_tier="FUZZY_NAME",
                            confidence_score=round(top_score, 4),
                            matched_registrar_student_id=top_student.id,
                            resolved_campus_id=top_student.campus_id,
                            resolved_campus=top_student.campus,
                            resolved_program=top_student.course,
                            conflict_reason="NO_COR_PRINTED",
                            candidate_matches=cand_list,
                            remarks="Administrative hold: Matched student enrolled without printed COR.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="NO_COR_PRINTED",
                        )
                    )
                    continue

                if is_ambiguous:
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="AMBER_CONFLICT",
                            match_tier="FUZZY_NAME",
                            confidence_score=round(top_score, 4),
                            conflict_reason="MULTI_MATCH",
                            candidate_matches=cand_list,
                            remarks="Multiple students with similar names found. Resolution required.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                        )
                    )
                    continue

                if top_score >= 0.90:
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="GREEN_VERIFIED",
                            match_tier="FUZZY_NAME",
                            confidence_score=round(top_score, 4),
                            matched_registrar_student_id=top_student.id,
                            resolved_campus_id=top_student.campus_id,
                            resolved_campus=top_student.campus,
                            resolved_program=top_student.course,
                            conflict_reason=None,
                            remarks=f"High confidence fuzzy match ({top_score:.0%} name similarity).",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="ENROLLED",
                        )
                    )
                    continue
                else:
                    # 0.72 <= top_score < 0.90: Amber typo match for Chairman confirmation
                    results.append(
                        GranteeRecordOut(
                            row_id=record.row_id,
                            verification_status="AMBER_CONFLICT",
                            match_tier="FUZZY_NAME",
                            confidence_score=round(top_score, 4),
                            matched_registrar_student_id=top_student.id,
                            resolved_campus_id=top_student.campus_id,
                            resolved_campus=top_student.campus,
                            resolved_program=top_student.course,
                            candidate_matches=cand_list,
                            conflict_reason="FUZZY_TYPO",
                            remarks=f"Fuzzy typo match ({top_score:.0%}): Matched to {top_student.student_name}. Chairman confirmation required.",
                            source_pdf_name=rec_source,
                            scholarship_program=rec_prog,
                            enrollment_status="ENROLLED",
                        )
                    )
                    continue


        # --- Tier 4: Unmatched ---
        results.append(
            GranteeRecordOut(
                row_id=record.row_id,
                verification_status="RED_UNMATCHED",
                match_tier=None,
                confidence_score=None,
                matched_registrar_student_id=None,
                resolved_campus_id=None,
                resolved_program=None,
                conflict_reason=None,
                candidate_matches=None,
                remarks="No matching student record found in SIS.",
                source_pdf_name=rec_source,
                scholarship_program=rec_prog,
            )
        )

    return VerifyGranteesResponse(
        service_version="4.0.0",
        summary=GranteeVerificationSummary(
            total_records=len(results),
            green_count=sum(1 for r in results if r.verification_status == "GREEN_VERIFIED"),
            amber_count=sum(1 for r in results if r.verification_status == "AMBER_CONFLICT"),
            red_count=sum(1 for r in results if r.verification_status == "RED_UNMATCHED"),
        ),
        records=results,
    )


# --- Legacy /verify-masterlist Endpoint (v3.0.0 Compatibility) ---
@app.post("/verify-masterlist", response_model=VerifyMasterlistResponse)
def verify_masterlist(payload: VerifyMasterlistRequest) -> VerifyMasterlistResponse:
    by_id: dict[str, list[RegistrarStudentIn]] = defaultdict(list)
    by_name: dict[str, list[RegistrarStudentIn]] = defaultdict(list)
    by_campus: dict[int | None, list[RegistrarStudentIn]] = defaultdict(list)
    for student in payload.registrar_students:
        student_id = normalize_value(student.student_id_number)
        name = normalize_name(student.student_name)
        if student_id:
            by_id[student_id].append(student)
        if name:
            by_name[name].append(student)
        by_campus[student.campus_id].append(student)

    results = [verify_record(record, by_id, by_name, by_campus) for record in payload.records]
    return VerifyMasterlistResponse(
        service_version="4.0.0",
        summary=VerificationSummary(
            total_records=len(results),
            enrolled_count=count(results, "enrollment_status", "enrolled"),
            not_enrolled_count=count(results, "enrollment_status", "not_enrolled"),
            cor_printed_count=count(results, "cor_status", "cor_printed"),
            no_cor_printed_count=count(results, "cor_status", "no_cor_printed"),
            qualified_count=count(results, "qualification_status", "qualified"),
            not_qualified_count=count(results, "qualification_status", "not_qualified"),
            needs_review_count=count(results, "qualification_status", "needs_review"),
        ),
        records=results,
    )


def verify_record(record, by_id, by_name, by_campus) -> MasterlistRecordOut:
    student_id = normalize_value(record.student_id_number)
    name = normalize_name(record.student_name)
    candidates = by_id.get(student_id, []) if student_id else by_name.get(name, []) if name else []

    if not candidates:
        fuzzy_result = fuzzy_suggestion(name, by_campus.get(record.campus_id, []))
        if fuzzy_result and fuzzy_result[0] == "ambiguous":
            return review(record, "ambiguous", "Multiple same-campus official records have similar names; Registrar selection is required.")
        if fuzzy_result:
            _, student, score = fuzzy_result
            return review(
                record,
                "possible_match",
                f"Possible same-campus official student match ({score:.0%} name similarity); Registrar confirmation is required.",
                matched_student_id=student.id,
                similarity_score=score,
            )
        return review(record, "unmatched", "No confident official student match was found.")
    same_campus = [student for student in candidates if student.campus_id == record.campus_id]
    if not same_campus:
        return review(record, "inconsistent", "A possible student match exists in another campus.")
    if len(same_campus) != 1:
        return review(record, "ambiguous", "Multiple official student records match; Registrar review is required.")

    student = same_campus[0]
    enrollment = "enrolled" if normalize_value(student.enrollment_status).replace(" ", "_") == "enrolled" else "not_enrolled"
    if student.cor_printed is None:
        cor = "needs_review"
    else:
        cor = "cor_printed" if student.cor_printed else "no_cor_printed"
    qualification = "qualified" if enrollment == "enrolled" and cor == "cor_printed" else "needs_review" if cor == "needs_review" else "not_qualified"
    return MasterlistRecordOut(
        row_id=record.row_id,
        match_status="matched",
        enrollment_status=enrollment,
        cor_status=cor,
        qualification_status=qualification,
        matched_student_id=student.id,
        campus_id=student.campus_id,
        remarks="Confident campus-scoped official student match.",
    )


def review(
    record,
    match_status: MatchStatus,
    remarks: str,
    matched_student_id: int | None = None,
    similarity_score: float | None = None,
) -> MasterlistRecordOut:
    return MasterlistRecordOut(
        row_id=record.row_id,
        match_status=match_status,
        enrollment_status="needs_review",
        cor_status="needs_review",
        qualification_status="needs_review",
        matched_student_id=matched_student_id,
        similarity_score=similarity_score,
        campus_id=record.campus_id,
        remarks=remarks,
    )


def fuzzy_suggestion(
    normalized_name: str, campus_students: list[RegistrarStudentIn]
) -> tuple[Literal["possible_match", "ambiguous"], RegistrarStudentIn | None, float] | None:
    if not normalized_name:
        return None

    scored = sorted(
        (
            (SequenceMatcher(None, normalized_name, normalize_name(student.student_name)).ratio(), student)
            for student in campus_students
            if normalize_name(student.student_name)
        ),
        key=lambda item: item[0],
        reverse=True,
    )
    if not scored or scored[0][0] < 0.90:
        return None
    if len(scored) > 1 and scored[0][0] - scored[1][0] < 0.05:
        return "ambiguous", None, round(scored[0][0], 4)

    return "possible_match", scored[0][1], round(scored[0][0], 4)


def normalize_name(value: str | None) -> str:
    normalized = unicodedata.normalize("NFKD", value or "")
    ascii_name = "".join(character for character in normalized if not unicodedata.combining(character))
    return " ".join(sorted(re.findall(r"[a-z0-9]+", ascii_name.casefold())))


def normalize_value(value: str | None) -> str:
    return " ".join((value or "").strip().casefold().replace("-", " ").split())


def count(records: list[MasterlistRecordOut], field: str, value: str) -> int:
    return sum(1 for record in records if getattr(record, field) == value)
