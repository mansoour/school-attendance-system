<x-admin-layout :title="__('Class attendance')">
<div class="page-head"><div><h1>{{ __('Class attendance') }}</h1><p>{{ __('Choose a class to record today’s attendance.') }}</p></div>
<form method="get" class="page-actions"><input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" aria-label="{{ __('Date') }}" onchange="this.form.submit()"></form></div>
<div class="stat-grid">
@forelse($classes as $row)
<a class="stat" href="{{ route('school.attendance.take',[$row['classroom'],'date'=>$date->toDateString()]) }}" style="text-decoration:none">
<span class="stat-icon" style="background:{{ $row['records'] ? 'var(--s-excused-bg)' : 'var(--s-late-bg)' }};color:{{ $row['records'] ? 'var(--s-excused)' : 'var(--s-late)' }}"><x-ui-icon :name="$row['records'] ? 'circle-check' : 'clipboard-check'"/></span>
<div><strong style="font-size:1rem">{{ $row['classroom']->label() }}</strong><span>{{ $row['students'] }} {{ __('students') }} · @if($row['records']){{ __('Taken') }} · {{ $row['absent'] }} {{ __('school.status.absent') }}@else{{ __('Not taken') }}@endif</span></div></a>
@empty<p class="empty">{{ __('No classes yet.') }}</p>@endforelse
</div>
</x-admin-layout>
