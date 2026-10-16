# Contributing

Use PHP 8.3+ and Node 22.12+. Follow the [README](README.md) setup instructions and work on a focused branch.

Before submitting changes:

```sh
php artisan test --compact
npm run typecheck
npm run build
php vendor/bin/pint --test
composer validate --no-check-publish
```

Backend tests use in-memory SQLite. Do not run them against personal or production data. Add meaningful tests for access control, persistence and other behavioral changes. Check both languages, RTL/LTR layout, light/dark themes, narrow screens and empty/error states when changing interfaces.

Keep English and Arabic messages together. Submit stable permission identifiers from forms and translate their labels separately. Never trust hidden navigation as authorization. Preserve existing application keys and user data during upgrades; add forward migrations for schema changes.

Do not commit environments, credentials, account data, database snapshots/dumps, uploads, backup archives, logs, caches or generated dependencies. Keep seed content neutral and do not add shared default accounts. Retain third-party license notices.

Describe the problem, resulting behavior and validation in each pull request. Keep unrelated formatting and dependencies out of focused fixes. Use [SECURITY.md](SECURITY.md) for sensitive reports.
