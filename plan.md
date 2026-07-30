# SKSUScholarSync Enhanced Development Plan

## Project Title
**SKSUScholarSync: An Integrated Scholarship Processing, Verification, and Monitoring System**

## Purpose
This plan updates the previous development roadmap based on the revised study objectives. The system will support certificate requests, scholarship masterlist submission, microservice-based enrollment validation, continuing scholarship evaluation, central monitoring, reporting, and formal system evaluation.

## Main Technology Stack
- Laravel MVC for the main web system
- Blade templates, Tailwind CSS, Alpine.js, and FontAwesome for the interface
- MySQL for the main database
- Laravel Storage for uploaded ORs, requirements, masterlists, and certificates
- Gmail SMTP for email notifications
- PDF, Excel, and CSV exports for reports
- Python FastAPI microservice for masterlist and enrollment verification

## Scope Classification

### Keep From the Previous Plan
These phases still match the new objectives and should remain:
- Project setup and foundation
- Authentication and role-based access
- Student Certificate of No Scholarship request flow
- Admin OR verification and notification flow
- Certificate generation and download flow
- Agency masterlist upload flow
- Python microservice verification
- Coordinator validation workflow
- Chairman approval workflow
- Continuing scholarship evaluation
- Central monitoring
- Reports module
- UI and design implementation
- Security and access control
- Testing and system evaluation

### Add to the Plan
The revised objectives introduce these new requirements:
- Registrar role or registrar data source for enrollment checking
- Agency scholarship policy, rules, guidelines, and qualification requirement uploads
- Student-facing scholarship details and eligibility viewing
- Automated qualified and unqualified applicant separation
- Enrollment Verification Reports
- Agency Submission Reports
- Admin user account management by role
- More explicit evaluation metrics: functionality, usability, reliability, and performance efficiency

### Revise or Rename
These items should be adjusted for clearer alignment:
- Rename **Python Microservice Verification** to **Registrar and Microservice Enrollment Verification**.
- Expand **Agency Masterlist Upload Module** into **Agency Scholarship Portal and Masterlist Submission**.
- Expand **Reports Module** to include enrollment verification and agency submission reports.
- Expand **Central Monitoring** to include user account and system access management.
- Treat Gmail notifications as a reusable notification service, not only a certificate feature.

### Remove or Deprioritize
Nothing major should be removed from the previous plan. However:
- Do not keep a separate navbar-centered layout if the system standard is sidebar-based dashboards.
- Do not treat certificate generation as a manual-only action; approved requests should support automatic generation.
- Do not limit reports to the old nine report types; the revised objectives now require ten report flows.

## Updated Development Phases

## Phase 1: Project Setup and Foundation

### Phase 1.1: Laravel Project Setup
- Install and configure Laravel.
- Configure `.env` file.
- Set app name, timezone, database, mail, filesystem, and storage settings.

### Phase 1.2: Database Setup
- Create and configure the MySQL database.
- Run initial migrations.
- Prepare seeders for core roles and sample accounts.

### Phase 1.3: Frontend Setup
- Install and configure Tailwind CSS.
- Install FontAwesome or an icon set.
- Create the shared Blade layout.
- Standardize sidebar-based dashboards.

### Phase 1.4: Authentication Setup
- Implement login and registration.
- Add password hashing and session handling.
- Configure authenticated redirects.

### Phase 1.5: Role-Based Access Setup
- Define system roles:
  - Student
  - Administrator
  - Scholarship Agency
  - Scholarship Coordinator
  - Scholarship Chairman
  - Registrar
- Add role support to users.
- Create middleware and permission checks for role-based access.

### Phase 1.6: Role-Based Dashboards
- Create dashboards for each role.
- Redirect users after login based on role.
- Show role-specific sidebar navigation.

## Phase 2: Student Certificate Request Flow

### Phase 2.1: Certificate Request Form
- Allow students to request a Certificate of No Scholarship online.
- Collect purpose and required request details.

### Phase 2.2: Official Receipt and Requirement Upload
- Allow students to upload ORs and supporting documents.
- Validate file type, file size, and required fields.
- Store files securely using Laravel Storage.

### Phase 2.3: Request Status Tracking
- Add statuses:
  - Pending
  - Verified
  - Rejected
  - Approved
  - Certificate Generated
- Allow students to track request progress in real time.

