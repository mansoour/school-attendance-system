<x-admin-layout :title="__('School settings')">
<div class="page-head"><div><h1>{{ __('School settings') }}</h1><p>{{ __('Details shown in reports and guardian messages, and the school day timing.') }}</p></div></div>
<div class="grid-2">
<form method="post" action="{{ route('school.settings.save') }}" class="card stack-sm">@csrf @method('PUT')
<h2><x-ui-icon name="school"/>{{ $school->name }}</h2>
@include('school.partials.school-fields',['school'=>$school,'full'=>false])
<button><x-ui-icon name="check"/>{{ __('Save') }}</button></form>
<section class="card"><h2><x-ui-icon name="sliders-horizontal"/>{{ __('More settings') }}</h2>
<ul class="list">
@can('grades.manage')<li><a href="{{ route('school.grades.index') }}"><x-ui-icon name="layers"/> {{ __('Grades') }}</a></li>@endcan
@can('classes.manage')<li><a href="{{ route('school.classes.index') }}"><x-ui-icon name="school"/> {{ __('Classes') }}</a></li>@endcan
@can('employees.manage')<li><a href="{{ route('school.employees.index') }}"><x-ui-icon name="id-card"/> {{ __('Employees') }}</a></li>@endcan
@can('settings.manage')<li><a href="{{ route('admin.settings','school-notifications') }}"><x-ui-icon name="bell-ring"/> {{ __('School notifications') }}</a></li><li><a href="{{ route('admin.settings','guardians') }}"><x-ui-icon name="user-plus"/> {{ __('Guardian registration') }}</a></li>@endcan
</ul></section>
</div>
</x-admin-layout>
