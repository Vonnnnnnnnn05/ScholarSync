# Final System Objectives

## Specific Objectives

The study aims to design and develop **SKSUScholarSync: An Integrated Scholarship Processing, Verification, and Monitoring System** that will:

### 1. Allow Students to (Certificate Request Flow)

1.1 Request a Certificate of No Scholarship online.  
1.2 Upload an Official Receipt (OR) electronically.  
1.3 Track the status of certificate requests in real time.  
1.4 Receive notifications regarding the approval or rejection of certificate requests.

### 2. Allow Administrators to (Certificate Verification Flow)

2.1 Review and verify uploaded Official Receipts (OR).  
2.2 Approve or reject certificate requests based on OR verification.  
2.3 Send automated email notifications regarding certificate request status.

### 3. Allow the System to (Certificate Generation Flow)

3.1 Automatically generate Certificates of No Scholarship for approved requests.  
3.2 Store generated certificates and their corresponding records.  
3.3 Provide students with access to download and print approved certificates.  
3.4 Maintain certificate request and issuance history for monitoring and auditing purposes.

### 4. Allow Administrators to (Scholarship Posting and Management Flow)

4.1 Manage scholarship agencies and their corresponding scholarship programs.  
4.2 Create and publish available scholarship opportunities.  
4.3 Manage scholarship descriptions, qualifications, requirements, rules, policies, guidelines, and application deadlines.  
4.4 Provide official application links for available scholarships.  
4.5 Update, activate, deactivate, or archive scholarship opportunities.  
4.6 Make published scholarship opportunities accessible to students.

### 5. Allow Students to (Scholarship Application Information Flow)

5.1 View available scholarship opportunities posted in the system.  
5.2 View scholarship descriptions, qualifications, requirements, rules, policies, guidelines, and application deadlines.  
5.3 Access the official application link provided for each scholarship opportunity.  
5.4 View information regarding the scholarship agency offering the scholarship.

### 6. Allow the Scholarship Chairman to (Masterlist and Approval Flow)

6.1 Upload name-only scholarship masterlists received from scholarship agencies through CSV integration.
6.2 Associate uploaded masterlists with their corresponding scholarship agencies; scholarship program and fund-source fields are not required for name validation.
6.3 Distribute scholar records to their respective campuses for validation.  
6.4 Monitor the validation progress of scholarship masterlists across the seven campuses.  
6.5 Review validation results submitted by Campus Scholarship Coordinators.  
6.6 Approve or reject scholarship validation results.  
6.7 Finalize validated scholar records for scholarship processing and monitoring.

### 7. Allow Campus Scholarship Coordinators to (Campus Validation Flow)

7.1 Access scholarship records assigned to their respective campuses.  
7.2 Review assigned scholar records before validation.  
7.3 Initiate scholar record validation through the microservice.  
7.4 Review validation results and identify records requiring further verification.  
7.5 Coordinate with the Campus Registrar regarding records with discrepancies.  
7.6 Submit completed campus validation results to the Scholarship Chairman.

### 8. Allow Campus Registrars to (Enrollment Verification Flow)

8.1 Access scholar records requiring verification within their respective campuses.  
8.2 Use the microservice to verify student enrollment information.  
8.3 Review records containing missing, unmatched, or inconsistent information.  
8.4 Confirm or resolve enrollment discrepancies identified during validation.  
8.5 Provide verified enrollment results to the Campus Scholarship Coordinator.

### 9. Allow the Microservice to (Automated Validation Flow)

9.1 Cross-check scholarship records against official student and enrollment data.  
9.2 Match scholar information with corresponding student records.  
9.3 Determine whether scholars are officially enrolled in their respective campuses.  
9.4 Identify unmatched or inconsistent scholar records.  
9.5 Categorize scholar records according to validation results.  
9.6 Provide validation results to authorized Campus Scholarship Coordinators and Registrars.