### Phase 2.4: Student Certificate Request History
- Show all previous certificate requests.
- Display remarks, status, OR details, and certificate download availability.

### Phase 2.5: Student Notifications
- Notify students when requests are approved, rejected, or completed.

## Phase 3: Administrator Verification and Notification Flow

### Phase 3.1: OR and Requirement Review Page
- Allow administrators to view uploaded ORs and supporting documents.
- Show student details, purpose, uploaded files, remarks, and status.

### Phase 3.2: Requirement Verification
- Allow administrators to verify authenticity and completeness.
- Record verifying admin and verification date.

### Phase 3.3: Approval and Rejection
- Allow administrators to approve valid requests.
- Allow administrators to reject invalid or incomplete submissions.
- Require rejection remarks.

### Phase 3.4: Gmail SMTP Notification Setup
- Configure Gmail SMTP.
- Create reusable notification mail templates.

### Phase 3.5: Automated Status Notifications
- Send email notifications for approved requests.
- Send email notifications for rejected requests.
- Send email notifications for generated certificates.

## Phase 4: Certificate Output Flow

### Phase 4.1: Certificate Template
- Create the Certificate of No Scholarship PDF layout.
- Auto-fill student information, purpose, date, and signatory details.

### Phase 4.2: Certificate Numbering
- Generate unique certificate numbers.
- Store certificate numbers in the database.

### Phase 4.3: Automatic PDF Generation
- Generate a PDF certificate after approval.
- Save generated PDF path in the certificates table.

### Phase 4.4: Student Download and Printing
- Allow students to download approved certificates.
- Allow printing access through the generated PDF.
- Prevent unauthorized access to other students' certificates.

### Phase 4.5: Certificate History and Audit
- Track generated certificates.
- Maintain request history for monitoring and auditing.
- Allow administrators to view certificate records.

## Phase 5: Agency Scholarship Portal and Masterlist Submission

### Phase 5.1: Agency Portal
- Create a dedicated scholarship agency dashboard.
- Allow agencies to manage uploaded masterlists.
- Allow agencies to view released validation results.

### Phase 5.2: Scholarship Program and Fund Source Management
- Allow agencies to submit scholarship program details.
- Record fund source, program name, slots, academic year, and status.

### Phase 5.3: Policy, Rules, and Eligibility Upload
- Allow agencies to upload scholarship rules, policies, guidelines, and qualification requirements.
- Store files securely.
- Make approved scholarship details visible to students.

### Phase 5.4: CSV Masterlist Upload
- Allow agencies to upload scholar masterlists through CSV integration.
- Validate file type, required columns, and data format.

### Phase 5.5: Masterlist Preview
- Show uploaded CSV data before final submission.
- Highlight missing, duplicate, and invalid fields.

### Phase 5.6: Masterlist Import
- Store the original masterlist file.
- Save individual records into the masterlist records table.

### Phase 5.7: Duplicate Checking
- Detect duplicate student ID numbers within the uploaded file.
- Mark duplicate records for review.

## Phase 6: Registrar and Microservice Enrollment Verification

### Phase 6.1: Registrar Data Source
- Provide a registrar role or managed enrolled-student data source.
- Store or import enrolled student records for verification.

### Phase 6.2: Python Microservice Setup
- Create a Python FastAPI microservice.
- Add `/health-check` endpoint.
- Add `/verify-masterlist` endpoint.

### Phase 6.3: Data Transfer From Laravel
- Send uploaded masterlist data to the Python API.
- Include student ID number, student name, scholarship program, and fund source.

### Phase 6.4: Enrollment Matching
- Compare masterlist records with enrolled student records from the registrar data source.
- Identify enrolled scholars.
- Identify unenrolled scholars.
- Identify duplicate records.
- Identify invalid records.

### Phase 6.5: Qualified and Unqualified Separation
- Automatically separate qualified and unqualified applicants based on enrollment and validation rules.
- Generate validated lists for coordinator review.

### Phase 6.6: Save Verification Results
- Store verification status per masterlist record.
- Mark records as enrolled, unenrolled, duplicate, invalid, qualified, or unqualified.

## Phase 7: Coordinator Validation Workflow

### Phase 7.1: Coordinator Dashboard
- Show pending masterlists for validation.
- Display validation summaries.

