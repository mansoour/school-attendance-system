<?php

namespace App\Settings;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SecretStore
{
    public const KEYS = ['mail_password', 'storage_access_key', 'storage_secret', 'resend_api_key', 'resend_webhook_secret', 'gemini_api_key', 'backup_archive_password', 'r2_access_key_id', 'r2_secret_access_key', 'recaptcha_site_key', 'recaptcha_secret_key'];

    public function put(string $key, ?string $value): void
    {
        abort_unless(in_array($key, self::KEYS, true), 422);
        if ($value === null) {
            DB::table('integration_secrets')->where('key', $key)->delete();

            return;
        }
        DB::table('integration_secrets')->updateOrInsert(['key' => $key], ['value' => Crypt::encryptString($value), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function get(string $key): ?string
    {
        $value = DB::table('integration_secrets')->where('key', $key)->value('value');

        return $value ? Crypt::decryptString($value) : null;
    }

    public function configured(): array
    {
        return DB::table('integration_secrets')->pluck('key')->all();
    }
}
