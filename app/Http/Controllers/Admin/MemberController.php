<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\MemberNotification;
use App\Services\Audit\AuditLogger;
use App\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles', 'subscriptions.plan')->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')))
            ->when($request->query('staff') === '1', fn ($q) => $q->permission('admin.access'))
            ->when(in_array($request->query('marketing'), ['0', '1'], true), fn ($q) => $q->where('marketing_opt_in', $request->query('marketing')));
        if (app(SettingsService::class)->get('plans.enabled') && $request->filled('plan')) {
            $query->whereHas('subscriptions', fn ($q) => $q->where('plan_id', $request->query('plan'))->where('status', 'active')->where('starts_at', '<=', now())->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now())));
        }

        return view('admin.members', ['users' => $query->latest()->paginate(20)->withQueryString(), 'roles' => Role::all(), 'plans' => Plan::all()]);
    }

    public function unlock(User $user)
    {
        $user->forceFill(['locked_until' => null, 'failed_login_attempts' => 0])->save();
        app(AuditLogger::class)->record('members.unlocked', $user);

        return back()->with('status', __('Account unlocked.'));
    }

    public function roles()
    {
        return view('admin.roles', ['roles' => Role::where('guard_name', 'web')->with('permissions')->withCount('users')->orderBy('name')->get(), 'permissions' => Permission::where('guard_name', 'web')->orderBy('name')->get()]);
    }

    public function saveRole(Request $request)
    {
        $data = $this->validateRole($request);
        DB::transaction(function () use ($data) {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
            $role->syncPermissions($data['permissions'] ?? []);
            app(AuditLogger::class)->record('roles.saved', $role);
        });

        return back()->with('status', __('Saved.'));
    }

    public function deleteRole(Role $role)
    {
        abort_unless($role->guard_name === 'web', 404);
        abort_if($role->name === 'owner', 409);
        DB::transaction(function () use ($role) {
            $settings = app(SettingsService::class);
            $settings->set('staff_alerts.support_roles', array_values(array_diff($settings->get('staff_alerts.support_roles'), [$role->name])));
            app(AuditLogger::class)->record('roles.deleted', $role);
            $role->delete();
        });

        return back()->with('status', __('Deleted.'));
    }

    public function updateRole(Request $request, Role $role)
    {
        abort_unless($role->guard_name === 'web', 404);
        abort_if($role->name === 'owner', 403);
        $data = $this->validateRole($request, $role);
        DB::transaction(function () use ($data, $role) {
            $oldName = $role->name;
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);
            $settings = app(SettingsService::class);
            $settings->set('staff_alerts.support_roles', array_map(fn ($name) => $name === $oldName ? $role->name : $name, $settings->get('staff_alerts.support_roles')));
            app(AuditLogger::class)->record('roles.saved', $role);
        });

        return back()->with('status', __('Saved.'));
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        abort_if(is_string($request->input('name')) && strtolower(trim($request->input('name'))) === 'owner', 403);

        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id)],
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);
    }

    public function assignRole(Request $request, User $user)
    {
        $data = $request->validate(['roles' => 'nullable|array', 'roles.*' => ['string', Rule::exists('roles', 'name')]]);
        abort_if($user->hasRole('owner') || in_array('owner', $data['roles'] ?? []), 403);
        $user->syncRoles($data['roles'] ?? []);
        app(AuditLogger::class)->record('members.roles_updated', $user);

        return back()->with('status', __('Saved.'));
    }

    public function subscribe(Request $request, User $user)
    {
        $data = $request->validate(['plan_id' => 'required|exists:plans,id', 'starts_at' => 'required|date', 'ends_at' => 'nullable|date|after:starts_at']);
        DB::transaction(function () use ($data, $user, $request) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $user->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);
            $subscription = $user->subscriptions()->create($data + ['created_by' => $request->user()->id, 'source' => 'manual']);
            app(AuditLogger::class)->record('subscription.assigned', $subscription);
            $user->notify(new MemberNotification('plan_changed'));
        });

        return back()->with('status', __('Saved.'));
    }

    public function cancel(Subscription $subscription)
    {
        $subscription->update(['status' => 'cancelled']);
        app(AuditLogger::class)->record('subscription.cancelled', $subscription);
        $subscription->user->notify(new MemberNotification('plan_changed'));

        return back()->with('status', __('Saved.'));
    }
}
