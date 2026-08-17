import re
import unicodedata
from collections import defaultdict
from typing import Literal

from fastapi import FastAPI
from pydantic import BaseModel, Field

VerificationStatus = Literal["enrolled", "no_cor_printed", "unenrolled"]


class MasterlistRecordIn(BaseModel):
    row_id: int
    student_name: str | None = None


class RegistrarStudentIn(BaseModel):
    id: int
    student_name: str | None = None
    campus_id: int | None = None
    enrollment_status: str = "enrolled"
    cor_printed: bool = False


class VerifyMasterlistRequest(BaseModel):
    records: list[MasterlistRecordIn] = Field(default_factory=list)
    registrar_students: list[RegistrarStudentIn] = Field(default_factory=list)


class MasterlistRecordOut(BaseModel):
    row_id: int
    status: VerificationStatus
    matched_student_id: int | None = None
    campus_id: int | None = None
    remarks: str | None = None


class VerificationSummary(BaseModel):
    total_records: int
    enrolled_count: int
    no_cor_printed_count: int
    unenrolled_count: int


class VerifyMasterlistResponse(BaseModel):
    summary: VerificationSummary
    records: list[MasterlistRecordOut]


app = FastAPI(title="ScholarSync Masterlist Verifier", version="2.0.0")


@app.get("/health-check")
def health_check() -> dict[str, str]:
    return {"status": "ok", "service": "masterlist-verifier"}


@app.post("/verify-masterlist", response_model=VerifyMasterlistResponse)
def verify_masterlist(payload: VerifyMasterlistRequest) -> VerifyMasterlistResponse:
    enrolled_by_name: dict[str, list[RegistrarStudentIn]] = defaultdict(list)

    for student in payload.registrar_students:
        name = normalize_name(student.student_name)
        status = normalize_value(student.enrollment_status).replace(" ", "_")
        if name and status == "enrolled":
            enrolled_by_name[name].append(student)

    verified_records: list[MasterlistRecordOut] = []

    for record in payload.records:
        name = normalize_name(record.student_name)
        matches = enrolled_by_name.get(name, []) if name else []

        if not name:
            result = MasterlistRecordOut(row_id=record.row_id, status="unenrolled", remarks="Student name is required.")
        elif len(matches) > 1:
            result = MasterlistRecordOut(row_id=record.row_id, status="unenrolled", remarks="Multiple enrolled students have this name; manual checking is required.")
        elif not matches:
            result = MasterlistRecordOut(row_id=record.row_id, status="unenrolled", remarks="No matching enrolled student record found.")
        else:
            student = matches[0]
            status: VerificationStatus = "enrolled" if student.cor_printed else "no_cor_printed"
            result = MasterlistRecordOut(
                row_id=record.row_id,
                status=status,
                matched_student_id=student.id,
                campus_id=student.campus_id,
                remarks="Matched enrolled Registrar record with printed COR." if student.cor_printed else "Matched enrolled Registrar record; COR has not been printed.",
            )

        verified_records.append(result)

    return VerifyMasterlistResponse(
        summary=VerificationSummary(
            total_records=len(verified_records),
            enrolled_count=count_status(verified_records, "enrolled"),
            no_cor_printed_count=count_status(verified_records, "no_cor_printed"),
            unenrolled_count=count_status(verified_records, "unenrolled"),
        ),
        records=verified_records,
    )


def normalize_name(value: str | None) -> str:
    normalized = unicodedata.normalize("NFKD", value or "")
    ascii_name = "".join(character for character in normalized if not unicodedata.combining(character))
    return " ".join(sorted(re.findall(r"[a-z0-9]+", ascii_name.casefold())))


def normalize_value(value: str | None) -> str:
    return " ".join((value or "").strip().casefold().replace("-", " ").split())


def count_status(records: list[MasterlistRecordOut], status: VerificationStatus) -> int:
    return sum(1 for record in records if record.status == status)
