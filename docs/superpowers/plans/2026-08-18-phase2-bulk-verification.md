# Phase 2 Bulk Verification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Automatically verify campus masterlist batches in queued chunks and let Registrars resolve only exceptions while preserving complete audit history.

**Architecture:** Laravel owns workflow state, database persistence, queue retries, and chunk orchestration. FastAPI performs deterministic campus-scoped matching and returns separate enrollment, COR, and qualification results; immutable snapshots preserve each automatic result and append-only resolutions preserve Registrar changes.

**Tech Stack:** Laravel 12, Eloquent, database queues, Pest, FastAPI, Pydantic, pytest, Blade.

**Spec:** `docs/superpowers/specs/2026-08-17-beneficiary-masterlist-campus-workflow-design.md`

## Global Constraints

- Never remove an imported beneficiary record because verification failed.
- Never classify an uncertain match as `not_enrolled`.
- Limit each microservice request to at most 500 records from one campus.
- Qualification means enrolled plus printed COR for Phase 2; uncertainty remains `needs_review`.
- Preserve automatic results separately from Registrar resolutions.
- Scholarship Agencies remain external and have no account or role.

---

### Task 1: Safe microservice verification contract

**Files:**
- Modify: `microservices/masterlist-verifier/test_main.py`
- Modify: `microservices/masterlist-verifier/main.py`
- Modify: `microservices/masterlist-verifier/README.md`

**Interfaces:**
- Consumes: records with `row_id`, optional `student_id_number`, `student_name`, and `campus_id`; campus-scoped official records.
- Produces: `match_status`, `enrollment_status`, `cor_status`, `qualification_status`, matched ID/campus, message, and service version per row.

- [ ] Write pytest cases proving unique ID/name matches, negative official statuses, no COR, ambiguous/unmatched/blank inputs, and cross-campus candidates return the exact safe statuses.
- [ ] Run `python -m pytest -q` and confirm failures are caused by the old response contract.
- [ ] Implement the Pydantic contract and deterministic matching rules in `main.py`.
- [ ] Run `python -m pytest -q` and confirm all microservice tests pass.
- [ ] Update the README request/response example and safe-matching statement.
- [ ] Commit as `feat: add safe bulk verification contract`.

### Task 2: Auditable verification persistence

**Files:**
- Create: `database/migrations/2026_08_18_000001_create_bulk_verification_tables.php`
- Create: `app/Models/MasterlistVerificationRun.php`
- Create: `app/Models/MasterlistRecordVerification.php`
- Create: `app/Models/MasterlistRegistrarResolution.php`
- Modify: `app/Models/MasterlistRecord.php`
- Modify: `app/Models/MasterlistCampusBatch.php`
- Test: `tests/Feature/BulkMasterlistVerificationTest.php`

**Interfaces:**
- Produces: `MasterlistVerificationRun`, immutable record snapshots, separate automatic/final result columns, and append-only Registrar resolutions.

- [ ] Write a failing schema/model test asserting all result columns, run/snapshot/resolution tables, JSON casts, and relationships.
- [ ] Run the focused Pest test and confirm missing-schema failures.
- [ ] Add the migration and focused Eloquent models/relationships.
- [ ] Run the focused Pest test and confirm it passes.
- [ ] Commit as `feat: store auditable bulk verification results`.

### Task 3: Chunk client and background job

**Files:**
- Modify: `app/Services/MasterlistVerificationService.php`
- Create: `app/Jobs/VerifyMasterlistChunk.php`
- Modify: `config/services.php`
- Modify: `.env.example`
- Test: `tests/Feature/BulkMasterlistVerificationTest.php`

**Interfaces:**
- Consumes: `verifyChunk(MasterlistVerificationRun $run, array $recordIds): array`.
- Produces: transactional record updates and immutable snapshots; job retries use `$tries = 3` and exponential backoff.

- [ ] Write failing tests for campus-only payloads, a maximum 500 IDs, complete persistence, idempotent snapshots, and malformed responses applying no partial state.
- [ ] Run the focused test and confirm failures.
- [ ] Refactor the service into a strict chunk client and implement `VerifyMasterlistChunk` completion/failure state handling.
- [ ] Run focused tests and confirm they pass.
- [ ] Commit as `feat: process masterlist verification in queued chunks`.

### Task 4: Workflow dispatch and Registrar exception resolution

**Files:**
- Modify: `app/Services/MasterlistCampusWorkflowService.php`
- Modify: `app/Http/Controllers/Registrar/MasterlistVerificationController.php`
- Replace: `app/Http/Requests/UpdateRegistrarMasterlistRecordRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/BulkMasterlistVerificationTest.php`
- Modify: `tests/Feature/BeneficiaryMasterlistCampusWorkflowTest.php`

**Interfaces:**
- Coordinator submission creates a run and dispatches chunks after commit.
- Registrar resolution updates final statuses only and appends a resolution row.

- [ ] Write failing tests for queued submission, prompt response, campus isolation, exception-only completion gate, audited resolution, and failed-run retry.
- [ ] Run focused tests and confirm failures.
- [ ] Implement workflow dispatch, resolution validation/controller action, retry route, and completion gates.
- [ ] Update legacy workflow expectations to the automatic states.
- [ ] Run both workflow test files and confirm they pass.
- [ ] Commit as `feat: add registrar exception review workflow`.

### Task 5: Role dashboards and qualified export

**Files:**
- Modify: `resources/views/registrar/masterlists/index.blade.php`
- Modify: `resources/views/registrar/masterlists/show.blade.php`
- Modify: `resources/views/coordinator/masterlists/batch.blade.php`
- Modify: `resources/views/chairman/masterlists/show.blade.php`
- Modify: `app/Http/Controllers/Registrar/MasterlistVerificationController.php`
- Modify: `app/Http/Controllers/Chairman/MasterlistApprovalController.php`
- Modify: `app/Services/VerifiedMasterlistExportService.php`
- Test: `tests/Feature/BulkMasterlistVerificationTest.php`

**Interfaces:**
- Produces: summary/filter UI, exception forms, and CSV rows selected by `final_qualification_status = qualified`.

- [ ] Write failing feature tests for summary/filter visibility, resolved/automatic result distinction, and qualified-only export retaining traceability columns.
- [ ] Run focused tests and confirm failures.
- [ ] Implement paginated filtered queries, accessible Blade summaries/forms, coordinator/chairman result displays, and revised export.
- [ ] Run focused and existing chairman/workflow tests.
- [ ] Commit as `feat: expose bulk verification review and export`.

### Task 6: Full verification and operations documentation

**Files:**
- Modify: `README.md`
- Modify: `docs/superpowers/plans/2026-08-18-phase2-bulk-verification.md`

**Interfaces:**
- Documents required commands: FastAPI service and `php artisan queue:work --queue=masterlist-verification,default`.

- [ ] Document local services, queue worker, retry behavior, chunk size configuration, and conservative qualification scope.
- [ ] Run `vendor/bin/pint --test` and fix only touched-file formatting issues.
- [ ] Run `python -m pytest -q`.
- [ ] Run `npm run build`.
- [ ] Run the full Laravel suite with a valid test `APP_KEY`.
- [ ] Run `git diff --check` and inspect `git status --short`.
- [ ] Commit as `docs: document bulk verification operations`.
