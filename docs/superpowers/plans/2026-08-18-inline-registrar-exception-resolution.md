# Inline Registrar Exception Resolution Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let Registrars resolve spelling inconsistencies from the beneficiary review page by confirming safe same-campus suggestions or searching official enrollment records inline.

**Architecture:** The verifier will return a suggested same-campus Registrar record for a unique high-similarity name while keeping every fuzzy result in Needs Review. Laravel will validate all manual selections against the batch campus, link the official record without changing uploaded data, derive final statuses from that official record, and store the before/after audit resolution. The review page will expose inline candidate details, campus-scoped search, and save-and-next navigation.

**Tech Stack:** FastAPI/Pydantic, Python `difflib`, Laravel 12, Eloquent, Blade, Pest.

**Spec:** `docs/superpowers/specs/2026-08-17-beneficiary-masterlist-campus-workflow-design.md`

## Global Constraints

- Original uploaded beneficiary names must never be overwritten.
- Fuzzy matches must never automatically qualify or reject a beneficiary.
- Candidate lookup and confirmation must remain inside the Registrar's assigned campus.
- Existing automatic snapshots and Registrar resolutions must remain auditable.
- Large verification runs continue through the existing queue.

---

### Task 1: Safe fuzzy suggestions

**Files:**
- Modify: `microservices/masterlist-verifier/main.py`
- Test: `microservices/masterlist-verifier/test_main.py`

**Interfaces:**
- Consumes: `MasterlistRecordIn.student_name`, same-campus `RegistrarStudentIn` records.
- Produces: `match_status="possible_match"`, `matched_student_id`, `similarity_score`, and Needs Review statuses.

- [x] Write a failing test for `Von Essson Vergara` suggesting `Von Esson Vergara` within the same campus.
- [x] Run the focused pytest and confirm the current unmatched result.
- [x] Add normalized same-campus similarity scoring with a 0.90 threshold and unique-runner-up margin.
- [x] Keep enrollment, COR, and qualification as Needs Review for every fuzzy result.
- [x] Run all verifier tests.

### Task 2: Audited official-record confirmation

**Files:**
- Modify: `app/Http/Requests/UpdateRegistrarMasterlistRecordRequest.php`
- Modify: `app/Http/Controllers/Registrar/MasterlistVerificationController.php`
- Test: `tests/Feature/BulkMasterlistVerificationTest.php`

**Interfaces:**
- Consumes: optional `registrar_student_id` selected by the Registrar.
- Produces: campus-validated record linkage, official enrollment/COR-derived final statuses, and a resolution snapshot containing the selected official record.

- [x] Write failing tests for same-campus confirmation and cross-campus rejection.
- [x] Validate the selected Registrar student and enforce campus ownership in the controller.
- [x] Derive final statuses server-side when an official record is selected.
- [x] Preserve original beneficiary fields and record the linkage in resolution audit data.
- [x] Run focused Laravel tests.

### Task 3: Inline search and save-next review UI

**Files:**
- Modify: `app/Http/Controllers/Registrar/MasterlistVerificationController.php`
- Modify: `resources/views/registrar/masterlists/show.blade.php`
- Test: `tests/Feature/BulkMasterlistVerificationTest.php`

**Interfaces:**
- Consumes: campus-scoped Registrar student search results and suggested `registrar_student_id`.
- Produces: inline official-record selector, candidate comparison, and `next=1` redirect behavior.

- [x] Write a failing response test for suggested details, campus-scoped search, and Save and Review Next.
- [x] Load only assigned-campus enrollment candidates and render searchable selection in each exception form.
- [x] Show original and suggested names without editing either source record.
- [x] Add server-side redirect to the next unresolved exception after saving.
- [x] Run focused Laravel tests.

### Task 4: Verification and handoff

**Files:**
- Modify only files required by verification fixes.

**Interfaces:**
- Consumes: completed tasks 1-3.
- Produces: tested end-to-end exception resolution.

- [x] Run verifier pytest.
- [x] Run focused bulk/campus workflow tests.
- [x] Run Laravel Pint and the full Laravel suite.
- [x] Run the frontend production build and `git diff --check`.
- [x] Commit the completed workflow.
