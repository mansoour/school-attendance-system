<x-admin-layout :title="__('Students')">
@php($canManage=auth()->user()->can('students.manage'))
<div class="page-head"><div><h1>{{ __('Students') }}</h1><p>{{ $students->total() }} {{ __('students') }}</p></div>
<div class="page-actions">@can('students.import')<a class="button secondary" href="{{ route('school.students.import') }}"><x-ui-icon name="file-spreadsheet"/>{{ __('Import from Excel') }}</a>@endcan @if($canManage)<a class="button" href="{{ route('school.students.create') }}"><x-ui-icon name="user-plus"/>{{ __('Add student') }}</a>@endif</div></div>
<form method="get" class="toolbar">
<label class="grow">{{ __('Search') }}<input name="q" value="{{ request('q') }}" placeholder="{{ __('Name, national ID or phone') }}"></label>
<label>{{ __('Grade') }}<select name="grade"><option value="">{{ __('All grades') }}</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(request('grade')==$grade->id)>{{ $grade->name }}</option>@endforeach</select></label>
<label>{{ __('Class') }}<select name="classroom"><option value="">{{ __('All classes') }}</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(request('classroom')==$class->id)>{{ $class->label() }}</option>@endforeach</select></label>
<label class="check-row" style="min-width:auto"><input type="checkbox" name="unassigned" value="1" @checked(request('unassigned')==='1')>{{ __('Without class') }}</label>
<button><x-ui-icon name="search"/>{{ __('Search') }}</button>
</form>
<form method="post" action="{{ route('school.students.move') }}" x-data="{selected:[]}">
@csrf
@if($canManage)<div class="toolbar" x-show="selected.length" x-cloak style="align-items:center"><span class="small"><span x-text="selected.length"></span> {{ __('selected') }}</span><label>{{ __('Move to class') }}<select name="classroom_id"><option value="">{{ __('Without class') }}</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->label() }}</option>@endforeach</select></label><button class="tiny"><x-ui-icon name="check"/>{{ __('Move') }}</button></div>@endif
<div class="table-wrap"><table><thead><tr>@if($canManage)<th></th>@endif<th>{{ __('Student') }}</th><th>{{ __('National ID') }}</th><th>{{ __('Class') }}</th><th>{{ __('Guardian') }}</th><th class="num">{{ __('Absences') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($students as $student)
<tr>@if($canManage)<td><input type="checkbox" name="students[]" value="{{ $student->id }}" x-model="selected" aria-label="{{ $student->name }}"></td>@endif
<td><a href="{{ route('school.students.show',$student) }}"><strong>{{ $student->name }}</strong></a>@unless($student->is_active)<br><span class="pill">{{ __('Inactive') }}</span>@endunless</td>
<td dir="ltr" class="small">{{ $student->national_id }}</td><td>{{ $student->classroom?->label() ?? '—' }}</td>
<td class="small">{{ $student->guardian_name ?? '—' }}<br><span dir="ltr" class="muted">{{ $student->guardian_phone }}</span></td>
<td class="num">{{ $student->absences_count }}</td>
<td><div class="contact-buttons"><a href="{{ route('school.students.show',$student) }}" title="{{ __('View') }}"><x-ui-icon name="eye"/></a>@if($canManage)<a href="{{ route('school.students.edit',$student) }}" title="{{ __('Edit') }}"><x-ui-icon name="pencil"/></a>@endif</div></td></tr>
@empty<tr><td colspan="7" class="empty"><x-ui-icon name="users"/><br>{{ __('No students found.') }}</td></tr>@endforelse
</tbody></table></div>
</form>
{{ $students->links() }}
</x-admin-layout>
