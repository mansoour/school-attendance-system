# Integrations

Manage credentials at `/admin/integrations`. Values are encrypted with the installation's application key and are not returned to form inputs or audit metadata. Changing/removing a credential requires password confirmation and signals queued workers to restart.

## Email / Resend

Local mail uses `MAIL_MAILER=log`. Run `php artisan queue:work --tries=3`, then inspect `storage/logs/laravel.log` for verification/reset messages. These logs contain sensitive links and should remain private.

For production, verify a sending domain in Resend, save the API key in administration, configure `MAIL_MAILER=resend`, and set `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` in the environment. Clear configuration caches and restart supervised workers. Saving an API key alone does not switch the mail driver. The test-email button uses the currently selected driver.

Configure the Resend webhook destination as `https://your-domain.example/webhooks/resend` and save its signing secret. The endpoint verifies Svix signatures and timestamp tolerance, handles duplicate events, and updates matching provider IDs in mail history. Delivery status depends on those signed events reaching the application.

## Google Gemini

Save an API key and model name. The connection check requests model metadata. `App\Integrations\GeminiClient::generate()` provides a server-side text-generation adapter for future features. Do not expose the stored key to frontend code. No product-specific AI feature is included.

## Cloudflare R2

Save an access key ID and secret. In file-storage settings, enter the Cloudflare account ID and bucket name, then test the connection. The test writes, reads and deletes a temporary object. Give the credentials access only to the intended bucket.

Enable cloud storage to send new branding uploads to R2. Existing uploads retain their original disk; changing the switch does not migrate files. Branded assets are served through the application, so the bucket does not need to be public. Other future upload features must choose a disk explicitly.

## Google reCAPTCHA v3

Create keys for the deployment hostname and save both keys. Enable registration and/or contact checks in Security and select the minimum score (5 means 0.5). The backend verifies the action, hostname and score, and rejects token reuse. Configure `APP_URL` with the correct public hostname. Disabled checks make no Google verification request.

## Encrypted backups

Save an archive password of at least 12 characters. New SQLite snapshots and Spatie archives use encryption; older files retain their original password. Store the password and application key separately from the server. Without an archive password, SQLite snapshots remain private, unencrypted `.sqlite` files.

See [deployment and recovery](DEPLOYMENT.md) for schedules, retention and restore steps. Credentials are intentionally absent from the repository. Provider tests use HTTP fakes; verify your own production integrations after configuration.
