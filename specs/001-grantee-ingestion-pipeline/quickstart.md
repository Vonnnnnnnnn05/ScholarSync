# Quickstart Validation Guide

## Prerequisites

1. XAMPP running (Apache + PHP 8.2)
2. Python 3.11+ virtual environment activated in `microservices/masterlist-verifier/`
3. Java Runtime (for tabula-py PDF extraction)
4. Database migrated: `php artisan migrate`
5. Queue worker running: `php artisan queue:work --queue=grantee-verification,default`
6. Microservice running: `cd microservices/masterlist-verifier && uvicorn main:app --host 127.0.0.1 --port 8001`

## Scenario 1: CSV Upload & Green Verification

1. Log in as Scholarship Chairman
2. Navigate to `/chairman/grantees/upload`
3. Upload `Sample_Files/sample_grantees.csv` (should contain columns: student_name, student_id, campus)
4. Verify the preview table shows normalized headers and all rows
5. Fill in agency name ("CHED"), scholarship name ("Tulong Dunong"), academic year, semester
6. Confirm upload → redirected to reconciliation dashboard
7. Click "Verify Batch" → verification runs against SIS
8. **Expected**: Rows with valid student IDs → GREEN; rows without → AMBER or RED
9. Resolve any AMBER rows via student lookup dropdown
10. Click "Commit Batch" → verify `students.is_scholarship_grantee` is updated

## Scenario 2: Module A Grantee Block

1. After committing a batch, log in as a Student whose record was committed
2. Navigate to `/student/certificate-requests/create`
3. **Expected**: System displays a message that the student is a current scholarship grantee and cannot request a Certificate of Non-Grantee

## Scenario 3: PDF Upload

1. Log in as Scholarship Chairman
2. Navigate to `/chairman/grantees/upload`
3. Upload a sample PDF with a tabular grantee roster
4. **Expected**: The microservice extracts the table and returns normalized JSON
5. Preview table renders with correct data

## Scenario 4: Microservice Down

1. Stop the FastAPI microservice (`Ctrl+C` in terminal)
2. Upload a CSV and confirm staging
3. Click "Verify Batch"
4. **Expected**: Job retries and fails; Chairman sees "Verification failed. Retry?" message
5. Restart microservice → Click retry → Verification succeeds

## Scenario 5: Multi-Category PDF Upload & Campus Resolution from Access.xlsx

1. Ensure campus enrollment records exist by importing `Access.xlsx` as Campus Registrar (Column A = `ACCESS`, Column M = `enrollment_status`).
2. Log in as Scholarship Chairman and navigate to `/chairman/grantees/upload`.
3. Upload `LIST OF STUDENTS WHO AVAILED SCHOLARSHIP.pdf` (8-page PDF with 2 columns, zero campus columns, containing `TES-UniFAST`, `TDP`, and `CMSP` banners).
4. **Expected**: The coordinate-aware parser extracts left and right columns independently without horizontal name collisions; section banners assign the 3 programs across pages.
5. In the upload preview, notice `campus` is initially empty/unassigned.
6. Click "Confirm & Stage Grantees" then "Verify Batch".
7. **Expected**:
   - Students matching `Access.xlsx` automatically inherit `campus = "ACCESS Campus"`.
   - Students matching other campus records inherit their respective campus (e.g. `Isulan Campus`).
   - Clean enrolled matches turn `GREEN`.
   - Fuzzy typo matches (e.g. `BALASAG` vs `BALANSAG`), multiple candidates, or `NO_COR_PRINTED` turn `AMBER`.
8. Check the Reconciliation Dashboard:
   - Filter by tabs: `[TES-UniFAST]`, `[TDP]`, `[CMSP]`.
   - Verify the "Resolved Campus" column displays `ACCESS Campus` (or sister campus).
   - Resolve any Amber records via disambiguation dropdown.
9. Click "Commit Batch" → verify `students.is_scholarship_grantee = true` and `Module A` certificate of non-grantee requests are locked for all verified awardees.

## Validation Checklist

- [ ] Multi-category PDF banner state machine detects `TES-UniFAST`, `TDP`, and `CMSP`
- [ ] 2-column coordinate extraction isolates left ($x_0 \sim 90$) and right ($x_0 \sim 330$) without name collisions
- [ ] Cover page document title (`LIST OF STUDENTS...`) is cleanly excluded from student rows
- [ ] Double-comma typos (e.g. `ORCAJADA, HEARTY, MARIE`) and missing comma spaces are sanitized
- [ ] Campus Registrar import maps Column A as `campus` and Column M as `enrollment_status`
- [ ] Missing Column M applies 90% Enrolled / 8% No COR / 2% Unenrolled weighted distribution
- [ ] Microservice 3-tier matcher resolves destination Campus from `Access.xlsx`
- [ ] Cross-campus students inherit their enrolled campus (e.g. Isulan, Tacurong)
- [ ] Tabbed dashboard displays Category Tabs (`[TES]`, `[TDP]`, `[CMSP]`) and Resolved Campus column
- [ ] commitBatch blocks while unresolved Amber records remain
- [ ] commitBatch sets `is_scholarship_grantee = true` and locks Module A non-grantee certificates
