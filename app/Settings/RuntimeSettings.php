<?php

namespace App\Settings;

class RuntimeSettings
{
    public function apply(): void
    {
        $settings = app(SettingsService::class);
        $secrets = app(SecretStore::class);
        config(['session.lifetime' => $settings->get('security.session_lifetime')]);
        foreach (['resend_api_key' => 'services.resend.key', 'backup_archive_password' => 'backup.backup.password', 'mail_password' => 'mail.mailers.smtp.password'] as $key => $path) {
            if ($value = $secrets->get($key)) {
                config([$path => $value]);
            }
        }
        config(['filesystems.disks.r2' => [
            'driver' => 's3', 'key' => $secrets->get('r2_access_key_id'), 'secret' => $secrets->get('r2_secret_access_key'),
            'region' => 'auto', 'bucket' => $settings->get('storage.r2_bucket'),
            'endpoint' => 'https://'.$settings->get('storage.r2_account').'.r2.cloudflarestorage.com',
            'use_path_style_endpoint' => true, 'throw' => true,
        ]]);
    }
}
