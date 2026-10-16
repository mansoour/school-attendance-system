<x-admin-layout :title="__('Class attendance')">
@php($canTake=auth()->user()->can('attendance.take'))
<div class="page-head"><div><h1>{{ $classroom->label() }}</h1><p>{{ __('Attendance for') }} {{ $date->locale(app()->getLocale())->translatedFormat('l j F Y') }}</p></div>
<form method="get" class="page-actions" x-data>
<select aria-label="{{ __('Class') }}" @change="window.location=$event.target.value">@foreach($classes as $option)<option value="{{ route('school.attendance.take',[$option,'date'=>$date->toDateString()]) }}" @selected($option->id===$classroom->id)>{{ $option->label() }}</option>@endforeach</select>
<input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" aria-label="{{ __('Date') }}" onchange="this.form.submit()">
</form></div>
@if($students->isEmpty())
<div class="card empty"><x-ui-icon name="users"/><p>{{ __('This class has no students yet.') }}</p>@can('students.manage')<a class="button" href="{{ route('school.students.index') }}">{{ __('Manage students') }}</a>@endcan</div>
@else
<form method="post" action="{{ route('school.attendance.save',$classroom) }}" x-data="{ setAll(status){ document.querySelectorAll('input[data-status='+status+']').forEach(i=>i.checked=true) } }">
@csrf<input type="hidden" name="date" value="{{ $date->toDateString() }}">
@if($records->isEmpty())<div class="banner"><x-ui-icon name="clipboard-check"/><p>{{ __('Attendance has not been recorded for this class today. Everyone is marked present by default; change only absent or late students.') }}</p></div>@endif
@if($canTake)<div class="toolbar" style="align-items:center"><span class="small muted">{{ __('Mark everyone as') }}:</span>@foreach(['present','absent'] as $status)<button type="button" class="tiny secondary" @click="setAll('{{ $status }}')">{{ __('school.status.'.$status) }}</button>@endforeach</div>@endif
<div class="table-wrap"><table class="register"><thead><tr><th>#</th><th>{{ __('Student') }}</th><th>{{ __('Status') }}</th><th>{{ __('Late minutes') }}</th><th>{{ __('Note') }}</th></tr></thead><tbody>
@foreach($students as $student)
@php($record=$records->get($student->id))
@php($current=old("entries.$student->id.status",$record?->status ?? 'present'))
<tr><td class="muted">{{ $loop->iteration }}</td><td><strong>{{ $student->name }}</strong><br><small class="muted" dir="ltr">{{ $student->national_id }}</small></td>
<td><div class="seg" role="radiogroup" aria-label="{{ $student->name }}">@foreach(\App\Models\AttendanceRecord::STATUSES as $status)<label><input type="radio" name="entries[{{ $student->id }}][status]" value="{{ $status }}" data-status="{{ $status }}" @checked($current===$status) @disabled(!$canTake)><span class="s-{{ $status }}">{{ __('school.status.'.$status) }}</span></label>@endforeach</div></td>
<td><input class="mini" type="number" min="1" max="600" name="entries[{{ $student->id }}][late_minutes]" value="{{ $record?->late_minutes }}" aria-label="{{ __('Late minutes') }}" @disabled(!$canTake)></td>
<td><input class="note-input" name="entries[{{ $student->id }}][note]" value="{{ $record?->note }}" maxlength="500" aria-label="{{ __('Note') }}" @disabled(!$canTake)></td></tr>
@endforeach
</tbody></table></div>
@if($canTake)<div class="form-actions"><button><x-ui-icon name="check"/>{{ __('Save attendance') }}</button></div>@endif
</form>
@endif
</x-admin-layout>
