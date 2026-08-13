# Role and Workflow Realignment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver exactly five login roles, campus-scoped coordinator and registrar access, university-owned agency/opportunity/masterlist workflows, and no continuing-scholarship feature.

**Architecture:** Add first-class campus and user-campus relationships, then enforce them at request/query boundaries. Decouple agencies from authentication before deleting legacy agency users, transfer agency workflows to Admin and Chairman controllers, and remove renewal functionality through a forward schema migration plus code/reference deletion.

**Tech Stack:** PHP 8.2+, Laravel 12, Blade, Alpine.js, Tailwind CSS, Pest/PHPUnit, MySQL/SQLite test database, Python FastAPI microservice.

## Global Constraints

- Preserve agency organizations, programs, policies, masterlists, and scholar records while deleting Scholarship Agency login users.
- Use exactly five login roles: Student, Administrator, Scholarship Chairman, Campus Scholarship Coordinator, and Campus Registrar.
- Administrator and Scholarship Chairman access is university-wide; Coordinator and Registrar access is limited to their assigned campus.
- Remove continuing-scholarship data, routes, classes, UI, reports, and stored requirement files completely.
- Preserve and adapt the user's existing uncommitted Registrar and microservice changes.
- Retain existing Blade visual conventions; do not perform an unrelated UI redesign.

---

### Task 1: Role and Campus Domain Foundation

