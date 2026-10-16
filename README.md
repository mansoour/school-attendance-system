# School Attendance System

A bilingual (Arabic-first, RTL) daily school attendance platform built with Laravel. It connects school staff and guardians: daily attendance per class, absence and lateness follow-up, online excuses, student notes, guardian communication by WhatsApp, phone and email, configurable notifications, audit history and exportable reports.

[العربية](README.ar.md) · [Configuration](docs/CONFIGURATION.md) · [Integrations](docs/INTEGRATIONS.md) · [Deployment](docs/DEPLOYMENT.md) · [Architecture](docs/ARCHITECTURE.md)

## Roles

| Role | Arabic | Main responsibilities |
| --- | --- | --- |
| System administrator (`owner`) | مدير النظام | Full control: every school, all data, attendance, staff, system settings and notification rules |
| Principal (`principal`) | مديرة المدرسة | Manages the whole school: staff accounts, grades, classes, students, attendance, excuses, reports and activity log |
| Vice principal (`vice_principal`) | الوكيلة | Absence follow-up, attendance, excuse review, guardian communication, notes and reports (no staff or structure management) |
| Student affairs (`student_affairs`) | شؤون الطالبات | Grades and classes, students (add, edit, delete, assign to class, Excel import), daily attendance, excuses and guardian contact |
| Health advisor (`health_guide`) | الموجه الصحي | Views absences, adds and reviews excuses, records health notes |
| Student counselor (`counselor`) | المرشدة الطلابية | Follows absence causes, counseling notes and guidance plans, guardian contact and reports |
| Guardian (`guardian`) | ولي الأمر | Follows linked children's attendance, sends excuses with attachments, reads shared notes and notifications |

Default grants are seeded once and remain editable from **System settings → Roles**. Every permission is enforced on the server and all school data is scoped to the user's school.

## Features

**Daily follow-up**
- **Overview** dashboard: role cards, today's absence/late/excused counts, classes not yet taken, monthly absence by class and by grade (donut chart), latest alerts and quick actions.
- **Attendance tracking**: daily status of every class for any date, with the absent and late students and their excuse status.
- **Class attendance**: a one-screen register per class (present, absent, late with minutes, excused, note). Attendance is daily, not per lesson; a "class" is a section of a grade (A, B, C…).
- **Excuses**: guardians submit excuses online (with an optional PDF/image); staff review, approve or reject, or record an excuse received at school. Approval marks the day as excused.
- **Student notes**: general, health, counseling, behavior and attendance notes, optionally shared with the guardian.
- **Guardian communication**: absent and late students by date, grade and class, with WhatsApp (pre-filled message), call and email buttons. Every contact is logged on the student file.
- **Reports and statistics**: date range, grade and class filters; totals, absence rate, daily trend, most absent students and per-class table. Export to **Excel** (multi-sheet, RTL) and **Arabic PDF**, or print.

**School management**
- Multiple schools, with a school switcher for the system administrator.
- Grades (with stage and order) and classes (several sections at once, capacity, supervisor).
- Students with name, national ID, email, phone and guardian name/phone/email/relation; bulk move between classes; **Excel/CSV import** with Arabic or English headers and a downloadable template.
- Employee accounts created by the principal or system administrator; employees receive a password-setup link, and can be deactivated.
- School settings (contact details, school day start, lateness threshold) and a school activity log.

**Guardians**
- Public homepage with sign in and guardian registration.
- Registration asks for the guardian's name, email, mobile, relation to the student and the student's details. The administrator chooses the verification mode: national ID + full name, national ID + student phone, or all three. Arabic spelling variants are normalized when matching.
- The link activates after email verification (or after staff approval, when enabled). Missing guardian contact details are filled on the student record automatically.
- Guardians can link additional children from their portal.

**Notifications** — in-app and email, configured per event under **System settings → School notifications**: enable/disable, in-app, email and recipient staff roles.

