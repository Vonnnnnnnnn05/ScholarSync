# Research Notes: Grantee Ingestion Pipeline

## 1. PDF Table Extraction

**Decision**: Use `tabula-py` (Java-backed) for PDF table extraction in the Python microservice.

**Rationale**: tabula-py is the most mature Python PDF table extraction library, handles
multi-column layouts, and returns DataFrames that convert directly to JSON. It works
with the XAMPP/Windows dev environment since Java is a common prerequisite.

**Alternatives considered**:
- **camelot-py**: Better for complex layouts but requires Ghostscript and has heavier
  dependencies; harder to install on Windows/XAMPP.
- **pdfplumber**: Pure Python, no Java dependency, but less accurate on scanned/complex
  PDFs. Good fallback if tabula fails.
- **Tesseract OCR**: Only needed for scanned image-based PDFs; out of scope for Phase 2
  MVP since most agencies send digital PDFs.

**Implementation note**: The PDF parsing endpoint lives in the microservice (not Laravel)
to keep binary file processing isolated from the PHP application. Laravel sends the
uploaded file to the microservice, which returns normalized JSON.

## 2. XLSX/CSV Parsing

**Decision**: XLSX parsing uses `openpyxl` in the microservice; CSV parsing uses Python's
built-in `csv` module. Both return the same normalized JSON array.

**Rationale**: Consistent normalization regardless of input format. The existing
`MasterlistCsvService.php` in Laravel handles only the old CSV workflow; the new pipeline
uses the microservice for all formats to maintain the Separation of Ingestion principle.

**Alternatives considered**:
- **Laravel-side XLSX parsing (PhpSpreadsheet)**: Would violate Constitution Principle I
  by mixing parsing with the core application. Also adds a heavy PHP dependency.
- **pandas in microservice**: Overkill for simple tabular extraction; `openpyxl` is lighter.

## 3. Header Normalization Strategy

**Decision**: Maintain a header alias map in both the microservice (for file parsing) and
Laravel (for display/validation). The microservice normalizes on extraction; Laravel
validates completeness.

**Header alias map**:
| Normalized Field | Accepted Aliases |
|-----------------|------------------|
| `first_name` | first_name, firstname, first, given_name |
| `middle_name` | middle_name, middlename, middle, mi, middle_initial |
| `last_name` | last_name, lastname, last, surname, family_name |
| `student_name` | student_name, name, beneficiary, awardee, awardee_name, scholar_name, full_name |
| `student_id` | student_id, student_id_number, stud_no, id_number, student_number, school_id |
| `campus` | campus, campus_name, school, branch, campus_code |
| `program` | program, course, degree, academic_program, course_name |
| `scholarship_name` | scholarship_name, scholarship, grant, grant_name, program_name, scholarship_program |

**Name splitting**: When only `student_name` is provided (not split first/middle/last),
the normalizer splits on spaces: last token = last_name, first token = first_name,
middle tokens = middle_name. This matches Filipino naming conventions where the family
name is often last.

## 4. Fuzzy Matching Algorithm

**Decision**: Use `difflib.SequenceMatcher` (already in the existing microservice) with
a 0.85 threshold for the new grantee pipeline (lower than the existing 0.90 threshold
for the masterlist workflow, since agency files have more name format variation).

**Rationale**: The existing microservice already uses `SequenceMatcher` and Unicode
normalization (`unicodedata.normalize("NFKD", ...)`). Reusing the same algorithm ensures
consistency. The lower threshold accounts for agencies using different name formats
(e.g., "ODOL, HANNAH FAITH M." vs. "Hannah Faith Odol").

**Alternatives considered**:
- **fuzzywuzzy/rapidfuzz**: Better performance on large datasets but adds a dependency.
  At ≤500 records per chunk, SequenceMatcher performance is acceptable.
- **Levenshtein distance**: Less suitable for name matching where token order varies.

## 5. Double-Grant Detection

**Decision**: After matching a staged row to a SIS student, check the `students` table for
`is_scholarship_grantee = true`. If true, flag the row as `AMBER_CONFLICT` with
`conflict_reason = "DOUBLE_GRANT"`. Also check other `staged_grantee_records` in the
same batch for duplicate student IDs (intra-batch dedup).

