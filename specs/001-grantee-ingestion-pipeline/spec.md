# Feature Specification: Multi-File Scholarship Ingestion, Coordinate-Aware PDF Multi-Category Parser & Verification Pipeline

**Feature Branch**: `001-grantee-ingestion-pipeline`  
**Created**: 2026-10-15  
**Status**: Approved  
**Priority**: Critical (P1)

## Executive Summary
This feature establishes a high-throughput, fault-tolerant ingestion and verification pipeline capable of processing multiple external agency files (1 to $N$ PDFs, XLSX, and CSV documents) simultaneously. External agency documents (e.g. from UniFAST, CHED, LGUs) frequently present multi-page, two-column layouts containing multiple embedded scholarship programs (such as TES-UniFAST, Tulong-Dunong Program [TDP], and CHED Merit Scholarship Program [CMSP]) without strict tabular column borders.

The pipeline introduces:
1. **Coordinate-Aware Column Separation** using bounding box coordinates (`x0, y0, x1, y1`) to isolate side-by-side columns and prevent adjacent name collisions.
2. **Dynamic Multi-Category Banner State Machine** that tracks top-to-bottom section headers to accurately switch and assign subsequent student rows to specific scholarship programs.
3. **Robust Sanitization Engine** for comma spacing, Greek Unicode homoglyphs, and diacritic normalization.
4. **Master Roster Importer & Flexible Header Mapping** supporting varied SKSU Registrar formats, keyword-driven column detection, and deterministic status normalization into 3 system states (`ENROLLED`, `NO_COR_PRINTED`, `UNENROLLED`).
5. **3-Tier Matching & Conflict Engine** in the FastAPI microservice supporting Exact ID/Name, Fuzzy Typo (`SequenceMatcher >= 0.72`), Incomplete/Surname-only matching, intra-file and cross-category double-dipping detection, and color badge assignment (`GREEN`, `AMBER`, `RED`).
6. **Chairman Reconciliation Dashboard & Module A Guard** featuring category tabs, status filter pills, interactive candidate disambiguation, and atomic batch commit that locks non-grantee certification.

---

## User Scenarios & Testing

### User Story 1 — Batch Upload of Multiple Multi-Category Agency PDFs (Priority: P1)
The Scholarship Chairman uploads 2 or more PDF/Excel files simultaneously. One PDF contains TES-UniFAST recipients in Column 1 and Column 2, followed on page 3 by a header for "TULONG-DUNONG PROGRAM (TDP)", switching subsequent names to TDP. The system extracts tabular records across all pages without merging adjacent columns, tags each record with its `source_pdf_name` and `scholarship_program`, and stages them into `staged_grantee_records`.

**Acceptance Scenarios**:
1. **Given** 2 agency PDF files uploaded together, **When** processed by the batch upload endpoint, **Then** a single `grantee_upload_batch` is created tracking both files in `uploaded_filenames`, staging all parsed rows with their corresponding `source_pdf_name`.
2. **Given** a 2-column agency PDF where Left Column has $x_0 \sim 90$ and Right Column has $x_0 \sim 302\text{--}348$, **When** text containers are extracted, **Then** text elements in the Left Column and Right Column are separated into independent vertical streams, preventing horizontal concatenation of different students' names.
3. **Given** a PDF with section banners for "TERTIARY EDUCATION SUBSIDY (TES)", "TULONG-DUNONG PROGRAM (TDP)", and "CHED MERIT SCHOLARSHIP PROGRAM (CMSP)", **When** the parser scans top-to-bottom, **Then** all rows following each banner are assigned to that respective `scholarship_program`.
4. **Given** rows containing comma-spacing anomalies (e.g., `LASTNAME ,FIRSTNAME`), Greek homoglyphs (e.g., Greek `Μ, Α, Ε, Ο, Κ` substituted for Latin `M, A, E, O, K`), or `Ñ`, **When** sanitized, **Then** characters are normalized to clean Latin text with uniform spacing.

---

### User Story 2 — Master Roster Import with Flexible Header Mapping (Priority: P1)
The Campus Registrar uploads a campus enrollment file (e.g. `Access.xlsx` or CSV). The importer dynamically scans headers for status keywords (`ENROLL`, `COR`, `STATUS`, `REMARK`, `STANDING`), mapping to enrollment status and defaulting to Column M (Index 12) if headers are omitted.

