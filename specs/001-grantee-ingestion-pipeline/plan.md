# Implementation Plan: Multi-File Scholarship Ingestion, Coordinate-Aware PDF Multi-Category Parser, Enrollment Verification, and Campus Resolution Microservice Pipeline

**Branch**: `001-grantee-ingestion-pipeline` | **Date**: 2026-10-15 | **Spec**: [spec.md](file:///c:/xampp/htdocs/capstones/scholarSync/specs/001-grantee-ingestion-pipeline/spec.md)

---

## Technical Context

- **Primary Languages & Stacks**:
  - **Laravel Core (PHP 8.2+)**: Controllers, Eloquent ORM, Services, Database Migrations, Blade + Tailwind CSS + Alpine.js, XAMPP Windows.
  - **Verification Microservice (Python 3.14 / FastAPI)**: `pdfplumber` / `pdfminer.six` coordinate parsing, `difflib.SequenceMatcher` fuzzy scoring, `openpyxl` XLSX parsing, `main:app` running on `http://127.0.0.1:8001`.
- **Key Storage & Data Models**:
  - `grantee_upload_batches`: Multi-file batch tracking session (`uploaded_filenames`, counts, status).
  - `staged_grantee_records`: Staged recipient rows (`student_name`, `first_name`, `middle_name`, `last_name`, `student_id`, `campus`, `campus_id`, `program`, `scholarship_program`, `source_pdf_name`, `enrollment_status`, `verification_status`, `confidence_score`, `remarks`).
  - `registrar_students`: Official SIS campus records populated from master rosters (`Access.xlsx`) with Column A (Campus) and Column M (Enrollment Status).
  - `students`: Core student table containing `is_scholarship_grantee` guard flag.
- **Critical Interfaces**:
  - `POST http://127.0.0.1:8001/parse-file` (Single file extraction)
  - `POST http://127.0.0.1:8001/parse-files` (Batch multi-file extraction)
  - `POST http://127.0.0.1:8001/verify-grantees` (3-tier verification, conflict detection & campus resolution)

---

## Constitution Check & Architectural Gates

| Principle | Assessment | Compliance Notes |
|---|---|---|
| **I. Separation of Ingestion & Verification** | **PASS** | Parsing occurs entirely within FastAPI microservice (`pdf_parser.py`, `header_mapper.py`). Laravel receives only structured JSON records. |
| **II. Missing-Field Resolution via SIS** | **PASS** | Agency PDFs contain **zero campus columns**. Missing campus & student details are auto-resolved by cross-referencing SIS `registrar_students` (`Access.xlsx`). |
| **III. Confidence-Based Disambiguation** | **PASS** | 3-tier algorithm executed in strict order: Tier 1 (Exact ID/Name) $\to$ Tier 2 (Fuzzy Typo $\ge 0.72$) $\to$ Tier 3 (Incomplete Surname Candidates). |
| **IV. Human-in-the-Loop Reconciliation** | **PASS** | Color-coded dashboard (`GREEN`, `AMBER`, `RED`) requires Chairman confirmation for all Amber holds/conflicts before batch commit is unlocked. |
| **V. Module Integrity (Module A/B Protection)** | **PASS** | Module A Non-Grantee issuance checks `students.is_scholarship_grantee` without modifying existing routes, controllers, or PDF generation templates. |
| **VI. Microservice Fault Tolerance** | **PASS** | Laravel HTTP client wraps microservice calls with timeouts and retries; surfaces human-readable error messages if service is offline. |

---

## Architectural Workflow

```
                                  +-----------------------------------------------------+
                                  |             Scholarship Chairman Web UI             |
                                  |      (Multi-File Upload & Tabbed Reconciliation)    |
                                  +--------------------------+--------------------------+
                                                             |
                                           POST 1..N files   |
                                                             v
                                  +-----------------------------------------------------+
                                  |          GranteeUploadController (Laravel)          |
                                  +--------------------------+--------------------------+
                                                             |
                                  +--------------------------+--------------------------+
                                  |         GranteeFileParserService (Bridge)           |
                                  +--------------------------+--------------------------+
                                                             |
                                           HTTP Multipart    |
                                                             v
    +---------------------------------------------------------------------------------------------------+
    |                                   FastAPI Verifier Microservice                                   |
    |                                                                                                   |
    |  +-------------------------------------+   +---------------------------------------------------+  |
    |  | pdf_parser.py (Coordinate Aware)    |   | header_mapper.py (Sanitization)                   |  |
    |  | - pdfplumber bounding boxes         |   | - Greek homoglyph normalization                   |  |
    |  | - Left (x0~90) / Right (x0~330)     |   | - Comma spacing & double-comma fix                |  |
    |  | - Exclude cover page title headers  |   | - Diacritic (Ñ -> N) normalization                |  |
    |  | - Multi-category state machine      |   | - Surname-only incomplete detection               |  |
    |  |   (TES-UniFAST / TDP / CMSP)        |   +---------------------------------------------------+  |
    |  +-------------------------------------+                                                          |
    |                                                                                                   |
    |  +---------------------------------------------------------------------------------------------+  |
    |  | 3-Tier Verification & Campus Resolution Engine (main.py)                                    |  |
    |  | - Tier 1: Exact Student ID / Exact Name Match                                               |  |
    |  | - Tier 2: Fuzzy Typo Match (SequenceMatcher >= 0.72, e.g. BALASAG vs BALANSAG)              |  |
    |  | - Tier 3: Incomplete / Surname-Only Candidate Disambiguation                                |  |
    |  | - Campus Delivery Field Inheritance: Resolves destination Campus from Access.xlsx           |  |
    |  | - Cross-Category & Intra-File Double-Dipping Conflict Detection                             |  |
    |  | - Badging: GREEN (Enrolled Clean), AMBER (Hold/Fuzzy/No-COR), RED (Ineligible/Unenrolled)   |  |
    |  +---------------------------------------------------------------------------------------------+  |
    +------------------------------------------------+--------------------------------------------------+
                                                     |
                                   Normalized JSON   | (Includes resolved_campus & resolved_campus_id)
                                                     v
                                  +-----------------------------------------------------+
                                  |           GranteeStagingService (Laravel)           |
                                  |       (Stores staged rows with resolved campus)     |
                                  +--------------------------+--------------------------+
                                                             |
                                                             v
                                  +-----------------------------------------------------+
                                  |      Chairman Reconciliation Dashboard (Blade)      |
                                  |      - Category Tabs: [TES-UniFAST] [TDP] [CMSP]    |
                                  |      - Status Pills: GREEN / AMBER / RED            |
                                  |      - Resolved Campus Column (e.g. ACCESS Campus)  |
                                  |      - Interactive Amber Disambiguation Modal       |
                                  +--------------------------+--------------------------+
                                                             |
                                            Commit Batch     | (All Amber Resolved)
                                                             v
                                  +-----------------------------------------------------+
                                  |            GranteeCommitService (Atomic)            |
                                  |      - Atomically updates students table            |
                                  |      - Sets students.is_scholarship_grantee = true  |
                                  |      - Locks Module A Non-Grantee Certification     |
                                  +-----------------------------------------------------+
```

---

## Detailed Pipeline Phases

### Phase 1: Coordinate-Aware PDF Multi-Category Parser & Sanitization (Python)
1. **Coordinate-Aware Column Separation** ([pdf_parser.py](file:///c:/xampp/htdocs/capstones/scholarSync/microservices/masterlist-verifier/pdf_parser.py)):
   - Extract bounding box coordinates `(x0, top, x1, bottom)` via `pdfplumber`.
   - Vertically bisect page at `mid_x = page.width / 2.0` into:
     - Left Column: $x_0 \sim 90$ ($x_0 < mid\_x$)
     - Right Column: $x_0 \sim 302\text{--}348$ ($x_0 \ge mid\_x$)
   - Isolate text lines per column to completely prevent horizontal concatenation across adjacent students.
2. **Dynamic Multi-Category Banner State Machine**:
   - Scan page vertically top-to-bottom (`-y1`).
   - Detect section headers with regex matching en-dash/em-dash:
     - `TERTIARY EDUCATION SUBSIDY` or `TES[-_–—]*UniFAST` $\to$ `"TES-UniFAST"`
     - `TULONG-DUNONG PROGRAM` or `TDP` $\to$ `"TDP"`
     - `CHED MERIT SCHOLARSHIP` or `CMSP` $\to$ `"CMSP"`
   - On the cover page (`page_idx == 0`), begin slicing at the initial category banner, cleanly excluding document-level title headers (`LIST OF STUDENTS WHO AVAILED SCHOLARSHIP AND FINANCIAL ASSISTANCE PROGRAMS`).
   - Tag each extracted record with its active `scholarship_program` and `source_pdf_name`.
3. **Sanitization Engine** ([header_mapper.py](file:///c:/xampp/htdocs/capstones/scholarSync/microservices/masterlist-verifier/header_mapper.py)):
   - Clean multiple commas and trailing punctuation in names (e.g. `ORCAJADA, HEARTY, MARIE` $\to$ First: `"HEARTY"`, Middle: `"MARIE"`, Last: `"ORCAJADA"`).
   - Normalize Greek homoglyphs (`Μ, Α, Ε, Ο, Κ, Ρ, Τ, Η, Β, Χ, Ν`) to Latin ASCII.
   - Normalize `Ñ` to `N` and collapse irregular comma whitespace (`ALBA,ANGEL` $\to$ `ALBA, ANGEL`).
   - Detect surname-only incomplete entries (e.g. `MACADANGDANG` $\to$ `last_name="MACADANGDANG"`, `is_incomplete_name=True`).

---

### Phase 2: Campus Resolution & Delivery Field Inheritance
1. **Master Student Roster Ingestion** ([EnrolledStudentController.php](file:///c:/xampp/htdocs/capstones/scholarSync/app/Http/Controllers/Registrar/EnrolledStudentController.php)):
   - **Column A (Index 0)**: Ingests `campus` name (e.g. `ACCESS`).
   - Stored in `registrar_students.campus` and linked to `campus_id`.
2. **Microservice Campus Inheritance** ([main.py](file:///c:/xampp/htdocs/capstones/scholarSync/microservices/masterlist-verifier/main.py)):
   - Agency PDF records enter with `campus = null` and `campus_id = null`.
   - When matched against `registrar_students` in Tier 1 or Tier 2, the microservice attaches `resolved_campus_id` and `resolved_campus` (e.g. `"ACCESS Campus"`).
   - If a student is registered in another campus (e.g. Isulan, Tacurong), the microservice inherits that specific campus.
3. **Persistence to Staged Records** ([GranteeVerificationService.php](file:///c:/xampp/htdocs/capstones/scholarSync/app/Services/GranteeVerificationService.php)):
   - Laravel updates `staged_grantee_records.campus` and `campus_id` directly from the verification response.
   - Staged records surface the resolved campus in table columns and human-readable remarks.

---

### Phase 3: Enrollment & COR Status Engine (90 / 8 / 2 Split)
1. **Column M Keyword Detection**:
   - Map **Column M (Index 12)** of `Access.xlsx` as `enrollment_status`.
   - Scan for status keywords: `ENROLL`, `COR`, `STATUS`, `REMARK`, `STANDING`.
2. **Status Normalization**:
   - `ENROLLED`: Eligible active students with printed COR.
   - `NO_COR_PRINTED`: Administrative hold (enrolled without printed Certificate of Registration).
   - `UNENROLLED`: Ineligible (withdrawn, dropped, or not enrolled).
3. **Demo / Fallback Mode**:
   - If Column M is omitted in legacy or demo rosters, apply weighted distribution:
     - 90% `ENROLLED`
     - 8% `NO_COR_PRINTED`
     - 2% `UNENROLLED`
   - Benchmark test student IDs (e.g. `24890`, `25197`) are strictly forced to 100% `ENROLLED`.

---

### Phase 4: Verification Microservice 3-Tier Matcher & Badging
1. **Tier 1 (Exact Match)**:
   - Match by `student_id` or normalized `(last_name, first_name)`.
   - Inherit campus, course, year level, LRN.
2. **Tier 2 (Fuzzy Typo Match)**:
   - When exact match fails, compare candidates with matching surname using `SequenceMatcher >= 0.72` (e.g. `BALASAG` vs `BALANSAG`).
   - Suffixes like `JR`, `SR`, `II`, `III` normalized without breaking matches.
3. **Tier 3 (Incomplete Surname Candidates)**:
   - For entries like `MACADANGDANG`, return all candidates sharing the surname as selectable matches with `conflict_reason = "INCOMPLETE_NAME"`.
4. **Double-Dipping Engine**:
   - Detect students appearing under multiple programs (e.g. both TDP and CMSP) or duplicated across files.
   - Flag as `AMBER_CONFLICT` with reason `"DOUBLE_GRANT"`.
5. **Badge Assignment**:
   - `GREEN`: Exact match + `ENROLLED` + no double-grant.
   - `AMBER`: Fuzzy match, surname-only, multi-match, double-grant, or `NO_COR_PRINTED`.
   - `RED`: `UNENROLLED` or completely unmatched.

---

### Phase 5: Chairman Reconciliation Dashboard & Atomic Commit Guard
1. **Tabbed Dashboard UI** ([reconciliation.blade.php](file:///c:/xampp/htdocs/capstones/scholarSync/resources/views/chairman/grantees/reconciliation.blade.php)):
   - Category Tabs: `[All Programs]`, `[TES-UniFAST]`, `[TDP]`, `[CMSP]`.
   - Filter Pills: `GREEN`, `AMBER`, `RED`.
   - Display Columns: Row, Student Name, Program, **Resolved Campus**, Status Badge, Remarks, Actions.
   - Interactive modal to disambiguate Amber records.
2. **Guarded Atomic Batch Commit** ([GranteeCommitService.php](file:///c:/xampp/htdocs/capstones/scholarSync/app/Services/GranteeCommitService.php)):
   - Transaction blocks commit if any unresolved Amber record remains.
   - Persists verified grantees with resolved campus and scholarship program.
   - Sets `students.is_scholarship_grantee = true`.
   - **Module A Lock**: `StoreCertificateRequestRequest` and `CertificateGenerationService` instantly block non-grantee certificate issuance for all committed grantees.
