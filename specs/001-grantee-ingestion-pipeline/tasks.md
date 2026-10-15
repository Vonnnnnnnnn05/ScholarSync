# Tasks: Multi-File Ingestion, Coordinate-Aware PDF Multi-Category Parser & Verification Pipeline

**Branch**: `001-grantee-ingestion-pipeline` | **Spec**: [spec.md](file:///c:/xampp/htdocs/capstones/scholarSync/specs/001-grantee-ingestion-pipeline/spec.md) | **Plan**: [plan.md](file:///c:/xampp/htdocs/capstones/scholarSync/specs/001-grantee-ingestion-pipeline/plan.md)

---

## Phase 1: Python Microservice Coordinate-Aware Multi-Category Parser & Normalization
- [x] **Task 1.1**: Install `pdfplumber` in microservice environment and update `requirements.txt`.
- [x] **Task 1.2**: Implement `extract_coordinate_pdf()` in `pdf_parser.py` using `pdfplumber` bounding boxes `(x0, top, x1, bottom)` to segment Left ($x_0 < 300$, $\sim 90$) and Right ($x_0 \ge 300$, $\sim 302\text{--}348$) columns and sort top-to-bottom without horizontal collision.
- [x] **Task 1.3**: Implement Dynamic Multi-Category Banner State Machine in `pdf_parser.py` scanning section headers (`TERTIARY EDUCATION SUBSIDY`, `TULONG-DUNONG PROGRAM`, `CHED MERIT SCHOLARSHIP`) and tagging rows with `scholarship_program` (`TES-UniFAST`, `TDP`, `CMSP`) and `source_pdf_name`.
- [x] **Task 1.4**: Enhance string normalization in `header_mapper.py` and `pdf_parser.py`: normalize comma spacing (`LAST ,FIRST` $\to$ `LAST, FIRST`), Greek homoglyphs (`Μ, Α, Ε, Ο, Κ` $\to$ `M, A, E, O, K`), and `Ñ` $\to$ `N`, and detect surname-only incomplete entries.
- [x] **Task 1.5**: Implement multi-file ingestion endpoint `POST /parse-files` in `main.py` accepting 1 to $N$ files.

---

## Phase 2: Python Microservice 3-Tier Verification & Conflict Engine
- [x] **Task 2.1**: Implement 3-Tier Matching Algorithm in `main.py`:
  - Tier 1: Exact ID or exact normalized `(last_name, first_name)` match.
  - Tier 2: Fuzzy typo match with `SequenceMatcher >= 0.72` within matching surnames.
  - Tier 3: Incomplete / Surname-only match gathering all candidate matches sharing the surname.
- [x] **Task 2.2**: Implement Cross-Category and Intra-File Double-Dipping Conflict Detection across all categories (`TES-UniFAST`, `TDP`, `CMSP`) and uploaded files.
- [x] **Task 2.3**: Implement Badge Assignment (`GREEN`, `AMBER`, `RED`) with descriptive human-readable `remarks`.
- [x] **Task 2.4**: Update Python unit tests in `test_main.py` covering multi-category banners, coordinate extraction, typos, double-dipping, and badge assignment.

---

## Phase 3: Master Roster Importer & Flexible Header Mapping (Laravel)
- [x] **Task 3.1**: Enhance `EnrolledStudentController.php` with flexible keyword header detection (`ENROLL`, `COR`, `STATUS`, `REMARK`, `STANDING`) defaulting to Column M (Index 12) if unmapped.
- [x] **Task 3.2**: Implement Demo / Fallback Mode with weighted distribution (90% Enrolled, 8% No COR, 2% Unenrolled) while deterministically preserving benchmark student IDs (e.g. Student ID 24890) as strictly `ENROLLED`.

---

## Phase 4: Laravel Database, Staging & Multi-File Batch Controller
- [x] **Task 4.1**: Create database migration adding `uploaded_filenames` to `grantee_upload_batches`, and `source_pdf_name`, `scholarship_program`, `raw_agency_name`, `enrollment_status`, and `remarks` to `staged_grantee_records`.
- [x] **Task 4.2**: Update Eloquent models `GranteeUploadBatch.php` and `StagedGranteeRecord.php`.
- [x] **Task 4.3**: Update `GranteeFileParserService.php` and `GranteeStagingService.php` to handle multi-file responses.
- [x] **Task 4.4**: Update `GranteeUploadController.php` to accept multiple files `grantee_files[]` or single file `grantee_file`.

---

## Phase 5: Chairman Reconciliation Dashboard & Module A Guard
- [x] **Task 5.1**: Update `reconciliation.blade.php` with category tabs (`[All Programs]`, `[TES-UniFAST]`, `[TDP]`, `[CMSP]`) and badge filter pills (`GREEN`, `AMBER`, `RED`).
- [x] **Task 5.2**: Update `record-row.blade.php` and `batch-stats.blade.php` to display human-readable `remarks`, badges, and resolution actions.
- [x] **Task 5.3**: Verify atomic batch commit and Module A guard (`StoreCertificateRequestRequest` and `CertificateGenerationService`).
- [x] **Task 5.4**: Run feature test suite `GranteeIngestionPipelineTest.php` and full Laravel test suite to verify end-to-end functionality.
