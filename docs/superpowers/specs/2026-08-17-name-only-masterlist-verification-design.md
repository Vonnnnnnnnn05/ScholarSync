# Name-Only Masterlist Verification Design

## Goal

Simplify scholarship masterlist verification so the Scholarship Chairman uploads scholar names and the system reports whether each named student is enrolled, enrolled without a printed COR, or unenrolled. Scholarship program, fund source, eligibility, duplicate, and invalid classifications are outside this workflow.

## Input and source data

The uploaded masterlist requires one column: `student_name`. Each nonblank row becomes one masterlist record.

Laravel sends the verifier:

- Uploaded records containing `row_id` and `student_name`.
- Registrar records containing the database ID, student name, campus ID, enrollment status, and `cor_printed` flag.

Registrar enrollment records gain a required boolean `cor_printed` field defaulting to false. Existing records therefore safely appear as having no printed COR until the Registrar updates or reimports them.

## Matching and statuses

The verifier compares names case-insensitively after trimming whitespace, collapsing repeated spaces, and removing punctuation. It also compares normalized name tokens in sorted order so common name-order differences can match.

Each uploaded name receives exactly one status:

- `enrolled`: exactly one matching Registrar record is enrolled and has `cor_printed = true`.
- `no_cor_printed`: exactly one matching Registrar record is enrolled and has `cor_printed = false`.
- `unenrolled`: no enrolled Registrar record matches.

If multiple enrolled Registrar records have the same normalized name, the verifier does not guess. It returns `unenrolled` with a remark requiring manual checking. A blank uploaded name also returns `unenrolled` with an explanatory remark so every input row has one of the three agreed outcomes.

The response summary contains only `total_records`, `enrolled_count`, `no_cor_printed_count`, and `unenrolled_count`. Each matched result also returns `matched_student_id` and `campus_id` so Laravel can route it to the appropriate campus.

## Laravel integration

Laravel's CSV preview and import accept only `student_name`. The verification service sends the reduced payload, saves the returned status, match, campus, and remarks, and updates the three summary counts. Existing database columns unrelated to this flow may remain temporarily for compatibility, but the name-only workflow will not require or populate them.

Registrar enrollment CSV import and its maintenance form accept a `cor_printed` value. Accepted affirmative values are `1`, `true`, `yes`, and `printed`; other supplied values are treated as false.

Chairman and Coordinator masterlist screens show only the scholar name, campus where known, the three verification states, and remarks. Existing coordinator and chairman approval decisions remain available after automatic verification.

## Failure handling

Malformed CSV files without `student_name` are rejected during preview. If the verifier is unavailable or returns an unsuccessful response, the upload remains stored but unverified, following the existing failure behavior. Ambiguous names remain visible for manual review rather than being silently assigned to a campus.

## Testing

- Microservice tests cover normalization, name-order matching, the three statuses, blank names, and ambiguous matches.
- Laravel feature tests cover name-only CSV preview/import, payload mapping, persisted statuses and counts, campus assignment, and Registrar COR input.
- Existing approval-flow tests are updated to use the three simplified verification statuses.
