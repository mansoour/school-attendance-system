<x-admin-layout :title="__('Overview')">
@php
$roleIcons=['principal'=>'crown','vice_principal'=>'users-round','student_affairs'=>'file-text','health_guide'=>'heart-pulse','counselor'=>'user-round','guardian'=>'users'];
$palette=['#3fa6a0','#6fc3b8','#8b6fd6','#e67a9c','#f2b35a','#5aa0e6','#9ccf6b','#c58be0'];
$byGrade=$classes->groupBy(fn($r)=>$r['classroom']->grade?->name ?? '—')->map(fn($rows)=>$rows->sum('absent')+$rows->sum('excused'));
$gradeTotal=max(1,$byGrade->sum());
$start=0; $segments=[];
foreach($byGrade->values() as $i=>$value){ $end=$start+$value*100/$gradeTotal; $segments[]=$palette[$i%count($palette)].' '.$start.'% '.$end.'%'; $start=$end; }
$monthRecords=max(1,$classes->sum('records'));
@endphp
<div class="page-head"><div><h1>{{ __('Overview') }}</h1><p>{{ $date->locale(app()->getLocale())->translatedFormat('l j F Y') }}</p></div>
<form method="get" class="page-actions"><input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" aria-label="{{ __('Date') }}" onchange="this.form.submit()">
@can('attendance.take')<a class="button" href="{{ route('school.attendance.classes',['date'=>$date->toDateString()]) }}"><x-ui-icon name="clipboard-check"/>{{ __('Take attendance') }}</a>@endcan</form></div>

<div class="role-cards">
@foreach(['principal','vice_principal','student_affairs','health_guide','counselor','guardian'] as $role)
<div class="role-card"><span class="role-icon"><x-ui-icon :name="$roleIcons[$role]"/></span><strong>{{ __('school.roles.'.$role) }}</strong><small>{{ __('school.role_descriptions.'.$role) }}</small><span class="count">{{ $roleCounts[$role] ?? 0 }}</span></div>
@endforeach
</div>

<div class="stat-grid">
<div class="stat tone-present"><span class="stat-icon"><x-ui-icon name="users"/></span><div><strong>{{ $students }}</strong><span>{{ __('Active students') }}</span></div></div>
<div class="stat tone-absent"><span class="stat-icon"><x-ui-icon name="user-x"/></span><div><strong>{{ $today['absent'] }}</strong><span>{{ __('Absent today') }} · {{ $today['absence_rate'] }}%</span></div></div>
<div class="stat tone-late"><span class="stat-icon"><x-ui-icon name="clock"/></span><div><strong>{{ $today['late'] }}</strong><span>{{ __('Late today') }}</span></div></div>
<div class="stat tone-excused"><span class="stat-icon"><x-ui-icon name="file-check"/></span><div><strong>{{ $today['excused'] }}</strong><span>{{ __('Excused today') }}</span></div></div>
<div class="stat tone-violet"><span class="stat-icon"><x-ui-icon name="triangle-alert"/></span><div><strong>{{ $pendingExcuses }}</strong><span>{{ __('Excuses awaiting review') }}</span></div></div>
<div class="stat"><span class="stat-icon" style="background:var(--paper)"><x-ui-icon name="calendar-check"/></span><div><strong>{{ $notTaken }}</strong><span>{{ __('Classes without attendance today') }}</span></div></div>
</div>

