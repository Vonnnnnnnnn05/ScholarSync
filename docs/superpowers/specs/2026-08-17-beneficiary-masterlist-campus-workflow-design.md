# Beneficiary Master List Campus Workflow Design

## Goal

Implement the seven-campus SKSUScholarSync beneficiary master-list workflow while preserving existing certificate processing and keeping Scholarship Agencies external to authentication.

## Roles and Boundaries

- The Scholarship Chairman uploads beneficiary master lists, monitors all seven campuses, receives completed campus submissions, consolidates verified beneficiaries, exports the final list, and records that it was forwarded externally.
- Each Campus Scholarship Coordinator works only with records assigned to their campus. The Coordinator routes a campus batch to the Registrar, reviews returned results, and submits the completed campus batch to the Chairman. The Coordinator never performs official verification.
- Each Campus Registrar works only with records assigned to their campus. The Registrar compares beneficiaries with official enrollment records and explicitly marks each record `verified` or `not_verified`.
- The Administrator manages system accounts but does not participate in this master-list workflow.
- Scholarship Agencies remain domain records for identifying the external source or recipient. They have no login, user role, routes, dashboard, or system access.
- Student certificate requests and their existing management workflow are outside this change.

## Upload and Campus Distribution

The Chairman uploads a CSV with two required columns: `student_name` and `campus`. The preview resolves campus values against the seven active SKSU campuses using normalized campus names or codes. Blank or unknown campus values are row errors and block import so records are never silently routed to the wrong campus.

Import creates the existing `ScholarshipMasterlist` parent, its existing `MasterlistRecord` children, and one `MasterlistCampusBatch` for every campus represented in the file. Each record receives the resolved `campus_id`. Campuses with no uploaded records appear as `No records` in the Chairman's seven-campus progress view and do not block completion.

Automatic microservice verification is removed from the upload path. The existing verifier code may remain for compatibility, but it cannot assign beneficiary verification results in this workflow.

## Campus Batch State Machine

Each campus batch moves through these states:

1. `with_coordinator` — created and routed after Chairman import.
2. `with_registrar` — Coordinator submits the campus batch for official verification.
3. `returned_to_coordinator` — Registrar has decided every record and returns the results.
4. `submitted_to_chairman` — Coordinator reviews the results and sends the campus batch to the Chairman.

Transitions are forward-only. A batch cannot be submitted to the Registrar without records, returned while any record remains pending, or submitted to the Chairman before the Registrar returns it. Every transition records the acting user and timestamp in the campus batch and writes an audit entry.

The parent master list summarizes overall progress with `distributed`, `campus_verification`, `ready_for_consolidation`, and `released`. It becomes ready for consolidation only when every represented campus batch has reached `submitted_to_chairman`.

## Registrar Verification

The Registrar queue shows only `with_registrar` batches for the authenticated user's `campus_id`. Each beneficiary record is displayed alongside a matching official `RegistrarStudent` record when available. The Registrar explicitly chooses `verified` or `not_verified` and may add remarks. The system records `verified_by` and `verified_at` on the beneficiary record.

An official-record match is reference information, not an automatic decision. No Coordinator or Chairman endpoint may modify Registrar verification fields. Route middleware plus campus-aware controller checks enforce these boundaries.

The existing Registrar enrollment XLSX import remains the source-maintenance workflow and remains scoped to the Registrar's campus.

## Coordinator Review

The Coordinator queue shows only batches for the authenticated user's campus. Before Registrar submission, the Coordinator can inspect the routed names and campus assignment but cannot set verification status. After the Registrar returns the batch, the Coordinator can inspect statuses and remarks and submit the batch to the Chairman. No record-level Coordinator approval or rejection is required.

## Chairman Consolidation and External Export

The Chairman sees all master lists and a progress summary covering all seven campuses. When all represented campus batches are submitted, the Chairman can view a consolidated list containing only records marked `verified` by Registrars.

