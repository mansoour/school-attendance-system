<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Client feedback: student affairs manages students only. Grades and classes
 * belong to the principal and the system administrator, and guardian details
 * on the student form are limited to roles holding students.guardian_details.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $guardianDetails = Permission::firstOrCreate(['name' => 'students.guardian_details', 'guard_name' => 'web']);
        foreach (['owner', 'principal'] as $name) {
            Role::where('name', $name)->where('guard_name', 'web')->first()?->givePermissionTo($guardianDetails);
        }
        $affairs = Role::where('name', 'student_affairs')->where('guard_name', 'web')->first();
        foreach (['grades.manage', 'classes.manage'] as $permission) {
            if ($affairs?->hasPermissionTo($permission)) {
                $affairs->revokePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::where('name', 'student_affairs')->where('guard_name', 'web')->first()?->givePermissionTo(['grades.manage', 'classes.manage']);
        Permission::where('name', 'students.guardian_details')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
