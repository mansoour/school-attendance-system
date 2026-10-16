<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function signInOwner(): void
    {
        $owner = User::factory()->create(['locale' => 'ar']);
        $owner->assignRole('owner');
        $this->actingAs($owner)->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_role_can_be_created_renamed_and_deleted_with_assignments(): void
    {
        $this->signInOwner();
        $this->get('/admin/roles')->assertOk()->assertSee('value="users.manage"', false)->assertDontSee('value="إدارة الأعضاء"', false);
        $this->post('/admin/roles', ['name' => 'Editors', 'permissions' => ['admin.access', 'pages.manage']])->assertSessionHasNoErrors();
        $role = Role::findByName('Editors');
        $member = User::factory()->create();
        $member->assignRole($role, 'support');
        app(SettingsService::class)->set('staff_alerts.support_roles', ['Editors', 'support']);
        $this->patch('/admin/roles/'.$role->id, ['name' => 'Content team', 'permissions' => ['admin.access', 'notices.manage']])->assertSessionHasNoErrors();
        $this->assertTrue($member->fresh()->hasRole('Content team'));
        $this->assertTrue($role->fresh()->hasPermissionTo('notices.manage'));
        $this->assertFalse($role->fresh()->hasPermissionTo('pages.manage'));
        $this->assertSame(['Content team', 'support'], app(SettingsService::class)->get('staff_alerts.support_roles'));
        $this->delete('/admin/roles/'.$role->id)->assertRedirect();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseMissing('model_has_roles', ['role_id' => $role->id]);
        $this->assertTrue($member->fresh()->hasRole('support'));
        $this->assertSame(['support'], app(SettingsService::class)->get('staff_alerts.support_roles'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'roles.deleted', 'target_id' => (string) $role->id]);
    }

    public function test_duplicate_creation_and_renaming_cannot_overwrite_other_roles(): void
    {
        $this->signInOwner();
        $this->post('/admin/roles', ['name' => 'support', 'permissions' => []])->assertSessionHasErrors('name');
        $support = Role::findByName('support');
        $this->assertTrue($support->hasPermissionTo('support.access'));
        $this->patch('/admin/roles/'.$support->id, ['name' => 'translator', 'permissions' => []])->assertSessionHasErrors('name');
        $this->assertSame('support', $support->fresh()->name);
    }

    public function test_owner_is_protected_and_role_mutations_require_authorization_and_confirmation(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $role = Role::findByName('support');
        $this->actingAs($owner)->patch('/admin/roles/'.$role->id, ['name' => 'Changed'])->assertRedirect(route('password.confirm'));
        $this->withSession(['auth.password_confirmed_at' => time()]);
        $this->patch('/admin/roles/'.Role::findByName('owner')->id, ['name' => 'Changed'])->assertForbidden();
        $this->patch('/admin/roles/'.$role->id, ['name' => 'owner'])->assertForbidden();
        $this->delete('/admin/roles/'.Role::findByName('owner')->id)->assertConflict();
        $staff = User::factory()->create();
        $staff->assignRole('support');
        $this->actingAs($staff)->post('/admin/roles', ['name' => 'Changed'])->assertForbidden();
        $this->patch('/admin/roles/'.$role->id, ['name' => 'Changed'])->assertForbidden();
        $this->delete('/admin/roles/'.$role->id)->assertForbidden();
    }
}
