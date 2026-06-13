# SKSUScholarSync Organized Development Plan

## Summary
Build **SKSUScholarSync**, a Laravel-based scholarship processing, verification, certificate generation, monitoring, and reporting system with a Python microservice for scholar masterlist validation.

Main stack:
- Laravel MVC
- Blade Templates
- Tailwind CSS
- FontAwesome
- MySQL
- FastAPI or Flask microservice
- Gmail SMTP
- PDF, Excel, and CSV reports

## Phase 1: Project Setup and Foundation

### Phase 1.1: Laravel Project Setup
- Install and configure Laravel.
- Configure `.env` file.
- Set app name, timezone, database, mail, and storage settings.
 
### Phase 1.2: Database Setup
- Create MySQL database.
- Configure Laravel database connection.
- Run initial migrations.

### Phase 1.3: Frontend Setup
- Install Tailwind CSS.
- Install FontAwesome.
- Create base layout using Blade.

### Phase 1.4: Authentication Setup
- Install Laravel Breeze or custom authentication.
- Create login and registration flow.
- Add password hashing and session handling.

### Phase 1.5: Role-Based Access Setup
- Define user roles:
  - Student
  - Administrator
  - Scholarship Agency
  - Coordinator
  - Scholarship Chairman
- Add role column to users table.
- Create middleware for role-based access.

### Phase 1.6: Role-Based Dashboards
- Create dashboard routes and views for each role.
- Redirect users after login based on role.

## Phase 2: Student Certificate Request Module

### Phase 2.1: Certificate Request Form
- Allow students to request a Certificate of No Scholarship.
- Collect purpose and required request details.

### Phase 2.2: Official Receipt Upload
- Allow students to upload Official Receipt files.
- Validate file type and size.
- Store file using Laravel Storage.

### Phase 2.3: Request Status Tracking
- Add request statuses:
  - Pending
  - Verified
  - Rejected
  - Approved
- Allow students to view request progress.

### Phase 2.4: Student Certificate Request History
- Show all previous certificate requests.
- Display remarks, status, and certificate download availability.

## Phase 3: Official Receipt Verification and Notifications

### Phase 3.1: Admin OR Verification Page
- Allow administrators to view submitted OR uploads.
- Show student details, purpose, uploaded file, and request status.

### Phase 3.2: OR Approval
- Allow admin to verify valid OR uploads.
- Update request status.
- Record verifying admin and approval date.

### Phase 3.3: OR Rejection
- Allow admin to reject invalid OR uploads.
- Require rejection remarks.
- Notify student of rejection reason.

### Phase 3.4: Gmail SMTP Setup
- Configure Gmail SMTP in Laravel.
- Create reusable notification mail templates.

### Phase 3.5: Request Status Notifications
- Send email notifications for:
  - Approved requests
  - Rejected requests
  - Generated certificates

## Phase 4: Certificate Generation Module

### Phase 4.1: Certificate Template
- Create official Certificate of No Scholarship PDF layout.
- Auto-fill student information.

### Phase 4.2: Certificate Numbering
- Generate unique certificate numbers.
- Store certificate number in database.

### Phase 4.3: PDF Certificate Generation
- Generate PDF after request approval.
- Save generated PDF path in certificates table.

### Phase 4.4: Student Certificate Download
- Allow students to download approved certificates.
- Prevent unauthorized access to other students’ certificates.

### Phase 4.5: Certificate History
- Track generated certificates.
- Allow admin to view certificate records.

## Phase 5: Agency Masterlist Upload Module

### Phase 5.1: Agency Portal
- Create dedicated scholarship agency dashboard.
- Allow agencies to manage uploaded masterlists.

### Phase 5.2: CSV Upload
- Allow agencies to upload scholar masterlists.
- Validate CSV format, required columns, and file type.

### Phase 5.3: Masterlist Preview
- Show uploaded CSV data before final submission.
- Highlight missing or invalid fields.

### Phase 5.4: Masterlist Import
- Store masterlist file.
- Save individual records into masterlist_records table.

### Phase 5.5: Duplicate Checking
- Detect duplicate student ID numbers within the uploaded file.
- Mark duplicate records for review.

## Phase 6: Python Microservice Verification