### Phase 7.2: Review Enrolled Scholars
- Allow the coordinator to review enrolled scholar records.
- Add remarks if needed.

### Phase 7.3: Review Unenrolled and Invalid Scholars
- Allow the coordinator to review unenrolled, duplicate, and invalid records.
- Mark records for correction, rejection, or chairman review.

### Phase 7.4: Coordinator Validation Action
- Save coordinator remarks and validation status.
- Confirm qualified and unqualified records.

### Phase 7.5: Submit to Chairman
- Forward validated masterlists to the scholarship chairman.

## Phase 8: Chairman Approval Flow

### Phase 8.1: Chairman Approval Panel
- Show validated masterlists submitted by coordinators.

### Phase 8.2: Record Review
- Allow the chairman to review enrolled, unenrolled, duplicate, invalid, qualified, and unqualified records.

### Phase 8.3: Approval Action
- Allow the chairman to approve valid records.
- Save approval date and approving user.

### Phase 8.4: Rejection Action
- Allow the chairman to reject invalid records.
- Require rejection remarks.

### Phase 8.5: Release Final Scholar Records
- Release approved scholar records to scholarship agencies.
- Finalize scholarship approval decisions.

## Phase 9: Continuing Scholarship Evaluation Flow

### Phase 9.1: Student Requirement Upload
- Allow students to upload continuing scholarship renewal requirements.
- Validate required documents.

### Phase 9.2: Deadline-Based Submission
- Allow students to submit renewal requirements before deadlines.
- Track late, missing, and complete submissions.

### Phase 9.3: Application Status Tracking
- Add statuses:
  - Submitted
  - Under Evaluation
  - Approved
  - Rejected
  - Need Revision

### Phase 9.4: Admin or Coordinator Evaluation
- Allow authorized users to review submitted requirements.
- Add remarks and evaluation result.

### Phase 9.5: Revision Handling
- Allow students to resubmit requirements if marked Need Revision.

### Phase 9.6: Evaluation Result Notification
- Notify students of approval, rejection, or revision requests.

## Phase 10: Evaluation Module

### Phase 10.1: Applicant Monitoring
- Monitor continuing scholarship applicants.
- Track submitted, missing, pending, and completed requirements.

### Phase 10.2: Eligibility Assessment
- Assess student eligibility for continuing scholarships.
- Check academic and documentary requirements.

### Phase 10.3: Renewal Decision
- Determine whether students qualify for scholarship renewal.
- Store evaluation result, remarks, evaluator, and evaluation date.

## Phase 11: Central Monitoring and Administration

### Phase 11.1: Admin Monitoring Dashboard
- Display summary cards for:
  - Total Scholars
  - Pending Certificate Requests
  - Verified ORs
  - Uploaded Masterlists
  - Pending Evaluations
  - Approved Records
  - Active Agencies
  - Released Scholar Records

### Phase 11.2: Student Profile Management
- Allow administrators to manage student records.
- View student scholarship history and certificate requests.

### Phase 11.3: User Account Management
- Allow administrators to create and manage user accounts.
- Assign roles to students, administrators, agencies, coordinators, chairmen, and registrars.

### Phase 11.4: Scholar Records Monitoring
- Monitor scholar information, fund source, status, agency, and validation result.

### Phase 11.5: Transaction Monitoring
- Track certificate requests, OR verification, masterlist uploads, validations, evaluations, and approvals.

### Phase 11.6: Fund Source Monitoring
- Manage scholarship programs, agencies, and funding sources.

### Phase 11.7: Audit Trail
- Track important actions:
  - OR verification
  - Certificate generation
  - Masterlist upload
  - Microservice verification
  - Coordinator validation
  - Chairman approval
  - Evaluation decisions
  - Report generation
  - User account creation

## Phase 12: Reporting Flow

### Phase 12.1: Scholar Information Reports
- Generate scholar profile and scholarship status reports.

### Phase 12.2: Certificate Request Reports
- Generate reports for certificate requests by date, status, and student.

### Phase 12.3: Official Receipt Verification Reports
- Generate reports for verified and rejected OR uploads.

### Phase 12.4: Scholarship Masterlist Reports
- Generate uploaded, validated, approved, and rejected masterlist reports.

### Phase 12.5: Continuing Scholarship Evaluation Reports
- Generate reports for renewal applications and evaluation results.

