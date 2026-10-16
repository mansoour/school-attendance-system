<?php

namespace Database\Seeders;

use App\Models\Page;
use App\School\Roles;
use App\Support\DefaultContent;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public const SYSTEM_PERMISSIONS = ['admin.access', 'users.manage', 'roles.manage', 'pages.manage', 'notices.manage', 'email_templates.manage', 'emails.view', 'support.access', 'settings.manage', 'branding.manage', 'integrations.manage', 'health.view', 'audit.view', 'translations.edit', 'translations.publish'];

    public function run(): void
    {
        $permissions = array_merge(self::SYSTEM_PERMISSIONS, Roles::PERMISSIONS);
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web'])->givePermissionTo($permissions);
        foreach (['support' => ['admin.access', 'support.access', 'users.manage'], 'translator' => ['admin.access', 'translations.edit', 'translations.publish'], 'content_reviewer' => ['admin.access', 'pages.manage', 'notices.manage']] as $name => $grants) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])->givePermissionTo($grants);
        }
        foreach (Roles::grants() as $name => $grants) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if ($role->wasRecentlyCreated) {
                $role->givePermissionTo($name === 'principal' ? [...$grants, 'audit.view'] : $grants);
            }
        }
        Page::firstOrCreate(['slug' => 'about'], self::aboutPage());
        Page::firstOrCreate(['slug' => 'cookies'], ['title' => ['en' => 'Cookie policy', 'ar' => 'سياسة ملفات الارتباط'], 'body' => ['en' => 'Tawasul uses only essential cookies to keep you signed in, protect forms and remember your language and theme.', 'ar' => 'تستخدم منصة تواصل ملفات الارتباط الضرورية فقط لإبقائك مسجلًا للدخول وحماية النماذج وحفظ اللغة والمظهر.'], 'is_published' => true]);
        DefaultContent::install();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return array<string, mixed> */
    public static function aboutPage(): array
    {
        return [
            'title' => ['en' => 'About Tawasul', 'ar' => 'عن منصة تواصل'],
            'body' => [
                'ar' => <<<'MD'
## منصة تواصل: بالتواصل نعزز الانتظام ونبني المستقبل

منصة تواصل نظام لمتابعة الحضور والغياب اليومي للطالبات، يربط منسوبات المدرسة بأولياء الأمور في مكان واحد: تسجيل الغياب والتأخر، استقبال الأعذار ومراجعتها، الملاحظات الصحية والإرشادية، التواصل الفوري عبر واتساب والاتصال والبريد، والتقارير والإحصائيات.

### فكرة المنصة وابتكارها

**أ. عزيزة محمد عبدالله القرني**

### المدرسة

**مدرسة الابتدائية الخامسة عشرة والمتوسطة السابعة**

### مديرة المدرسة

**أ. صالحة حجلاء**

### أهداف المنصة

- تعزيز انتظام الطالبات وتقليل الغياب والتأخر.
- إشراك أولياء الأمور وإبلاغهم فور تسجيل الغياب أو التأخر.
- توحيد العمل بين مديرة المدرسة والوكيلات وشؤون الطالبات والموجه الصحي والموجهة الطلابية.
- توثيق الإجراءات وحفظ السجلات وسريتها.
- دعم اتخاذ القرار بتقارير وإحصائيات دقيقة.

معًا نحو بيئة تعليمية آمنة ومستقبل مشرق.
MD,
                'en' => <<<'MD'
## Tawasul: communication builds commitment

Tawasul is a daily student attendance platform that connects school staff and guardians in one place: recording absences and late arrivals, receiving and reviewing excuses, health and counseling notes, instant contact by WhatsApp, phone and email, and reports and statistics.

### Idea and innovation

**Ms. Aziza Mohammed Abdullah Al-Qarni**

### School

**Fifteenth Primary and Seventh Intermediate School**

### School principal

**Ms. Saleha Hajla**

### Goals

- Strengthen student attendance and reduce absence and lateness.
- Involve guardians and inform them as soon as an absence or late arrival is recorded.
- Unify the work of the principal, vice principals, student affairs, the health advisor and the student counselor.
- Document every action while keeping records confidential.
- Support decisions with accurate reports and statistics.

Together towards a safe learning environment and a bright future.
MD,
            ],
            'is_published' => true,
        ];
    }
}