### Phase 6.1: Microservice Setup
- Create Python FastAPI or Flask project.
- Add `/health-check` endpoint.
- Add `/verify-masterlist` endpoint.

### Phase 6.2: Data Transfer from Laravel
- Laravel sends uploaded masterlist data to Python API.
- Include student ID number, student name, scholarship program, and fund source.

### Phase 6.3: Enrollment Matching
- Python microservice compares masterlist records with enrolled student records.
- Identify:
  - Enrolled scholars
  - Unenrolled scholars
  - Duplicate records
  - Invalid records

### Phase 6.4: Validation Response
- Return validation summary to Laravel:
  - Total records
  - Enrolled count
  - Unenrolled count
  - Duplicate count
  - Invalid count

### Phase 6.5: Save Verification Results
- Laravel stores verification status per masterlist record.
- Mark records as enrolled, unenrolled, duplicate, or invalid.

## Phase 7: Coordinator Validation Workflow

### Phase 7.1: Coordinator Dashboard
- Show pending masterlists for validation.
- Display validation summaries.

### Phase 7.2: Review Enrolled Scholars
- Allow coordinator to review enrolled scholar records.
- Add remarks if needed.

### Phase 7.3: Review Unenrolled Scholars
- Allow coordinator to review unenrolled scholar records.
- Mark records for correction, rejection, or chairman review.

### Phase 7.4: Coordinator Validation Action
- Coordinator validates masterlist records.
- Save remarks and validation status.

### Phase 7.5: Submit to Chairman
- Forward validated masterlists to scholarship chairman.

## Phase 8: Chairman Approval Workflow

### Phase 8.1: Chairman Approval Panel
- Show validated masterlists submitted by coordinators.

### Phase 8.2: Record Review
- Chairman reviews enrolled, unenrolled, duplicate, and invalid records.

### Phase 8.3: Approval Action
- Chairman approves valid records.
- Save approval date and approving user.

### Phase 8.4: Rejection Action
- Chairman rejects invalid records.
- Require rejection remarks.

### Phase 8.5: Release Final Scholar Records
- Release approved scholar records to scholarship agencies.
- Allow agencies to view validated results.

## Phase 9: Continuing Scholarship Evaluation Module

### Phase 9.1: Student Requirement Upload
- Allow students to upload scholarship renewal requirements.
- Validate required documents.

### Phase 9.2: Application Status Tracking
- Add statuses:
  - Submitted
  - Under Evaluation
  - Approved
  - Rejected
  - Need Revision

### Phase 9.3: Admin or Coordinator Evaluation
- Allow authorized users to review submitted requirements.
- Add remarks and evaluation result.

### Phase 9.4: Revision Handling
- Allow students to resubmit requirements if marked Need Revision.

### Phase 9.5: Evaluation Result Notification
- Notify students of approval, rejection, or revision request.

## Phase 10: Central Monitoring Module

### Phase 10.1: Admin Monitoring Dashboard
- Display summary cards:
  - Total Scholars
  - Pending Certificate Requests
  - Verified ORs
  - Uploaded Masterlists
  - Pending Evaluations
  - Approved Records

### Phase 10.2: Student Profile Management
- Allow admin to manage student records.
- View student scholarship history and certificate requests.

### Phase 10.3: Scholar Records Monitoring
- Monitor scholar information, fund source, status, and agency.

### Phase 10.4: Transaction Monitoring
- Track certificate requests, OR verification, masterlist uploads, and evaluations.

### Phase 10.5: Fund Source Monitoring
- Manage scholarship programs and funding sources.

## Phase 11: Reports Module

### Phase 11.1: Scholar Information Reports
- Generate scholar profile and scholarship status reports.

### Phase 11.2: Certificate Request Reports
- Generate reports for certificate requests by date, status, and student.

### Phase 11.3: Official Receipt Verification Reports
- Generate reports for verified and rejected OR uploads.

### Phase 11.4: Scholarship Masterlist Reports
- Generate uploaded, validated, approved, and rejected masterlist reports.

### Phase 11.5: Continuing Scholarship Evaluation Reports
- Generate reports for renewal applications and evaluation results.

### Phase 11.6: Student Requirement Submission Reports
- Generate reports for uploaded requirements and missing documents.

### Phase 11.7: Scholarship Fund Source Reports
- Generate reports grouped by agency, fund source, or scholarship program.

