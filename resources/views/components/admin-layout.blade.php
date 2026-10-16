@props(['title' => null])
@php
$settings=app(\App\Settings\SettingsService::class);
$user=auth()->user();
$context=app(\App\School\SchoolContext::class);
$schoolGroups=[
 'Daily follow-up'=>[
  ['Overview','layout-dashboard',route('school.dashboard'),'attendance.view'],
  ['My children','heart-handshake',route('school.guardian.index'),'guardian.portal'],
  ['Attendance tracking','calendar-check',route('school.attendance.index'),'attendance.view'],
  ['Class attendance','clipboard-check',route('school.attendance.classes'),'attendance.take'],
  ['Excuses','file-check',route('school.excuses.index'),'excuses.view'],
  ['Student notes','notebook-pen',route('school.notes.index'),'notes.view'],
  ['Guardian communication','message-circle',route('school.communication.index'),'communication.access'],
  ['Reports and statistics','chart-column',route('school.reports.index'),'reports.view'],
  ['Notifications','bell',route('school.notifications'),'school.access'],
 ],
 'School management'=>[
  ['Students','users',route('school.students.index'),'students.view'],
  ['Classes','school',route('school.classes.index'),'classes.manage'],
  ['Grades','layers',route('school.grades.index'),'grades.manage'],
  ['Employees','id-card',route('school.employees.index'),'employees.manage'],
  ['Guardian accounts','user-check',route('school.guardians.index'),'guardians.approve'],
  ['Schools','building-2',route('school.schools.index'),'schools.manage'],
  ['School settings','sliders-horizontal',route('school.settings'),'school.settings'],
  ['Activity log','history',route('school.activity'),'audit.view'],
 ],
];
$groups=['Workspace'=>[['Overview','layout-dashboard',route('admin.index'),'admin.access'],['Members','users',route('admin.members'),'users.manage'],['Roles','key-round',route('admin.roles'),'roles.manage'],['Support','inbox',route('admin.support'),'support.access']], 'Content'=>[], 'Configuration'=>[], 'Operations'=>[['Integrations','plug',route('admin.integrations'),'integrations.manage'],['Health','activity',route('admin.health'),'health.view'],['Audit','list-checks',route('admin.audit'),'audit.view'],['Email history','mail',route('admin.emails'),'emails.view']]];
foreach(\App\Admin\Resources::all() as $name=>$resource) {
 if(in_array($name,['plans','features']) && !$settings->get('plans.enabled')) continue;
 $groups[in_array($name,['plans','features'])?'Configuration':'Content'][]=[ucwords(str_replace('-',' ',$name)),match($name){'pages'=>'files','plans'=>'layers','features'=>'blocks','notices'=>'megaphone',default=>'file-text'},route('admin.resources',$name),$resource[1]];
}
$groups['Content'][]=['Translations','languages',route('admin.translations'),'translations.edit'];
foreach(\App\Settings\SettingsRegistry::sections() as $section=>$entry) {
 if(in_array($section,['icons','cookies','social','styling','fonts'])) continue;
 $groups['Configuration'][]=[$entry['title'],$entry['icon'],route('admin.settings',$section),$entry['permission']];
}
$isActive=fn($url)=>url()->current()===$url || (str_contains($url,'/manage/') && str_starts_with(url()->current(),$url.'/')) || ($url===route('admin.settings','branding') && in_array(request()->route('section'),['icons','cookies','social','styling','fonts'])) || (str_contains($url,'/school/') && str_starts_with(url()->current(),$url.'/'));
$canSystem=$user->can('admin.access');
$inAdmin=request()->routeIs('admin.*');
@endphp
<x-shell :admin="true" :title="$title" :heading="$context->school()?->name ?? ($user->isSystemAdmin() ? __('All schools') : __('Administration'))"><div class="admin-grid"><aside id="admin-sidebar" class="admin-nav" :class="{'is-open':sidebarOpen}" aria-label="{{ __('Administration navigation') }}">
@if($user->isSystemAdmin() && $user->can('school.access'))
<form method="post" action="{{ route('school.switch') }}" class="school-switcher">@csrf<label for="school-switch">{{ __('Active school') }}</label><select id="school-switch" name="school_id" onchange="this.form.submit()"><option value="">{{ __('All schools') }}</option>@foreach(\App\Models\School::orderBy('name')->get() as $option)<option value="{{ $option->id }}" @selected((int) session('school_id')===$option->id)>{{ $option->name }}</option>@endforeach</select><noscript><button class="secondary small">{{ __('Switch') }}</button></noscript></form>
@endif
@if($user->can('school.access'))
@foreach($schoolGroups as $label=>$links)
@php($links=array_values(array_filter($links,fn($link)=>$user->can($link[3]))))
@if(count($links))<details class="nav-group" open><summary>{{ __($label) }}<x-ui-icon name="chevron-down"/></summary>@foreach($links as [$linkLabel,$icon,$url,$permission])
<a href="{{ $url }}" @if($isActive($url)) aria-current="page" @endif><x-ui-icon :name="$icon"/><span>{{ __($linkLabel) }}</span></a>
@endforeach</details>@endif
@endforeach
@endif
@if($canSystem)
<p class="nav-divider">{{ __('System settings') }}</p>
@foreach($groups as $label=>$links)
@php($links=array_values(array_filter($links,fn($link)=>$user->can($link[3]))))
@php($groupActive=collect($links)->contains(fn($link)=>$isActive($link[2])))
@if(count($links))<details class="nav-group" @if($inAdmin || $groupActive) open @endif><summary>{{ __($label) }}<x-ui-icon name="chevron-down"/></summary>@foreach($links as [$linkLabel,$icon,$url,$permission])
<a href="{{ $url }}" @if($isActive($url)) aria-current="page" @endif><x-ui-icon :name="$icon"/><span>{{ __($linkLabel) }}</span></a>
@endforeach</details>@endif
@endforeach
@endif
</aside><section class="admin-content">@include('partials.feedback'){{ $slot }}</section></div></x-shell>
