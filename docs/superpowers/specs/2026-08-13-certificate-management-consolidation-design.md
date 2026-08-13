# Certificate Management Consolidation Design

## Objective

Combine Administrator Official Receipt verification and generated certificate history into one Certificate Management module and one primary page.

## Interface

The Administrator sidebar and responsive navigation will contain one link labeled **Certificate Management**. It opens one page that contains:

1. A certificate-request section with status filters, student/request details, Official Receipt state, and a review action.
2. A generated-certificate history section with certificate number, student, related request, generation details, and PDF download action.

The request review screen remains a focused detail page within Certificate Management because verification, approval, rejection, and remarks require more space. Its back link returns to the unified page.

## Routes and Compatibility

New Administrator routes will use the `admin.certificate-management.*` namespace. Existing `admin.official-receipts.*` and `admin.certificates.*` URLs will remain as redirects or compatibility endpoints where needed so existing links and bookmarks do not fail. All routes retain Administrator-only authorization.

## Behavior

Existing OR download, verify, approve, reject, certificate generation, audit, notification, and PDF download behavior remains unchanged. The consolidation changes navigation and presentation, not certificate-processing rules.

## Verification

Tests will confirm that Administrators see one Certificate Management navigation item and one unified page, non-Administrators remain forbidden, request processing still works, generated PDFs remain downloadable, compatibility routes remain safe, and the full test suite passes.
