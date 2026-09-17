# Masterlist verifier review — 2026-09-11

Repository mapped, with detailed review of the FastAPI verifier and its Laravel upload, queue, workflow, persistence, Registrar resolution, export, configuration, schema, and test integration. This is a focused microservice review, not a claim that every unrelated UI file or third-party dependency was audited. Application code was not changed.

## Findings

### 1. High: fuzzy matching exceeds the default HTTP timeout

Locations: `microservices/masterlist-verifier/main.py:168`, `app/Services/MasterlistVerificationService.php:28`, `config/services.php:40`.

Every unmatched name scans all campus students, repeatedly normalizes their names, computes similarities, and sorts the entire candidate list. Every chunk also reloads and resends the complete campus enrollment list.

**Measured locally:** a direct Python call with 100 unmatched names and 2,000 official students took **18.97 seconds**, excluding HTTP and database work. The configured default HTTP timeout is **10 seconds**, and chunks can contain 500 names. Timing varies by machine and data, but this demonstrates a realistic timeout path well below the maximum chunk size. HTTP and job retries repeat the expensive computation; they do not solve it.

Fix direction: precompute normalized candidate names, narrow candidate searches, retain the top candidates without a full sort, and benchmark representative campus sizes. Align HTTP, worker, and queue reservation timeouts after measuring the improved implementation. A smaller chunk size is an interim mitigation.

### 2. High: normal development startup does not consume verification jobs

Locations: `composer.json:49`, `app/Jobs/VerifyMasterlistChunk.php:23`, `config/queue.php:43`.

`composer dev` starts `queue:listen` without `--queue`; configuration defaults to the `default` queue. Verification jobs explicitly use `masterlist-verification`. With the checked local database queue configuration, this startup path leaves verification jobs queued unless a separate correctly configured worker is running. It also does not start FastAPI.

Fix direction: make the development worker listen to `masterlist-verification,default` and document/start the Python process. The README already describes a separate worker, but its commands follow a `cd` into the microservice directory; the Artisan command must run from the Laravel project root.

### 3. High: interrupted jobs can remain stuck after their results are saved

Locations: `app/Jobs/VerifyMasterlistChunk.php:34`, `app/Jobs/VerifyMasterlistChunk.php:41`, `app/Services/MasterlistVerificationService.php:54`.

Result snapshots commit inside the service transaction, while progress counters and completion status update afterward. If the worker stops between these operations, redelivery finds the snapshots and returns immediately. It never repairs progress or completes the run. Concurrent duplicate deliveries can also pass the initial check and increment counters twice.

**Reproduced:** saved a chunk through the service, then invoked the job to simulate redelivery after the result commit. The run remained `processing`, with `completed_chunks=0` and `processed_records=0`.

Fix direction: persist a unique chunk completion record, result writes, and progress changes atomically. Reconcile completion on redelivery. Enforce terminal row states before allowing Registrar return; the current return guard only rejects `needs_review`, allowing `pending` if batch state becomes inconsistent.

### 4. High: old jobs can overwrite newer workflow state

Locations: `app/Jobs/VerifyMasterlistChunk.php:37`, `app/Jobs/VerifyMasterlistChunk.php:58`, `app/Services/MasterlistCampusWorkflowService.php:21`.

Jobs do not verify that their run is still the active run before changing records or batch status. One failed chunk marks the entire batch failed while sibling chunks can still be queued or running. The UI then permits retry/reverification, creating overlapping runs. Older jobs and failure callbacks can mutate the newer run's batch, and automatic writes do not protect `resolved_at` records at persistence time.

Submission also checks status on the supplied model before entering a transaction, without locking/rechecking it. Concurrent submissions can both create runs.

**Reproduced:** invoking an old job's failure callback changed a batch from `returned_to_coordinator` to `verification_failed`. This directly confirms the missing guard; a live worker race was not required or simulated.

Fix direction: keep an explicit active run ID, lock and recheck workflow transitions, and reject obsolete jobs at persistence and failure handling boundaries. Protect manual resolutions from automatic overwrites.

### 5. High: repeated retries forget earlier successful records

Location: `app/Services/MasterlistCampusWorkflowService.php:61`.

Retry excludes snapshots from only the immediately previous run, then selects remaining rows from the entire batch. Example: run 1 succeeds on A and fails on B; run 2 retries B and fails; run 3 now selects both A and B. A failed exception-only reverification can similarly cause a Coordinator retry to include unrelated or manually resolved rows.

**Reproduced:** the first retry contained 1 row; the next retry contained 2 rows even though the earlier successful row still had its snapshot.

Fix direction: track the original run's chunk/record membership and retry lineage. Retry only unfinished work for that operation, preserving earlier successes and excluding resolved records unless a separate explicit action authorizes reprocessing.

### 6. High: old enrollment data can qualify current beneficiaries

Locations: `app/Http/Controllers/Registrar/EnrolledStudentController.php:77`, `app/Services/MasterlistVerificationService.php:28`.

