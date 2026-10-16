<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the starter kit's optional plans and subscriptions, which the school
 * system does not use, and replaces the placeholder About page.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['feature_usages', 'subscriptions', 'feature_plan', 'features', 'plans'] as $table) {
            Schema::dropIfExists($table);
        }
        DB::table('settings')->whereIn('key', ['plans.enabled', 'homepage.show_pricing'])->delete();
        DB::table('email_templates')->where('event', 'plan_changed')->delete();
        DB::table('pages')->where('slug', 'refunds')->delete();

        if (Schema::hasTable('permissions')) {
            $ids = DB::table('permissions')->whereIn('name', ['billing.manage', 'plans.manage'])->pluck('id');
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
            $role = DB::table('roles')->where('name', 'billing')->value('id');
            if ($role) {
                DB::table('model_has_roles')->where('role_id', $role)->delete();
                DB::table('role_has_permissions')->where('role_id', $role)->delete();
                DB::table('roles')->where('id', $role)->delete();
            }
        }

        $about = DatabaseSeeder::aboutPage();
        $existing = DB::table('pages')->where('slug', 'about')->first();
        if ($existing && str_contains((string) $existing->body, 'starter content')) {
            DB::table('pages')->where('slug', 'about')->update(['title' => json_encode($about['title'], JSON_UNESCAPED_UNICODE), 'body' => json_encode($about['body'], JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Plans were optional and empty by default; recreate them from the original starter migration if needed.
    }
};
