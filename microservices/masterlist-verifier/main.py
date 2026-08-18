import re
import unicodedata
from collections import defaultdict
from typing import Literal

from fastapi import FastAPI
from pydantic import BaseModel, Field

EnrollmentStatus = Literal["enrolled", "not_enrolled", "needs_review"]
CorStatus = Literal["cor_printed", "no_cor_printed", "needs_review"]
QualificationStatus = Literal["qualified", "not_qualified", "needs_review"]
MatchStatus = Literal["matched", "unmatched", "ambiguous", "inconsistent"]


class MasterlistRecordIn(BaseModel):
    row_id: int
    student_id_number: str | None = None
    student_name: str | None = None
    campus_id: int | None = None


class RegistrarStudentIn(BaseModel):
    id: int
    student_id_number: str | None = None
    student_name: str | None = None
    campus_id: int | None = None
    enrollment_status: str = "enrolled"
    cor_printed: bool | None = None


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
    service_version: str = "3.0.0"
    summary: VerificationSummary
    records: list[MasterlistRecordOut]


app = FastAPI(title="ScholarSync Masterlist Verifier", version="3.0.0")


@app.get("/health-check")
def health_check() -> dict[str, str]:
    return {"status": "ok", "service": "masterlist-verifier", "version": "3.0.0"}


@app.post("/verify-masterlist", response_model=VerifyMasterlistResponse)
def verify_masterlist(payload: VerifyMasterlistRequest) -> VerifyMasterlistResponse:
    by_id: dict[str, list[RegistrarStudentIn]] = defaultdict(list)
    by_name: dict[str, list[RegistrarStudentIn]] = defaultdict(list)
    for student in payload.registrar_students:
        student_id = normalize_value(student.student_id_number)
        name = normalize_name(student.student_name)
        if student_id:
            by_id[student_id].append(student)
        if name:
            by_name[name].append(student)

    results = [verify_record(record, by_id, by_name) for record in payload.records]
    return VerifyMasterlistResponse(
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


def verify_record(record, by_id, by_name) -> MasterlistRecordOut:
    student_id = normalize_value(record.student_id_number)
    name = normalize_name(record.student_name)
    candidates = by_id.get(student_id, []) if student_id else by_name.get(name, []) if name else []

    if not candidates:
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


def review(record, match_status: MatchStatus, remarks: str) -> MasterlistRecordOut:
    return MasterlistRecordOut(
        row_id=record.row_id,
        match_status=match_status,
        enrollment_status="needs_review",
        cor_status="needs_review",
        qualification_status="needs_review",
        campus_id=record.campus_id,
        remarks=remarks,
    )


def normalize_name(value: str | None) -> str:
    normalized = unicodedata.normalize("NFKD", value or "")
    ascii_name = "".join(character for character in normalized if not unicodedata.combining(character))
    return " ".join(sorted(re.findall(r"[a-z0-9]+", ascii_name.casefold())))


def normalize_value(value: str | None) -> str:
    return " ".join((value or "").strip().casefold().replace("-", " ").split())


def count(records: list[MasterlistRecordOut], field: str, value: str) -> int:
    return sum(1 for record in records if getattr(record, field) == value)