Enrollment import upserts by student ID and campus. Rows absent from later uploads remain unchanged. Verification selects every campus record without filtering `academic_year` or `semester`; these fields are not sent to Python, and the masterlist lacks an explicit verification period.

Consequently, an old record marked enrolled with a printed COR remains eligible to qualify a later masterlist even if that student is absent from the current enrollment upload. Importing during processing can also make different chunks use different versions of official data.

Evidence: traced import, schema/model, and request construction; no live academic records were modified.

Fix direction: define a verification period and enrollment dataset version, retain period-specific records, and bind each run to a consistent dataset. Explicitly distinguish a complete replacement upload from an incremental update.

### 7. High: unknown enrollment values are treated as enrolled

Locations: `app/Http/Controllers/Registrar/EnrolledStudentController.php:176`, `app/Http/Controllers/Registrar/EnrolledStudentController.php:183`.

Unrecognized enrollment text silently defaults to `enrolled`. Missing or unrecognized COR data becomes `false`, losing the Python service's distinction between unknown and explicitly not printed.

**Reproduced:** `withdrawn` and `not enrolld` both became `enrolled`; missing and `unknown` COR values both became `false`. With a printed COR, an invalid enrollment value can therefore lead to automatic qualification. Missing COR data instead causes automatic disqualification rather than review.

Fix direction: validate recognized values and report row-level errors or preserve unknown values for review. Document any deliberate default for a missing enrollment column separately from invalid explicit values.

### 8. High: Laravel accepts impossible verification responses

Location: `app/Services/MasterlistVerificationService.php:49`.

Response validation checks row coverage but not enum values, field types, campus consistency, valid matched student linkage, or qualification invariants. `keyBy` also hides duplicate row IDs. An unknown matched student ID becomes null while the response's qualified status is still persisted.

**Reproduced with a fake HTTP response:** a result claiming a nonexistent student and wrong campus persisted as `final_qualification_status=qualified` with `registrar_student_id=null`.

This is a missing trust-boundary check, not proof that an external attacker can currently intercept the local connection.

Fix direction: validate the complete response before writing any row, require exactly one response per requested ID, enforce allowed status combinations and same-campus official linkage, and reject the whole chunk on contract violations.

### 9. Medium: manual resolution can mark a non-enrolled student qualified

Locations: `app/Http/Requests/UpdateRegistrarMasterlistRecordRequest.php:26`, `app/Http/Controllers/Registrar/MasterlistVerificationController.php:118`, `app/Services/VerifiedMasterlistExportService.php:18`.

When no official student is selected, the server accepts all three final statuses independently. The request can say `not_enrolled`, `no_cor_printed`, and `qualified` simultaneously. The controller preserves that combination, and export selects rows solely by qualified status.

**Reproduced:** the actual request validation rules accepted this contradictory combination. Controller/export persistence behavior was confirmed by code tracing.

Fix direction: derive qualification on the server from final enrollment and COR statuses. Manual review itself is intentional and should remain available with its existing audit reason.

## Additional boundaries and contract mismatches

- The FastAPI endpoint has no authentication, rate limits, string length limits, or cap on `registrar_students`. Loopback binding in the supplied instructions reduces exposure. If deployed on a reachable interface, restrict access and bound requests. It is stateless: calling it directly does not by itself modify Laravel records or expose a stored registrar database.
- Cross-campus detection is tested in Python, but Laravel sends only same-campus official records. Consequently, that integration generally reports an off-campus identity as unmatched rather than inconsistent. This preserves review safety but loses the advertised diagnosis.
- CSV import deliberately retains only name and campus. The advertised student-ID matching path is therefore not exercised by newly uploaded beneficiary CSVs. This matches the name-only design; it should be explained consistently in the API and user documentation.
- Run creation commits before all job dispatches complete. A process interruption or queue publication failure can leave a queued run without all its jobs. Durable chunk dispatch tracking and reconciliation are needed alongside the job fixes.

## Verification and limits

- `php artisan test --compact`: **167 passed, 747 assertions**.
- `python -m pytest -q -p no:cacheprovider`: **9 passed**, with Python deprecation warnings.
- Additional PHP checks used an explicitly verified SQLite `:memory:` database and fake HTTP/queue services. No application database records were changed by these reproductions.
- The Python benchmark called the matcher locally with generated names; it was not a live HTTP load test.
- Existing tests primarily cover happy paths, safe suggestions, scoping, and snapshots. They do not demonstrate crash recovery, concurrent delivery safety, retry lineage, or academic-period isolation.
- No application fixes were made. The existing modification to `database/seeders/CampusSeeder.php` was left untouched.

Suggested implementation order: restore reliable queue consumption and address measured matching cost; make chunk completion and run ownership safe; fix retry lineage; then enforce period-aware official data and status/response invariants. Add regression tests for each reproduced failure while fixing it.
