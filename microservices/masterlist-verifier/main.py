from typing import Literal

from fastapi import FastAPI
from pydantic import BaseModel, Field


VerificationStatus = Literal["enrolled", "unenrolled", "duplicate", "invalid"]


class MasterlistRecordIn(BaseModel):
    row_id: int
    student_id_number: str | None = None
    student_name: str | None = None
    scholarship_program: str | None = None
    fund_source: str | None = None


class EnrolledStudentIn(BaseModel): 
    id: int
    student_id_number: str
    student_name: str | None = None


class VerifyMasterlistRequest(BaseModel):
    records: list[MasterlistRecordIn] = Field(default_factory=list)
    enrolled_students: list[EnrolledStudentIn] = Field(default_factory=list)


class MasterlistRecordOut(BaseModel):
    row_id: int
    status: VerificationStatus
    matched_student_id: int | None = None
    remarks: str | None = None


class VerificationSummary(BaseModel):
    total_records: int
    enrolled_count: int
    unenrolled_count: int
    duplicate_count: int
    invalid_count: int


class VerifyMasterlistResponse(BaseModel):
    summary: VerificationSummary
    records: list[MasterlistRecordOut]


app = FastAPI(title="ScholarSync Masterlist Verifier", version="1.0.0")


@app.get("/health-check")
def health_check() -> dict[str, str]:
    return {"status": "ok", "service": "masterlist-verifier"}


@app.post("/verify-masterlist", response_model=VerifyMasterlistResponse)
def verify_masterlist(payload: VerifyMasterlistRequest) -> VerifyMasterlistResponse:
    student_id_counts: dict[str, int] = {}

    for record in payload.records:
        student_id = normalize(record.student_id_number)

        if student_id:
            student_id_counts[student_id] = student_id_counts.get(student_id, 0) + 1

    enrolled_by_student_id = {
        normalize(student.student_id_number): student
        for student in payload.enrolled_students
        if normalize(student.student_id_number)
    }

    verified_records: list[MasterlistRecordOut] = []

    for record in payload.records:
        errors = required_field_errors(record)
        student_id = normalize(record.student_id_number)

        if errors:
            verified_records.append(
                MasterlistRecordOut(
                    row_id=record.row_id,
                    status="invalid",
                    remarks=" ".join(errors),
                )
            )
            continue

        if student_id_counts.get(student_id, 0) > 1:
            verified_records.append(
                MasterlistRecordOut(
                    row_id=record.row_id,
                    status="duplicate",
                    remarks="Duplicate student ID in uploaded file.",
                )
            )
            continue

        matched_student = enrolled_by_student_id.get(student_id)

        if matched_student is None:
            verified_records.append(
                MasterlistRecordOut(
                    row_id=record.row_id,
                    status="unenrolled",
                    remarks="No matching enrolled student record found.",
                )
            )
            continue

        verified_records.append(
            MasterlistRecordOut(
                row_id=record.row_id,
                status="enrolled",
                matched_student_id=matched_student.id,
                remarks="Matched enrolled student record.",
            )
        )

    return VerifyMasterlistResponse(
        summary=VerificationSummary(
            total_records=len(verified_records),
            enrolled_count=count_status(verified_records, "enrolled"),
            unenrolled_count=count_status(verified_records, "unenrolled"),
            duplicate_count=count_status(verified_records, "duplicate"),
            invalid_count=count_status(verified_records, "invalid"),
        ),
        records=verified_records,
    )


def required_field_errors(record: MasterlistRecordIn) -> list[str]:
    errors: list[str] = []

    required_fields = {
        "Student ID Number": record.student_id_number,
        "Student Name": record.student_name,
        "Scholarship Program": record.scholarship_program,
        "Fund Source": record.fund_source,
    }

    for label, value in required_fields.items():
        if not normalize(value):
            errors.append(f"{label} is required.")

    return errors


def count_status(records: list[MasterlistRecordOut], status: VerificationStatus) -> int:
    return sum(1 for record in records if record.status == status)


def normalize(value: str | None) -> str:
    return (value or "").strip()
