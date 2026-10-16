<x-admin-layout :title="__('Overview')">
@php
$roleIcons=['principal'=>'crown','vice_principal'=>'users-round','student_affairs'=>'file-text','health_guide'=>'heart-pulse','counselor'=>'user-round','guardian'=>'users'];
$hour=(int) now()->format('H');
$greeting=$hour<12 ? __('Good morning') : __('Good evening');
@endphp

<section class="hero-panel">
<div class="hero-panel-text">
<span class="hero-chip"><x-ui-icon :name="$roleIcons[$role] ?? 'crown'"/>{{ __('school.roles.'.$role) }}</span>
<h1>{{ $greeting }}، {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}</h1>
<p>@dateLong($date)</p>
</div>
<img class="hero-decoration" src="{{ asset('images/sized/dashboard-decoration.webp') }}" alt="" aria-hidden="true" width="480" height="360">
<form method="get" class="hero-panel-actions">
<label class="field">{{ __('Date') }}<input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()"></label>
@can('attendance.take')<a class="button light" href="{{ route('school.attendance.classes',['date'=>$date->toDateString()]) }}"><x-ui-icon name="clipboard-check"/>{{ __('Take attendance') }}</a>@endcan
</form>
</section>

<div class="stat-grid">
<div class="stat tone-teal"><span class="stat-icon"><x-ui-icon name="users"/></span><div><strong>{{ $students }}</strong><span>{{ __('Active students') }}</span></div></div>
<a class="stat tone-absent" href="{{ route('school.attendance.index',['date'=>$date->toDateString()]) }}"><span class="stat-icon"><x-ui-icon name="user-x"/></span><div><strong>{{ $today['absent'] }}</strong><span>{{ __('Absent today') }} · {{ $today['absence_rate'] }}%</span></div></a>
<a class="stat tone-late" href="{{ route('school.attendance.index',['date'=>$date->toDateString()]) }}"><span class="stat-icon"><x-ui-icon name="clock"/></span><div><strong>{{ $today['late'] }}</strong><span>{{ __('Late today') }}</span></div></a>
<div class="stat tone-excused"><span class="stat-icon"><x-ui-icon name="file-check"/></span><div><strong>{{ $today['excused'] }}</strong><span>{{ __('Excused today') }}</span></div></div>
@can('excuses.view')<a class="stat tone-violet" href="{{ route('school.excuses.index',['status'=>'pending']) }}"><span class="stat-icon"><x-ui-icon name="triangle-alert"/></span><div><strong>{{ $pendingExcuses }}</strong><span>{{ __('Excuses awaiting review') }}</span></div></a>@endcan
<div class="stat tone-present"><span class="stat-icon"><x-ui-icon name="calendar-check"/></span><div><strong>{{ $notTaken->count() }}</strong><span>{{ __('Classes without attendance today') }}</span></div></div>
</div>

