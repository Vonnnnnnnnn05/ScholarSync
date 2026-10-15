# Microservice Contract: Multi-File Ingestion & Verification

**Service**: ScholarSync Masterlist Verifier  
**Base URL**: `http://localhost:8001`  
**Version**: 4.1.0  

---

## 1. POST `/parse-file` (Single File) & `/parse-files` (Multi-File)

Accepts 1 to $N$ uploaded PDF/XLSX/CSV documents, performs coordinate-aware column separation, runs the multi-category banner state machine, and normalizes characters.

**Request**: `multipart/form-data`
- `files`: Array of files, or `file`: Single binary file

**Response** (200 OK):
```json
{
  "success": true,
  "files_processed": 2,
  "total_rows": 128,
  "records": [
    {
      "row_number": 1,
      "source_pdf_name": "CHED_UniFAST_Batch1.pdf",
      "scholarship_program": "TES-UniFAST",
      "raw_agency_name": "Commission on Higher Education",
      "student_name": "ODOL, HANNAH FAITH M.",
      "first_name": "HANNAH FAITH",
      "middle_name": "M.",
      "last_name": "ODOL",
      "student_id": "2024-00123",
      "campus": "ACCESS",
      "program": "BS Nursing",
      "is_incomplete_name": false
    },
    {
      "row_number": 2,
      "source_pdf_name": "TDP_Grantees.pdf",
      "scholarship_program": "TDP",
      "raw_agency_name": "Tulong-Dunong Program",
      "student_name": "LUMENDA, KHALID",
      "first_name": "KHALID",
      "middle_name": null,
      "last_name": "LUMENDA",
      "student_id": null,
      "campus": null,
      "program": null,
      "is_incomplete_name": false
    }
  ]
}
```

---

## 2. POST `/verify-grantees`

Accepts staged grantee rows and verifies them against institutional SIS registrar records using the 3-tier matching engine, cross-category double-dipping detection, and badge assignment.

**Request**: `application/json`
```json
{
  "records": [
    {
      "row_id": 1,
      "source_pdf_name": "CHED_UniFAST_Batch1.pdf",
      "scholarship_program": "TES-UniFAST",
      "first_name": "HANNAH FAITH",
      "middle_name": "M.",
      "last_name": "ODOL",
      "student_name": "ODOL, HANNAH FAITH M.",
      "student_id": "25197",
      "campus_id": 1
    }
  ],
  "registrar_students": [
    {
      "id": 1,
      "student_id_number": "25197",
      "student_name": "ODOL, HANNAH FAITH M.",
      "campus_id": 1,
      "campus": "ACCESS Campus",
      "enrollment_status": "enrolled",
      "cor_printed": true,
      "course": "BS Nursing",
      "year_level": "3"
    }
  ],
  "existing_grantee_student_ids": [5, 12]
}
```

**Response** (200 OK):
```json
{
  "service_version": "4.1.0",
  "summary": {
    "total_records": 1,
    "green_count": 1,
    "amber_count": 0,
    "red_count": 0
  },
  "records": [
    {
      "row_id": 1,
      "verification_status": "GREEN_VERIFIED",
      "verification_badge": "GREEN",
      "match_tier": "EXACT_ID",
      "confidence_score": 1.0,
      "matched_registrar_student_id": 1,
      "resolved_campus_id": 1,
      "resolved_campus": "ACCESS Campus",
      "resolved_program": "BS Nursing",
      "enrollment_status": "ENROLLED",
      "conflict_reason": null,
      "candidate_matches": [],
      "remarks": "Exact SIS ID match (ACCESS Campus); Active enrolled with printed COR."
    }
  ]
}
```
