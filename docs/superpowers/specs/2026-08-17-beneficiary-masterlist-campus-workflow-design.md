# Beneficiary Masterlist Campus Workflow Design

## Goal

Process agency beneficiary masterlists across SKSU's seven campuses using automatic bulk enrollment and COR verification. Campus Registrars supervise automatic results and resolve exceptions instead of manually deciding every scholar. Every agency record remains stored and traceable, including unsuccessful and uncertain records.

## Roles and boundaries

- The Scholarship Chairman uploads an agency masterlist, monitors all represented campuses, receives completed campus results, consolidates final qualified scholars, exports the result, and records external forwarding.
- Each Campus Scholarship Coordinator accesses only their campus, reviews its routed batch, submits it for verification, reviews returned results, and submits it to the Chairman. The Coordinator never performs official verification.
- Each Campus Registrar accesses only their campus. The Registrar reviews the automatic summary, inspects negative results, and resolves uncertain or discrepant records.
- The verification microservice performs campus-scoped bulk cross-checking against official student and enrollment data.
- Scholarship Agencies remain external domain records. They have no login, role, route, dashboard, or system access.

## Upload and preservation

The Chairman uploads a CSV containing `student_name` and `campus`; `student_id_number` remains optional when provided by an agency. Import resolves the campus against the seven active campuses, creates one `MasterlistRecord` per source row, and creates one `MasterlistCampusBatch` for each represented campus.

Original uploaded values are immutable verification inputs. Matching never overwrites them, and failed or unqualified records are never removed. A 3,000-row agency file therefore remains 3,000 database records after verification and export.

## Batch state machine

Each represented campus batch moves forward through:

1. `with_coordinator` — imported and routed to the Coordinator.
2. `verification_queued` — Coordinator submitted it; a verification run and queued work exist.
3. `verification_processing` — one or more chunks are being processed.
4. `awaiting_registrar_review` — all chunks succeeded; automatic results are available.
5. `verification_failed` — one or more chunks exhausted retries and require an authorized retry.
6. `returned_to_coordinator` — Registrar completed required exception resolutions and returned the batch.
7. `submitted_to_chairman` — Coordinator reviewed and submitted the campus result.

Retrying a failed run returns the batch to `verification_queued`. Transitions record actor and timestamp where a user initiated them and always write an audit event. The parent masterlist uses `distributed`, `campus_verification`, `ready_for_consolidation`, and `released`. It becomes ready only when every represented campus batch is submitted to the Chairman. Campuses with no records do not block completion.

## Automatic verification rules

Matching is restricted to the batch's campus. The microservice attempts an exact student ID match when supplied, then a unique normalized-name match within the campus. Normalization may ignore case, accents, punctuation, excess whitespace, and token order. Fuzzy spelling similarity is not a confident match. Blank identifiers, no match, multiple matches, cross-campus candidates, missing official data, and inconsistent academic-period data produce `needs_review`, never `not_enrolled`.

Every record receives three separate results:

- Enrollment: `enrolled`, `not_enrolled`, or `needs_review`.
- COR: `cor_printed`, `no_cor_printed`, or `needs_review`.
- Qualification: `qualified`, `not_qualified`, or `needs_review`.

A confident official match with `enrollment_status = enrolled` produces `enrolled`; a confident match explicitly marked otherwise produces `not_enrolled`. COR is determined only for a confident match.

The current system has no machine-readable, program-specific eligibility rules. For Phase 2, automatic qualification means only that the scholar has a confident match, is enrolled, and has a printed COR. A confident `not_enrolled` or `no_cor_printed` result produces `not_qualified`. Any uncertain enrollment or COR result produces `needs_review`. This narrow rule must not be presented as evaluating free-text scholarship-policy requirements.

## Data model and audit history

`masterlist_verification_runs` records one processing attempt for a campus batch: status, totals, chunk progress, result counts, attempts, timestamps, and failure summary.

`masterlist_records` retains original data and stores current automatic and final enrollment, COR, and qualification results; match status; matched Registrar student; automatic timestamp and message; and current Registrar resolution attribution. Final results initially copy automatic results and change only through an audited Registrar resolution.

`masterlist_record_verifications` is an immutable per-run snapshot containing original submitted data, matched official data when available, three automatic results, match status, reason message, microservice version, and verification timestamp.

