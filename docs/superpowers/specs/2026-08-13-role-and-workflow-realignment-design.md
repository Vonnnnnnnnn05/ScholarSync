# Role and Workflow Realignment Design

## Objective

Realign SKSUScholarSync with the final study objectives by reducing authentication to five user roles, making campus access explicit, treating scholarship agencies as managed data rather than users, transferring agency-owned workflows to authorized university roles, and completely removing the continuing-scholarship renewal feature.

## System Roles

The application will support exactly these login roles:

1. **Student** — manages their certificate requests and views published scholarship opportunities.
2. **Administrator** — manages accounts, campuses, agencies, programs, opportunities, certificate processing, monitoring, and reports.
3. **Scholarship Chairman** — uploads agency-provided masterlists, distributes records to campuses, monitors validation, reviews submitted results, and finalizes scholar records.
4. **Campus Scholarship Coordinator** — works only with masterlist records assigned to their campus, initiates validation, reviews results, coordinates discrepancies, and submits campus results.
5. **Campus Registrar** — works only with enrollment and discrepancy records for their campus and provides verification results to the coordinator.

The existing role values `coordinator` and `registrar` may remain stable internally to reduce migration risk, but every user-facing label must use the full campus-specific title. The `scholarship_agency` role will be removed from the role enum, validation, middleware, account forms, seeders, dashboards, navigation, and tests.

## Campus Model and Authorization

A `campuses` table will define the university's seven campuses using stable identifiers, names, and active status. Existing campus names in the academic-options data will seed the initial records.

Users will receive a nullable `campus_id` foreign key:

- It is required for Campus Scholarship Coordinator and Campus Registrar accounts.
- Student campus membership will be linked through the student's campus assignment while preserving compatibility with existing student profile and certificate displays during migration.
- Administrator and Scholarship Chairman accounts remain university-wide and must not require a campus.

Campus filtering must be enforced in server-side queries and authorization checks, not only in navigation. Coordinators and registrars must receive an HTTP 403 or 404 response when attempting to access another campus's records. Admin and Chairman workflows can access all seven campuses.

## Scholarship Agency Conversion

Scholarship agencies remain domain records because programs, opportunities, masterlists, and reports depend on them. They cease to own user accounts.

The `agencies.user_id` relationship will become nullable or be removed in a forward migration before obsolete users are deleted. The migration will then delete every user whose role is `scholarship_agency`, preserving associated agencies, programs, policies, masterlists, and scholar records.

Agency contact name, email, and phone remain fields on the agency record. There will be no agency dashboard, authentication route, middleware permission, seeded agency account, or agency-only interface.

## Workflow Ownership

### Scholarship opportunities

Administrators manage agencies, programs, descriptions, qualifications, requirements, policies, guidelines, deadlines, status, and official external application links. Students can browse active published opportunities and follow the external links. Students do not apply for or renew scholarships inside SKSUScholarSync.

### Masterlist validation

The Chairman receives masterlists outside the system and uploads them inside SKSUScholarSync. Each imported record is assigned to a campus. Coordinators access only records for their assigned campus and initiate microservice validation. Registrars access only records or enrollment data requiring verification for their campus. Coordinators submit completed campus results to the Chairman, who reviews and finalizes them university-wide.

Existing masterlist parsing and verification services should be reused. Agency upload controllers and views will either be moved to the Chairman namespace and routes or replaced with Chairman-owned equivalents, with no agency-authentication assumptions.

## Continuing-Scholarship Removal

The continuing-scholarship feature will be deleted completely, including:

- student renewal and evaluator routes;
- renewal controllers and form requests;
- scholarship application and requirement models, factories, enum, mail, views, and feature tests;
- renewal navigation, dashboard cards, monitoring data, transaction sections, report types, and exports;
- renewal references in documentation and role descriptions;
- `scholarship_requirements` and `scholarship_applications` tables, in dependency-safe order; and
- uploaded renewal requirement files that belong exclusively to removed records.

A forward migration will drop the renewal tables for existing installations. Historical renewal data will not be retained because the feature is explicitly out of system scope.

## Data Migration Safety

Migration order must preserve remaining scholarship data:

1. Create and seed campuses.
2. Add campus assignments needed by scoped users and records.
3. Detach agencies from agency login users without cascading agency deletion.
4. Delete `scholarship_agency` users.
5. Remove renewal tables and obsolete schema.

The migration must tolerate databases with no agency users or renewal rows. Existing student campus strings will be mapped to seeded campuses where names match; unmatched values remain visible and are reported for administrator correction rather than silently reassigned.

## Interface Changes

Dashboards, sidebar navigation, account management forms, welcome-page statistics, and role descriptions will reflect only the five roles and their updated responsibilities. Agency tools disappear. Chairman navigation gains masterlist upload and campus-progress monitoring. Administrator navigation gains agency and opportunity management. Coordinator and Registrar pages prominently display their assigned campus and omit university-wide controls.

The existing visual language and Blade component conventions will be retained; this is an information architecture and authorization change, not a broad visual redesign.

## Error Handling and Auditability

- Invalid or missing campus assignments block campus-scoped actions with a clear corrective message.
- Cross-campus record access is denied server-side.
- Masterlist upload validation continues to return row and format errors without partially importing data.
- Agency, opportunity, masterlist, validation, discrepancy, submission, and finalization actions remain auditable.
- Obsolete agency routes return 404 after removal and renewal routes are no longer registered.

## Testing and Acceptance Criteria

Automated tests must demonstrate that:

- only the five intended roles can be assigned and each redirects to the correct dashboard;
- no scholarship agency account, route, dashboard, or account option remains;
- deleting legacy agency users preserves agencies and all dependent scholarship data;
- administrators can manage agencies and published opportunities;
- the Chairman can upload and distribute masterlists;
- coordinators and registrars can access their own campus data but not another campus's data;
- students can request certificates, download approved certificates, browse opportunities, and use official external links;
- renewal routes, classes, UI, report types, and database tables are absent;
- masterlist verification, certificate processing, notifications, monitoring, and remaining reports continue to pass; and
- the Python microservice contract remains compatible with the Laravel caller, including campus-aware validation data where required.

Verification will include focused Pest tests, the complete PHP test suite, route inspection, migration refresh testing, frontend asset compilation, and the microservice's available automated checks or syntax validation.

## Scope Boundaries

This change does not introduce direct integrations with scholarship agencies, registrar information systems, or external application portals. Agencies provide masterlists to the Chairman outside SKSUScholarSync, registrar data continues through the existing import/microservice boundary, and scholarship applications occur through administrator-provided external links.
