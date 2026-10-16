<?php

namespace App\Settings;

use Illuminate\Support\Facades\DB;

class SettingsService
{
    public const DEFAULTS = [
        'security.registration_open' => true, 'security.email_verification' => true,
        'security.admin_two_factor_required' => false, 'security.recovery_codes' => true,
        'security.lockout_attempts' => 10, 'security.lockout_minutes' => 15,
        'branding.name.en' => 'Tawasul', 'branding.name.ar' => 'منصة تواصل',
        'branding.color' => '#315a50', 'branding.logo.en' => null, 'branding.logo.ar' => null,
        'homepage.title.en' => 'Better communication, stronger attendance.', 'homepage.title.ar' => 'بالتواصل نعزز الانتظام ونبني المستقبل',
        'homepage.description.en' => 'A daily attendance platform connecting school staff and guardians: attendance, excuses, notes, notifications and reports in one place.', 'homepage.description.ar' => 'منصة لمتابعة الحضور والغياب اليومي تربط منسوبات المدرسة بأولياء الأمور: تسجيل الغياب والتأخر، الأعذار، الملاحظات، الإشعارات والتقارير في مكان واحد.',
        'staff_alerts.support_roles' => ['owner', 'support'],
    ];

    public function get(string $key): mixed
    {
        $value = DB::table('settings')->where('key', $key)->value('value');

        return $value === null ? (self::defaults()[$key] ?? null) : json_decode($value, true);
    }

    public function set(string $key, mixed $value): void
    {
        if (! array_key_exists($key, self::defaults())) {
            throw new \InvalidArgumentException('Unknown setting');
        }
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'created_at' => now(), 'updated_at' => now()]);
    }

    public static function defaults(): array
    {
        return array_replace(self::DEFAULTS, SettingsRegistry::defaults());
    }
}