| Event | Recipients |
| --- | --- |
| Student absent / late | Guardians (optional staff copy) |
| Excuse submitted | Health advisor, student affairs, vice principal (configurable) |
| Excuse reviewed | Guardians |
| Note shared | Guardians |
| Guardian registered / link approved | Staff roles / the guardian |
| Repeated absence (threshold configurable) | Counselor, principal, vice principal (configurable) |

**Audit** — every create, update and delete of schools, grades, classes, students, attendance, excuses, notes and guardian contacts is recorded, plus imports, exports, account and role changes.

### Starter kit foundation (kept)

All features of the underlying Arabic Laravel SaaS starter kit remain available under **System settings**: Arabic/English RTL interfaces, registration, email verification, password reset, optional MFA, permission-scoped administration, editable roles, branding (names, logos, icons, colors, fonts, dark mode), translation editor, Markdown pages, site notices, mail templates, support inbox, audit history, health checks, optional plans, Resend/Gemini/reCAPTCHA/Cloudflare R2 integrations, queues, scheduler and encrypted backups.

## Requirements

- PHP **8.3+** and Composer 2, with PDO SQLite, ZIP, GD, mbstring and XML extensions.
- Node.js **22.12+** and npm.
- SQLite by default. Other database engines require their corresponding PHP driver and connection configuration.

## Quick start

```sh
git clone https://github.com/mansoour/school-attendance-system.git
cd school-attendance-system
composer run setup
php artisan storage:link
composer run dev
```

`composer run setup` installs dependencies, creates `.env` and an empty SQLite database when missing, generates an application key, runs migrations and seeds (permissions and roles) and builds assets.

Open **http://127.0.0.1:8017**. The development command starts Laravel, the queue worker, scheduler and Vite (port 5177).

To use built assets without Vite, run these in separate terminals:

```sh
php artisan serve --host=127.0.0.1 --port=8017
php artisan queue:work --tries=3
php artisan schedule:work
```

### Windows / PowerShell

Select compatible PHP and Node installations on `PATH` before running Composer/npm. If XAMPP provides an older PHP, invoke a compatible executable explicitly, for example `C:\php\php.exe artisan serve --host=127.0.0.1 --port=8017`.

## First steps

1. Create the system administrator. The account receives a password-setup link (with the local **log** mail driver, open `storage/logs/laravel.log`; keep the queue worker running):

   ```sh
   php artisan school:admin admin@example.com --name="System administrator"
   ```

   An existing account can also be promoted with `php artisan starter:owner you@example.com`.
2. Sign in, open **Schools** and add your school (or run `php artisan school:demo` for a fictional demo school with sample attendance).
3. Add grades and classes, then add or import students.
4. Under **Employees**, create the principal and staff accounts.
5. Review **System settings → School notifications** and **Guardian registration**.
6. Guardians register from the homepage using their child's details.

No default user or password is seeded.

## Database and content

The repository includes migrations, factories and seeders. **It does not include a populated database, accounts, credentials, uploads or student data.** Seeders add permissions, roles and neutral page placeholders; rerunning them does not overwrite edited role grants.

Excuse attachments are stored on the private `local` disk and are only downloadable by authorized staff of the school and the student's linked guardians.

## Verification

```sh
php artisan test --compact
npm run typecheck
npm run build
php vendor/bin/pint --test
composer validate --no-check-publish
```

Tests use isolated **in-memory SQLite** and do not require live integration credentials.

## Documentation

- [Configuration and defaults](docs/CONFIGURATION.md)
- [Integration setup](docs/INTEGRATIONS.md)
- [Deployment, backups and recovery](docs/DEPLOYMENT.md)
- [Architecture and extension points](docs/ARCHITECTURE.md)
- [Contributing](CONTRIBUTING.md)
- [Security reporting](SECURITY.md)
- [Changelog](CHANGELOG.md)
- [Third-party notices](docs/THIRD_PARTY.md)

## License

[MIT](LICENSE). Bundled fonts and third-party packages retain their own licenses and notices.
