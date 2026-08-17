# Beneficiary Master List Campus Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Route Chairman-uploaded beneficiary records through campus Coordinators and Registrars, then consolidate Registrar-verified records for external export.

**Architecture:** Reuse `ScholarshipMasterlist`, `MasterlistRecord`, `Campus`, `RegistrarStudent`, agencies, authentication, audit logging, and existing role pages. Add one campus-batch model for independent campus state, make Registrar decisions authoritative, and centralize guarded transitions in a small workflow service.

**Tech Stack:** Laravel, PHP 8, Eloquent, Blade, Pest, CSV streaming

## Global Constraints

- SKSUScholarSync has exactly seven active campuses.
- Each campus permits one active Coordinator and one active Registrar.
- Coordinators and Registrars access only their assigned campus.
- Only Registrars set official `verified` or `not_verified` decisions.
- Scholarship Agencies remain external records with no user role or login.
- Existing certificate processing remains unchanged.
- Preserve the unrelated change in `microservices/masterlist-verifier/test_main.py`.

---

### Task 1: Campus batches and campus-aware import

**Files:**
- Create: `database/migrations/2026_08_17_000002_create_masterlist_campus_batches.php`
- Create: `app/Models/MasterlistCampusBatch.php`
- Modify: `app/Models/ScholarshipMasterlist.php`
- Modify: `app/Models/MasterlistRecord.php`
- Modify: `app/Models/Campus.php`
- Modify: `app/Services/MasterlistCsvService.php`
- Modify: `app/Http/Controllers/Chairman/MasterlistUploadController.php`
- Modify: `resources/views/chairman/uploads/create.blade.php`
- Modify: `resources/views/chairman/uploads/preview.blade.php`
- Test: `tests/Feature/BeneficiaryMasterlistCampusWorkflowTest.php`

**Interfaces:**
- `MasterlistCsvService::REQUIRED_COLUMNS` becomes `['student_name', 'campus']`.
- `MasterlistCampusBatch` belongs to a master list and campus and exposes its records through matching `masterlist_id` and `campus_id`.
- Import produces parent status `distributed`, campus-scoped records, and one `with_coordinator` batch per represented campus without invoking `MasterlistVerificationService`.

- [ ] **Step 1: Write failing import tests**

Test that a valid two-campus CSV creates two batches and resolved record campus IDs, that an unknown campus blocks preview/import, and that no verifier HTTP call occurs during import.

- [ ] **Step 2: Run the focused test and verify failure**

Run: `php artisan test tests/Feature/BeneficiaryMasterlistCampusWorkflowTest.php --filter=upload`

Expected: FAIL because `campus` is not required and the campus-batch table/model do not exist.

- [ ] **Step 3: Add schema and model relationships**

Create `masterlist_campus_batches` with unique `masterlist_id/campus_id`, status, three actor foreign keys, three timestamps, and timestamps. Add nullable `verified_by` and `verified_at` to `masterlist_records`. Add Eloquent relationships and casts.

- [ ] **Step 4: Implement deterministic import**

Resolve normalized active campus names/codes during preview, include `campus_id` and display name in preview rows, reject unknown campus values, create records and batches transactionally, set parent status to `distributed`, and remove `$this->verifier->verify($masterlist)` from import.

- [ ] **Step 5: Run migration and focused tests**

Run: `php artisan migrate:fresh --seed && php artisan test tests/Feature/BeneficiaryMasterlistCampusWorkflowTest.php --filter=upload`

Expected: PASS.

### Task 2: One campus officer per role

**Files:**
- Modify: `app/Http/Requests/Admin/StoreUserRequest.php`
- Modify: `app/Http/Requests/Admin/UpdateUserRequest.php`
- Modify: `database/seeders/RoleAccountSeeder.php`
- Modify: `tests/Feature/AdminUserManagementTest.php`
- Modify: `tests/Feature/RoleAccountSeederTest.php`

**Interfaces:**
- Account validation rejects a second active `coordinator` or `registrar` for the same campus and permits replacement when the existing account is inactive.
- Seeder creates deterministic `coordinator.<campus-code>@scholarsync.test` and `registrar.<campus-code>@scholarsync.test` accounts for all seven campuses.

- [ ] **Step 1: Write failing account cardinality and seeder tests**

Exercise account creation/update through real admin routes and assert seven Coordinator plus seven Registrar accounts after seeding.

- [ ] **Step 2: Run focused tests and verify failure**

Run: `php artisan test tests/Feature/AdminUserManagementTest.php tests/Feature/RoleAccountSeederTest.php`

Expected: FAIL because duplicate campus officers are accepted and only one campus pair is seeded.

- [ ] **Step 3: Implement validation and seeding**

Use an after-validation query scoped to active users, selected role, and campus, ignoring the updated user. Generate all campus officer accounts while retaining Student, Administrator, and Chairman fixtures.

- [ ] **Step 4: Run focused tests**

Run: `php artisan test tests/Feature/AdminUserManagementTest.php tests/Feature/RoleAccountSeederTest.php`

Expected: PASS.

### Task 3: Coordinator-to-Registrar verification workflow