@if($mode==='leadership')
<div class="role-cards">
@foreach(['principal','vice_principal','student_affairs','health_guide','counselor','guardian'] as $card)
<div class="role-card"><img class="role-img" src="{{ asset('images/sized/'.$card.'.webp') }}" alt="" width="80" height="80" loading="lazy"><strong>{{ __('school.roles.'.$card) }}</strong><small>{{ __('school.role_descriptions.'.$card) }}</small><span class="count">{{ $roleCounts[$card] ?? 0 }}</span></div>
@endforeach
</div>
@php
$palette=['#0e9aa7','#8b5cf6','#ec4899','#f59e0b','#3b82f6','#10b981','#38c6c0','#b794f6'];
$byGrade=$classes->groupBy(fn($r)=>$r['classroom']->grade?->name ?? '—')->map(fn($rows)=>$rows->sum('absent')+$rows->sum('excused'));
$gradeTotal=max(1,$byGrade->sum()); $start=0; $segments=[];
foreach($byGrade->values() as $i=>$value){ $end=$start+$value*100/$gradeTotal; $segments[]=$palette[$i%count($palette)].' '.$start.'% '.$end.'%'; $start=$end; }
@endphp
<div class="grid-3">
<section class="card"><div class="card-head"><h2><x-ui-icon name="chart-column"/>{{ __('Absence and lateness statistics') }} <small class="muted">({{ __('this month') }})</small></h2>@can('reports.view')<a class="small" href="{{ route('school.reports.index') }}">{{ __('Full report') }}</a>@endcan</div>
<div class="table-wrap"><table><thead><tr><th>{{ __('Stage') }}</th><th>{{ __('Grade') }}</th><th>{{ __('Class') }}</th><th class="num">{{ __('Students') }}</th><th class="num">{{ __('Absences') }}</th><th class="num">{{ __('Absence rate') }}</th><th>{{ __('Today') }}</th><th></th></tr></thead><tbody>
@forelse($classes as $row)
@php($t=$todayByClass[$row['classroom']->id] ?? null)
<tr><td><span class="pill tone-present">{{ __('school.stage.'.($row['classroom']->grade?->stage ?? 'primary')) }}</span></td><td>{{ $row['classroom']->grade?->name }}</td><td>{{ $row['classroom']->name }}</td><td class="num">{{ $row['students'] }}</td><td class="num">{{ $row['absent']+$row['excused'] }}</td><td class="num">{{ $row['absence_rate'] }}%</td>
<td>@if(!$t || !$t['records'])<span class="pill">{{ __('Not taken') }}</span>@elseif($t['absent'])<span class="pill tone-absent">{{ $t['absent'] }} {{ __('school.status.absent') }}</span>@elseif($t['late'])<span class="pill tone-late">{{ $t['late'] }} {{ __('school.status.late') }}</span>@else<span class="pill tone-excused">{{ __('Complete') }}</span>@endif</td>
<td><div class="contact-buttons"><a href="{{ route('school.attendance.take',[$row['classroom'],'date'=>$date->toDateString()]) }}" title="{{ __('Class attendance') }}"><x-ui-icon name="eye"/></a>@can('communication.access')<a class="wa" href="{{ route('school.communication.index',['classroom'=>$row['classroom']->id,'date'=>$date->toDateString()]) }}" title="{{ __('Guardian communication') }}"><x-ui-icon name="message-circle"/></a>@endcan</div></td></tr>
@empty<tr><td colspan="8" class="empty">{{ __('No classes yet.') }} @can('classes.manage')<a href="{{ route('school.classes.index') }}">{{ __('Add classes') }}</a>@endcan</td></tr>@endforelse
</tbody></table></div></section>
<div class="stack-sm">
<section class="card"><h2><x-ui-icon name="chart-pie"/>{{ __('Absence by grade') }}</h2>
@if($byGrade->sum())<div class="donut-wrap"><div class="donut" data-label="{{ round(($classes->sum('absent')+$classes->sum('excused'))*100/max(1,$classes->sum('records'))) }}%" style="background:conic-gradient({{ implode(',',$segments) }})"></div><ul class="legend">@foreach($byGrade as $grade=>$value)<li><i style="background:{{ $palette[$loop->index%count($palette)] }}"></i>{{ $grade }} <span class="muted">({{ $value }})</span></li>@endforeach</ul></div>
@else<p class="empty"><x-ui-icon name="chart-pie"/><br>{{ __('No absences recorded this month.') }}</p>@endif</section>
@include('school.partials.dashboard-alerts')
</div></div>
@endif

@if($mode==='affairs')
<div class="grid-3">
<section class="card"><div class="card-head"><h2><x-ui-icon name="clipboard-check"/>{{ __('Classes waiting for attendance') }}</h2><a class="small" href="{{ route('school.attendance.classes',['date'=>$date->toDateString()]) }}">{{ __('All classes') }}</a></div>
@if($notTaken->isEmpty())<div class="illustrated-state"><img src="{{ asset('images/sized/all-classes-recorded.webp') }}" alt="" width="200" height="200"><p>{{ __('Attendance has been recorded for every class today. Well done!') }}</p></div>
@else<div class="stat-grid">@foreach($notTaken as $row)<a class="stat tone-late" href="{{ route('school.attendance.take',[$row['classroom'],'date'=>$date->toDateString()]) }}"><span class="stat-icon"><x-ui-icon name="clipboard-check"/></span><div><strong style="font-size:1rem">{{ $row['classroom']->label() }}</strong><span>{{ $row['students'] }} {{ __('students') }}</span></div></a>@endforeach</div>@endif
</section>
<div class="stack-sm">
<section class="card"><h2><x-ui-icon name="send"/>{{ __('Quick actions') }}</h2><div class="quick-actions">
<a href="{{ route('school.attendance.classes') }}"><x-ui-icon name="clipboard-check"/>{{ __('Take attendance') }}</a>
<a href="{{ route('school.students.create') }}"><x-ui-icon name="user-plus"/>{{ __('Add student') }}</a>
<a href="{{ route('school.students.import') }}"><x-ui-icon name="file-spreadsheet"/>{{ __('Import from Excel') }}</a>
<a href="{{ route('school.communication.index') }}"><x-ui-icon name="phone"/>{{ __('Contact guardians') }}</a>
<a href="{{ route('school.guardians.index',['status'=>'pending']) }}"><x-ui-icon name="user-check"/>{{ __('Guardian accounts') }} ({{ $pendingGuardians }})</a>
<a href="{{ route('school.students.index',['unassigned'=>1]) }}"><x-ui-icon name="users"/>{{ __('Without class') }} ({{ $unassigned }})</a>
</div></section>
@include('school.partials.dashboard-alerts')
</div></div>
@endif

