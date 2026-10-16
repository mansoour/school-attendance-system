<?php

namespace App\Settings;

use App\School\Notifier;

class SettingsRegistry
{
    public static function sections(): array
    {
        $bool = fn ($label, $default, $help = '') => compact('label', 'default', 'help') + ['type' => 'boolean', 'rules' => 'boolean'];
        $text = fn ($label, $default = '', $help = '', $rules = 'nullable|string|max:1000') => compact('label', 'default', 'help', 'rules') + ['type' => 'text'];
        $number = fn ($label, $default, $min, $max, $help = '') => compact('label', 'default', 'min', 'max', 'help') + ['type' => 'number', 'rules' => "integer|min:$min|max:$max"];
        $select = fn ($label, $default, $options, $help = '') => compact('label', 'default', 'options', 'help') + ['type' => 'select', 'rules' => 'in:'.implode(',', array_keys($options))];
        $area = fn ($label, $default = '', $help = '') => compact('label', 'default', 'help') + ['type' => 'textarea', 'rules' => 'nullable|string|max:5000'];
        $upload = fn ($label, $help = '') => compact('label', 'help') + ['default' => null, 'type' => 'file', 'rules' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048'];
        $sections = [
            'settings' => ['title' => 'Security', 'icon' => 'shield-check', 'permission' => 'settings.manage', 'description' => 'Registration, passwords, sessions, account lockout and two-factor authentication.', 'fields' => [
                'security.registration_open' => $bool('Allow new registrations', true, 'Existing members can still sign in when registration is closed.'),
                'security.email_verification' => $bool('Require email verification for new accounts', true, 'New members receive a verification link. When disabled, new accounts are verified automatically.'),
                'security.password_min' => $number('Minimum password length', 12, 8, 64, 'Applies to new passwords and password changes.'),
                'security.password_mixed' => $bool('Require uppercase and lowercase letters', false),
                'security.password_numbers' => $bool('Require a number', true),
                'security.password_symbols' => $bool('Require a symbol', false),
                'security.session_lifetime' => $number('Session lifetime in minutes', 120, 5, 43200, 'Inactive sessions expire after this period. Remember me allows a longer sign-in.'),
                'security.lockout_attempts' => $number('Failed attempts before account lockout', 10, 0, 100, 'Consecutive incorrect passwords. Set to 0 to disable account lockout. Sign-in rate limits still apply.'),
                'security.lockout_minutes' => $number('Account lockout duration in minutes', 15, 1, 1440),
                'security.admin_two_factor_required' => $bool('Require two-factor authentication for administrators', false, 'When disabled, staff may enter administration without enrolling. Accounts that already enabled two-factor authentication still use it at sign-in.'),
                'security.member_two_factor_allowed' => $bool('Allow members to enable two-factor authentication', true),
                'security.recovery_codes' => $bool('Offer recovery codes', true, 'Single-use codes for a lost authenticator. Disabling this also prevents existing recovery codes from being used.'),
                'security.recaptcha_contact' => $bool('Use reCAPTCHA on the contact form', false, 'Requires the site key and secret key in Integrations.'),
                'security.recaptcha_register' => $bool('Use reCAPTCHA on registration', false, 'Requires the site key and secret key in Integrations.'),
                'security.recaptcha_score' => $number('Minimum reCAPTCHA score', 5, 1, 9, 'A value of 5 means 0.5. Submissions below this score are refused.'),
            ]],
            'appearance' => ['title' => 'Appearance', 'icon' => 'sun-moon', 'permission' => 'settings.manage', 'description' => 'Default language and light or dark appearance.', 'fields' => [
                'appearance.locale' => $select('Default language', 'ar', ['en' => 'English', 'ar' => 'Arabic'], 'Used until visitors choose a language. Members keep their saved language.'),
                'appearance.dark_enabled' => $bool('Offer dark mode', true, 'Adds a light, dark and system theme switch to the navigation bar.'),
                'appearance.theme' => $select('Default theme', 'light', ['light' => 'Light', 'dark' => 'Dark', 'system' => 'System']),
            ]],
            'maintenance' => ['title' => 'Maintenance', 'icon' => 'construction', 'permission' => 'settings.manage', 'description' => 'Temporarily close the site while staff retain access.', 'fields' => [
                'maintenance.enabled' => $bool('Enable maintenance mode', false, 'Visitors and members see the maintenance message. Staff can sign in and continue working.'),
                'maintenance.message.en' => $area('Maintenance message in English'),
                'maintenance.message.ar' => $area('Maintenance message in Arabic'),
            ]],
            'storage' => ['title' => 'File storage', 'icon' => 'cloud', 'permission' => 'integrations.manage', 'description' => 'Choose local storage or an independent Cloudflare R2 bucket.', 'fields' => [
                'storage.r2_enabled' => $bool('Store new uploads in Cloudflare R2', false, 'Existing files stay on their original disk. Configure access keys in Integrations first.'),
                'storage.r2_account' => $text('Cloudflare account ID', '', '', 'nullable|regex:/^[a-f0-9]{32}$/i'),
                'storage.r2_bucket' => $text('R2 bucket', '', '', 'nullable|regex:/^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$/'),
            ]],
            'branding' => ['title' => 'Brand identity', 'icon' => 'badge', 'permission' => 'branding.manage', 'description' => 'Names and logos used across the website and administration.', 'fields' => [
                'branding.name.en' => $text('Site name in English', 'Tawasul', '', 'required|string|max:100'),
                'branding.name.ar' => $text('Site name in Arabic', 'منصة تواصل', '', 'nullable|string|max:100'),
                'branding.logo.en' => $upload('English logo', 'PNG, JPEG or WebP, up to 2 MB.'),
                'branding.logo.ar' => $upload('Arabic logo', 'Falls back to the English logo when not provided.'),
            ]],
            'icons' => ['title' => 'Site icons', 'icon' => 'image', 'permission' => 'branding.manage', 'description' => 'Browser tab and device icons. Square PNG images are recommended.', 'fields' => [
                'branding.favicon' => $upload('Browser tab icon', 'Use a square image, preferably 64 × 64 pixels or larger.'),
                'branding.touch_icon' => $upload('Apple touch icon', 'Recommended size: 180 × 180 pixels.'),
            ]],
            'cookies' => ['title' => 'Cookie notice', 'icon' => 'cookie', 'permission' => 'branding.manage', 'description' => 'A short notice about essential cookies and saved preferences.', 'fields' => [
                'branding.cookie_enabled' => $bool('Show the cookie notice', false),
                'branding.cookie_text.en' => $area('Notice text in English', '', 'Leave blank to use the default notice.'),
                'branding.cookie_text.ar' => $area('Notice text in Arabic', '', 'Leave blank to use the default notice.'),
            ]],
            'social' => ['title' => 'Social media links', 'icon' => 'share-2', 'permission' => 'branding.manage', 'description' => 'Only links with a saved address appear in the footer.', 'fields' => []],
            'styling' => ['title' => 'Styling', 'icon' => 'palette', 'permission' => 'branding.manage', 'description' => 'Preview colors before saving. Check contrast in both themes.', 'fields' => []],
            'fonts' => ['title' => 'Fonts', 'icon' => 'type', 'permission' => 'branding.manage', 'description' => 'Fonts are hosted locally. No Google Fonts request is sent by visitors.', 'fields' => [
                'branding.font_body' => $select('English body font', 'inter', ['inter' => 'Inter', 'system' => 'System font']),
                'branding.font_heading' => $select('English heading font', 'intertight', ['intertight' => 'Inter Tight', 'inter' => 'Inter', 'system' => 'System font']),
                'branding.font_arabic' => $select('Arabic text and headings', 'ibm', ['ibm' => 'IBM Plex Sans Arabic', 'system' => 'System font']),
            ]],
            'homepage' => ['title' => 'Homepage', 'icon' => 'house', 'permission' => 'pages.manage', 'description' => 'Neutral homepage content for your next product.', 'fields' => [
                'homepage.title.en' => $text('Heading in English', 'Better communication, stronger attendance.', '', 'required|string|max:200'),
                'homepage.title.ar' => $text('Heading in Arabic', 'بالتواصل نعزز الانتظام ونبني المستقبل'),
                'homepage.description.en' => $area('Description in English', 'A daily attendance platform connecting school staff and guardians: attendance, excuses, notes, notifications and reports in one place.'),
                'homepage.description.ar' => $area('Description in Arabic', 'منصة لمتابعة الحضور والغياب اليومي تربط منسوبات المدرسة بأولياء الأمور: تسجيل الغياب والتأخر، الأعذار، الملاحظات، الإشعارات والتقارير في مكان واحد.'),
            ]],
            'staff-alerts' => ['title' => 'Staff alerts', 'icon' => 'bell-ring', 'permission' => 'settings.manage', 'description' => 'Choose the staff roles notified when a support message arrives.', 'fields' => [
                'staff_alerts.support_roles' => ['label' => 'Recipient roles', 'type' => 'roles', 'default' => ['owner', 'support'], 'rules' => 'nullable|array', 'help' => 'Recipients must also have permission to access the support inbox.'],
            ]],
        ];
        $sections['guardians'] = ['title' => 'Guardian registration', 'icon' => 'user-plus', 'permission' => 'settings.manage', 'description' => 'How guardians register and link their accounts to students.', 'fields' => [
            'guardians.match_mode' => $select('Student verification on registration', 'national_id_name', ['national_id_name' => 'Student national ID and full name', 'national_id_phone' => 'Student national ID and student phone', 'national_id_name_phone' => 'Student national ID, full name and phone'], 'Guardians must enter matching student details to link their account.'),
            'guardians.require_approval' => $bool('Require staff approval for guardian links', false, 'When disabled, the link is approved as soon as the guardian verifies their email.'),
            'guardians.fill_student_contact' => $bool('Fill missing guardian contact details on the student record', true, 'Copies the guardian name, phone, email and relation when the school has not provided them.'),
        ]];
        $sections['school-notifications'] = ['title' => 'School notifications', 'icon' => 'bell-ring', 'permission' => 'settings.manage', 'description' => 'Turn each school event on or off, choose in-app or email delivery, and choose which staff roles receive it.', 'fields' => [
            'school_notify.absence_threshold' => $number('Repeated absence threshold (days in 30 days)', 3, 1, 30, 'Staff are alerted when a student reaches this many absences.'),
        ]];
        foreach (Notifier::events() as $event) {
            $title = ucfirst(str_replace('_', ' ', $event));
            $sections['school-notifications']['fields']["school_notify.$event.enabled"] = $bool("$title: enabled", true);
            $sections['school-notifications']['fields']["school_notify.$event.database"] = $bool("$title: in-app", true);
            $sections['school-notifications']['fields']["school_notify.$event.mail"] = $bool("$title: email", true);
            if (in_array($event, Notifier::STAFF_EVENTS, true)) {
                $sections['school-notifications']['fields']["school_notify.$event.roles"] = ['label' => "$title: recipient roles", 'type' => 'roles', 'default' => Notifier::DEFAULT_ROLES[$event], 'rules' => 'nullable|array', 'help' => ''];
            }
        }
        foreach (['x' => 'X', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'github' => 'GitHub', 'whatsapp' => 'WhatsApp'] as $key => $label) {
            $sections['social']['fields']['branding.social.'.$key] = $text($label, '', '', 'nullable|url:http,https|max:500');
        }
        $labels = ['primary' => 'Primary color', 'cream' => 'Hero and card background', 'accent' => 'Highlights', 'text' => 'Main text', 'sage' => 'Soft background', 'muted' => 'Secondary text', 'border' => 'Borders and dividers', 'surface' => 'Page and form background'];
        $colors = ['light' => ['#0E7C86', '#F6F4FF', '#8B5CF6', '#1E293B', '#E6F6F4', '#64748B', '#E6E3F3', '#FFFFFF'], 'dark' => ['#5EEAD4', '#1A1730', '#C4B5FD', '#F1F5F9', '#1F2E3A', '#A5B4C8', '#2E2A4A', '#13111F']];
        foreach ($colors as $theme => $values) {
            foreach (array_keys($labels) as $i => $token) {
                $sections['styling']['fields']["styling.$theme.$token"] = ['label' => $labels[$token], 'theme' => $theme, 'default' => $values[$i], 'type' => 'color', 'rules' => 'regex:/^#[0-9a-fA-F]{6}$/'];
            }
        }

        return $sections;
    }

    public static function defaults(): array
    {
        $defaults = [];
        foreach (self::sections() as $section) {
            foreach ($section['fields'] as $key => $field) {
                $defaults[$key] = $field['default'];
            }
        }

        return $defaults + ['integrations.gemini_model' => 'gemini-2.5-flash'];
    }
}
