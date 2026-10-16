<x-admin-layout>
<div class="page-title"><x-ui-icon :name="$definition['icon']"/><h1>{{ __($definition['title']) }}</h1></div><p>{{ __($definition['description']) }}</p>
@if(in_array($section,['branding','icons','cookies','social','styling','fonts']))
<nav class="setting-tabs" aria-label="{{ __('Branding') }}">@foreach(['branding','icons','cookies','social','styling','fonts'] as $tab)<a href="{{ route('admin.settings',$tab) }}" @if($section===$tab) aria-current="page" @endif>{{ __(\App\Settings\SettingsRegistry::sections()[$tab]['title']) }}</a>@endforeach</nav>
@endif
<form method="post" enctype="multipart/form-data" action="{{ route('admin.settings.save',$section) }}" class="settings-form" x-data="{colors: {{ Illuminate\Support\Js::from($values->all()) }}, previewTheme: 'light'}">@csrf
<div class="{{ $section==='styling' ? 'styling-grid' : '' }}"><div>
@foreach(collect($definition['fields'])->groupBy(fn($field)=>$field['theme'] ?? '', preserveKeys: true) as $theme=>$fields)
<section class="settings-card stack">
@if($theme)<h2>{{ __($theme==='light'?'Light theme':'Dark theme') }}</h2>@endif
@foreach($fields as $key=>$field)
@php($input=str_replace('.','_',$key))
@php($value=old($input,$values[$key]))
<div class="setting-field">
@if($field['type']==='boolean')
<input type="hidden" name="{{ $input }}" value="0"><label class="check-label"><input type="checkbox" name="{{ $input }}" value="1" @checked($value)><span>{{ __($field['label']) }}</span></label>
@else
<label for="{{ $input }}">{{ __($field['label']) }}</label>
@if($field['type']==='textarea')
<textarea id="{{ $input }}" name="{{ $input }}" rows="3" @if(str_ends_with($key,'.ar')) dir="rtl" @elseif(str_ends_with($key,'.en')) dir="ltr" @endif>{{ $value }}</textarea>
@elseif($field['type']==='select')
<select id="{{ $input }}" name="{{ $input }}">@foreach($field['options'] as $option=>$label)<option value="{{ $option }}" @selected($value===$option)>{{ __($label) }}</option>@endforeach</select>
@elseif($field['type']==='roles')
<select id="{{ $input }}" name="{{ $input }}[]" multiple>@foreach(\Spatie\Permission\Models\Role::all() as $role)<option value="{{ $role->name }}" @selected(in_array($role->name,$value ?? []))>{{ __(ucfirst($role->name)) }}</option>@endforeach</select>
@elseif($field['type']==='file')
@if($value)<img class="asset-preview" src="{{ app(\App\Support\BrandingStorage::class)->url($value) }}" alt="{{ __($field['label']) }}"><label><input type="checkbox" name="{{ $input }}_remove" value="1">{{ __('Remove current image') }}</label>@endif
<input id="{{ $input }}" name="{{ $input }}" type="file" accept="image/png,image/jpeg,image/webp">
@elseif($field['type']==='color')
<div class="color-field"><input type="color" aria-label="{{ __($field['label']) }}" x-model="colors['{{ $key }}']"><input id="{{ $input }}" name="{{ $input }}" dir="ltr" pattern="#[0-9a-fA-F]{6}" x-model="colors['{{ $key }}']"></div>
@else
<input id="{{ $input }}" name="{{ $input }}" value="{{ $value }}" type="{{ $field['type'] }}" @if(isset($field['min'])) min="{{ $field['min'] }}" max="{{ $field['max'] }}" @endif>
@endif
@endif
@if($field['help'] ?? '')<p class="field-help">{{ __($field['help']) }}</p>@endif
@error($input)<p class="field-error">{{ $message }}</p>@enderror
</div>
@endforeach
</section>
@endforeach
</div>
@if($section==='styling')
<aside class="style-preview"><div class="title-row"><h2>{{ __('Live preview') }}</h2><select x-model="previewTheme" aria-label="{{ __('Preview theme') }}"><option value="light">{{ __('Light theme') }}</option><option value="dark">{{ __('Dark theme') }}</option></select></div>
<div class="preview-card" :style="{backgroundColor: colors['styling.'+previewTheme+'.surface'],color:colors['styling.'+previewTheme+'.text'],borderColor:colors['styling.'+previewTheme+'.border']}"><div :style="{backgroundColor:colors['styling.'+previewTheme+'.cream']}"><span class="badge" :style="{backgroundColor:colors['styling.'+previewTheme+'.sage']}">{{ __('Your next chapter') }}</span><h2>{{ __('A fresh start for your next idea.') }}</h2><p :style="{color:colors['styling.'+previewTheme+'.muted']}">{{ __('Your account, your workspace, your next chapter.') }}</p><span class="button" :style="{backgroundColor:colors['styling.'+previewTheme+'.primary']}">{{ __('Get started') }}</span></div><div dir="rtl" lang="ar"><h2>{{ __('Your next chapter', [], 'ar') }}</h2><p>{{ __('Your account, your workspace, your next chapter.', [], 'ar') }}</p></div><div :style="{backgroundColor:colors['styling.'+previewTheme+'.primary']}"><span class="button" :style="{backgroundColor:colors['styling.'+previewTheme+'.accent'],color:'#102219'}">{{ __('Explore') }}</span></div></div><p class="field-help">{{ __('This preview is private. Save to apply your changes.') }}</p></aside>
@endif
</div><button><x-ui-icon name="check"/>{{ __('Save changes') }}</button></form>
@if(in_array($section,['styling','fonts']))<form method="post" action="{{ route('admin.settings.reset',$section) }}" class="reset-form">@csrf<button class="secondary"><x-ui-icon name="rotate-ccw"/>{{ __('Restore defaults') }}</button></form>@endif
</x-admin-layout>
