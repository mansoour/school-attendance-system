<x-admin-layout :title="__('Attendance tracking')">
<div class="page-head"><div><h1>{{ __('Attendance tracking') }}</h1><p>{{ __('Daily attendance for every class. Attendance is recorded once per school day.') }}</p></div>
@can('attendance.take')<div class="page-actions"><a class="button" href="{{ route('school.attendance.classes',['date'=>$date->toDateString()]) }}"><x-ui-icon name="clipboard-check"/>{{ __('Take attendance') }}</a></div>@endcan</div>
<form method="get" class="toolbar">
<label>{{ __('Date') }}<input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"></label>
<label>{{ __('Grade') }}<select name="grade"><option value="">{{ __('All grades') }}</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(request('grade')==$grade->id)>{{ $grade->name }}</option>@endforeach</select></label>
<button><x-ui-icon name="search"/>{{ __('Show') }}</button>
</form>
<div class="stat-grid">
<div class="stat tone-present"><span class="stat-icon"><x-ui-icon name="circle-check"/></span><div><strong>{{ $totals['present'] }}</strong><span>{{ __('school.status.present') }}</span></div></div>
<div class="stat tone-absent"><span class="stat-icon"><x-ui-icon name="user-x"/></span><div><strong>{{ $totals['absent'] }}</strong><span>{{ __('school.status.absent') }}</span></div></div>
<div class="stat tone-late"><span class="stat-icon"><x-ui-icon name="clock"/></span><div><strong>{{ $totals['late'] }}</strong><span>{{ __('school.status.late') }}</span></div></div>
<div class="stat tone-excused"><span class="stat-icon"><x-ui-icon name="file-check"/></span><div><strong>{{ $totals['excused'] }}</strong><span>{{ __('school.status.excused') }}</span></div></div>
</div>
<div class="grid-2">
<section class="card"><h2><x-ui-icon name="school"/>{{ __('Classes') }}</h2>
<div class="table-wrap"><table><thead><tr><th>{{ __('Class') }}</th><th class="num">{{ __('Students') }}</th><th class="num">{{ __('school.status.absent') }}</th><th class="num">{{ __('school.status.late') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
@forelse($classes as $row)
<tr><td>{{ $row['classroom']->label() }}</td><td class="num">{{ $row['students'] }}</td><td class="num">{{ $row['absent'] }}</td><td class="num">{{ $row['late'] }}</td>
<td>@if($row['records'])<span class="pill tone-excused">{{ __('Taken') }} ({{ $row['records'] }}/{{ $row['students'] }})</span>@else<span class="pill tone-pending">{{ __('Not taken') }}</span>@endif</td>
<td><a class="button tiny secondary" href="{{ route('school.attendance.take',[$row['classroom'],'date'=>$date->toDateString()]) }}">{{ auth()->user()->can('attendance.take') ? __('Take / edit') : __('View') }}</a></td></tr>
@empty<tr><td colspan="6" class="empty">{{ __('No classes yet.') }}</td></tr>@endforelse
</tbody></table></div></section>
<section class="card"><h2><x-ui-icon name="user-x"/>{{ __('Absent and late students') }}</h2>
<div class="table-wrap"><table><thead><tr><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th>{{ __('Status') }}</th><th>{{ __('Excuse') }}</th></tr></thead><tbody>
@forelse($records as $record)
<tr><td>@can('students.view')<a href="{{ route('school.students.show',$record->student) }}">{{ $record->student?->name }}</a>@else{{ $record->student?->name }}@endcan</td><td>{{ $record->classroom?->label() }}</td><td><span class="pill tone-{{ $record->status }}">{{ __('school.status.'.$record->status) }}@if($record->late_minutes) · {{ $record->late_minutes }} {{ __('min') }}@endif</span></td>
<td>@if($excuse=$record->excuses->last())<span class="pill tone-{{ $excuse->status }}">{{ __('school.excuse_status.'.$excuse->status) }}</span>@else<span class="muted small">—</span>@endif</td></tr>
@empty<tr><td colspan="4" class="empty">{{ __('No absences or late arrivals recorded.') }}</td></tr>@endforelse
</tbody></table></div></section>
</div>
</x-admin-layout>
