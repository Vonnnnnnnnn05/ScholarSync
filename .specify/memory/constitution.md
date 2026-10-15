<!-- SYNC IMPACT REPORT
Version change: 0.0.0 → 1.0.0
Modified principles: N/A (initial population)
Added sections:
  - Core Principles (I–VI)
  - Technology & Architecture Constraints
  - Development Workflow
  - Governance
Removed sections: None
Follow-up TODOs: None
-->

# SKSUScholarSync Constitution

## Core Principles

### I. Separation of Ingestion and Verification (NON-NEGOTIABLE)
Raw, unstructured external-agency files (multi-layout PDFs, scanned
rosters, unstructured Excel/CSV files) MUST first pass through a
PDF-to-Excel/JSON Extraction & Normalization Layer before reaching
the Verification Microservice. The verification service MUST NOT
receive unparsed binary blobs, raw PDF bytes, or non-tabular payloads.
Rationale: external agencies provide wildly inconsistent file layouts;
a dedicated normalization stage isolates parsing failures from
verification logic and makes both layers independently testable.

### II. Missing-Field Resolution via SIS Cross-Reference
When an external scholarship agency omits a student's campus or
Student ID, the system MUST NOT reject the row. Instead, the row MUST
be staged with an `Incomplete/Unassigned` status. The Verification
Microservice MUST cross-reference the central Student Information
System (SIS) to auto-resolve the missing campus, program, and
enrollment status. Rows that remain unresolvable after SIS lookup MUST
be flagged for human review — never silently discarded.

### III. Confidence-Based Disambiguation
The Verification Microservice MUST employ a tiered matching strategy:
1. **Tier 1 — Exact ID Match**: match via `student_id` first.
2. **Tier 2 — Fuzzy Name Match**: when IDs are absent, run string
   similarity scoring across all campuses and return candidate
   matches with confidence scores.
3. **Tier 3 — Policy & Conflict Check**: verify whether the matched
   student already holds an active scholarship to prevent unauthorized
   double-granting; automatically update Module A (Certificate of
   Non-Grantee) eligibility.
The system MUST attempt tiers in order and MUST NOT skip Tier 1 when
a student ID is present.

### IV. Human-in-the-Loop Reconciliation (NON-NEGOTIABLE)
The database commit MUST NEVER be fully blind. The Scholarship
Chairman MUST review a color-coded staging dashboard before committing
any batch:
- **Green** = 100% Auto-Verified (exact match, no conflicts).
- **Amber** = Multiple Name Matches, Missing Campus Ambiguity, or
  Grant Conflict — requires manual resolution.
- **Red** = Unmatched against the SIS — requires investigation.
The `commitBatch` transaction MUST NOT proceed until every Amber
record has been explicitly resolved or acknowledged by the Chairman.

### V. Module Integrity — Protect Existing Modules
Module A (Certificate of Non-Grantee Issuance) and Module B
(Scholarship Opportunities Bulletin) are complete and deployed. New
Phase 2 code MUST NOT alter, remove, or break existing Module A/B
routes, controllers, services, views, or database schemas. Phase 2
additions MUST integrate additively — e.g., the `is_scholarship_grantee`
flag MUST be checked by Module A without modifying its generation
logic; Module B remains read-only for students.

### VI. Microservice Boundary & Fault Tolerance
The Python FastAPI Verification Microservice communicates with the
Laravel Core System exclusively via HTTP JSON payloads. The Core
System MUST handle microservice unavailability gracefully: verification
chunks MUST be retried with exponential back-off, and exhausted
failures MUST be surfaced to the user for manual retry — never
silently swallowed. Payload size MUST NOT exceed 500 records per
chunk.

## Technology & Architecture Constraints

- **Core Application**: Laravel (PHP) with Blade templates, served
  via XAMPP on Windows.
- **Frontend**: Blade + Tailwind CSS + Alpine.js; Vite for asset
  bundling.
- **Verification Microservice**: Python 3 + FastAPI, deployed on
  `localhost:8001`.
- **Database**: SQLite (development), with production migration
  path to MySQL/MariaDB.
- **Queue**: Laravel database queue driver;
  `masterlist-verification` queue for chunked verification jobs.
- **File Storage**: Laravel `local` disk for uploaded files.
- **Seven SKSU Campuses**: ACCESS, Bagumbayan, Isulan, Kalamansig,
  Lutayan, Palimbang, Tacurong — all campus-scoped roles are
  restricted to their assigned campus.

## Development Workflow

- **Additive Migrations Only**: new migrations MUST NOT alter
  existing table columns unless adding nullable columns or indices.
  Destructive changes require a separate RFC and version bump.
- **Service Layer Pattern**: all business logic MUST reside in
  `App\Services\*`; controllers MUST remain thin orchestrators.
- **Audit Trail**: every state-changing action on scholarship
  batches and grantee records MUST be logged via `AuditTrailService`.
- **Role-Based Access**: all new routes MUST be guarded by the
  existing role middleware (`role:scholarship_chairman`,
  `role:coordinator`, `role:registrar`, `role:administrator`).
- **Existing Microservice Contract**: the Python FastAPI
  `/verify-masterlist` endpoint contract (request/response models)
  is versioned; breaking payload changes require a MAJOR version
  bump of `service_version`.

## Governance

This constitution supersedes ad-hoc decisions for all Phase 2 and
subsequent development. Amendments require:
1. A documented rationale for the change.
2. An impact analysis on existing Modules A, B, and the
   microservice contract.
3. A version bump following semantic versioning (MAJOR for
   principle removal/redefinition, MINOR for new principles,
   PATCH for clarifications).
All pull requests and code reviews MUST verify compliance with the
principles above before merge.

**Version**: 1.0.0 | **Ratified**: 2026-10-15 | **Last Amended**: 2026-10-15
