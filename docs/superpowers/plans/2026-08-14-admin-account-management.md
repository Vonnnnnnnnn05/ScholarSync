# Admin Account Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend Administrator User Management with account editing and safe account deactivation.

**Architecture:** Add update and destroy endpoints to the existing administrator-only user resource. Use dedicated form requests for authorization and validation, keep controller methods thin, store account removal as an inactive status, and render the controls on the existing Blade page.

**Tech Stack:** Laravel, Eloquent, Blade, Alpine.js, Pest, Tailwind CSS

## Global Constraints

- Only Administrators can manage accounts.
- Delete means deactivate; historical data is retained.
- The signed-in Administrator cannot deactivate their own account.
- Campus Scholarship Coordinator and Campus Registrar accounts require an active campus.
- All changes must be audited.

---

### Task 1: Define account update and deactivation behavior

**Files:**
- Create: `app/Http/Requests/Admin/UpdateUserRequest.php`
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AdminUserManagementTest.php`

**Interfaces:**
- Consumes: `UserRole::requiresCampus()`, `AuditTrailService::record()`, route-bound `User $user`
- Produces: `UserController::update(UpdateUserRequest $request, User $user, AuditTrailService $audit): RedirectResponse` and `UserController::destroy(Request $request, User $user, AuditTrailService $audit): RedirectResponse`

- [ ] **Step 1: Write failing feature tests** for editing account attributes, preserving a blank password, changing a supplied password, rejecting duplicate email/invalid campus assignments, deactivating an account, preventing self-deactivation, recording audit entries, and denying both endpoints to non-Administrators.
- [ ] **Step 2: Run `php artisan test tests/Feature/AdminUserManagementTest.php`** and confirm the new tests fail because update and destroy routes do not exist.
- [ ] **Step 3: Create `UpdateUserRequest`** using administrator authorization, normalized name/email input, unique-email validation that ignores the bound user, role validation, conditional active-campus validation, nullable confirmed password validation, and status validation limited to `active` and `inactive`.
- [ ] **Step 4: Add update and destroy routes** as `PATCH /admin/users/{user}` and `DELETE /admin/users/{user}` named `admin.users.update` and `admin.users.destroy`.
- [ ] **Step 5: Implement `update`** to update name, email, role, campus, status, and password only when supplied, then record `user_updated` with before/after values.
- [ ] **Step 6: Implement `destroy`** to reject the authenticated user's own account, set the target status to `inactive`, and record `user_deactivated`.
- [ ] **Step 7: Run `php artisan test tests/Feature/AdminUserManagementTest.php`** and confirm all account-management tests pass.

### Task 2: Add edit and delete controls to User Management

**Files:**
- Modify: `resources/views/admin/users/index.blade.php`
- Test: `tests/Feature/AdminUserManagementTest.php`

**Interfaces:**
- Consumes: `admin.users.update`, `admin.users.destroy`, `$roles`, `$campuses`, paginated `$users`
- Produces: accessible Edit modal and deactivate confirmation form for each listed account

- [ ] **Step 1: Add failing response assertions** that the page shows Edit and Delete controls and the appropriate update/deactivation form actions.
- [ ] **Step 2: Run the focused test** and confirm it fails because the controls are absent.
- [ ] **Step 3: Add an Actions column** with Edit and Delete buttons, using Alpine state to open a populated edit modal and a confirmation dialog.
- [ ] **Step 4: Add the edit form** with name, email, role, campus, status, optional password, password confirmation, validation messages, cancel, and save controls.
- [ ] **Step 5: Add the delete form** using the DELETE method and copy that clearly states the account will be deactivated while records are retained; hide or disable it for the signed-in account.
- [ ] **Step 6: Run `php artisan test tests/Feature/AdminUserManagementTest.php`** and confirm the feature suite passes.

### Task 3: Enforce inactive-account login blocking and regressions

**Files:**
- Modify: authentication request or middleware file identified by the existing login flow
- Test: `tests/Feature/Auth/AuthenticationTest.php`
- Test: `tests/Feature/AdminUserManagementTest.php`

**Interfaces:**
- Consumes: `users.status`
- Produces: authentication that accepts only accounts with `status = active`

- [ ] **Step 1: Locate the existing credential assembly** with `rg -n "attempt|authenticate" app tests/Feature/Auth` and write a failing test proving an inactive account cannot log in.
- [ ] **Step 2: Run `php artisan test tests/Feature/Auth/AuthenticationTest.php`** and confirm the inactive-account test fails.
- [ ] **Step 3: Add `status => active` to the authentication credentials** while preserving current validation and throttling behavior.
- [ ] **Step 4: Run `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/AdminUserManagementTest.php`** and confirm both suites pass.
- [ ] **Step 5: Run `php artisan test` and `npm run build`** to verify the complete application and compiled frontend.
