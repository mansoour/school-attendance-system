@php
$settings=app(\App\Settings\SettingsService::class);
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
@endphp
<x-shell :admin="true"><div class="admin-grid"><aside id="admin-sidebar" class="admin-nav" :class="{'is-open':sidebarOpen}" aria-label="{{ __('Administration navigation') }}">
@foreach($groups as $label=>$links)
@php($links=array_values(array_filter($links,fn($link)=>auth()->user()->can($link[3]))))
@if(count($links))<details class="nav-group" open><summary>{{ __($label) }}<x-ui-icon name="chevron-down"/></summary>@foreach($links as [$label,$icon,$url,$permission])
@php($active=url()->current()===$url || (str_contains($url,'/manage/') && str_starts_with(url()->current(),$url.'/')) || ($url===route('admin.settings','branding') && in_array(request()->route('section'),['icons','cookies','social','styling','fonts'])))
<a href="{{ $url }}" @if($active) aria-current="page" @endif><x-ui-icon :name="$icon"/><span>{{ __($label) }}</span></a>
@endforeach</details>@endif
@endforeach
</aside><section class="admin-content">@include('partials.feedback'){{ $slot }}</section></div></x-shell>