**Acceptance Scenarios**:
1. **Given** a file with column headers like `Enrollment Status` or `Student Remarks`, **When** imported, **Then** the column is dynamically recognized without hardcoded index requirements.
2. **Given** varied status values (e.g., `Enrolled`, `Regular`, `No COR Printed`, `Unenrolled`, `Withdrawn`), **When** normalized, **Then** records map deterministically to:
   - `ENROLLED` (Eligible with printed COR)
   - `NO_COR_PRINTED` (Enrolled without printed COR; administrative hold)
   - `UNENROLLED` (Ineligible / dropped)
3. **Given** a legacy or demo roster missing an explicit status column, **When** processed in fallback mode, **Then** the engine applies a weighted 90% Enrolled / 8% No COR Printed / 2% Unenrolled distribution while strictly ensuring specified test records (e.g. Student ID 24890, 25197) remain 100% `ENROLLED` and non-grantee.

---

### User Story 3 — Microservice Verification & Double-Dipping Conflict Engine (Priority: P1)
The Chairman triggers verification for the staged batch. The FastAPI microservice evaluates records against master institutional SIS records using a 3-tier algorithm and conflict detector:
1. **Tier 1 (Exact Match)**: Matches `student_id` or normalized `(last_name, first_name)`. Auto-populates institutional details (LRN, Program, Campus, Year Level).
2. **Tier 2 (Fuzzy Typo Match)**: If exact match fails, runs `SequenceMatcher >= 0.72` within matching surnames to catch agency typos (e.g. `BALASAG` vs `BALANSAG`, `LUMNEDA` vs `LUMENDA`, `ALONZO, ALFREDO` vs `ALONZO, ALFREDO, JR.`).
3. **Tier 3 (Incomplete / Surname-Only Match)**: If first name was omitted in the source document, identifies all students sharing that surname as candidate matches.
4. **Cross-Category & Intra-File Double-Dipping**: Detects students appearing under multiple programs (e.g. both TDP and CMSP) or duplicated across uploaded files.

**Verification Badges**:
- `GREEN` (Auto-Verified): 100% Exact match + `ENROLLED` status + No scholarship conflicts.
- `AMBER` (Action Needed / Hold): Fuzzy typo match, missing first name, multiple candidate matches across campuses, cross-category double-dipping, or `NO_COR_PRINTED` status.
- `RED` (Ineligible): `UNENROLLED` status or completely unmatched in campus databases.

---

### User Story 4 — Campus Resolution & Delivery Field Inheritance (Priority: P1)
External agency PDF files (such as `LIST OF STUDENTS WHO AVAILED SCHOLARSHIP.pdf`) contain **zero campus columns** — listing only student names grouped under scholarship programs (`TES-UniFAST`, `TDP`, `CMSP`). The system must resolve the correct campus destination through the master student roster (`Access.xlsx` and other campus rosters).

**Acceptance Scenarios**:
1. **Given** master student spreadsheets (`Access.xlsx`), **When** imported by the Campus Registrar, **Then** Column A (Index 0) is mapped as `campus` (e.g. `ACCESS Campus`), while Column M (Index 12) is mapped as `enrollment_status`.
2. **Given** an agency PDF record with no campus specified, **When** the Verification Microservice successfully matches the student to an institutional record in `Access.xlsx`, **Then** the system automatically inherits and populates the `campus` (e.g., `ACCESS Campus`) and `campus_id` fields.
3. **Given** a student listed in the PDF who is enrolled at another campus (e.g. `Isulan Campus` or `Tacurong Campus`), **When** verified against cross-campus SIS records, **Then** the record resolves to that specific campus rather than defaulting to the Chairman's or batch's campus.
4. **Given** unresolved or unmatched students without campus, **When** staged, **Then** they remain flagged as `UNASSIGNED / Incomplete` for manual Chairman resolution.

---

