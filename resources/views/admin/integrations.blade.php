<x-admin-layout><div class="page-title"><x-ui-icon name="plug"/><h1>{{ __('Integrations') }}</h1></div>
<div class="integration-grid">
@foreach($cards as $provider=>$card)
<section class="settings-card"><div class="title-row"><h2><x-ui-icon :name="$card['icon']"/>{{ __($card['title']) }}</h2><span class="badge {{ count(array_diff(array_keys($card['keys']),$configured)) ? 'pending' : '' }}">{{ __(count(array_diff(array_keys($card['keys']),$configured)) ? 'Not configured' : 'Configured') }}</span></div><p>{{ __($card['help']) }}</p>
@if($provider==='resend')<p>{{ __('Mailer') }}: <code>{{ config('mail.default') }}</code><br>{{ __('From') }}: <bdi>{{ config('mail.from.address') }}</bdi></p>@endif
@foreach($card['keys'] as $key=>$label)
<div class="secret-card"><div class="title-row"><label for="{{ $key }}">{{ __($label) }}</label><small>{{ __(in_array($key,$configured)?'Saved securely':'Not set') }}</small></div>
<form method="post" action="{{ route('admin.integrations.save') }}" class="secret-form">@csrf<input type="hidden" name="key" value="{{ $key }}"><input id="{{ $key }}" type="password" name="value" required autocomplete="new-password" placeholder="{{ __('Enter a new value to replace it') }}" @if($key==='backup_archive_password') minlength="12" @endif><button class="secondary">{{ __('Save') }}</button></form>
@if(in_array($key,$configured))<form method="post" action="{{ route('admin.integrations.remove') }}">@csrf @method('DELETE')<input type="hidden" name="key" value="{{ $key }}"><button class="text-button danger">{{ __('Remove key') }}</button></form>@endif</div>
@endforeach
@if($provider==='gemini')<form method="post" action="{{ route('admin.integrations.model') }}" class="stack">@csrf<label>{{ __('Model') }}<input name="model" value="{{ app(\App\Settings\SettingsService::class)->get('integrations.gemini_model') }}" required></label><button class="secondary">{{ __('Save') }}</button></form>@endif
<div class="card-actions">@if(in_array($provider,['resend','gemini','r2']))<form method="post" action="{{ route('admin.integrations.test',$provider) }}">@csrf<button class="secondary"><x-ui-icon name="activity"/>{{ __($provider==='resend'?'Send me a test email':'Test connection') }}</button></form>@endif
@if($provider==='r2')<a href="{{ route('admin.settings','storage') }}">{{ __('Account, bucket and storage switch') }}</a>@endif
@if($provider==='recaptcha')<a href="{{ route('admin.settings','settings') }}">{{ __('Choose forms and minimum score') }}</a>@endif</div>
</section>
@endforeach</div></x-admin-layout>
