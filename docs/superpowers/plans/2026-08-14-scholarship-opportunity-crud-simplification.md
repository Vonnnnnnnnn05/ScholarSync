# Scholarship Opportunity CRUD Simplification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add accessible View, Edit, and Delete actions to the existing opportunity-only administrator page.

**Architecture:** Extend the existing administrator opportunity controller with route-model-bound update and delete operations. Keep creation and inline agency/program text inputs, and render View, Edit, and Delete as Alpine-powered dialogs matching User Management.

**Tech Stack:** Laravel, Blade, Tailwind CSS, Pest/PHPUnit

## Global Constraints

- Do not add separate agency or program management sections.
- Use centered modals for View and Edit and a styled confirmation dialog for Delete.
- Preserve fillable agency and program fields and existing records.
- Delete only the selected opportunity.
- Support Draft, Published/Active, Inactive, and Archived lifecycle statuses; students see Published only.

---

### Task 1: Opportunity update and deletion behavior

**Files:**
- Modify: `tests/Feature/RevisedObjectivesTest.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Admin/ScholarshipOpportunityController.php`

**Interfaces:**
- Produces: `admin.scholarships.update` PATCH route and `admin.scholarships.destroy` DELETE route.

- [ ] Write feature tests proving administrators can update and delete an opportunity while referenced agencies/programs remain.
- [ ] Run the focused tests and confirm failures caused by missing routes.
- [ ] Add route-model-bound update/delete routes and controller methods.
- [ ] Run the focused tests and confirm they pass.

### Task 2: Simplified opportunity interface

**Files:**
- Modify: `tests/Feature/RevisedObjectivesTest.php`
- Modify: `resources/views/admin/scholarships/index.blade.php`

**Interfaces:**
- Consumes: opportunity update/delete routes from Task 1.
- Produces: accessible View and Edit modals plus a styled Delete confirmation dialog.

- [ ] Write a feature test requiring an opportunity-only page and the three action labels.
- [ ] Run the test and confirm it fails against the current action-free list.
- [ ] Render inline creation, a read-only View modal, Edit form modal, and styled Delete confirmation dialog.
- [ ] Run focused tests and confirm they pass.

### Task 3: Opportunity lifecycle and student visibility

**Files:**
- Modify: `tests/Feature/RevisedObjectivesTest.php`
- Modify: `app/Http/Controllers/Admin/ScholarshipOpportunityController.php`
- Modify: `resources/views/admin/scholarships/index.blade.php`

- [ ] Write a feature test that deactivates a published opportunity, confirms it is hidden from students, republishes it, and confirms it becomes visible.
- [ ] Run the focused test and confirm Inactive validation fails.
- [ ] Accept and display the Inactive status while keeping the student query restricted to Published.
- [ ] Run focused tests and confirm they pass.

### Task 4: Verification

**Files:**
- Verify all modified files.

- [ ] Run Pint on modified PHP files.
- [ ] Run the full Laravel test suite.
- [ ] Run the frontend production build.
- [ ] Run `git diff --check`.
