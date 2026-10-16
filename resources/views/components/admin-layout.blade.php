@props(['title' => null])
@php
$settings=app(\App\Settings\SettingsService::class);
$user=auth()->user();
$context=app(\App\School\SchoolContext::class);
// [label, icon, url, permission]; each group has its own accent color.
$schoolGroups=[
 ['Dashboard','house','teal',[
  ['Overview','layout-dashboard',route('school.dashboard'),'attendance.view'],
  ['My children','heart-handshake',route('school.guardian.index'),'role:guardian'],
  ['Notifications','bell',route('school.notifications'),'school.access'],
 ]],
 ['Attendance','calendar-check','violet',[
  ['Attendance tracking','calendar-check',route('school.attendance.index'),'attendance.view'],
  ['Class attendance','clipboard-check',route('school.attendance.classes'),'attendance.take'],
  ['Excuses','file-check',route('school.excuses.index'),'excuses.view'],
 ]],
 ['Follow-up and communication','message-circle','pink',[
  ['Student notes','notebook-pen',route('school.notes.index'),'notes.view'],
  ['Guardian communication','message-circle',route('school.communication.index'),'communication.access'],
 ]],
 ['Reports','chart-column','amber',[
  ['Reports and statistics','chart-column',route('school.reports.index'),'reports.view'],
  ['Activity log','history',route('school.activity'),'audit.view'],
 ]],
 ['School management','school','blue',[
  ['Students','users',route('school.students.index'),'students.view'],
  ['Classes','school',route('school.classes.index'),'classes.manage'],
  ['Grades','layers',route('school.grades.index'),'grades.manage'],
  ['Employees','id-card',route('school.employees.index'),'employees.manage'],
  ['Guardian accounts','user-check',route('school.guardians.index'),'guardians.approve'],
  ['School settings','sliders-horizontal',route('school.settings'),'school.settings'],
  ['Schools','building-2',route('school.schools.index'),'schools.manage'],
 ]],
 ['My account','user-round','green',[
  ['Account','user',route('profile.edit'),null],
  ['Security','shield-check',route('security.edit'),null],
 ]],
];
$groups=['Workspace'=>[['Overview','layout-dashboard',route('admin.index'),'admin.access'],['Members','users',route('admin.members'),'users.manage'],['Roles','key-round',route('admin.roles'),'roles.manage'],['Support','inbox',route('admin.support'),'support.access']], 'Content'=>[], 'Configuration'=>[], 'Operations'=>[['Integrations','plug',route('admin.integrations'),'integrations.manage'],['Health','activity',route('admin.health'),'health.view'],['Audit','list-checks',route('admin.audit'),'audit.view'],['Email history','mail',route('admin.emails'),'emails.view']]];
foreach(\App\Admin\Resources::all() as $name=>$resource) {
 $groups['Content'][]=[ucwords(str_replace('-',' ',$name)),match($name){'pages'=>'files','notices','site-notices'=>'megaphone',default=>'file-text'},route('admin.resources',$name),$resource[1]];
}
$groups['Content'][]=['Translations','languages',route('admin.translations'),'translations.edit'];
foreach(\App\Settings\SettingsRegistry::sections() as $section=>$entry) {
 if(in_array($section,['icons','cookies','social','styling','fonts'])) continue;
 $groups['Configuration'][]=[$entry['title'],$entry['icon'],route('admin.settings',$section),$entry['permission']];
}
$current=url()->current();
$isActive=fn($url)=>$current===$url || (str_contains($url,'/manage/') && str_starts_with($current,$url.'/')) || ($url===route('admin.settings','branding') && in_array(request()->route('section'),['icons','cookies','social','styling','fonts'])) || (str_contains($url,'/school/') && str_starts_with($current,$url.'/'));
$allowed=fn($link)=>$link[3]===null || (str_starts_with($link[3],'role:') ? $user->hasRole(substr($link[3],5)) : $user->can($link[3]));
$inAdmin=request()->routeIs('admin.*');
$roleName=$user->roles->pluck('name')->first(fn($r)=>in_array($r,['owner',...\App\School\Roles::all()]));
@endphp
<x-shell :admin="true" :title="$title" :heading="$context->school()?->name ?? ($user->isSystemAdmin() ? __('All schools') : __('Administration'))"><div class="admin-grid"><aside id="admin-sidebar" class="admin-nav" :class="{'is-open':sidebarOpen}" aria-label="{{ __('Administration navigation') }}">
<div class="nav-profile"><span class="avatar">{{ mb_substr($user->name,0,1) }}</span><div><strong>{{ $user->name }}</strong>@if($roleName)<small>{{ __('school.roles.'.$roleName) }}</small>@endif</div></div>
@if($user->isSystemAdmin() && $user->can('school.access'))
<form method="post" action="{{ route('school.switch') }}" class="school-switcher">@csrf<label for="school-switch"><x-ui-icon name="building-2"/>{{ __('Active school') }}</label><select id="school-switch" name="school_id" onchange="this.form.submit()"><option value="">{{ __('All schools') }}</option>@foreach(\App\Models\School::orderBy('name')->get() as $option)<option value="{{ $option->id }}" @selected((int) session('school_id')===$option->id)>{{ $option->name }}</option>@endforeach</select><noscript><button class="secondary small">{{ __('Switch') }}</button></noscript></form>
@endif
@foreach($schoolGroups as [$label,$groupIcon,$tone,$links])
@php($links=array_values(array_filter($links,$allowed)))
@if(count($links))<details class="nav-group tone-{{ $tone }}" open><summary><span class="nav-group-icon"><x-ui-icon :name="$groupIcon"/></span>{{ __($label) }}<x-ui-icon name="chevron-down"/></summary>@foreach($links as [$linkLabel,$icon,$url,$permission])
<a href="{{ $url }}" @if($isActive($url)) aria-current="page" @endif><x-ui-icon :name="$icon"/><span>{{ __($linkLabel) }}</span></a>
@endforeach</details>@endif
@endforeach
@if($user->can('admin.access'))
<p class="nav-divider"><x-ui-icon name="settings"/>{{ __('System settings') }}</p>
@foreach($groups as $label=>$links)
@php($links=array_values(array_filter($links,$allowed)))
@php($groupActive=collect($links)->contains(fn($link)=>$isActive($link[2])))
@if(count($links))<details class="nav-group nav-system" @if($inAdmin && $groupActive) open @endif><summary>{{ __($label) }}<x-ui-icon name="chevron-down"/></summary>@foreach($links as [$linkLabel,$icon,$url,$permission])
<a href="{{ $url }}" @if($isActive($url)) aria-current="page" @endif><x-ui-icon :name="$icon"/><span>{{ __($linkLabel) }}</span></a>
@endforeach</details>@endif
@endforeach
@endif
<form method="post" action="{{ route('logout') }}" class="nav-logout">@csrf<button class="text-button"><x-ui-icon name="log-out"/>{{ __('Log Out') }}</button></form>
</aside><section class="admin-content">@include('partials.feedback'){{ $slot }}</section></div></x-shell>
