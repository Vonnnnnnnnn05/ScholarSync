# Data Model: Multi-File Ingestion, Coordinate-Aware PDF Parsing & Verification Pipeline

## Entities & Schemas

### 1. `grantee_upload_batches`

Tracks a batch upload session containing 1 to $N$ agency documents.

| Field | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | bigint (PK) | No | auto | Primary key |
| `uploaded_by` | bigint (FK → users.id) | No | — | Chairman user ID |
| `agency_name` | string(255) | Yes | null | External funding agency |
| `scholarship_name` | string(255) | Yes | null | Default program name if single |
| `academic_year` | string(20) | Yes | null | Academic Year (e.g. 2026-2027) |
| `semester` | string(20) | Yes | null | Semester (e.g. 1st Semester) |
| `original_file_name` | string(255) | No | — | Primary or primary-first filename |
| `uploaded_filenames` | json | Yes | null | Array of all uploaded file names: `["ched_tes.pdf", "tdp_2026.pdf"]` |
| `stored_file_path` | string(500) | No | — | Stored path on disk |
| `file_type` | enum('csv', 'xlsx', 'pdf') | No | 'pdf' | Detected file type |
| `raw_headers` | json | Yes | null | Ingested column headers |
| `total_rows` | unsigned int | No | 0 | Total staged records |
| `green_count` | unsigned int | No | 0 | Auto-verified clean records |
| `amber_count` | unsigned int | No | 0 | Records requiring resolution/hold |
| `red_count` | unsigned int | No | 0 | Ineligible / unmatched records |
| `pending_count` | unsigned int | No | 0 | Unverified records |
| `status` | enum | No | 'staged' | Lifecycle: `staged`, `verifying`, `reconciling`, `verified`, `committed`, `failed` |
| `verified_at` | timestamp | Yes | null | Completion of microservice verification |
| `committed_at` | timestamp | Yes | null | Completion of atomic DB commit |
| `committed_by` | bigint (FK → users.id) | Yes | null | Chairman who finalized the batch |
| `created_at` / `updated_at` | timestamps | No | auto | Laravel timestamps |

---

### 2. `staged_grantee_records`

Stores individual normalized grantee rows extracted across all uploaded files and categories.

| Field | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | bigint (PK) | No | auto | Primary key |
| `batch_id` | bigint (FK → grantee_upload_batches.id) | No | — | Associated upload batch |
| `row_number` | unsigned int | No | — | Row sequence index |
| `source_pdf_name` | string(255) | Yes | null | Origin filename (e.g. `UniFAST_Batch1.pdf`) |
| `scholarship_program` | string(255) | Yes | null | Active banner program: `TES-UniFAST`, `TDP`, `CMSP` |
| `raw_agency_name` | string(255) | Yes | null | Raw agency name from banner or file metadata |
| `raw_payload` | json | Yes | null | Full unparsed row data for audit |
| `first_name` | string(255) | Yes | null | Sanitized given name (or null if surname-only) |
| `middle_name` | string(255) | Yes | null | Sanitized middle initial/name |
| `last_name` | string(255) | Yes | null | Sanitized surname |
| `student_name` | string(500) | Yes | null | Full normalized name (`LAST, FIRST M.`) |
| `student_id` | string(50) | Yes | null | Agency-supplied student ID |
| `campus` | string(255) | Yes | null | Raw or resolved campus name |
| `campus_id` | bigint (FK → campuses.id) | Yes | null | Resolved campus foreign key |
| `program` | string(255) | Yes | null | Academic degree program |
| `enrollment_status` | string(50) | Yes | null | Institutional status: `ENROLLED`, `NO_COR_PRINTED`, `UNENROLLED` |
| `verification_status` | enum | No | 'PENDING' | Status: `PENDING`, `GREEN_VERIFIED`, `AMBER_CONFLICT`, `RED_UNMATCHED` |
| `verification_badge` | virtual / accessor | — | — | Maps to `GREEN`, `AMBER`, `RED` |
| `confidence_score` | decimal(5,4) | Yes | null | Match similarity score ($0.0000$ to $1.0000$) |
| `matched_sis_student_id` | bigint (FK → registrar_students.id) | Yes | null | SIS master record match |
| `matched_student_id` | bigint (FK → students.id) | Yes | null | User account student match |
| `conflict_reason` | string(255) | Yes | null | e.g. `DOUBLE_GRANT`, `FUZZY_MATCH`, `SURNAME_ONLY`, `NO_COR_PRINTED` |
| `candidate_matches` | json | Yes | null | Array of candidate matches for Amber selection |
| `remarks` | text | Yes | null | Human-readable verification reasoning |
| `resolved_by` | bigint (FK → users.id) | Yes | null | Chairman user resolving the record |
| `resolved_at` | timestamp | Yes | null | Time of manual resolution |
| `resolution_notes` | text | Yes | null | Chairman audit commentary |
| `created_at` / `updated_at` | timestamps | No | auto | Laravel timestamps |