### 10. Allow Administrators to (System Administration Flow)

10.1 Manage user accounts, roles, permissions, and system access.  
10.2 Manage the seven campuses and campus-specific user assignments.  
10.3 Manage student and scholar profiles.  
10.4 Monitor certificate requests and scholarship validation activities across all campuses.  
10.5 Maintain centralized scholarship records.

### 11. Allow the System to (Reporting Flow)

11.1 Generate Scholar Information Reports.  
11.2 Generate Certificate Request and Official Receipt Verification Reports.  
11.3 Generate Scholarship Masterlist and Validation Reports.  
11.4 Generate Scholarship Agency and Available Scholarship Reports.  
11.5 Generate Enrollment Verification Reports.  
11.6 Generate Approved and Rejected Certificate Request Reports.  
11.7 Generate campus-specific scholarship and validation reports.  
11.8 Generate consolidated scholarship reports covering all seven campuses.

### 12. Evaluate the System in Terms of:

12.1 Functionality  
12.2 Usability  
12.3 Reliability  
12.4 Performance Efficiency

---

# SKSUScholarSync System Flow

## System Roles and Access

The system uses five user roles:

- **Student** — accesses only their own account, certificate requests, records, and scholarship information.
- **Administrator** — manages university-wide users, campuses, scholarship information, certificate processing, monitoring, and reports.
- **Scholarship Chairman** — manages masterlist upload, campus distribution, validation monitoring, review, and final approval across all seven campuses.
- **Campus Scholarship Coordinator** — validates scholar records assigned to their campus and submits completed results to the Chairman.
- **Campus Registrar** — verifies enrollment information and resolves discrepancies for records assigned to their campus.

The **Scholarship Agency is not a login role**. It provides scholarship information and sends its masterlist to the Scholarship Chairman outside the system. The **microservice is not a user role**; it is a system component used by authorized Campus Scholarship Coordinators and Campus Registrars.

## 1. Login and Role-Based Access Flow

1. The user logs in using an authorized account.
2. The system authenticates the account and identifies its role.
3. The system checks the user's permissions and campus assignment when applicable.
4. The user is redirected to the appropriate role-based dashboard.
5. University-wide roles can access records across all campuses, while campus-based roles can access only their assigned campus.

**Access summary:**

- University-wide: Administrator and Scholarship Chairman
- Campus-specific: Campus Scholarship Coordinator and Campus Registrar
- Personal records only: Student

## 2. Certificate Management Flow

1. A student submits an online request for a Certificate of No Scholarship.
2. The student uploads the required Official Receipt (OR).
3. The system stores the request with a **Pending** status and records it in the student's request history.
4. An Administrator opens Certificate Management and reviews the request and uploaded OR.
5. The Administrator verifies the OR and either approves or rejects the request.
6. If rejected, the system records the reason and notifies the student through the system and email.
7. If approved, the system automatically generates and stores the Certificate of No Scholarship.
8. The system notifies the student that the certificate is available.
9. The student downloads or prints the approved certificate.
10. The system retains the complete request, verification, issuance, and notification history for monitoring and auditing.

**Status flow:**

`Pending → Under Review → Approved → Certificate Generated`

or

`Pending → Under Review → Rejected`

## 3. Scholarship Posting and Student Information Flow

1. The Administrator records and manages scholarship agencies and their scholarship programs.
2. The Administrator creates a scholarship opportunity and enters its description, qualifications, requirements, rules, policies, guidelines, deadline, and official application link.
3. The Administrator publishes, updates, deactivates, reactivates, or archives the opportunity as needed.
4. Published opportunities become visible to students.
5. Students browse available scholarships and view the complete program and agency information.
6. Students follow the official external application link when they choose to apply.
7. Scholarship applications are completed outside SKSUScholarSync; the system provides information and access to the official link only.

**Opportunity flow:**

`Agency and Program Setup → Opportunity Creation → Publication → Student Viewing → External Application Link`