### Phase 12.6: Student Requirement Submission Reports
- Generate reports for uploaded, missing, revised, and completed requirements.

### Phase 12.7: Scholarship Fund Source Reports
- Generate reports grouped by agency, fund source, or scholarship program.

### Phase 12.8: Approved and Rejected Request Reports
- Generate reports for all approved and rejected transactions.

### Phase 12.9: Enrollment Verification Reports
- Generate reports for enrolled, unenrolled, duplicate, invalid, qualified, and unqualified records.

### Phase 12.10: Agency Submission Reports
- Generate reports for agency masterlist submissions, policy uploads, and released validation results.

### Phase 12.11: Export Formats
- Support export to:
  - PDF
  - Excel
  - CSV

## Phase 13: Student Scholarship Discovery

### Phase 13.1: Scholarship Details Page
- Allow students to view available scholarship programs.
- Show agency, fund source, eligibility rules, requirements, deadlines, and status.

### Phase 13.2: Agency Policies and Guidelines
- Allow students to view downloadable agency policies, guidelines, and qualification requirements.

### Phase 13.3: Eligibility Awareness
- Present scholarship qualification details clearly so students can understand whether they may apply or renew.

## Phase 14: UI and Design Implementation

### Phase 14.1: Layout Design
- Use a shared sidebar-based layout with main content area.
- Remove unnecessary navbar-heavy layouts from role dashboards.

### Phase 14.2: Role-Specific Navigation
- Show menu items based on user role.
- Group related sidebar links such as Monitoring and Reports into expandable folders.

### Phase 14.3: Dashboard Components
- Create dashboard cards, tables, charts, filters, and status badges.

### Phase 14.4: Form Design
- Create clean upload forms, request forms, user management forms, and evaluation forms.

### Phase 14.5: Modal and Alert Design
- Add confirmation modals.
- Add success, warning, error, and validation alerts.

### Phase 14.6: Visual Style
- Use an academic and government-style interface.
- Suggested colors:
  - Emerald Green
  - Dark Blue
  - Light Gray
  - Gold or Yellow accent

## Phase 15: Security and Access Control

### Phase 15.1: Route Protection
- Protect all private routes using authentication middleware.

### Phase 15.2: Role Permission Checks
- Prevent users from accessing pages outside their role.

### Phase 15.3: File Access Security
- Restrict uploaded ORs, requirements, masterlists, policies, and certificates to authorized users.

### Phase 15.4: Input Validation
- Validate forms, uploads, CSV data, status changes, and report filters.

### Phase 15.5: Audit Trail Security
- Record important actions with actor, timestamp, affected record, and metadata.

### Phase 15.6: Data Privacy
- Protect student information and scholarship records.
- Limit access to sensitive documents based on role.

## Phase 16: Testing and Quality Assurance

### Phase 16.1: Authentication and Role Testing
- Test login, logout, registration, user creation, and role redirects.

### Phase 16.2: Certificate Request Testing
- Test request submission, OR upload, approval, rejection, notification, generation, and download.

### Phase 16.3: Agency Portal Testing
- Test scholarship program setup, policy upload, CSV upload, preview, import, and duplicate detection.

### Phase 16.4: Microservice Testing
- Test API health check.
- Test masterlist verification response.
- Test enrolled, unenrolled, duplicate, invalid, qualified, and unqualified classifications.

### Phase 16.5: Workflow Testing
- Test coordinator validation and chairman approval flow.

### Phase 16.6: Continuing Evaluation Testing
- Test requirement upload, deadline handling, review, revision, approval, rejection, and notification.

### Phase 16.7: Report Testing
- Test filters and exports for PDF, Excel, and CSV.

### Phase 16.8: Security Testing
- Test unauthorized access prevention.
- Test file access restrictions.
- Test role permission boundaries.

## Phase 17: System Evaluation

### Phase 17.1: Functionality Evaluation
- Check whether all required modules satisfy the study objectives.
- Verify that core workflows produce correct outputs.

### Phase 17.2: Usability Evaluation
- Evaluate whether students, administrators, agencies, coordinators, chairmen, and registrars can complete their tasks easily.
- Review layout clarity, form simplicity, and navigation consistency.

### Phase 17.3: Reliability Evaluation
- Check system stability during repeated use.
- Verify error handling for failed uploads, invalid CSV files, unavailable microservice responses, and failed email delivery.

