# API Endpoints: Grantee Ingestion Pipeline

## Laravel Web Routes (Chairman)

All routes are prefixed with `/chairman/grantees` and guarded by `role:scholarship_chairman`.

### File Upload & Preview

| Method | URI | Controller | Action | Description |
|--------|-----|-----------|--------|-------------|
| GET | `/chairman/grantees/upload` | `GranteeUploadController@create` | Show upload form | File upload page with drag-and-drop |
| POST | `/chairman/grantees/upload/parse` | `GranteeUploadController@parse` | Parse & preview | Sends file to microservice for parsing, returns preview table |
| POST | `/chairman/grantees/upload` | `GranteeUploadController@store` | Confirm & stage | Creates batch + stages rows after Chairman confirms preview |

#### POST `/chairman/grantees/upload/parse`

**Request**: `multipart/form-data`
```
file: <UploadedFile>  (PDF, XLSX, or CSV, max 10MB)
```

**Response**: JSON
```json
{
  "success": true,
  "original_headers": ["Beneficiary", "Stud_No", "Campus"],
  "normalized_headers": ["student_name", "student_id", "campus"],
  "missing_fields": ["program"],
  "rows": [
    {
      "row_number": 2,
      "student_name": "HANNAH FAITH M. ODOL",
      "first_name": "HANNAH FAITH",
      "middle_name": "M.",
      "last_name": "ODOL",
      "student_id": "2024-00123",
      "campus": "ACCESS",
      "program": null,
      "scholarship_name": null,
      "errors": []
    }
  ],
  "total_rows": 150,
  "file_type": "csv"
}
```

#### POST `/chairman/grantees/upload`

**Request**: `application/x-www-form-urlencoded` or JSON
```json
{
  "temp_file_path": "grantees/tmp/abc123.csv",
  "agency_name": "CHED",
  "scholarship_name": "Tulong Dunong",
  "academic_year": "2026-2027",
  "semester": "1st Semester"
}
```

**Response**: Redirect to reconciliation dashboard for the new batch.

---

### Reconciliation Dashboard

| Method | URI | Controller | Action | Description |
|--------|-----|-----------|--------|-------------|
| GET | `/chairman/grantees` | `GranteeReconciliationController@index` | List batches | Dashboard listing all upload batches |
| GET | `/chairman/grantees/{batch}` | `GranteeReconciliationController@show` | Batch detail | Color-coded staging table |
| POST | `/chairman/grantees/{batch}/verify` | `GranteeReconciliationController@verify` | Trigger verification | Sends staged rows to microservice |
| PATCH | `/chairman/grantees/{batch}/records/{record}/resolve` | `GranteeReconciliationController@resolve` | Resolve Amber | Chairman picks correct student |
| POST | `/chairman/grantees/{batch}/commit` | `GranteeReconciliationController@commit` | Commit batch | Final transaction |

#### POST `/chairman/grantees/{batch}/verify`

**Request**: Empty body (batch ID from URL).

**Response**: JSON
```json
{
  "success": true,
  "message": "Verification queued. 3 chunks of 500 records each.",
  "batch_status": "verifying"
}
```

#### PATCH `/chairman/grantees/{batch}/records/{record}/resolve`

**Request**: JSON
```json
{
  "matched_sis_student_id": 42,
  "matched_student_id": 15,
  "resolution_notes": "Confirmed correct student after phone verification"
}
```

**Response**: JSON
```json
{
  "success": true,
  "record_id": 789,
  "new_status": "GREEN_VERIFIED"
}
```

#### POST `/chairman/grantees/{batch}/commit`

**Request**: Empty body.

**Precondition**: All `AMBER_CONFLICT` rows must be resolved. Fails with 422 otherwise.

**Response**: JSON
```json
{
  "success": true,
  "committed_count": 148,
  "skipped_red_count": 2,
  "grantee_flags_updated": 148,
  "batch_status": "committed"
}
```

---

### Student Lookup (for Amber resolution)

| Method | URI | Controller | Action | Description |
|--------|-----|-----------|--------|-------------|
| GET | `/chairman/grantees/students/search` | `GranteeReconciliationController@searchStudents` | AJAX search | Returns matching SIS students |

#### GET `/chairman/grantees/students/search?q=ODOL&campus_id=1`

**Response**: JSON
```json
{
  "results": [
    {
      "registrar_student_id": 42,
      "student_id_number": "2024-00123",
      "student_name": "ODOL, HANNAH FAITH M.",
      "campus_id": 1,
      "campus_name": "ACCESS",
      "course": "BSN",
      "year_level": "3rd",
      "enrollment_status": "enrolled",
      "is_scholarship_grantee": false
    }
  ]
}
```
