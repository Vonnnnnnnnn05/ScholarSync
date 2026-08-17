# Name-Only Masterlist Verification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Verify uploaded scholar names against Registrar data and classify each as enrolled, no COR printed, or unenrolled.

**Architecture:** FastAPI owns deterministic name normalization and classification. Laravel owns CSV ingestion, Registrar data, persistence, campus routing, and the approval UI; it sends a reduced JSON contract to FastAPI and persists the returned match.

**Tech Stack:** Python 3, FastAPI, Pydantic, pytest, Laravel, Eloquent, Pest, Blade, SQLite/MySQL migrations.

## Global Constraints

- The masterlist CSV requires only `student_name`.
- Verification exposes only `enrolled`, `no_cor_printed`, and `unenrolled`.
- Program, fund source, eligibility, duplicate, and invalid classifications are not part of verification.
- Ambiguous names must not be assigned automatically.
- Existing coordinator and chairman approval decisions remain available.

---

### Task 1: Microservice name matcher

**Files:**
- Create: `microservices/masterlist-verifier/test_main.py`
- Modify: `microservices/masterlist-verifier/main.py`
- Modify: `microservices/masterlist-verifier/README.md`

**Interfaces:**
- Consumes: `records[{row_id, student_name}]` and `registrar_students[{id, student_name, campus_id, enrollment_status, cor_printed}]`.
- Produces: `records[{row_id, status, matched_student_id, campus_id, remarks}]` and three status counts.

- [ ] **Step 1: Write failing API tests** for punctuation/case/name-order normalization, enrolled, no-COR, unenrolled, blank, and ambiguous names using `fastapi.testclient.TestClient`.
- [ ] **Step 2: Run `python -m pytest microservices/masterlist-verifier/test_main.py -q`** and confirm failures against the old request contract.
- [ ] **Step 3: Replace the old ID/program/fund/eligibility models and algorithm** with `normalize_name()` and the three-state response contract. Group enrolled Registrar rows by normalized name; match only groups containing exactly one row.
- [ ] **Step 4: Run the microservice tests** and confirm they pass.
- [ ] **Step 5: Update the README** with a concrete request/response example and commit the independently passing microservice.

### Task 2: Registrar COR data

**Files:**
- Create: `database/migrations/2026_08_17_000001_add_cor_printed_to_registrar_students.php`
- Modify: `app/Models/RegistrarStudent.php`
- Modify: `app/Http/Controllers/Registrar/EnrolledStudentController.php`
- Modify: `resources/views/registrar/enrolled-students/index.blade.php`
- Modify: `tests/Feature/RegistrarEnrollmentUploadTest.php`

**Interfaces:**
- Produces: persisted boolean `RegistrarStudent::$cor_printed`, default false.

- [ ] **Step 1: Extend the workbook feature test** with a `cor_printed` column containing `yes`, assert a true cast, and assert the table displays `Printed`.
- [ ] **Step 2: Run `php artisan test tests/Feature/RegistrarEnrollmentUploadTest.php`** and confirm the new assertion fails.
- [ ] **Step 3: Add the boolean migration/model cast/import parser** accepting `1`, `true`, `yes`, and `printed`; include the optional column in the Registrar instructions and table.
- [ ] **Step 4: Rerun the Registrar test** and commit the passing COR-data slice.

### Task 3: Laravel verifier contract and persistence

**Files:**
- Create: `database/migrations/2026_08_17_000002_add_no_cor_count_to_scholarship_masterlists.php`
- Modify: `app/Models/ScholarshipMasterlist.php`
- Modify: `app/Services/MasterlistVerificationService.php`
- Modify: `tests/Feature/RevisedObjectivesTest.php`

**Interfaces:**
- Consumes: Task 1 JSON response and Task 2 `cor_printed` data.
- Produces: record status/match/campus/remarks and masterlist three-state counts.

- [ ] **Step 1: Add an HTTP-faked feature test** asserting the reduced outbound payload and persisted `no_cor_printed` response.
- [ ] **Step 2: Run the focused Pest test** and confirm it fails against the old payload and summary mapping.
- [ ] **Step 3: Add `no_cor_printed_count` and rewrite payload/persistence mapping** without eligibility, duplicate, or invalid response dependencies.
- [ ] **Step 4: Run the focused test** and commit the passing integration slice.

### Task 4: Name-only CSV upload

**Files:**
- Modify: `app/Services/MasterlistCsvService.php`
- Modify: `resources/views/chairman/uploads/create.blade.php`
- Modify: `resources/views/chairman/uploads/preview.blade.php`
- Modify: `resources/views/chairman/uploads/show.blade.php`
- Modify: `tests/Feature/RevisedObjectivesTest.php`

**Interfaces:**
- Consumes: CSV with header `student_name`.
- Produces: pending masterlist records passed to Task 3 verification.

- [ ] **Step 1: Add feature coverage** proving a one-column CSV previews and imports, while a CSV missing `student_name` is rejected.
- [ ] **Step 2: Run the focused tests** and confirm the old four-column requirement fails.
- [ ] **Step 3: Reduce `REQUIRED_COLUMNS`, preview rows, and persistence to the scholar name** and remove old metadata from the three upload screens.
- [ ] **Step 4: Run focused upload tests** and commit the passing name-only upload slice.

### Task 5: Approval UI compatibility

**Files:**
- Modify: `app/Http/Controllers/Coordinator/MasterlistValidationController.php`
- Modify: `app/Http/Controllers/Chairman/MasterlistApprovalController.php`
- Modify: `resources/views/coordinator/masterlists/index.blade.php`
- Modify: `resources/views/coordinator/masterlists/show.blade.php`
- Modify: `resources/views/chairman/masterlists/index.blade.php`
- Modify: `resources/views/chairman/masterlists/show.blade.php`
- Modify: `tests/Feature/CoordinatorMasterlistValidationTest.php`
- Modify: `tests/Feature/ChairmanMasterlistApprovalTest.php`

**Interfaces:**
- Consumes: three verification statuses persisted by Task 3.
- Preserves: coordinator review, chairman decisions, and final release.

- [ ] **Step 1: Rewrite approval-flow fixtures/assertions** around enrolled, no-COR, and unenrolled summaries and filters.
- [ ] **Step 2: Run both approval feature files** and confirm failures expose old statuses/copy.
- [ ] **Step 3: Update filters, cards, record columns, labels, and colors** to the three-state workflow while leaving manual approval states intact.
- [ ] **Step 4: Run both approval test files** and commit the passing UI compatibility slice.

### Task 6: Full verification and documentation alignment

**Files:**
- Modify: `ROLE_FUNCTIONS.md`
- Modify: `flow.md`

**Interfaces:**
- Produces: project documentation consistent with the implemented workflow.

- [ ] **Step 1: Update role and flow language** to specify name-only comparison and the three outcomes.
- [ ] **Step 2: Run `python -m pytest microservices/masterlist-verifier -q`** and record the passing total.
- [ ] **Step 3: Run `php artisan test` and `npm run build`**; fix only regressions caused by this feature and rerun until green.
- [ ] **Step 4: Run `git diff --check` and inspect `git status --short`** for accidental or unrelated changes.
- [ ] **Step 5: Commit final documentation or verification fixes** and report exact evidence.
