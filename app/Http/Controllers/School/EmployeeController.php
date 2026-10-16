<?php

namespace App\Http\Controllers\School;

use App\Models\School;
use App\Models\User;
use App\School\Roles;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Principals and the system administrator create staff accounts. New employees
 * receive a password-setup link by email; no password is shared.
 */
class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = $this->context()->scope(User::query())->role(Roles::STAFF)->with('roles', 'school')
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($q) => $q->where('name', 'like', "%$t%")->orWhere('email', 'like', "%$t%")))
            ->when(in_array($request->query('role'), Roles::STAFF, true), fn ($q) => $q->role($request->query('role')))
            ->orderBy('name')->paginate(30)->withQueryString();

        return view('school.employees', ['employees' => $employees, 'roles' => $this->assignableRoles($request->user()), 'schools' => $request->user()->isSystemAdmin() ? School::orderBy('name')->get() : collect()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'job_title' => $data['job_title'] ?? null,
                'school_id' => $this->schoolFor($request, $data), 'password' => Str::password(32), 'locale' => app()->getLocale(), 'is_active' => true,
            ]);
            $user->markEmailAsVerified();
            $user->syncRoles([$data['role']]);
            app(AuditLogger::class)->record('employees.created', $user, ['role' => $data['role']]);

            return $user;
        });
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', __('Account created. A password setup link was emailed to the employee.'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeEmployee($request, $user);
        $data = $this->validated($request, $user);
        $user->update(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'job_title' => $data['job_title'] ?? null, 'is_active' => $request->boolean('is_active'), 'school_id' => $this->schoolFor($request, $data, $user)]);
        $user->syncRoles([$data['role']]);
        app(AuditLogger::class)->record('employees.updated', $user, ['role' => $data['role'], 'active' => $user->is_active]);
        if (! $user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return back()->with('status', __('Saved.'));
    }

    public function reset(Request $request, User $user)
    {
        $this->authorizeEmployee($request, $user);
        Password::sendResetLink(['email' => $user->email]);
        app(AuditLogger::class)->record('employees.password_link_sent', $user);

        return back()->with('status', __('A password reset link was emailed.'));
    }

    private function assignableRoles(User $actor): array
    {
        return $actor->isSystemAdmin() ? Roles::STAFF : array_values(array_diff(Roles::STAFF, ['principal']));
    }

    private function authorizeEmployee(Request $request, User $user): void
    {
        abort_if($user->hasRole('owner') || $user->id === $request->user()->id, 403);
        abort_unless($user->hasAnyRole(Roles::STAFF), 404);
        $this->context()->authorize($user);
        abort_if($user->hasRole('principal') && ! $request->user()->isSystemAdmin(), 403);
    }

    private function schoolFor(Request $request, array $data, ?User $user = null): int
    {
        if ($request->user()->isSystemAdmin()) {
            return (int) ($data['school_id'] ?? $user?->school_id ?? $this->context()->requireId());
        }

        return $this->context()->requireId();
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:200',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'phone' => 'nullable|string|max:40',
            'job_title' => 'nullable|string|max:120',
            'role' => ['required', Rule::in($this->assignableRoles($request->user()))],
            'school_id' => 'nullable|integer|exists:schools,id',
            'is_active' => 'boolean',
        ]);
    }
}
