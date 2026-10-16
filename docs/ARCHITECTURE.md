# Architecture

## Stack

Laravel 13 / PHP 8.3+, Blade and Alpine.js for public/account/admin interfaces, Vite/Tailwind CSS for assets, and SQLite by default. Composer and npm lockfiles pin the dependency graph.

## Main areas

| Location | Responsibility |
| --- | --- |
| `app/Http/Controllers/Admin` | Settings, content, roles, members, support and operations |
| `app/Settings` | Typed setting definitions, persisted values and encrypted secrets |
| `app/Localization` | Locale registry, published catalogs, fallback and missing-phrase tracking |

| `app/Services/Security`, `app/Security` | TOTP, recovery codes and replay protection |
| `app/Integrations` | Gemini and reCAPTCHA server adapters |
| `app/Operations` | Health checks and SQLite backup creation |
| `app/Services/Audit` | Privileged-action audit records |
| `resources/views` | Shared shell and Blade interfaces |
| `lang` | English/Arabic source catalogs and framework messages |
| `database/migrations` | Fresh schema for the standalone application |
| `database/seeders` | Neutral pages, permissions, roles and optional plan examples |
| `tests` | In-memory SQLite feature and unit tests |

Authentication uses the `web` guard. Administrative routes require verified authentication, `admin.access`, optional enforced MFA and section permissions. Sensitive mutations also require recent password confirmation. The owner role is protected. New features should enforce their permissions on the server, even if their navigation item is hidden.

Settings are persisted in this application's database; encrypted secrets are applied at runtime. Public uploads retain a disk/path record, so changing storage does not break earlier uploads. The shared navbar supports localized RTL navigation, theme preference and owned notifications.

Queue workers process mail and notifications. The scheduler records a heartbeat and runs backups. Keep each deployment's environment, key, database, cache namespace and storage independent.

## School module

| Location | Responsibility |
| --- | --- |
| `app/School/Roles.php` | School permissions and default role grants |
| `app/School/SchoolContext.php` | Active school resolution and query scoping (`scope()`, `authorize()`) |
| `app/School/AttendanceService.php` | Saving a class register and announcing absences/late arrivals |
| `app/School/ExcuseService.php` | Excuse submission and review |
| `app/School/GuardianLinker.php` | Matching registering guardians to students and activating links |
| `app/School/Notifier.php` | Event delivery according to the School notifications settings |
| `app/School/Stats.php`, `StudentImporter.php` | Report aggregates and Excel/CSV import |
| `app/Http/Controllers/School`, `routes/school.php` | School workspace under `/school` |
| `resources/views/school` | School screens, sharing the administration sidebar layout |

Attendance is one record per student per day (`attendance_records` is unique on `student_id` + `date`, stored as `Y-m-d`). School models use the `Audited` trait so every create, update and delete is written to the audit log. Staff accounts carry a `school_id`; the owner role (system administrator) selects a school or works across all schools.

## Extending the kit

Add reusable settings through `SettingsRegistry`, including validation, defaults, permission and translated labels. Use named routes, the shared layouts and `<x-ui-icon>` in Blade; use `@lucide/vue` in Vue. Directional icons must be mirrored in RTL. Add English and Arabic copy together and preserve translation placeholders.


The kit has no tenant isolation model, payment checkout or product-specific business workflow. A future multi-tenant product must introduce and test its own tenant boundaries.