**Files:**
- Create: `app/Services/MasterlistCampusWorkflowService.php`
- Create: `app/Http/Controllers/Registrar/MasterlistVerificationController.php`
- Create: `app/Http/Requests/UpdateRegistrarMasterlistRecordRequest.php`
- Create: `resources/views/registrar/masterlists/index.blade.php`
- Create: `resources/views/registrar/masterlists/show.blade.php`
- Modify: `app/Http/Controllers/Coordinator/MasterlistValidationController.php`
- Modify: `resources/views/coordinator/masterlists/index.blade.php`
- Modify: `resources/views/coordinator/masterlists/show.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/sidebar.blade.php`
- Test: `tests/Feature/BeneficiaryMasterlistCampusWorkflowTest.php`

**Interfaces:**
- `MasterlistCampusWorkflowService::submitToRegistrar(batch, user)`, `returnToCoordinator(batch, user)`, and `submitToChairman(batch, user)` enforce state, completeness, campus, actor role, timestamps, parent summary status, transactions, and audit records.
- Registrar update accepts only `verification_status` in `verified/not_verified` plus optional remarks and stores authenticated `verified_by/verified_at`.

- [ ] **Step 1: Write failing workflow and isolation tests**

Prove Coordinator submission, Registrar-only decisions, complete-before-return, Coordinator review/submission, and 403 responses for another campus.

- [ ] **Step 2: Run focused tests and verify failure**

Run: `php artisan test tests/Feature/BeneficiaryMasterlistCampusWorkflowTest.php --filter=campus`

Expected: FAIL because campus-batch routes, controller, and transitions do not exist.

- [ ] **Step 3: Implement the workflow service and routes**

Add Coordinator batch routes for show/submit-to-registrar/submit-to-chairman and Registrar routes for index/show/record update/return. Every controller query must require the authenticated user's non-null `campus_id` and matching batch `campus_id`.

- [ ] **Step 4: Replace Coordinator verification controls and add Registrar UI**

Coordinator pages show names, workflow state, and read-only Registrar results. Registrar pages show beneficiary names, matched campus enrollment references, decisions, and a return action disabled by server validation until complete.

- [ ] **Step 5: Run focused tests**

Run: `php artisan test tests/Feature/BeneficiaryMasterlistCampusWorkflowTest.php tests/Feature/CoordinatorMasterlistValidationTest.php tests/Feature/RegistrarEnrollmentUploadTest.php`

Expected: PASS after updating obsolete Coordinator expectations to the routing contract.

### Task 4: Chairman consolidation, CSV export, and release

**Files:**
- Create: `app/Services/VerifiedMasterlistExportService.php`
- Modify: `app/Http/Controllers/Chairman/MasterlistApprovalController.php`
- Modify: `resources/views/chairman/masterlists/index.blade.php`
- Modify: `resources/views/chairman/masterlists/show.blade.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/BeneficiaryMasterlistCampusWorkflowTest.php`
- Modify: `tests/Feature/ChairmanMasterlistApprovalTest.php`

**Interfaces:**
- `VerifiedMasterlistExportService::download(ScholarshipMasterlist): StreamedResponse` exports only `verification_status = verified` records with agency, source file, beneficiary, and campus.
- Chairman export/release is available only when every represented batch is `submitted_to_chairman`; release records `approved_by`, `approved_at`, status `released`, and an audit event.

- [ ] **Step 1: Write failing consolidation/export tests**

Prove seven-campus progress visibility, incomplete-list blocking, verified-only CSV contents, all-not-verified empty export, and successful final release.

- [ ] **Step 2: Run focused tests and verify failure**

Run: `php artisan test tests/Feature/BeneficiaryMasterlistCampusWorkflowTest.php --filter=chairman`

Expected: FAIL because export does not exist and current release uses per-record Chairman decisions.

- [ ] **Step 3: Implement consolidation and export**

Query all seven campuses with optional batches, expose verified records only, stream CSV without external API calls, and replace per-record Chairman decision actions with export and confirmed release.

- [ ] **Step 4: Run focused tests**

Run: `php artisan test tests/Feature/BeneficiaryMasterlistCampusWorkflowTest.php tests/Feature/ChairmanMasterlistApprovalTest.php`

Expected: PASS after updating obsolete Chairman per-record approval expectations.

### Task 5: Regression verification

**Files:**
- Verify all changed PHP, Blade, migration, seeder, and test files.

**Interfaces:**
- Produces a migration-clean, formatted application with all existing workflows passing.

- [ ] **Step 1: Format and inspect**

Run: `php vendor/bin/pint --test` and `git diff --check -- . ':(exclude)microservices/masterlist-verifier/test_main.py'`

Expected: PASS with no formatting errors in scoped files.

- [ ] **Step 2: Verify migrations and full suite**

Run: `php artisan migrate:fresh --seed && php artisan test`

Expected: PASS with zero test failures.

- [ ] **Step 3: Commit implementation**

Stage only the workflow files and commit with `feat: implement campus masterlist verification workflow`; leave `microservices/masterlist-verifier/test_main.py` unstaged.
