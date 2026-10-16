<x-admin-layout :title="$student->exists ? __('Edit student') : __('Add student')">
<div class="page-head"><div><h1>{{ $student->exists ? __('Edit student') : __('Add student') }}</h1></div>
@if($student->exists)<div class="page-actions"><a class="button secondary" href="{{ route('school.students.show',$student) }}">{{ __('Back to student') }}</a></div>@endif</div>
<form method="post" action="{{ $student->exists ? route('school.students.update',$student) : route('school.students.store') }}" class="card">
@csrf @if($student->exists) @method('PUT') @endif
<h2><x-ui-icon name="user-round"/>{{ __('Student details') }}</h2>
<div class="form-grid">
<label>{{ __('Full name') }} *<input name="name" value="{{ old('name',$student->name) }}" required maxlength="200"></label>
<label>{{ __('National ID') }} *<input name="national_id" value="{{ old('national_id',$student->national_id) }}" required maxlength="20" dir="ltr" inputmode="numeric"></label>
<label>{{ __('Class') }}<select name="classroom_id"><option value="">{{ __('Without class') }}</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('classroom_id',$student->classroom_id)==$class->id)>{{ $class->label() }}</option>@endforeach</select></label>
<label>{{ __('Student mobile number') }}<input name="phone" value="{{ old('phone',$student->phone) }}" dir="ltr" maxlength="40"></label>
<label>{{ __('Student email') }}<input type="email" name="email" value="{{ old('email',$student->email) }}" dir="ltr"></label>
<label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$student->exists ? $student->is_active : true))>{{ __('Active student') }}</label>
</div>
<h2 style="margin-top:22px"><x-ui-icon name="users"/>{{ __('Guardian details') }}</h2>
<p class="small">{{ __('Filled automatically when a guardian registers, if left empty.') }}</p>
<div class="form-grid">
<label>{{ __('Guardian name') }}<input name="guardian_name" value="{{ old('guardian_name',$student->guardian_name) }}" maxlength="200"></label>
<label>{{ __('Relation') }}<select name="guardian_relation"><option value="">—</option>@foreach(\App\Models\Student::RELATIONS as $relation)<option value="{{ $relation }}" @selected(old('guardian_relation',$student->guardian_relation)===$relation)>{{ __('school.relation.'.$relation) }}</option>@endforeach</select></label>
<label>{{ __('Guardian mobile number') }}<input name="guardian_phone" value="{{ old('guardian_phone',$student->guardian_phone) }}" dir="ltr" maxlength="40"></label>
<label>{{ __('Guardian email') }}<input type="email" name="guardian_email" value="{{ old('guardian_email',$student->guardian_email) }}" dir="ltr"></label>
</div>
<div class="form-actions"><button><x-ui-icon name="check"/>{{ __('Save') }}</button></div>
</form>
@if($student->exists)
<form method="post" action="{{ route('school.students.destroy',$student) }}" class="card" style="margin-top:18px" onsubmit="return confirm('{{ __('Delete this student and all of their records?') }}')">@csrf @method('DELETE')
<h2><x-ui-icon name="trash-2"/>{{ __('Delete student') }}</h2><p class="small">{{ __('This permanently removes the student with their attendance, excuses and notes. Prefer marking the student inactive.') }}</p><button class="secondary">{{ __('Delete') }}</button></form>
@endif
</x-admin-layout>
