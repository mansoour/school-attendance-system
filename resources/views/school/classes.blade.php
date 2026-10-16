<x-admin-layout :title="__('Classes')">
<div class="page-head"><div><h1>{{ __('Classes') }}</h1><p>{{ __('Each grade is divided into classes (A, B, C…). Attendance is taken daily per class.') }}</p></div></div>
<form method="get" class="toolbar"><label>{{ __('Grade') }}<select name="grade" onchange="this.form.submit()"><option value="">{{ __('All grades') }}</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(request('grade')==$grade->id)>{{ $grade->name }}</option>@endforeach</select></label></form>
<div class="grid-3">
<div class="table-wrap"><table><thead><tr><th>{{ __('Grade') }}</th><th>{{ __('Class') }}</th><th class="num">{{ __('Students') }}</th><th class="num">{{ __('Capacity') }}</th><th>{{ __('Supervisor') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($classes as $class)
<tr><td>{{ $class->grade?->name }}</td><td><strong>{{ $class->name }}</strong></td><td class="num"><a href="{{ route('school.students.index',['classroom'=>$class->id]) }}">{{ $class->students_count }}</a></td><td class="num">{{ $class->capacity ?? '—' }}</td><td class="small">{{ $class->supervisor?->name ?? '—' }}</td>
<td><details class="row-details"><summary>{{ __('Edit') }}</summary>
<form method="post" action="{{ route('school.classes.update',$class) }}">@csrf @method('PUT')
<select name="grade_id">@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected($class->grade_id===$grade->id)>{{ $grade->name }}</option>@endforeach</select>
<input name="name" value="{{ $class->name }}" required><input type="number" name="capacity" value="{{ $class->capacity }}" min="1" max="200" placeholder="{{ __('Capacity') }}">
<select name="supervisor_id"><option value="">{{ __('No supervisor') }}</option>@foreach($staff as $member)<option value="{{ $member->id }}" @selected($class->supervisor_id===$member->id)>{{ $member->name }}</option>@endforeach</select>
<button class="tiny">{{ __('Save') }}</button></form>
<form method="post" action="{{ route('school.classes.destroy',$class) }}" onsubmit="return confirm('{{ __('Delete this class?') }}')">@csrf @method('DELETE')<button class="tiny secondary"><x-ui-icon name="trash-2"/>{{ __('Delete') }}</button></form></details></td></tr>
@empty<tr><td colspan="6" class="empty">{{ __('No classes yet.') }}</td></tr>@endforelse
</tbody></table></div>
<form method="post" action="{{ route('school.classes.store') }}" class="card stack-sm">@csrf<h2><x-ui-icon name="plus"/>{{ __('Add classes') }}</h2>
@if($grades->isEmpty())<p class="small">{{ __('Add a grade first.') }} <a href="{{ route('school.grades.index') }}">{{ __('Grades') }}</a></p>@else
<label class="field">{{ __('Grade') }}<select name="grade_id" required>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(request('grade')==$grade->id)>{{ $grade->name }}</option>@endforeach</select></label>
<label class="field">{{ __('Class names') }}<input name="name" required maxlength="120" placeholder="أ، ب، ج"><small class="muted">{{ __('Separate several classes with commas.') }}</small></label>
<label class="field">{{ __('Capacity') }}<input type="number" name="capacity" min="1" max="200"></label>
<label class="field">{{ __('Supervisor') }}<select name="supervisor_id"><option value="">{{ __('No supervisor') }}</option>@foreach($staff as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></label>
<button><x-ui-icon name="check"/>{{ __('Add') }}</button>@endif</form>
</div>
</x-admin-layout>
