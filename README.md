# Arabic Laravel SaaS Starter Kit

A standalone Laravel foundation for Arabic and English SaaS applications, with RTL interfaces, authentication, administration, localization, branding and optional subscription plans.

[العربية](README.ar.md) · [Configuration](docs/CONFIGURATION.md) · [Integrations](docs/INTEGRATIONS.md) · [Deployment](docs/DEPLOYMENT.md) · [Architecture](docs/ARCHITECTURE.md)

## Features

- Arabic/English interfaces with RTL layouts, mirrored arrows and local IBM Plex Sans Arabic, Inter and Inter Tight fonts.
- Registration, email verification, password reset, account settings/export/deletion and optional authenticator-based MFA with recovery codes.
- Permission-scoped administration, member filters, editable roles and protected ownership.
- Grouped, independently scrollable admin sidebar and a full-width navbar with account/language/theme controls and a notification dropdown.
- Plans disabled by default, manual subscriptions, feature switches and monthly usage limits when enabled.
- Branding tabs for localized names/logos, site icons, cookie notice, social links, fonts and separate light/dark color palettes with preview.
- Phrase-based translation editor: language management, drafts, preview, publishing, revision restoration and missing-phrase tracking.
- Markdown pages, scheduled site notices, mail templates, support inbox, audit history and health checks.
- Resend, Google Gemini, Google reCAPTCHA v3 and Cloudflare R2 integration settings with encrypted credentials.
- Database queues, scheduler heartbeat, SQLite snapshots and optional AES-256 encrypted backup archives.

This is a reusable application foundation. Payment checkout, invoices, tenant isolation and product-specific workflows are not included.

## Requirements

- PHP **8.3+** and Composer 2; install the PHP extensions required by `composer install`, including PDO SQLite and ZIP for the default setup.
- Node.js **22.12+** and npm.
- SQLite by default. Other database engines require their corresponding PHP driver and connection configuration.

## Quick start

```sh
git clone https://github.com/mansoour/arabic-laravel-saas-starter-kit.git
cd arabic-laravel-saas-starter-kit
composer run setup
php artisan storage:link
composer run dev
```

`composer run setup` installs dependencies, creates `.env` and an empty SQLite database when missing, generates an application key, runs migrations/neutral seeds and builds assets. It preserves an existing application key. Run every command from the project directory.

Open **http://127.0.0.1:8017**. The development command starts Laravel, the queue worker, scheduler and Vite (port 5177). Stop the group with Ctrl+C. Each cloned project should have its own environment, key, database, storage and runtime ports.

To use built assets without Vite, run these in separate terminals:

```sh
php artisan serve --host=127.0.0.1 --port=8017
php artisan queue:work --tries=3
php artisan schedule:work
```

### Windows / PowerShell

Select compatible PHP and Node installations on `PATH` before running Composer/npm. If XAMPP provides an older PHP, invoke a compatible executable explicitly, for example `C:\php\php.exe artisan serve --host=127.0.0.1 --port=8017`. Start commands from this repository, not from your home directory.

## Create the first administrator

1. Register at `/register`.
2. Keep the queue worker running. Development mail uses the **log** driver, so open `storage/logs/laravel.log` and follow the verification link.
3. Assign your registered account the owner role:

```sh
php artisan starter:owner you@example.com
```

4. Sign in and visit **`/admin`**.

No default user or password is seeded. Mandatory staff MFA is **off by default** and can be enabled under Security. Accounts that enabled MFA voluntarily still require it at sign-in.

## Database and content

The repository includes migrations, factories and neutral seeders. **It does not include a populated database, accounts, credentials, uploads or installation content.** A fresh SQLite database is created during setup; migrations provide the complete schema. To use another database, create an empty database, edit `.env` and run `php artisan starter:install`.

Seeders add permissions/default roles, neutral page placeholders and an optional example plan/feature. Plans stay disabled until an administrator enables them. Review and replace public/legal placeholders before launch. Rerunning seeds adds default records/grants; it does not create an owner account.

Local environments, database files/dumps, uploads, archives, logs, caches and workspace instructions are excluded from Git. Dependencies and built assets are generated during setup. Keep `.env` and `APP_KEY` private.

## Configuration and integrations

Use the administration screens to configure security, maintenance, languages, plans, branding and support alerts. Sensitive changes require recent password confirmation. Deleting a role removes its assignments while preserving accounts and other roles; owner remains protected.

Development emails stay in the log. Production Resend delivery requires a verified sending domain, an API key, sender configuration and a deliberate mail-driver change. Other integrations remain inactive until their own keys/settings are supplied. See the [integration guide](docs/INTEGRATIONS.md).

Backups are stored privately on the local installation by default. Configure an archive password for encryption and arrange independent off-site retention. Preserve the original application key and every required archive password. See [deployment and recovery](docs/DEPLOYMENT.md).

## Verification

```sh
php artisan test --compact
npm run typecheck
npm run build
php vendor/bin/pint --test
composer validate --no-check-publish
```

Tests use isolated **in-memory SQLite**, fake provider requests where appropriate, and do not require live integration credentials. Check both RTL/LTR layouts and light/dark themes when changing interfaces.

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