The final export is a CSV containing the external agency, master-list source, beneficiary name, and campus. Downloading the export does not call or authenticate an external agency. A separate confirmed action marks the parent list `released`, stores the Chairman and timestamp, and records an audit event indicating that the list was forwarded externally.

The existing per-record Chairman approval fields may remain for database compatibility but no longer gate consolidation or release.

## Account Cardinality

The seven active campuses are the authoritative campus set. Account create and update validation permits at most one active Coordinator and one active Registrar per campus. A replacement may be assigned after the existing account is inactive or moved. University-wide roles do not receive a campus assignment.

The role-account seeder creates one Coordinator and one Registrar for each of the seven campuses, plus the existing university-wide and student accounts. Generated campus account emails are deterministic and unique. No agency account is seeded.

## Data Model

A new `masterlist_campus_batches` table contains:

- `masterlist_id` and `campus_id`, unique as a pair;
- workflow `status`;
- Coordinator-to-Registrar actor and timestamp;
- Registrar-return actor and timestamp; and
- Coordinator-to-Chairman actor and timestamp.

`masterlist_records` gains nullable `verified_by` and `verified_at` columns. Existing `campus_id` and `verification_status` are reused. Model relationships connect master lists, campus batches, campuses, records, and users without duplicating master-list or agency data.

## Interface Changes

- Chairman upload and preview pages document and display the required campus column.
- Chairman master-list pages show seven-campus progress, consolidated verified records, export, and final release.
- Coordinator pages become routing and returned-result review screens.
- Registrar navigation gains a beneficiary verification queue alongside enrollment-record maintenance.
- Labels use `Verified`, `Not Verified`, and the workflow state names in user-facing form.

Existing Blade components, responsive layout, pagination, status alerts, confirmation controls, and visual language are reused.

## Error Handling and Security

- Missing or unknown campuses block import with row-specific errors.
- Cross-campus Coordinator and Registrar access returns HTTP 403 or 404.
- Invalid state transitions return a validation error without partially updating data.
- Registrar return is blocked until every record has a decision.
- Coordinator submission to the Chairman is blocked until the Registrar has returned the batch.
- Chairman release is blocked until every represented campus batch is submitted.
- Empty final verified lists may still be exported and released once every campus batch is complete, preserving an auditable all-not-verified outcome.
- Multi-record state transitions run in database transactions.

## Planned Files

### Create

- A migration for `masterlist_campus_batches` and Registrar verification attribution.
- `app/Models/MasterlistCampusBatch.php`.
- A Registrar beneficiary-verification controller and form request.
- A focused campus-batch workflow service and final CSV export action/service.
- Registrar beneficiary queue and detail Blade views.
- Focused feature tests for upload routing, Registrar verification, campus isolation, workflow transitions, consolidation, and export.

### Modify

- `ScholarshipMasterlist`, `MasterlistRecord`, `Campus`, and `User` relationships.
- Relevant factories and `RoleAccountSeeder`.
- `MasterlistCsvService` and Chairman upload controller/views.
- Coordinator and Chairman master-list controllers/views.
- Account create/update requests.
- `routes/web.php`, sidebar/navigation, and existing master-list/account tests.

The existing verifier is removed only from the Chairman import call path. Unrelated certificate, scholarship-opportunity, monitoring, and reporting functionality is not refactored.

## Verification

Test-first feature coverage will prove:

- the authoritative campus seed contains exactly seven campuses;
- each campus can have only one active Coordinator and Registrar;
- no Scholarship Agency role or account exists;
- upload requires valid student and campus values and creates campus-scoped batches;
- Coordinators and Registrars cannot access another campus;
- Coordinators can route and submit but cannot verify records;
- only Registrars can record `verified` or `not_verified` outcomes;
- incomplete batches cannot advance;
- independently progressing campuses do not block one another;
- the Chairman can see all campus progress and only consolidate after all represented batches return;
- final CSV contains only Registrar-verified beneficiaries; and
- existing certificate processing and unrelated role access tests continue to pass.
