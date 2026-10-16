<x-admin-layout :title="__('Reports and statistics')">
@php($query=request()->only(['from','to','grade','classroom']))
@php($maxDaily=max(1,$daily->max('absent') ?? 1))
<div class="page-head"><div><h1>{{ __('Reports and statistics') }}</h1><p>{{ \App\Support\Dates::both($from) }} — {{ \App\Support\Dates::both($to) }}</p></div>
@can('reports.export')<div class="page-actions"><a class="button secondary" href="{{ route('school.reports.export',['format'=>'xlsx']+$query) }}"><x-ui-icon name="file-spreadsheet"/>{{ __('Export Excel') }}</a><a class="button secondary" href="{{ route('school.reports.export',['format'=>'pdf']+$query) }}"><x-ui-icon name="file-down"/>{{ __('Export PDF') }}</a><button type="button" class="secondary" onclick="window.print()"><x-ui-icon name="printer"/>{{ __('Print') }}</button></div>@endcan</div>
<form method="get" class="toolbar">
<label>{{ __('From') }}<input type="date" name="from" value="{{ $from->toDateString() }}"></label>
<label>{{ __('To') }}<input type="date" name="to" value="{{ $to->toDateString() }}"></label>
<label>{{ __('Grade') }}<select name="grade"><option value="">{{ __('All grades') }}</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected($gradeId==$grade->id)>{{ $grade->name }}</option>@endforeach</select></label>
<label>{{ __('Class') }}<select name="classroom"><option value="">{{ __('All classes') }}</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected($classroomId==$class->id)>{{ $class->label() }}</option>@endforeach</select></label>
<button><x-ui-icon name="search"/>{{ __('Show') }}</button>
</form>
<div class="stat-grid">
<div class="stat"><span class="stat-icon" style="background:var(--paper)"><x-ui-icon name="calendar"/></span><div><strong>{{ $totals['records'] }}</strong><span>{{ __('Attendance records') }}</span></div></div>
<div class="stat tone-absent"><span class="stat-icon"><x-ui-icon name="user-x"/></span><div><strong>{{ $totals['absent'] }}</strong><span>{{ __('Unexcused absences') }}</span></div></div>
<div class="stat tone-excused"><span class="stat-icon"><x-ui-icon name="file-check"/></span><div><strong>{{ $totals['excused'] }}</strong><span>{{ __('Excused absences') }}</span></div></div>
<div class="stat tone-late"><span class="stat-icon"><x-ui-icon name="clock"/></span><div><strong>{{ $totals['late'] }}</strong><span>{{ __('Late arrivals') }} · {{ $totals['late_rate'] }}%</span></div></div>
<div class="stat tone-violet"><span class="stat-icon"><x-ui-icon name="chart-pie"/></span><div><strong>{{ $totals['absence_rate'] }}%</strong><span>{{ __('Absence rate') }}</span></div></div>
</div>
<div class="grid-2">
<section class="card"><h2><x-ui-icon name="chart-column"/>{{ __('Daily absences') }}</h2>
@if($daily->isEmpty())<p class="empty">{{ __('No data for this period.') }}</p>@else
<div class="bars" role="img" aria-label="{{ __('Daily absences') }}">@foreach($daily as $day)<div title="{{ \App\Support\Dates::both($day->date) }}: {{ $day->absent }}"><span style="height:{{ round($day->absent*100/$maxDaily) }}%"></span></div>@endforeach</div>@endif</section>
<section class="card"><h2><x-ui-icon name="triangle-alert"/>{{ __('Most absent students') }}</h2>
<div class="table-wrap"><table><thead><tr><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th class="num">{{ __('Absences') }}</th><th class="num">{{ __('Without excuse') }}</th></tr></thead><tbody>
@forelse($topAbsent as $row)<tr><td><a href="{{ route('school.students.show',$row['student']) }}">{{ $row['student']->name }}</a></td><td class="small">{{ $row['student']->classroom?->label() }}</td><td class="num">{{ $row['total'] }}</td><td class="num">{{ $row['unexcused'] }}</td></tr>
@empty<tr><td colspan="4" class="empty">{{ __('No absences in this period.') }}</td></tr>@endforelse</tbody></table></div></section>
</div>
<section class="card" style="margin-top:18px"><h2><x-ui-icon name="school"/>{{ __('By class') }}</h2>
<div class="table-wrap"><table><thead><tr><th>{{ __('Grade') }}</th><th>{{ __('Class') }}</th><th class="num">{{ __('Students') }}</th><th class="num">{{ __('school.status.present') }}</th><th class="num">{{ __('school.status.absent') }}</th><th class="num">{{ __('school.status.excused') }}</th><th class="num">{{ __('school.status.late') }}</th><th class="num">{{ __('Absence rate') }}</th></tr></thead><tbody>
@forelse($byClass as $row)<tr><td>{{ $row['classroom']->grade?->name }}</td><td>{{ $row['classroom']->name }}</td><td class="num">{{ $row['students'] }}</td><td class="num">{{ $row['present'] }}</td><td class="num">{{ $row['absent'] }}</td><td class="num">{{ $row['excused'] }}</td><td class="num">{{ $row['late'] }}</td><td class="num"><span class="pill {{ $row['absence_rate']>=10 ? 'tone-absent' : 'tone-excused' }}">{{ $row['absence_rate'] }}%</span></td></tr>
@empty<tr><td colspan="8" class="empty">{{ __('No classes yet.') }}</td></tr>@endforelse</tbody></table></div></section>
</x-admin-layout>