---

### 3. Master Institutional Students (`students` & `registrar_students`)

- **`registrar_students`**: Official SIS campus records populated by Campus Registrars from files like `Access.xlsx`.
  - **Column A (Index 0)**: `campus` name (e.g. `ACCESS Campus`). Explicitly stored to enable multi-campus resolution.
  - **Column C / D (Index 2/3)**: `student_id_number` (unique), LRN.
  - **Column E, F, G (Index 4, 5, 6)**: `last_name`, `first_name`, `middle_name` / `student_name`.
  - **Column H (Index 7)**: `course` / academic program (e.g. `BS Information Technology`).
  - **Column I (Index 8)**: `year_level` (e.g. `3rd Year`).
  - **Column M (Index 12)**: `enrollment_status` (`ENROLLED`, `NO_COR_PRINTED`, `UNENROLLED`).
  - `cor_printed`: Boolean flag derived from Column M or Column N.
  - `campus_id`: Foreign key to `campuses.id`.

- **`students`**: User-facing student profiles.
  - `is_scholarship_grantee`: Boolean flag. Set to `true` upon `commitBatch`.
  - Guard logic in `StoreCertificateRequestRequest` and `CertificateGenerationService` blocks Certificate of Non-Grantee when `is_scholarship_grantee === true`.

---

### 4. Campus Resolution & Inheritance Engine

```
Agency PDF (Zero Campus Column) ──▶ Microservice 3-Tier Matcher
                                            │
                             ┌──────────────┴──────────────┐
                             ▼                             ▼
                 Match in Access.xlsx           Match in Isulan/Tacurong
                             │                             │
                             ▼                             ▼
                 Inherit "ACCESS Campus"         Inherit "Isulan Campus"
                 (campus_id = 1)                 (campus_id = 3)
                             │                             │
                             └──────────────┬──────────────┘
                                            ▼
                        staged_grantee_records.campus
                        staged_grantee_records.campus_id
                                            │
                                            ▼
                        Chairman Dashboard (Shows Resolved Campus)
                                            │
                                  [Commit Batch]
                                            │
                                            ▼
                        Atomic Persistence to Scholarship Masterlist
```

## State Transition Models

### Upload Batch Lifecycle
```
[UPLOAD (1..N files)] 
       │
       ▼
   [STAGED] ──(Microservice /verify-grantees)──▶ [RECONCILING]
                                                       │
                     ┌─────────────────────────────────┴───────────────────┐
                     ▼                                                     ▼
           (Unresolved Amber)                                   (All Resolved / Clean)
             Commit Blocked                                                │
                                                                           ▼
                                                                      [COMMITTED]
                                                              (Locks Module A Grantees)
```

### Verification Badge Logic
| Badge | Criteria | Action |
|---|---|---|
| `GREEN` (Auto-Verified) | Exact ID/Name Match + `ENROLLED` (cor_printed = true) + Zero Conflicts | Ready for commit |
| `AMBER` (Action Needed / Hold) | Fuzzy Match ($\ge 0.72$), Missing First Name, Double-Dipping Conflict, or `NO_COR_PRINTED` | Requires Chairman resolution/confirmation |
| `RED` (Ineligible) | `UNENROLLED` status or no matching record in any campus | Excluded from grantee commit |
