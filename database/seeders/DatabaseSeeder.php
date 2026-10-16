<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Page;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = ['admin.access', 'users.manage', 'roles.manage', 'billing.manage', 'plans.manage', 'pages.manage', 'notices.manage', 'email_templates.manage', 'emails.view', 'support.access', 'settings.manage', 'branding.manage', 'integrations.manage', 'health.view', 'audit.view', 'translations.edit', 'translations.publish'];
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        foreach (['owner' => $permissions, 'support' => ['admin.access', 'support.access', 'users.manage'], 'billing' => ['admin.access', 'users.manage', 'billing.manage', 'plans.manage'], 'translator' => ['admin.access', 'translations.edit', 'translations.publish'], 'content_reviewer' => ['admin.access', 'pages.manage', 'notices.manage']] as $name => $grants) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->givePermissionTo($grants);
        }
        $plan = Plan::firstOrCreate(['code' => 'free'], ['name' => ['en' => 'Free', 'ar' => 'مجاني'], 'description' => ['en' => 'The essentials to get started.', 'ar' => 'الأساسيات للبدء.'], 'is_free' => true, 'is_default' => true]);
        $feature = Feature::firstOrCreate(['code' => 'reports'], ['name' => ['en' => 'Example reports', 'ar' => 'تقارير تجريبية']]);
        if (! $plan->features()->whereKey($feature->id)->exists()) {
            $plan->features()->attach($feature->id, ['allowance' => 10]);
        }
        foreach (['about' => ['About', 'من نحن'], 'help' => ['Help', 'المساعدة'], 'privacy' => ['Privacy', 'الخصوصية'], 'cookies' => ['Cookie policy', 'سياسة ملفات الارتباط'], 'refunds' => ['Refund policy', 'سياسة الاسترداد'], 'terms' => ['Terms', 'الشروط']] as $slug => $titles) {
            Page::firstOrCreate(['slug' => $slug], ['title' => ['en' => $titles[0], 'ar' => $titles[1]], 'body' => ['en' => 'This is starter content. Replace it with your organization’s information before launch.', 'ar' => 'هذا محتوى أولي. استبدله بمعلومات مؤسستك قبل الإطلاق.'], 'is_published' => true]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