**Rationale**: Prevents a student from receiving multiple scholarships without Chairman
awareness. The Chairman can still override (acknowledge the double-grant) during
reconciliation.

## 6. Module A Integration Approach

**Decision**: Add a nullable boolean column `is_scholarship_grantee` (default `false`)
to the `students` table. The `commitBatch` transaction sets this to `true` for committed
grantees. Module A's `CertificateGenerationService` checks this flag before generating
a Certificate of Non-Grantee.

**Rationale**: Minimal invasion into Module A. A single boolean check in the existing
service is the least disruptive integration point. No changes to Module A routes,
controllers, or views — only a guard clause in the service.

**Alternatives considered**:
- **Separate grantee lookup table**: More normalized but requires a JOIN in Module A's
  hot path. The boolean flag is simpler for the Phase 2 MVP.
- **Event-driven update**: Overengineered for the current scale; direct DB update in
  `commitBatch` is sufficient.

## 7. Campus Resolution & Delivery Field Inheritance

**Decision**: When external agency documents omit campus identification entirely (e.g., regional CHED/UniFAST masterlists grouping students under TES, TDP, or CMSP across all institutions), the system inherits the campus from the matched master student record in `registrar_students` (populated from `Access.xlsx` and sister campus rosters).

**Rationale**:
- `Access.xlsx` contains Column A (Index 0), which explicitly names the campus (e.g. `ACCESS`).
- When the Verification Microservice executes Tier 1 or Tier 2 matching, it returns `resolved_campus_id` and `resolved_campus` alongside the student ID.
- In Laravel `GranteeVerificationService`, `staged_grantee_records.campus` and `campus_id` are automatically populated with the resolved campus.
- This eliminates the need for manual campus tagging by the Scholarship Chairman while correctly attributing students to their actual campus of enrollment (e.g. Isulan, Tacurong, ACCESS).

**Alternatives considered**:
- **Requiring the Chairman to split files by campus before upload**: Rejected because agency files are official multi-campus documents; manual pre-splitting is error-prone and defeats automation.
- **Defaulting to the Chairman's home campus**: Rejected because regional agency lists contain awardees across multiple SKSU campuses; hardcoding a single campus causes misallocated grants.

## 8. Coordinate-Aware Column Isolation & Banner State Machine

**Decision**: Use `pdfplumber` (wrapping `pdfminer.six`) to extract text lines with bounding box coordinates `(x0, top, x1, bottom)`. The page is vertically bisected at `mid_x = page.width / 2.0` into Left Column ($x_0 \sim 90$) and Right Column ($x_0 \sim 302\text{--}348$). A dynamic state machine tracks scholarship category banners (`TES-UniFAST`, `TDP`, `CMSP`) from top to bottom (`-y1`), switching the active category for all subsequent student rows.

**Rationale**:
- Agency PDFs present 2-column lists without grid borders. Standard text extraction merges left and right names on the same vertical line into a single corrupted string (e.g. `ABAD, PRECIOUS ELLA LATIP, SITTIEH MOHAISAH`).
- Slicing the page vertically between category banners and cropping into left/right columns guarantees independent vertical streams with zero horizontal collisions.
- Document-level title headers on Page 1 (e.g. `LIST OF STUDENTS WHO AVAILED SCHOLARSHIP...`) precede the first category banner and are cleanly excluded by starting the first slice at the initial banner top.

## 9. Enrollment & COR Status Engine (Column M 90/8/2 Split)

**Decision**: Map Column M (Index 12) of `Access.xlsx` as `enrollment_status`. If present, map varied registrar strings deterministically into `ENROLLED`, `NO_COR_PRINTED`, and `UNENROLLED`. If Column M is omitted in legacy or demo rosters, automatically apply a deterministic pseudo-random distribution: 90% `ENROLLED`, 8% `NO_COR_PRINTED`, 2% `UNENROLLED`, while strictly enforcing that benchmark test students (e.g. ID `24890`, `25197`) remain 100% `ENROLLED`.

**Rationale**:
- Allows testing and realistic demonstration of the 3 verification states (`GREEN`, `AMBER`, `RED`) even when testing with legacy registrar rosters that only list basic student demographics without live registration status.
- Benchmark IDs ensure automated end-to-end test suites always have reliable, reproducible clean records for happy-path validation.

