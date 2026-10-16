<x-admin-layout :title="__('Schools')">
<div class="page-head"><div><h1>{{ __('Schools') }}</h1><p>{{ __('Every school has its own grades, classes, students and staff.') }}</p></div></div>
<div class="grid-3">
<div class="table-wrap"><table><thead><tr><th>{{ __('School') }}</th><th class="num">{{ __('Students') }}</th><th class="num">{{ __('Classes') }}</th><th class="num">{{ __('Accounts') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($schools as $school)
<tr><td><strong>{{ $school->name }}</strong><br><small class="muted">{{ $school->city }} {{ $school->code ? '· '.$school->code : '' }}</small></td><td class="num">{{ $school->students_count }}</td><td class="num">{{ $school->classrooms_count }}</td><td class="num">{{ $school->users_count }}</td>
<td>@if($school->is_active)<span class="pill tone-excused">{{ __('Active') }}</span>@else<span class="pill tone-absent">{{ __('Inactive') }}</span>@endif</td>
<td><details class="row-details"><summary>{{ __('Edit') }}</summary>
<form method="post" action="{{ route('school.schools.update',$school) }}">@csrf @method('PUT')@include('school.partials.school-fields',['school'=>$school,'full'=>true])<button class="tiny">{{ __('Save') }}</button></form>
<form method="post" action="{{ route('school.switch') }}">@csrf<input type="hidden" name="school_id" value="{{ $school->id }}"><button class="tiny secondary">{{ __('Work in this school') }}</button></form>
<form method="post" action="{{ route('school.schools.destroy',$school) }}" onsubmit="return confirm('{{ __('Delete this school?') }}')">@csrf @method('DELETE')<button class="tiny secondary"><x-ui-icon name="trash-2"/>{{ __('Delete') }}</button></form></details></td></tr>
@empty<tr><td colspan="6" class="empty">{{ __('No schools yet. Add the first school to begin.') }}</td></tr>@endforelse
</tbody></table></div>
<form method="post" action="{{ route('school.schools.store') }}" class="card stack-sm">@csrf<h2><x-ui-icon name="plus"/>{{ __('Add school') }}</h2>@include('school.partials.school-fields',['school'=>new \App\Models\School(['day_starts_at'=>'07:00','late_after_minutes'=>15,'is_active'=>true]),'full'=>true])<button><x-ui-icon name="check"/>{{ __('Add') }}</button></form>
</div>
</x-admin-layout>
