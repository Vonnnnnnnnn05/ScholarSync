# Admin Account Management Design

## Goal

Allow Administrators to add, edit, and safely remove access for system accounts from the existing User Management page.

## Account Operations

- **Add:** Keep the current account-creation form and its role/campus validation.
- **Edit:** Allow the Administrator to update an account's name, email, role, assigned campus, status, and optionally password.
- **Delete:** Treat deletion as account deactivation by setting `status` to `inactive`. Historical records and audit references remain intact.

## Rules and Safety

- Only Administrators may use account-management routes.
- Coordinator and Registrar accounts require an active campus assignment; university-wide roles have no campus assignment.
- Email addresses remain unique.
- Leaving the password fields blank during editing preserves the current password.
- An Administrator cannot deactivate their own signed-in account.
- Creating, updating, and deactivating accounts creates audit-log entries.
- Deactivated accounts remain visible in User Management and cannot authenticate.

## Interface

The existing account table gains Edit and Delete actions. Edit opens an accessible inline modal populated with the selected account. Delete opens a confirmation prompt explaining that access will be disabled and records preserved.

## Verification

Feature tests will cover authorized editing, optional password changes, role/campus validation, deactivation, self-deactivation protection, audit entries, and denial of access to non-Administrators and inactive users.
