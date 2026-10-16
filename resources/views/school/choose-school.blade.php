<x-admin-layout :title="__('School settings')">
<div class="page-head"><div><h1>{{ __('School settings') }}</h1><p>{{ __('You are viewing all schools. Choose the school whose settings you want to edit.') }}</p></div>
@can('schools.manage')<div class="page-actions"><a class="button secondary" href="{{ route('school.schools.index') }}"><x-ui-icon name="plus"/>{{ __('Add school') }}</a></div>@endcan</div>
<div class="stat-grid">
@forelse($schools as $school)
<form method="post" action="{{ route('school.switch') }}">@csrf<input type="hidden" name="school_id" value="{{ $school->id }}">
<button type="submit" class="stat school-pick"><span class="stat-icon" style="background:linear-gradient(135deg,var(--c-teal),var(--c-violet));color:#fff"><x-ui-icon name="school"/></span><span><strong>{{ $school->name }}</strong><span>{{ $school->students_count }} {{ __('students') }}{{ $school->city ? ' · '.$school->city : '' }}</span></span></button></form>
@empty<div class="card empty"><x-ui-icon name="school"/><p>{{ __('No schools yet. Add the first school to begin.') }}</p></div>@endforelse
</div>
</x-admin-layout>
