@props(['action'])
@if(app(\App\Settings\SettingsService::class)->get('security.recaptcha_'.$action))
@php($siteKey=app(\App\Settings\SecretStore::class)->get('recaptcha_site_key'))
<input type="hidden" name="recaptcha_token" data-recaptcha-action="{{ $action }}" data-site-key="{{ $siteKey }}">
<script src="https://www.google.com/recaptcha/api.js?render={{ urlencode($siteKey ?? '') }}" async defer></script>
<p class="field-help">{{ __('This form is protected by Google reCAPTCHA.') }}</p>
@endif