### User Story 5 — Chairman Reconciliation Dashboard & Module A Guard (Priority: P1)
The Chairman views the reconciliation dashboard equipped with:
- **Category Tabs**: `[All Programs]`, `[TES-UniFAST]`, `[TDP]`, `[CMSP]`.
- **Status Filter Pills**: `GREEN`, `AMBER`, `RED`.
- **Resolved Campus Column & Remarks**: Displaying the auto-resolved campus (e.g. `ACCESS Campus`) and human-readable remarks (e.g., "Exact SIS match (ACCESS Campus); Active enrolled", "Fuzzy match (0.88 similarity): Matched to BALANSAG, JANE (Isulan Campus)", "Cross-category conflict: Also listed under TDP", "Administrative hold: No COR printed").
- **Interactive Disambiguation**: Dropdown selection to confirm fuzzy matches or pick the correct student from candidate lists.
- **Guarded Commit**: Clicking **Commit Batch** enforces that all Amber records are resolved, sets `is_scholarship_grantee = true` on master student accounts, persists resolved campus and scholarship program, and instantly locks Module A (Certificate of Non-Grantee Issuance).

---

## Functional Requirements

| ID | Requirement Description |
|---|---|
| **FR-001** | System MUST support batch upload of 1 to $N$ PDF documents and/or Excel sheets simultaneously. |
| **FR-002** | PDF parser MUST extract text bounding boxes `(x0, top, x1, bottom)` and segment side-by-side columns (Left $x_0 \sim 90$, Right $x_0 \sim 302\text{--}348$) before sorting top-to-bottom, excluding document title headers above initial banners. |
| **FR-003** | PDF parser MUST maintain a dynamic multi-category banner state machine, switching `scholarship_program` upon detecting section headers (`TERTIARY EDUCATION SUBSIDY`, `TULONG-DUNONG PROGRAM`, `CHED MERIT SCHOLARSHIP`). |
| **FR-004** | Normalization layer MUST clean comma spacing (`LAST ,FIRST` $\to$ `LAST, FIRST`), strip erroneous internal commas (e.g. `ORCAJADA, HEARTY, MARIE` $\to$ First: `HEARTY`, Middle: `MARIE`), normalize Greek homoglyphs to Latin equivalents, and normalize `Ñ` to `N`. |
| **FR-005** | Master roster importer MUST scan header rows for keywords (`ENROLL`, `COR`, `STATUS`, `REMARK`, `STANDING`) and default to Column M (Index 12) if unmapped; Column A (Index 0) MUST map to campus name. |
| **FR-006** | System MUST standardize enrollment status into `ENROLLED`, `NO_COR_PRINTED`, and `UNENROLLED`. |
| **FR-007** | Demo fallback mode MUST distribute missing status values (90% Enrolled, 8% No COR, 2% Unenrolled) while keeping benchmark student IDs (e.g. 24890) strictly `ENROLLED`. |
| **FR-008** | Verification engine MUST execute 3-tier matching: Tier 1 (Exact ID/Name), Tier 2 (Fuzzy Typo $\ge 0.72$), Tier 3 (Surname-Only Candidates). |
| **FR-009** | Verification engine MUST detect cross-category and intra-batch double-dipping, assigning `AMBER` with explicit conflict remarks. |
| **FR-010** | Verification engine MUST assign `GREEN` for exact match + `ENROLLED`, `AMBER` for fuzzy/hold/conflict/`NO_COR_PRINTED`, and `RED` for `UNENROLLED` or missing. |
| **FR-011** | Reconciliation dashboard MUST offer category tabs (`[All Programs]`, `[TES-UniFAST]`, `[TDP]`, `[CMSP]`), badge filter pills (`GREEN`, `AMBER`, `RED`), and explicit Resolved Campus display. |
| **FR-012** | Batch commit MUST atomically update master student records with scholarship grant details and set `is_scholarship_grantee = true`. |
| **FR-013** | Module A MUST check `is_scholarship_grantee` and strictly block non-grantee certificate issuance for active scholarship grantees. |
| **FR-014** | Campus Resolution Engine MUST inherit `campus` from Column A (Index 0) of the matched master student record (`Access.xlsx`) and populate `staged_grantee_records.campus` and `campus_id` even when external agency PDFs omit campus data entirely. |
| **FR-015** | Cross-Campus Disambiguation MUST resolve students to their actual campus of enrollment across all 7 SKSU campuses (ACCESS, Bagumbayan, Isulan, Kalamansig, Lutayan, Palimbang, Tacurong). |