## 4. Scholarship Masterlist and Campus Validation Flow

### Step A: Chairman Uploads and Distributes the Masterlist

1. A scholarship agency sends its masterlist to the Scholarship Chairman outside the system.
2. The Chairman uploads the masterlist to SKSUScholarSync through CSV integration.
3. The Chairman associates the uploaded masterlist with the correct scholarship agency and scholarship program.
4. The system validates the file structure and flags missing, invalid, or duplicate entries.
5. Valid scholar records are grouped and distributed according to campus.
6. Each Campus Scholarship Coordinator can access only the scholar records assigned to their campus.

### Step B: Coordinator Initiates Automated Validation

1. The Campus Scholarship Coordinator reviews the assigned scholar records.
2. The Coordinator initiates validation through the microservice.
3. The microservice cross-checks each scholar against official student and enrollment data.
4. The microservice matches student details, checks official enrollment, and categorizes each result.
5. Validation results are returned as matched, unmatched, missing, inconsistent, or otherwise requiring verification.

### Step C: Registrar Resolves Enrollment Discrepancies

1. Records requiring further enrollment verification become available to the Campus Registrar for the same campus.
2. The Registrar reviews the student and enrollment information through the microservice.
3. The Registrar confirms correct records or resolves missing, unmatched, and inconsistent information.
4. The Registrar records the verification result and returns it to the Campus Scholarship Coordinator.

### Step D: Coordinator Submits Campus Results

1. The Coordinator reviews the automated and Registrar-confirmed results.
2. The Coordinator ensures that all assigned records have a completed validation result.
3. The Coordinator submits the completed campus validation results to the Scholarship Chairman.
4. The Chairman dashboard updates the validation progress for that campus.

### Step E: Chairman Reviews and Finalizes Results

1. The Chairman monitors validation progress across all seven campuses.
2. The Chairman reviews the results submitted by each Campus Scholarship Coordinator.
3. The Chairman approves or rejects the submitted validation results and records any necessary remarks.
4. Once the review is complete, the Chairman finalizes the validated scholar records for scholarship processing and monitoring.

**Masterlist flow:**

`Agency sends masterlist outside the system → Chairman uploads CSV → System distributes records by campus → Coordinator initiates microservice validation → Registrar resolves discrepancies → Coordinator submits results → Chairman approves or rejects → Records finalized`

## 5. System Administration and Monitoring Flow

1. The Administrator manages user accounts, the five roles, permissions, and system access.
2. The Administrator manages all seven campuses and assigns Coordinator and Registrar accounts to the correct campus.
3. The Administrator manages student and scholar profiles.
4. The Administrator monitors certificate requests and scholarship validation activities across all campuses.
5. The system records important actions, status changes, and generated records in a centralized audit history.

## 6. Reporting Flow

1. An authorized user selects a report type and any applicable campus, program, status, or date filters.
2. The system retrieves the authorized records and generates the requested report.
3. Available reports include:
   - Scholar Information Reports
   - Certificate Request and OR Verification Reports
   - Scholarship Masterlist and Validation Reports
   - Scholarship Agency and Available Scholarship Reports
   - Enrollment Verification Reports
   - Approved and Rejected Certificate Request Reports
   - Campus-specific Scholarship and Validation Reports
   - Consolidated Scholarship Reports for all seven campuses
4. The authorized user reviews or exports the generated report.

## 7. System Evaluation Flow

1. Representative users perform the workflows assigned to their roles.
2. The completed system is evaluated in terms of functionality, usability, reliability, and performance efficiency.
3. Evaluation results are collected and analyzed to determine whether SKSUScholarSync satisfies its stated objectives.

## Overall System Flow

`User logs in → System applies role and campus access → User completes an authorized workflow → System validates and stores the result → Relevant users receive status updates → Authorized personnel monitor records and generate reports`

The system does **not** include a continuing scholarship or scholarship renewal workflow.
