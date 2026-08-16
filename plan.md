# SKSUScholarSync Development Scope

SKSUScholarSync supports certificate processing, administrator-published scholarship opportunities, Chairman-owned masterlist intake, campus-scoped enrollment validation, centralized monitoring, and reporting.

## Roles

1. Student
2. Administrator
3. Scholarship Chairman
4. Campus Scholarship Coordinator
5. Campus Registrar

Scholarship agencies are managed records and do not authenticate. Coordinators and Registrars are assigned a `campus_id`; Administrators and the Chairman have university-wide access.

## Core Workflows

- Students request and download Certificates of No Scholarship.
- Administrators verify Official Receipts, manage users/campuses/agencies/programs, and publish opportunities with official external application links.
- The Chairman uploads agency-provided CSV masterlists, oversees validation across seven campuses, and finalizes scholar records.
- Coordinators and Registrars validate only records belonging to their assigned campus through the Laravel/FastAPI validation boundary.
- Administrators generate certificate, scholar, masterlist, agency/opportunity, enrollment, campus, and consolidated reports.

Continuing-scholarship applications and renewals are outside the system scope.
