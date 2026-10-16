<x-admin-layout :title="__('Employees')">
<div class="page-head"><div><h1>{{ __('Employees') }}</h1><p>{{ __('Create staff accounts. Employees receive an email link to set their own password.') }}</p></div></div>
<form method="get" class="toolbar">
<label class="grow">{{ __('Search') }}<input name="q" value="{{ request('q') }}" placeholder="{{ __('Name or email') }}"></label>
<label>{{ __('Role') }}<select name="role"><option value="">{{ __('All roles') }}</option>@foreach(\App\School\Roles::STAFF as $role)<option value="{{ $role }}" @selected(request('role')===$role)>{{ __('school.roles.'.$role) }}</option>@endforeach</select></label>
<button><x-ui-icon name="search"/>{{ __('Search') }}</button></form>
<div class="grid-3">
<div class="table-wrap"><table><thead><tr><th>{{ __('Employee') }}</th><th>{{ __('Role') }}</th>@if($schools->isNotEmpty())<th>{{ __('School') }}</th>@endif<th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($employees as $employee)
@php($role=$employee->roles->pluck('name')->first(fn($r)=>in_array($r,\App\School\Roles::STAFF)))
@php($editable=$employee->id!==auth()->id() && in_array($role,$roles))
<tr><td><strong>{{ $employee->name }}</strong><br><small class="muted" dir="ltr">{{ $employee->email }}</small>@if($employee->job_title)<br><small>{{ $employee->job_title }}</small>@endif</td>
<td><span class="pill tone-present">{{ __('school.roles.'.$role) }}</span></td>@if($schools->isNotEmpty())<td class="small">{{ $employee->school?->name }}</td>@endif
<td>@if($employee->is_active)<span class="pill tone-excused">{{ __('Active') }}</span>@else<span class="pill tone-absent">{{ __('Inactive') }}</span>@endif</td>
<td>@if($editable)<details class="row-details"><summary>{{ __('Edit') }}</summary>
<form method="post" action="{{ route('school.employees.update',$employee) }}">@csrf @method('PUT')
<input name="name" value="{{ $employee->name }}" required><input type="email" name="email" value="{{ $employee->email }}" required dir="ltr"><input name="phone" value="{{ $employee->phone }}" placeholder="{{ __('Mobile number') }}" dir="ltr"><input name="job_title" value="{{ $employee->job_title }}" placeholder="{{ __('Job title') }}">
<select name="role">@foreach($roles as $option)<option value="{{ $option }}" @selected($role===$option)>{{ __('school.roles.'.$option) }}</option>@endforeach</select>
@if($schools->isNotEmpty())<select name="school_id">@foreach($schools as $school)<option value="{{ $school->id }}" @selected($employee->school_id===$school->id)>{{ $school->name }}</option>@endforeach</select>@endif
<label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($employee->is_active)>{{ __('Active') }}</label>
<button class="tiny">{{ __('Save') }}</button></form>
<form method="post" action="{{ route('school.employees.reset',$employee) }}">@csrf<button class="tiny secondary"><x-ui-icon name="mail"/>{{ __('Send password link') }}</button></form></details>@endif</td></tr>
@empty<tr><td colspan="5" class="empty">{{ __('No employees yet.') }}</td></tr>@endforelse
</tbody></table>{{ $employees->links() }}</div>
<form method="post" action="{{ route('school.employees.store') }}" class="card stack-sm">@csrf<h2><x-ui-icon name="user-plus"/>{{ __('Add employee') }}</h2>
<label class="field">{{ __('Full name') }}<input name="name" required value="{{ old('name') }}"></label>
<label class="field">{{ __('Email') }}<input type="email" name="email" required dir="ltr" value="{{ old('email') }}"></label>
<label class="field">{{ __('Mobile number') }}<input name="phone" dir="ltr" value="{{ old('phone') }}"></label>
<label class="field">{{ __('Job title') }}<input name="job_title" value="{{ old('job_title') }}"></label>
<label class="field">{{ __('Role') }}<select name="role" required>@foreach($roles as $role)<option value="{{ $role }}" @selected(old('role')===$role)>{{ __('school.roles.'.$role) }}</option>@endforeach</select></label>
@if($schools->isNotEmpty())<label class="field">{{ __('School') }}<select name="school_id" required>@foreach($schools as $school)<option value="{{ $school->id }}" @selected((int) session('school_id')===$school->id)>{{ $school->name }}</option>@endforeach</select></label>@endif
<button><x-ui-icon name="check"/>{{ __('Create account') }}</button></form>
</div>
</x-admin-layout>
