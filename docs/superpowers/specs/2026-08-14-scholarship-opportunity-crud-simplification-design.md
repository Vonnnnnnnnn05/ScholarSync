# Scholarship Opportunity CRUD Simplification

## Scope

The administrator Scholarship Opportunities page will manage scholarship opportunities only. Separate Agency Management and Program Management sections and endpoints will be removed from this page.

## Interface

- Keep the existing inline opportunity creation form; do not use a modal or add a separate Add button.
- Keep Scholarship Agency and Scholarship Program as fillable text fields in the opportunity form.
- Show saved opportunities in a compact list with icon actions for View, Edit, and Delete.
- View opens a centered read-only modal with the complete opportunity details.
- Edit opens a centered, scrollable modal containing the selected opportunity form.
- Delete opens the same styled confirmation dialog used by User Management before submitting.
- Escape and backdrop clicks close each dialog; browser `confirm()` is not used.

## Data and Behavior

- Preserve existing agency, program, and opportunity records.
- Creating or editing an opportunity continues to resolve its typed agency and program values internally.
- Opportunity lifecycle statuses are Draft, Published/Active, Inactive, and Archived.
- Administrators activate an opportunity by setting it to Published and deactivate it by setting it to Inactive.
- Published opportunities remain visible to students; Draft, Inactive, and Archived opportunities remain hidden.
- Deleting an opportunity removes only that opportunity, not its referenced agency or program.

## Validation and Access

- Existing administrator role protection and opportunity validation remain in force.
- Delete uses a protected Laravel DELETE route with CSRF verification.
- Action icons include accessible labels and tooltips.

## Verification

- Feature tests cover the simplified page, update behavior, deletion, authorization, and published-only student visibility.
- Run Laravel formatting, the full test suite, and the frontend production build.