`masterlist_registrar_resolutions` is append-only history containing previous and new final results, reason, Registrar, and timestamp. A Registrar reason is mandatory whenever an automatic result is changed or a `needs_review` result is resolved.

## Background processing

Coordinator submission and queue dispatch are separated from the browser request:

1. In a database transaction, the workflow validates ownership/state, creates a queued verification run, and changes the batch status.
2. After commit, Laravel dispatches database-queue jobs in chunks of at most 500 masterlist records.
3. Each job loads only its record IDs plus official Registrar records for the same campus and sends them to FastAPI.
4. Each valid response is stored transactionally and updates run progress.
5. Completion aggregates counts and moves the batch to `awaiting_registrar_review`.

Jobs are idempotent by verification run and record ID. A continuously running Laravel queue worker is a deployment requirement. The browser never waits for FastAPI to process a full campus batch.

## Registrar exception review

The Registrar batch page displays automatic summary counts and filters for `needs_review`, `not_enrolled`, `no_cor_printed`, `not_qualified`, ambiguous/unmatched records, resolved records, and all records. Successfully qualified records require no manual decision.

For an exception, the Registrar can compare immutable agency data with the matched official snapshot, confirm the automatic result, or set final enrollment, COR, and qualification results with a mandatory reason. Automatic fields never change when the Registrar acts; final fields and an append-only resolution record capture the action.

The Registrar may return the batch only after background verification succeeded and every `needs_review` final result is resolved. Confirmed negative results may remain negative; records are never deleted.

## Failure and retry behavior

- Transient connection errors, timeouts, and HTTP 5xx responses use bounded retries with exponential backoff.
- Invalid or incomplete microservice responses fail the chunk without applying partial results.
- Responses containing unknown, duplicate, or cross-campus record IDs are rejected.
- Completed chunks remain stored when another chunk fails.
- Exhausted jobs mark the run and batch failed and preserve all prior results.
- Retrying targets failed or incomplete work and never clears successful snapshots.
- Microservice unavailability never changes a scholar to `not_enrolled`.
- Failure, retry, automatic completion, Registrar resolution, and workflow transition events are audited.

## Coordinator and Chairman completion

After Registrar return, the Coordinator reviews separate enrollment, COR, qualification, and Registrar-resolution results and sends the campus batch to the Chairman. The Chairman monitors all seven campuses but only represented batches gate completion.

The final export includes records whose final qualification result is `qualified`. It identifies source agency/file, original scholar identifiers, campus, final enrollment/COR/qualification results, and verification timestamp. Exporting does not authenticate or communicate with the external agency. A separate Chairman action records that the export was forwarded externally.

## Security

- Coordinators and Registrars are authorized by both role and exact `campus_id`.
- The Chairman has system-wide masterlist access.
- Only a Registrar may create Registrar resolutions.
- Coordinator and Chairman actions cannot modify automatic or final Registrar-controlled result fields.
- State transitions and multi-record writes are transactional.
- Uploaded values and official-record snapshots are escaped in views and exports.

## Planned implementation areas

- Database migrations, models, relationships, factories, and validation for runs, snapshots, result fields, and resolutions.
- FastAPI contract and tests for campus-aware matching and safe `needs_review` behavior.
- Laravel verification client, queued chunk jobs, completion/failure handling, and queue-operation documentation.
- Campus workflow transitions and audit events.
- Registrar summary, filtering, exception resolution, and retry controls.
- Coordinator review, Chairman progress, qualified-only consolidation/export, reports, and dashboard counters.
- Feature and microservice tests covering large-list preservation, chunking, uncertainty, retries, campus isolation, audit history, completion gates, and existing certificate regressions.

## Acceptance criteria

- Coordinator submission returns promptly and queues campus-scoped bulk verification.
- Uncertain matching never becomes automatic `not_enrolled`.
- Enrollment, COR, and qualification results are independently stored.
- Confident enrolled plus printed-COR records automatically qualify under the Phase 2 rule.
- The Registrar need not touch successfully qualified records.
- Every exception resolution preserves automatic results and append-only history.
- A failed chunk is retryable without deleting records or successful results.
- All original rows remain queryable regardless of outcome.
- Only final qualified scholars appear in the Chairman export.
- Role, campus, audit, and existing certificate behavior remain protected by tests.