**Files:**
- Create: `app/Models/Campus.php`
- Create: `database/factories/CampusFactory.php`
- Create: `database/seeders/CampusSeeder.php`
- Create: `database/migrations/2026_08_13_000001_create_campuses_and_realign_roles.php`
- Modify: `app/Enums/UserRole.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Student.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `database/seeders/RoleAccountSeeder.php`
- Modify: `database/factories/UserFactory.php`
- Test: `tests/Feature/RoleAccessTest.php`
- Test: `tests/Feature/RoleAccountSeederTest.php`

**Interfaces:**
- Produces: `Campus` model; `User::campus(): BelongsTo`; `UserRole::requiresCampus(): bool`; five `UserRole` cases.
- Consumes: campus names in `public/data/sksu-academic-options.json`.

- [ ] **Step 1: Write failing domain and migration tests**

Assert `UserRole::cases()` contains only `student`, `administrator`, `scholarship_chairman`, `coordinator`, and `registrar`; assert full labels for campus roles; assert seven campuses are seeded; assert coordinator/registrar seeded accounts have `campus_id`; assert agency users are absent while agencies remain.

- [ ] **Step 2: Run the focused tests and confirm red state**

Run: `php artisan test tests/Feature/RoleAccessTest.php tests/Feature/RoleAccountSeederTest.php`
Expected: FAIL because `ScholarshipAgency` still exists and campuses/user assignments do not.

- [ ] **Step 3: Implement the campus and role foundation**

Create `campuses(id, code unique, name unique, is_active, timestamps)`, add nullable indexed `users.campus_id`, seed the seven JSON-defined campuses, remove the agency enum case, add full campus-role labels and `requiresCampus()`, and update account seed/factory behavior. In the migration, make `agencies.user_id` nullable before setting agency rows to null and deleting `scholarship_agency` users.

- [ ] **Step 4: Run focused tests**

Run: `php artisan test tests/Feature/RoleAccessTest.php tests/Feature/RoleAccountSeederTest.php`
Expected: PASS.

- [ ] **Step 5: Commit the domain foundation**

Run: `git add app/Enums/UserRole.php app/Models/Campus.php app/Models/User.php app/Models/Student.php database/factories database/migrations/2026_08_13_000001_create_campuses_and_realign_roles.php database/seeders tests/Feature/RoleAccessTest.php tests/Feature/RoleAccountSeederTest.php && git commit -m "feat: realign roles and add campus assignments"`

### Task 2: Administrator Account and Scholarship Management

**Files:**
- Modify: `app/Http/Requests/Admin/StoreUserRequest.php`
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `resources/views/admin/users/index.blade.php`
- Create: `app/Http/Controllers/Admin/ScholarshipOpportunityController.php`
- Create: `app/Http/Requests/Admin/StoreScholarshipOpportunityRequest.php`
- Create: `app/Http/Requests/Admin/UpdateScholarshipOpportunityRequest.php`
- Create: `resources/views/admin/scholarships/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AdminUserManagementTest.php`
- Test: `tests/Feature/RevisedObjectivesTest.php`

**Interfaces:**
- Consumes: `UserRole::requiresCampus()`, `Campus`, `Agency`, `ScholarshipProgram`, and `ScholarshipPolicy`.
- Produces: administrator routes `admin.scholarships.index/store/update/destroy`; validated external `application_link`; campus-aware user creation.

- [ ] **Step 1: Write failing admin behavior tests**

Cover rejection of agency as a role, required campus for coordinator/registrar, forbidden campus for university-wide roles, administrator creation/publication/update/archive of agency-backed scholarship opportunities, and student visibility of active opportunities with official links.

- [ ] **Step 2: Confirm tests fail**

Run: `php artisan test tests/Feature/AdminUserManagementTest.php tests/Feature/RevisedObjectivesTest.php`
Expected: FAIL because account validation and Admin scholarship routes do not satisfy the new ownership rules.

- [ ] **Step 3: Implement account and opportunity management**

Validate `campus_id` conditionally by role, store it on users, show campus selectors only for campus roles, and replace agency-owned policy publishing with administrator CRUD using the existing agency/program/policy schema. Accept only `http`/`https` official application links and keep archived/inactive opportunities out of student results.

- [ ] **Step 4: Run focused tests**

Run: `php artisan test tests/Feature/AdminUserManagementTest.php tests/Feature/RevisedObjectivesTest.php`
Expected: PASS.

- [ ] **Step 5: Commit administrator workflows**

Run: `git add app/Http/Controllers/Admin app/Http/Requests/Admin resources/views/admin resources/views/student/scholarships routes/web.php tests/Feature/AdminUserManagementTest.php tests/Feature/RevisedObjectivesTest.php && git commit -m "feat: move scholarship management to administrators"`

### Task 3: Chairman Masterlist Upload and Campus Distribution

**Files:**
- Create: `app/Http/Controllers/Chairman/MasterlistUploadController.php`
- Modify: `app/Http/Controllers/Chairman/MasterlistApprovalController.php`
- Modify: `app/Services/MasterlistCsvService.php`
- Modify: `app/Models/ScholarshipMasterlist.php`
- Modify: `app/Models/MasterlistRecord.php`
- Create: `database/migrations/2026_08_13_000002_add_campus_assignment_to_masterlists.php`
- Move/adapt: `resources/views/agency/masterlists/*` to `resources/views/chairman/masterlists/*`
- Modify: `routes/web.php`
- Test: `tests/Feature/ChairmanMasterlistApprovalTest.php`
- Replace: `tests/Feature/AgencyMasterlistUploadTest.php` with `tests/Feature/ChairmanMasterlistUploadTest.php`

**Interfaces:**
- Consumes: `MasterlistCsvService`, `Campus`, `Agency`, `ScholarshipProgram`.
- Produces: `chairman.masterlists.create/preview/store`; campus assignment on imported records; Chairman-wide progress data.

- [ ] **Step 1: Write failing Chairman upload/distribution tests**

Assert only Chairman can upload, agency/program selection uses stored domain records, CSV preview validates required fields, imported records receive a campus assignment, and the progress screen groups validation counts by campus.

- [ ] **Step 2: Confirm tests fail**

Run: `php artisan test tests/Feature/ChairmanMasterlistUploadTest.php tests/Feature/ChairmanMasterlistApprovalTest.php`
Expected: FAIL because uploads are agency-owned and records lack normalized campus assignment.

- [ ] **Step 3: Transfer and adapt the workflow**

Move upload orchestration into the Chairman namespace, remove `agencyFor($request)` assumptions, require existing `agency_id` and `program_id`, normalize CSV campus names to `campus_id`, reject unknown campus values with row-specific validation errors, and expose per-campus progress to Chairman.

- [ ] **Step 4: Run focused tests**

Run: `php artisan test tests/Feature/ChairmanMasterlistUploadTest.php tests/Feature/ChairmanMasterlistApprovalTest.php`
Expected: PASS.

- [ ] **Step 5: Commit Chairman upload ownership**

Run: `git add app/Http/Controllers/Chairman app/Models app/Services/MasterlistCsvService.php database/migrations/2026_08_13_000002_add_campus_assignment_to_masterlists.php resources/views/chairman routes/web.php tests/Feature/ChairmanMasterlist* && git commit -m "feat: move masterlist intake to chairman"`

### Task 4: Campus-Scoped Coordinator and Registrar Workflows

**Files:**
- Create: `app/Policies/ScholarshipMasterlistPolicy.php`
- Modify: `app/Http/Controllers/Coordinator/MasterlistValidationController.php`
- Modify: `app/Http/Controllers/Registrar/EnrolledStudentController.php`
- Modify: `app/Services/MasterlistVerificationService.php`
- Modify: `resources/views/coordinator/masterlists/index.blade.php`
- Modify: `resources/views/coordinator/masterlists/show.blade.php`
- Modify: `resources/views/registrar/enrolled-students/index.blade.php`
- Modify: `microservices/masterlist-verifier/main.py`
- Test: `tests/Feature/CoordinatorMasterlistValidationTest.php`
- Test: `tests/Feature/RegistrarEnrollmentUploadTest.php`
- Test: `tests/Feature/RevisedObjectivesTest.php`

**Interfaces:**
- Consumes: authenticated `User::campus_id`; masterlist/record campus assignment; microservice verification endpoint.
- Produces: campus-scoped coordinator queries; campus-scoped registrar imports and verification; denial of cross-campus access.

- [ ] **Step 1: Add failing campus-isolation tests**

Create two campuses and assert a coordinator/registrar sees, imports, validates, and updates only their assigned campus. Assert cross-campus route access returns 403 or 404 and accounts without a campus receive a corrective 403 response.

- [ ] **Step 2: Confirm tests fail without disturbing the user's current edits**

Run: `php artisan test tests/Feature/CoordinatorMasterlistValidationTest.php tests/Feature/RegistrarEnrollmentUploadTest.php tests/Feature/RevisedObjectivesTest.php`
Expected: FAIL on campus isolation assertions.

- [ ] **Step 3: Implement server-side campus scoping**

Scope every Coordinator and Registrar query by the authenticated user's `campus_id`, authorize bound records before mutation/download, stamp registrar-imported rows with the user's campus, pass campus identifiers through Laravel verification payloads, and preserve the current microservice import/verification enhancements.

- [ ] **Step 4: Run PHP and Python verification**

Run: `php artisan test tests/Feature/CoordinatorMasterlistValidationTest.php tests/Feature/RegistrarEnrollmentUploadTest.php tests/Feature/RevisedObjectivesTest.php`
Run: `python -m py_compile microservices/masterlist-verifier/main.py`
Expected: all commands PASS.

- [ ] **Step 5: Commit campus authorization**

Run: `git add app/Http/Controllers/Coordinator app/Http/Controllers/Registrar app/Policies app/Services/MasterlistVerificationService.php resources/views/coordinator resources/views/registrar microservices/masterlist-verifier/main.py tests/Feature/CoordinatorMasterlistValidationTest.php tests/Feature/RegistrarEnrollmentUploadTest.php tests/Feature/RevisedObjectivesTest.php && git commit -m "feat: enforce campus-scoped validation"`

### Task 5: Remove Agency Authentication and Continuing Scholarships

**Files:**
- Delete: `app/Http/Controllers/Agency/`
- Delete: `resources/views/agency/`
- Delete: `app/Http/Controllers/Student/ScholarshipRenewalController.php`
- Delete: `app/Http/Controllers/Evaluator/ScholarshipRenewalEvaluationController.php`
- Delete: `app/Http/Requests/StoreScholarshipRenewalRequest.php`
- Delete: `app/Http/Requests/EvaluateScholarshipRenewalRequest.php`
- Delete: `app/Models/ScholarshipApplication.php`
- Delete: `app/Models/ScholarshipRequirement.php`
- Delete: `app/Enums/ScholarshipApplicationStatus.php`
- Delete: `app/Mail/ScholarshipEvaluationResultMail.php`
- Delete: `resources/views/student/scholarship-renewals/`
- Delete: `resources/views/evaluator/`
- Delete: `resources/views/emails/scholarship-applications/`
- Delete: renewal factories and `tests/Feature/ScholarshipRenewalTest.php`
- Create: `database/migrations/2026_08_13_000003_drop_continuing_scholarship_tables.php`
- Modify: `routes/web.php`
- Modify: `app/Models/Student.php`
- Test: `tests/Feature/RemovedFeaturesTest.php`

**Interfaces:**
- Consumes: migrated agency records and transferred Admin/Chairman routes from Tasks 2–3.
- Produces: no agency or renewal routes/classes/tables; cleanup of `storage/app/private/scholarship-requirements` during migration/maintenance command where present.

- [ ] **Step 1: Write failing absence tests**

Assert agency dashboard/policy/masterlist route names and student/evaluator renewal route names are absent; assert agency and renewal controller classes are absent after autoload rebuild; assert renewal tables are absent after migration.

- [ ] **Step 2: Confirm tests fail**

Run: `php artisan test tests/Feature/RemovedFeaturesTest.php`
Expected: FAIL because obsolete routes, classes, and tables still exist.

- [ ] **Step 3: Remove obsolete code and data paths**

Delete the listed artifacts, remove imports and relationships, add a dependency-safe migration dropping `scholarship_requirements` before `scholarship_applications`, and remove exclusively owned renewal files using explicit Storage paths without touching certificate/masterlist files.

- [ ] **Step 4: Rebuild autoload and run absence tests**

Run: `composer dump-autoload`
Run: `php artisan test tests/Feature/RemovedFeaturesTest.php`
Expected: PASS.

- [ ] **Step 5: Commit removals**

Run: `git add -A app database resources routes tests && git commit -m "refactor: remove agency login and scholarship renewals"`

### Task 6: Dashboards, Monitoring, Reports, and Documentation Realignment

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `app/Http/Controllers/WelcomeController.php`
- Modify: `app/Http/Controllers/Admin/Monitoring/MonitoringDashboardController.php`
- Modify: `app/Http/Controllers/Admin/Monitoring/TransactionMonitoringController.php`
- Modify: `app/Services/ReportBuilderService.php`
- Modify: `resources/views/layouts/sidebar.blade.php`
- Modify: `resources/views/dashboards/show.blade.php`
- Modify: `resources/views/welcome.blade.php`
- Modify: `resources/views/admin/monitoring/*`
- Modify: `resources/views/admin/reports/*`
- Modify: `ROLE_FUNCTIONS.md`
- Modify: `README.md`
- Modify: `flow.md`
- Modify: `plan.md`
- Test: `tests/Feature/RoleDashboardTest.php`
- Test: `tests/Feature/AdminCentralMonitoringTest.php`
- Test: `tests/Feature/AdminReportsTest.php`

**Interfaces:**
- Consumes: five-role enum, administrator opportunities, Chairman uploads, campus-scoped validation.
- Produces: objectives-aligned role copy, navigation, metrics, transactions, and reports without renewal/agency-login concepts.

- [ ] **Step 1: Update tests to describe the final interface**

Assert five dashboards and their responsibilities, no agency/renewal navigation or metrics, campus titles for Coordinator/Registrar, and reports limited to certificate, scholar, masterlist/validation, agency/opportunity, enrollment, decision, campus, and consolidated outputs.

- [ ] **Step 2: Confirm tests fail**

Run: `php artisan test tests/Feature/RoleDashboardTest.php tests/Feature/AdminCentralMonitoringTest.php tests/Feature/AdminReportsTest.php`
Expected: FAIL on obsolete agency and renewal references.

- [ ] **Step 3: Realign presentation and reporting**

Remove renewal queries/imports/cards/transaction tables/report builders, remove agency dashboard branches, add Admin agency/opportunity and Chairman intake responsibilities, use full campus-role labels, show assigned campus on scoped dashboards, and update repository documentation to the final objectives.

- [ ] **Step 4: Run focused tests**

Run: `php artisan test tests/Feature/RoleDashboardTest.php tests/Feature/AdminCentralMonitoringTest.php tests/Feature/AdminReportsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit UI/report alignment**

Run: `git add app/Http/Controllers app/Services/ReportBuilderService.php resources/views ROLE_FUNCTIONS.md README.md flow.md plan.md tests/Feature && git commit -m "refactor: align dashboards and reports with final roles"`

### Task 7: Full Migration and Regression Verification

**Files:**
- Modify as required by failures: files already listed in Tasks 1–6 only.
- Test: complete test suites and build outputs.

**Interfaces:**
- Consumes: all preceding tasks.
- Produces: verified application meeting the design acceptance criteria.

- [ ] **Step 1: Run formatting and static syntax checks**

Run: `vendor/bin/pint --test`
Run: `php -l routes/web.php`
Run: `python -m py_compile microservices/masterlist-verifier/main.py`
Expected: PASS.

- [ ] **Step 2: Verify fresh and forward migrations**

Run: `php artisan migrate:fresh --seed --env=testing`
Run: `php artisan route:list`
Expected: migrations and seeders succeed; route list contains no agency-login or scholarship-renewal routes.

- [ ] **Step 3: Run the complete automated test suite**

Run: `php artisan test`
Expected: PASS with zero failures.

- [ ] **Step 4: Compile production frontend assets**

Run: `npm run build`
Expected: Vite build succeeds with no missing Blade/JS imports.

- [ ] **Step 5: Inspect the final diff and working tree**

Run: `git diff --check`
Run: `git status --short`
Expected: no whitespace errors; any pre-existing user changes are identified and preserved.

- [ ] **Step 6: Commit verification-only fixes if required**

If verification required a correction, stage each corrected path explicitly with `git add path/to/corrected/file.php`, inspect `git diff --cached`, then run `git commit -m "test: complete role workflow regression coverage"`. If no correction was needed, do not create an empty commit.
Expected: final implementation is committed without sweeping unrelated user work into the commit.
