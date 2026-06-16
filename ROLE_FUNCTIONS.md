# ScholarSync Role Functions

This document summarizes the main functions of each user role in the ScholarSync system.

## Implementation Check

Overall status: mostly attained.

Attained -> Student certificate request, OR upload, request status tracking, certificate download, renewal upload, renewal status tracking, and revision handling.

Attained -> Administrator OR verification, OR rejection with remarks, certificate approval/generation, central monitoring, reports, exports, renewal evaluation, fund source management, and audit trail.

Partially Attained -> Administrator user account and role management. Role-based accounts, middleware, seeders, and dashboards are implemented, but a full administrator user-management CRUD screen is not yet implemented.

Attained -> Scholarship Agency masterlist upload, CSV preview, duplicate/invalid checking, import, verification result tracking, and released result viewing.

Attained -> Coordinator masterlist validation, record remarks, validation decisions, submission to chairman, and renewal evaluation.

Attained -> Scholarship Chairman masterlist review, approval, rejection with remarks, approval date/user recording, and final release to agencies.

Attained -> Python microservice masterlist verification endpoints and Laravel result storage.

## Student

Student -> Register and sign in to the portal

Student -> Request a Certificate of No Scholarship

Student -> Enter certificate request details and purpose

Student -> Upload Official Receipt files for certificate requests

Student -> Track certificate request status: Pending, Verified, Rejected, Approved

Student -> View remarks from administrators or evaluators

Student -> Download generated approved certificates

Student -> Upload continuing scholarship renewal requirements

Student -> Track renewal application status: Submitted, Under Evaluation, Approved, Rejected, Need Revision

Student -> Resubmit requirements when revision is requested

Student -> View certificate request history and renewal history

## Administrator

Administrator -> Manage and monitor the overall ScholarSync system

Administrator -> View central monitoring dashboard and system charts

Administrator -> Verify submitted Official Receipt uploads

Administrator -> Approve valid OR submissions

Administrator -> Reject invalid OR submissions with remarks

Administrator -> Trigger certificate approval workflow after OR verification

Administrator -> View generated certificate records and certificate history

Administrator -> Monitor student profiles and scholarship history

Administrator -> Monitor scholar records, fund sources, transactions, and reports

Administrator -> Manage scholarship programs and funding sources

Administrator -> Review and evaluate continuing scholarship renewal applications

Administrator -> Generate reports for scholars, certificates, OR verification, masterlists, renewals, requirements, and fund sources

Administrator -> Export reports as PDF, Excel, or CSV

Administrator -> View audit trail for important system actions

## Scholarship Agency

Scholarship Agency -> Access agency dashboard

Scholarship Agency -> Upload scholar masterlist CSV files

Scholarship Agency -> Preview uploaded CSV data before final import

Scholarship Agency -> Review missing, invalid, or duplicate fields before submission

Scholarship Agency -> Submit masterlists for system verification

Scholarship Agency -> View uploaded masterlist history

Scholarship Agency -> Track masterlist verification results

Scholarship Agency -> View final released scholar records after chairman approval

Scholarship Agency -> Review approved, rejected, duplicate, invalid, enrolled, and unenrolled scholar results

## Coordinator

Coordinator -> Access coordinator validation dashboard

Coordinator -> View pending verified masterlists for validation

Coordinator -> Review validation summaries from the Python verification service

Coordinator -> Review enrolled scholar records

Coordinator -> Review unenrolled scholar records

Coordinator -> Add remarks to masterlist records

Coordinator -> Mark records for correction, rejection, or chairman review

Coordinator -> Save coordinator validation status per masterlist record

Coordinator -> Submit fully reviewed masterlists to the Scholarship Chairman

Coordinator -> Evaluate continuing scholarship renewal applications

Coordinator -> Add evaluation remarks and results for renewal applications

## Scholarship Chairman

Scholarship Chairman -> Access chairman approval panel

Scholarship Chairman -> View masterlists submitted by coordinators

Scholarship Chairman -> Review enrolled, unenrolled, duplicate, and invalid masterlist records

Scholarship Chairman -> Approve valid scholar records

Scholarship Chairman -> Reject invalid scholar records with required remarks

Scholarship Chairman -> Save approval or rejection decision per record

Scholarship Chairman -> Record approval date and approving user

Scholarship Chairman -> Release final scholar records to scholarship agencies

Scholarship Chairman -> Provide final approval control before results become visible to agencies

## Role Workflow Summary

Student -> Requests certificate or submits renewal requirements

Administrator -> Verifies OR, approves certificate request, monitors system, and generates reports

Scholarship Agency -> Uploads scholar masterlists and receives released results

Python Microservice -> Validates masterlist records against enrolled student records

Coordinator -> Reviews and validates verified masterlist records

Scholarship Chairman -> Gives final approval or rejection and releases final records

## Access Control Summary

Student -> Student certificate requests, certificate downloads, renewal submissions

Administrator -> Admin verification, monitoring, reports, audit trail, program management

Scholarship Agency -> Agency masterlist upload and final released records

Coordinator -> Coordinator validation workflow and renewal evaluation

Scholarship Chairman -> Chairman approval workflow and final release