### Phase 11.8: Approved and Rejected Request Reports
- Generate reports for all approved and rejected transactions.

### Phase 11.9: Export Formats
- Support export to:
  - PDF
  - Excel
  - CSV

## Phase 12: UI and Design Implementation

### Phase 12.1: Layout Design
- Create shared layout with sidebar, navbar, and main content area.

### Phase 12.2: Role-Specific Navigation
- Show menu items based on user role.

### Phase 12.3: Dashboard Components
- Create dashboard cards, tables, charts, and status badges.

### Phase 12.4: Form Design
- Create clean upload forms, request forms, and evaluation forms.

### Phase 12.5: Modal and Alert Design
- Add confirmation modals.
- Add success, warning, and error alerts.

### Phase 12.6: Visual Style
- Use academic/government style.
- Suggested colors:
  - Emerald Green
  - Dark Blue
  - Light Gray
  - Gold/Yellow accent

## Phase 13: Security and Access Control

### Phase 13.1: Route Protection
- Protect all routes using authentication middleware.

### Phase 13.2: Role Permission Checks
- Prevent users from accessing pages outside their role.

### Phase 13.3: File Access Security
- Restrict uploaded ORs, requirements, and certificates to authorized users.

### Phase 13.4: Input Validation
- Validate forms, uploads, CSV data, and status changes.

### Phase 13.5: Audit Trail
- Track important actions:
  - OR verification
  - Certificate generation
  - Masterlist validation
  - Chairman approval
  - Report generation

## Phase 14: Testing and Quality Assurance

### Phase 14.1: Authentication Testing
- Test login, logout, registration, and role redirects.

### Phase 14.2: Certificate Request Testing
- Test request submission, OR upload, approval, rejection, and download.

### Phase 14.3: Masterlist Upload Testing
- Test valid CSV, invalid CSV, duplicate records, and missing fields.

### Phase 14.4: Microservice Testing
- Test API health check.
- Test masterlist verification response.

### Phase 14.5: Workflow Testing
- Test coordinator validation and chairman approval flow.

### Phase 14.6: Report Testing
- Test report filters and PDF, Excel, CSV exports.

### Phase 14.7: Security Testing
- Test unauthorized access prevention.
- Test file access restrictions.

## Phase 15: System Evaluation

### Phase 15.1: Functionality Evaluation
- Check whether all required modules work correctly.

### Phase 15.2: Usability Evaluation
- Check if users can complete tasks easily.

### Phase 15.3: Reliability Evaluation
- Check system stability during normal use.

### Phase 15.4: Performance Efficiency Evaluation
- Check page speed, CSV processing speed, and report generation time.

### Phase 15.5: Security Evaluation
- Check authentication, authorization, file security, and data validation.

### Phase 15.6: Maintainability Evaluation
- Check code organization, naming, reusable components, and documentation.

### Phase 15.7: Accessibility Evaluation
- Check readable text, contrast, labels, and keyboard-friendly navigation.

## Key Database Tables

- `users`
- `students`
- `certificate_requests`
- `certificates`
- `agencies`
- `scholarship_masterlists`
- `masterlist_records`
- `scholarship_applications`
- `scholarship_requirements`
- `notifications`
- `reports`

## Key Public Interfaces

- Student dashboard
- Admin dashboard
- Agency portal
- Coordinator dashboard
- Chairman approval panel
- Certificate request form
- OR verification page
- Masterlist upload page
- Validation results page
- Reports page

## Microservice API

### `GET /health-check`
Checks whether the Python microservice is running.

### `POST /verify-masterlist`
Validates uploaded scholar masterlist records.

Expected response summary:
```json
{
  "total_records": 100,
  "enrolled": 85,
  "unenrolled": 10,
  "duplicates": 5,
  "invalid": 0
}
```

## Assumptions

- Laravel will be the main system.
- MySQL will be the main database.
- FastAPI is recommended for the Python microservice.
- Gmail SMTP will be used for email notifications.
- PDF, Excel, and CSV exports are required.
- The system will use role-based dashboards and access control.
- The project title is: **SKSUScholarSync: An Integrated Scholarship Processing, Verification, and Monitoring System with Microservice-Based Scholar Validation**.