@if($mode==='health')
<div class="grid-3">
<section class="card"><div class="card-head"><h2><x-ui-icon name="heart-pulse"/>{{ __('Excuses awaiting review') }}</h2><a class="small" href="{{ route('school.excuses.index',['status'=>'pending']) }}">{{ __('View all') }}</a></div>
<ul class="list">@forelse($excuses as $excuse)<li class="tone-{{ $excuse->type==='medical' ? 'absent' : 'violet' }}"><span class="list-icon"><x-ui-icon :name="$excuse->type==='medical' ? 'stethoscope' : 'file-text'"/></span><div style="flex:1"><strong class="small">{{ $excuse->student?->name }}</strong> <span class="pill">{{ __('school.excuse_type.'.$excuse->type) }}</span><p class="small" style="margin:4px 0">{{ \Illuminate\Support\Str::limit($excuse->reason,120) }}</p><small>{{ $excuse->student?->classroom?->label() }} · {{ \App\Support\Dates::both($excuse->date) }}</small></div><a class="button tiny" href="{{ route('school.excuses.index',['status'=>'pending']) }}">{{ __('Review') }}</a></li>@empty<li><span class="list-icon"><x-ui-icon name="circle-check"/></span><div class="small">{{ __('No excuses awaiting review.') }}</div></li>@endforelse</ul></section>
<div class="stack-sm">
<section class="card"><div class="card-head"><h2><x-ui-icon name="notebook-pen"/>{{ __('Latest health notes') }}</h2><a class="small" href="{{ route('school.notes.index',['category'=>'health']) }}">{{ __('View all') }}</a></div>
<ul class="list">@forelse($notes as $note)<li><div><strong class="small">{{ $note->student?->name }}</strong><p class="small" style="margin:4px 0">{{ \Illuminate\Support\Str::limit($note->body,100) }}</p><small>{{ $note->author?->name }}</small></div></li>@empty<li class="small muted">{{ __('No notes yet.') }}</li>@endforelse</ul></section>
@include('school.partials.dashboard-alerts')
</div></div>
@endif

@if($mode==='counselor')
<div class="grid-3">
<section class="card"><div class="card-head"><h2><x-ui-icon name="triangle-alert"/>{{ __('Students needing follow-up') }} <small class="muted">({{ __('Last 30 days') }})</small></h2></div>
<div class="table-wrap"><table><thead><tr><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th class="num">{{ __('Absences') }}</th><th class="num">{{ __('Without excuse') }}</th><th></th></tr></thead><tbody>
@forelse($topAbsent as $row)<tr><td><a href="{{ route('school.students.show',$row['student']) }}"><strong>{{ $row['student']->name }}</strong></a></td><td class="small">{{ $row['student']->classroom?->label() }}</td><td class="num"><span class="pill tone-absent">{{ $row['total'] }}</span></td><td class="num">{{ $row['unexcused'] }}</td><td><a class="button tiny secondary" href="{{ route('school.students.show',$row['student']) }}"><x-ui-icon name="notebook-pen"/>{{ __('Add note') }}</a></td></tr>
@empty<tr><td colspan="5" class="empty">{{ __('No absences in this period.') }}</td></tr>@endforelse
</tbody></table></div></section>
<div class="stack-sm">
<section class="card"><div class="card-head"><h2><x-ui-icon name="user-round"/>{{ __('Latest counseling notes') }}</h2><a class="small" href="{{ route('school.notes.index',['category'=>'counseling']) }}">{{ __('View all') }}</a></div>
<ul class="list">@forelse($notes as $note)<li><div><strong class="small">{{ $note->student?->name }}</strong> <span class="pill">{{ __('school.note_category.'.$note->category) }}</span><p class="small" style="margin:4px 0">{{ \Illuminate\Support\Str::limit($note->body,100) }}</p></div></li>@empty<li class="small muted">{{ __('No notes yet.') }}</li>@endforelse</ul></section>
@include('school.partials.dashboard-alerts')
</div></div>
@endif

@if($mode!=='leadership')
<section class="card" style="margin-top:18px"><div class="card-head"><h2><x-ui-icon name="user-x"/>{{ __('Absent and late students') }}</h2>@can('communication.access')<a class="small" href="{{ route('school.communication.index',['date'=>$date->toDateString()]) }}">{{ __('Contact guardians') }}</a>@endcan</div>
<div class="table-wrap"><table><thead><tr><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th>{{ __('Status') }}</th><th>{{ __('Excuse') }}</th></tr></thead><tbody>
@forelse($absentToday as $record)<tr><td><a href="{{ route('school.students.show',$record->student) }}">{{ $record->student?->name }}</a></td><td class="small">{{ $record->classroom?->label() }}</td><td><span class="pill tone-{{ $record->status }}">{{ __('school.status.'.$record->status) }}</span></td><td>@if($excuse=$record->excuses->last())<span class="pill tone-{{ $excuse->status }}">{{ __('school.excuse_status.'.$excuse->status) }}</span>@else<span class="muted small">—</span>@endif</td></tr>
@empty<tr><td colspan="4" class="empty"><x-ui-icon name="circle-check"/><br>{{ __('No absences or late arrivals recorded.') }}</td></tr>@endforelse
</tbody></table></div></section>
@endif
</x-admin-layout>
