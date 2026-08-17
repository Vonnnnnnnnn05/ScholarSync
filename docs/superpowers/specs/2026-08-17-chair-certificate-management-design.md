# Scholarship Chair Certificate Management Design

## Goal

Allow the Scholarship Chairman to manage certificate requests with the same permissions as the Administrator while preserving all unrelated role restrictions.

## Access and Workflow

The existing official-receipt and generated-certificate route groups will accept both the `administrator` and `scholarship_chairman` roles. Both roles may list and inspect certificate requests, view or download official receipts, verify receipts, approve or reject requests, and view or download generated certificates.

The existing controllers, views, route names, status transitions, notifications, PDF generation, and audit logging remain shared. Audit records continue to identify the authenticated user who performed each action. Student, Coordinator, and Registrar accounts remain forbidden from these management routes.

## Interface

The Scholarship Chairman sidebar will show the existing Certificate Management link. It will lead to the shared certificate-request management screen. Administrator navigation and all existing certificate-management URLs remain unchanged.

## Error Handling and Security

Existing validation and state checks remain in force, including required rejection remarks, approval only after verification, and missing-file responses. Server-side role middleware—not navigation visibility—enforces access.

## Verification

Feature tests will prove that a Scholarship Chairman can access the request list and detail screens, view and download receipts, verify requests, approve verified requests, reject requests with remarks, and access generated certificates. Existing Administrator behavior must continue to pass, and at least one unrelated role must remain forbidden.

## Scope Boundaries

This change does not grant the Scholarship Chairman access to user management, scholarship opportunity administration, monitoring, reports, or any other Administrator-only feature. It does not duplicate controllers or views, rename existing routes, or change certificate business rules.
