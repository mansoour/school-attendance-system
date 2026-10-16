# Configuration

## Environment

Copy `.env.example` to `.env`, then run `php artisan starter:install`. The installer creates a missing default SQLite file, generates an application key when needed, runs migrations and loads neutral seeds. Keep the key when upgrading or restoring an installation.

Set `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG` and database credentials for each installation. Use distinct `SESSION_COOKIE`, `CACHE_PREFIX` and `REDIS_PREFIX` values when multiple applications share infrastructure. Clear cached configuration after environment changes with `php artisan config:clear`.

The default database is SQLite at `database/database.sqlite`. For another supported database, create an empty database, set `DB_CONNECTION` and its connection variables, and run the installer. Install the corresponding PHP database extension. Production backup commands for other engines require their database dump executable.

## Administration

Register an account, verify its email, and assign ownership using `php artisan starter:owner you@example.com`. Open `/admin`.

| Area | Controls |
| --- | --- |
| Security | Registration, email verification, password policy, sessions, lockouts, staff/member MFA, recovery codes, reCAPTCHA |
| Appearance | Default language, default theme and availability of dark mode |
| Maintenance | Localized downtime message; staff retain sign-in and access |
| Brand identity | Localized name/logo plus tabs for icons, cookies, social links, colors and fonts |
| Homepage | Localized heading and description |
| Staff alerts | Roles notified about support messages |
| Roles | Create, rename, change permissions and delete roles; owner is protected |


Mandatory staff MFA is off by default. Accounts that voluntarily enabled MFA still use it at sign-in. Enabling mandatory staff MFA redirects staff to enrollment before administration access. Recovery codes are single use.

Deleting an assigned role removes that role from its members, leaving accounts and other roles intact. Renaming or deleting a role updates its support-alert configuration. Password confirmation is required for sensitive administrative changes.

## Languages and themes

English and Arabic ship with the application. Arabic uses RTL layouts, mirrored directional arrows and locally hosted IBM Plex Sans Arabic. Visitors select a language; members retain their saved preference. The navbar language dropdown offers other active languages with names and flags.

At `/admin/translations`, add a hidden language, edit phrases, save drafts, preview and publish. Activate it when ready. Keep placeholders such as `:minutes` intact. Publishing an older revision restores its content. Missing phrases fall back to English and are recorded when encountered. Check font coverage for new scripts; page content currently has English/Arabic fields with English fallback.

Branding colors have separate light/dark palettes and a private preview. Theme choices persist in the browser; system mode follows device preference. Administration > Appearance controls whether dark mode is offered.

## Content and notifications

Pages support Markdown with raw HTML escaped. Replace the neutral legal and homepage placeholders before launch. Notices support scheduling and dismissal. Mail templates are plain text and can use `{name}`. Security email cannot be disabled through notification preferences.

The navbar shows recent notifications and the unread count, with mark-all-read and view-all actions. Notifications belong to the signed-in member. The database queue worker must run to deliver queued notifications and mail.

## Excluded installation data

Git excludes local environments, database files/dumps, uploads, backups, exports, logs, sessions, caches, dependencies and local workspace instructions. Migrations, factories and neutral seeders are included. No default account, credentials, live content or database snapshot is supplied.
