# Scholarship Chair Certificate Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give Scholarship Chairmen full access to the existing certificate-request management workflow.

**Architecture:** Reuse the current Administrator controllers, views, URLs, and audit behavior. Expand only the certificate route middleware to accept both management roles and expose the existing navigation link to both roles.

**Tech Stack:** Laravel, PHP 8, Blade, Pest

## Global Constraints

- Preserve all existing certificate state transitions, validation, notifications, PDF generation, and audit attribution.
- Keep Student, Coordinator, and Registrar accounts forbidden.
- Do not broaden access to any other Administrator-only area.

---

### Task 1: Shared certificate-management authorization

**Files:**
- Modify: `tests/Feature/AdminOfficialReceiptVerificationTest.php`
- Modify: `tests/Feature/CertificateGenerationTest.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: Laravel `role` middleware with comma-separated allowed role values.
- Produces: Existing `admin.official-receipts.*` and `admin.certificates.*` routes authorized for `administrator` and `scholarship_chairman`.

- [ ] **Step 1: Write failing feature tests**

Add real HTTP tests using `User::factory()->role(UserRole::ScholarshipChairman)` that prove the Chair can list and inspect requests, download receipts, verify, approve, reject with remarks, and list/view/download generated certificates. Assert the acting Chair's ID is stored in `verified_by` and `approved_by`. Keep unrelated-role forbidden assertions.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/AdminOfficialReceiptVerificationTest.php tests/Feature/CertificateGenerationTest.php`

Expected: new Chair requests fail with HTTP 403 because both route groups currently use `role:administrator`.

- [ ] **Step 3: Expand only certificate route middleware**

In `routes/web.php`, change the middleware on the `admin/official-receipts` and `admin/certificates` groups to:

```php
Route::middleware('role:administrator,scholarship_chairman')
```

Do not modify middleware for scholarships, users, monitoring, reports, or other role workflows.

- [ ] **Step 4: Run focused tests**

Run: `php artisan test tests/Feature/AdminOfficialReceiptVerificationTest.php tests/Feature/CertificateGenerationTest.php`

Expected: PASS.

### Task 2: Chair navigation access

**Files:**
- Modify: `tests/Feature/RoleDashboardTest.php`
- Modify: `resources/views/layouts/sidebar.blade.php`

**Interfaces:**
- Consumes: Existing `admin.official-receipts.index` route and `User::hasRole()`.
- Produces: A visible Certificate Management sidebar link for Administrator and Scholarship Chairman users only.

- [ ] **Step 1: Write the failing dashboard test**

Add a Chairman dashboard assertion that the rendered response contains the URL from `route('admin.official-receipts.index')` and the text `Certificate Management`.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Feature/RoleDashboardTest.php`

Expected: FAIL because the current sidebar condition permits only Administrators.

- [ ] **Step 3: Expand the sidebar condition**

Set the Certificate Management link's `show` value to:

```php
$user->hasAnyRole([
    \App\Enums\UserRole::Administrator,
    \App\Enums\UserRole::ScholarshipChairman,
])
```

If the model exposes no `hasAnyRole()` helper, use two explicit `hasRole()` calls joined by `||`.

- [ ] **Step 4: Run focused and regression tests**

Run: `php artisan test tests/Feature/RoleDashboardTest.php tests/Feature/AdminOfficialReceiptVerificationTest.php tests/Feature/CertificateGenerationTest.php tests/Feature/RoleAccessTest.php`

Expected: PASS.

- [ ] **Step 5: Commit implementation**

```bash
git add routes/web.php resources/views/layouts/sidebar.blade.php tests/Feature/AdminOfficialReceiptVerificationTest.php tests/Feature/CertificateGenerationTest.php tests/Feature/RoleDashboardTest.php
git commit -m "feat: allow chair to manage certificate requests"
```