<div class="grid-3">
<div class="stack-sm">
<section class="card"><div class="card-head"><h2><x-ui-icon name="chart-column"/>{{ __('Absence and lateness statistics') }} <small class="muted">({{ __('this month') }})</small></h2>@can('reports.view')<a class="small" href="{{ route('school.reports.index') }}">{{ __('Full report') }}</a>@endcan</div>
<div class="table-wrap"><table><thead><tr><th>{{ __('Stage') }}</th><th>{{ __('Grade') }}</th><th>{{ __('Class') }}</th><th class="num">{{ __('Students') }}</th><th class="num">{{ __('Absences') }}</th><th class="num">{{ __('Absence rate') }}</th><th>{{ __('Today') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($classes as $row)
@php($t=$todayByClass[$row['classroom']->id] ?? null)
<tr><td><span class="pill tone-present">{{ __('school.stage.'.($row['classroom']->grade?->stage ?? 'primary')) }}</span></td><td>{{ $row['classroom']->grade?->name }}</td><td>{{ $row['classroom']->name }}</td><td class="num">{{ $row['students'] }}</td><td class="num">{{ $row['absent']+$row['excused'] }}</td><td class="num">{{ $row['absence_rate'] }}%</td>
<td>@if(!$t || !$t['records'])<span class="pill">{{ __('Not taken') }}</span>@elseif($t['absent'])<span class="pill tone-absent">{{ $t['absent'] }} {{ __('school.status.absent') }}</span>@elseif($t['late'])<span class="pill tone-late">{{ $t['late'] }} {{ __('school.status.late') }}</span>@else<span class="pill tone-excused">{{ __('Complete') }}</span>@endif</td>
<td><div class="contact-buttons"><a href="{{ route('school.attendance.take',[$row['classroom'],'date'=>$date->toDateString()]) }}" title="{{ __('Class attendance') }}"><x-ui-icon name="eye"/></a>@can('communication.access')<a class="wa" href="{{ route('school.communication.index',['classroom'=>$row['classroom']->id,'date'=>$date->toDateString()]) }}" title="{{ __('Guardian communication') }}"><x-ui-icon name="message-circle"/></a>@endcan</div></td></tr>
@empty<tr><td colspan="8" class="empty">{{ __('No classes yet.') }} @can('classes.manage')<a href="{{ route('school.classes.index') }}">{{ __('Add classes') }}</a>@endcan</td></tr>@endforelse
</tbody></table></div></section>
</div>
<div class="stack-sm">
<section class="card"><h2><x-ui-icon name="chart-pie"/>{{ __('Absence by grade') }}</h2>
@if($byGrade->sum())<div class="donut-wrap"><div class="donut" data-label="{{ round(($classes->sum('absent')+$classes->sum('excused'))*100/$monthRecords) }}%" style="background:conic-gradient({{ implode(',',$segments) }})"></div><ul class="legend">@foreach($byGrade as $grade=>$value)<li><i style="background:{{ $palette[$loop->index%count($palette)] }}"></i>{{ $grade }} <span class="muted">({{ $value }})</span></li>@endforeach</ul></div>
@else<p class="empty"><x-ui-icon name="chart-pie"/><br>{{ __('No absences recorded this month.') }}</p>@endif</section>
<section class="card"><div class="card-head"><h2><x-ui-icon name="bell"/>{{ __('Latest alerts') }}</h2><a class="small" href="{{ route('school.notifications') }}">{{ __('View all') }}</a></div>
<ul class="list">@forelse($recentAbsences as $record)<li class="tone-{{ $record->status }}"><span class="list-icon"><x-ui-icon :name="$record->status==='late'?'clock':'user-x'"/></span><div><strong class="small">{{ __('school.status.'.$record->status) }}: {{ $record->student?->name }}</strong><small>{{ $record->classroom?->label() }} · {{ $record->date->toDateString() }}</small></div></li>
@empty<li><span class="list-icon"><x-ui-icon name="circle-check"/></span><div class="small">{{ __('No absences or late arrivals recorded.') }}</div></li>@endforelse</ul></section>
<section class="card"><h2><x-ui-icon name="send"/>{{ __('Quick actions') }}</h2><div class="quick-actions">
@can('attendance.take')<a href="{{ route('school.attendance.classes') }}"><x-ui-icon name="clipboard-check"/>{{ __('Take attendance') }}</a>@endcan
@can('communication.access')<a href="{{ route('school.communication.index') }}"><x-ui-icon name="phone"/>{{ __('Contact guardians') }}</a>@endcan
@can('excuses.view')<a href="{{ route('school.excuses.index',['status'=>'pending']) }}"><x-ui-icon name="file-check"/>{{ __('Review excuses') }}</a>@endcan
@can('notes.manage')<a href="{{ route('school.notes.index') }}"><x-ui-icon name="notebook-pen"/>{{ __('Add a note') }}</a>@endcan
@can('reports.view')<a href="{{ route('school.reports.index') }}"><x-ui-icon name="chart-column"/>{{ __('View reports') }}</a>@endcan
@can('students.manage')<a href="{{ route('school.students.create') }}"><x-ui-icon name="user-plus"/>{{ __('Add student') }}</a>@endcan
</div></section>
<div class="banner"><x-ui-icon name="heart-handshake"/><p>{{ __('Guardians are contacted when an absence or late arrival is recorded, and records are kept confidential.') }}</p></div>
</div>
</div>
</x-admin-layout>
