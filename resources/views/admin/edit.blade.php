<x-admin-layout><p class="eyebrow">{{ __(ucwords(str_replace('-',' ',$resource))) }}</p><h1>{{ $record->exists ? __('Edit entry') : __('New entry') }}</h1><form method="post" action="{{ route('admin.resources.save',[$resource,$record->id]) }}" class="stack">@csrf
@foreach($fields as $field=>$rule)
@php($value=old($field,data_get($record,$field)))
@php($name=preg_replace('/\.([^\.]+)/','[$1]',$field))
<label>{{ __(ucwords(str_replace(['.','_'], ' ', $field))) }}
@if(str_contains($rule,'boolean'))<input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" name="{{ $name }}" value="1" @checked($value)>
@elseif(str_contains($rule,'|in:'))<select name="{{ $name }}">@foreach(explode(',',explode('|',explode('|in:',$rule)[1])[0]) as $option)<option value="{{ $option }}" @selected($value===$option)>{{ __(ucfirst($option)) }}</option>@endforeach</select>
@elseif(str_starts_with($field,'body.') || str_starts_with($field,'description.'))<textarea name="{{ $name }}" rows="7">{{ $value }}</textarea>
@else<input name="{{ $name }}" type="{{ str_contains($rule,'date') ? 'datetime-local' : ((str_contains($rule,'integer') || str_contains($rule,'numeric')) ? 'number' : 'text') }}" value="{{ $value instanceof \Carbon\CarbonInterface ? $value->format('Y-m-d\TH:i') : ($value ?? (str_contains($rule,'integer')?'0':'')) }}" @if(str_contains($rule,'numeric')) step="0.01" @endif>
@endif</label>@endforeach
@if($resource==='site-notices')<p class="quiet">{{ __('Schedule times are in UTC.') }}</p>@endif
@if($resource==='pages')<p class="quiet">{{ __('Body supports Markdown. Raw HTML is escaped.') }}</p>@endif
@if($resource==='email-templates')<p class="quiet">{{ __('Use {name} for the recipient. Templates are plain text. Security email cannot be disabled.') }}</p>@endif
<button>{{ __('Save changes') }}</button></form></x-admin-layout>