### Phase 17.4: Performance Efficiency Evaluation
- Check page speed.
- Check CSV processing speed.
- Check report generation time.
- Check microservice response time.

## Key Database Tables
- `users`
- `students`
- `agencies`
- `registrar_students`
- `certificate_requests`
- `certificates`
- `scholarship_programs`
- `scholarship_policies`
- `scholarship_masterlists`
- `masterlist_records`
- `verification_results`
- `continuing_scholarship_applications`
- `student_requirements`
- `notifications`
- `audit_logs`
- `reports`

## Key Public and Role-Based Interfaces
- Welcome page with live monitoring summary
- Student dashboard
- Student certificate request page
- Student scholarship discovery page
- Student renewal requirement page
- Admin dashboard
- Admin user management page
- Admin monitoring page
- Admin OR verification page
- Admin reports page
- Agency portal
- Agency masterlist upload page
- Agency policy and requirements upload page
- Coordinator validation dashboard
- Chairman approval panel
- Registrar enrolled-student management page
- Certificate download page
- Validation results page

## Microservice API

### `GET /health-check`
Checks whether the Python microservice is running.

### `POST /verify-masterlist`
Validates uploaded scholar masterlist records against registrar enrollment data.

Expected request fields:
```json
{
  "records": [
    {
      "student_id_number": "2026-0001",
      "student_name": "Sample Student",
      "scholarship_program": "Sample Scholarship",
      "fund_source": "Sample Fund Source"
    }
  ]
}
```

Expected response summary:
```json
{
  "total_records": 100,
  "enrolled_count": 85,
  "unenrolled_count": 10,
  "duplicate_count": 3,
  "invalid_count": 2,
  "qualified_count": 85,
  "unqualified_count": 15
}
```

## Updated Role Responsibilities

### Student
- Request Certificate of No Scholarship.
- Upload ORs and supporting requirements.
- Track certificate request status.
- Download generated certificates.
- View scholarship details, eligibility requirements, and agency policies.
- Upload continuing scholarship renewal requirements.
- Track renewal evaluation results.

### Administrator
- Verify ORs and supporting documents.
- Approve or reject certificate requests.
- Manage student profiles and scholar records.
- Manage user accounts and system access.
- Monitor system transactions.
- Generate reports.
- Maintain audit records.

### Scholarship Agency
- Manage scholarship program information.
- Upload scholar masterlists.
- Upload scholarship rules, policies, guidelines, and qualification requirements.
- View validation results released by the chairman.

### Scholarship Coordinator
- Review submitted masterlists.
- Review enrolled, unenrolled, duplicate, invalid, qualified, and unqualified records.
- Add validation remarks.
- Submit validated records to the chairman.
- Evaluate continuing scholarship requirements when authorized.

### Registrar
- Provide or manage enrolled-student records.
- Support enrollment cross-checking.
- Serve as the trusted source for determining official enrollment status.

### Scholarship Chairman
- Review coordinator-validated masterlists.
- Approve or reject validation results.
- Release final scholar records to agencies.
- Finalize scholarship approval decisions.

### Python Microservice
- Receive masterlist records from Laravel.
- Compare records against registrar enrollment data.
- Identify enrolled, unenrolled, duplicate, and invalid records.
- Separate qualified and unqualified applicants.
- Return validation summaries to Laravel.

## Priority Implementation Notes
1. Add the Registrar role because the revised objectives explicitly include registrar-based enrollment checking.
2. Add agency policy and eligibility uploads because agencies must provide rules, policies, guidelines, and qualification requirements.
3. Add student scholarship discovery so students can view scholarship details and eligibility requirements.
4. Expand reports to include Enrollment Verification Reports and Agency Submission Reports.
5. Keep the existing implemented modules, but revise labels and pages to match the new objective wording.
6. Continue using audit trails for all important approval, rejection, upload, verification, and report actions.

## Assumptions
- Laravel remains the main system.
- MySQL remains the main database.
- FastAPI is recommended for the Python microservice.
- Gmail SMTP will be used for email notifications.
- PDF, Excel, and CSV exports are required.
- The system will use role-based dashboards and sidebar navigation.
- Registrar enrollment records may be imported or manually managed unless direct registrar system integration is later provided.
