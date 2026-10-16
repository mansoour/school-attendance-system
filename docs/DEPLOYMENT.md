# Deployment and recovery

## Prepare a release

1. Install PHP 8.3+ with the extensions required by Composer, including PDO SQLite for the default database, ZIP, mbstring, OpenSSL, fileinfo and DOM/XML. Use Node 22.12+ for asset builds.
2. Run `composer install --no-dev --optimize-autoloader`, `npm ci` and `npm run build`. Build assets before omitting Node dependencies from the runtime image.
3. Configure an independent `.env`: `APP_ENV=production`, `APP_DEBUG=false`, a correct HTTPS `APP_URL`, secure session cookies and database credentials. Generate a unique application key for a new installation.
4. Run `php artisan starter:install` for a new installation. For upgrades, back up first and run `php artisan migrate --force`; review seeders before rerunning them because they add default roles and grants.
5. Run `php artisan storage:link`. Make `storage/`, `bootstrap/cache/` and the SQLite database/directory writable by the application user. Keep database files outside the public document root.
6. Point the web server document root to `public/`; deny access to dotfiles. Use HTTPS.
7. Run `php artisan config:cache`, `php artisan route:cache` and `php artisan view:cache`. Rebuild these after configuration or code changes.
8. Supervise `php artisan queue:work --tries=3 --timeout=90`. Run `php artisan queue:restart` after releases so workers reload code. The process manager must restart workers when they exit.
9. Run `php artisan schedule:run` every minute through your scheduler. Use the release directory as the working directory.
10. Register/verify an owner, configure email and integrations, review security settings, replace placeholders and check the health page.

The PHP development server is for local use. Plans provide manual access management only; payments, invoices, tax and recurring billing are outside the starter kit.

## Backups

For SQLite, `php artisan starter:backup` uses `VACUUM INTO`, checks database integrity and retains snapshots for 30 days under `storage/app/private/backups`. An archive password produces an AES-256 ZIP and the temporary plaintext snapshot is removed. Without one, the snapshot is a `.sqlite` file.

The scheduler also runs Spatie file backups for local public uploads. Non-SQLite database backups use `backup:run` and need the engine's dump tool. Default backup destinations are private local storage. Configure independent off-site copies and retention. R2 branding objects need their own backup/versioning policy; the local file backup does not include remote objects.

Preserve these items securely: application key, archive passwords for every retained generation, database backups, public uploads and any remote objects. A database snapshot alone does not contain uploaded files.

## Restore procedure

1. Stop writers and queue workers and enable maintenance at the web-server/process layer. Keep a copy of the current installation before replacing data.
2. Decrypt the chosen archive with its original password. Restore the database to a separate location first and verify it with the appropriate database tools.
3. Restore uploads/remote objects and the original `APP_KEY`; encrypted settings and MFA secrets depend on that key. Set file permissions and database paths.
4. Check schema compatibility with the selected code release. Apply required migrations only after a successful backup and review.
5. Clear/rebuild application caches, restart workers, and verify sign-in, storage, mail and representative administrative pages before reopening.

Restore commands depend on the selected database and deployment layout. Test the procedure on an isolated environment before an incident.

## Monitoring

The admin health page checks database/storage access, scheduler heartbeat, queue age, failed jobs, backup freshness and production mail/debug configuration. Configure external uptime and error monitoring for deployment. Review `php artisan queue:failed` when jobs fail. Keep mail logs and backup files private.
