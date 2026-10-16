@php($registering=request()->routeIs('register'))
<x-shell><div class="auth-split">
<div class="auth-card"><p class="eyebrow">{{ __('Communication builds commitment') }}</p><h1>{{ $registering ? __('Guardian registration') : __('Welcome') }}</h1>{{ $slot }}</div>
<div class="auth-visual" aria-hidden="true"><img src="{{ asset('images/sized/'.($registering ? 'guardian-registration' : 'sign-in').'.webp') }}" alt="" width="720" height="840"></div>
</div></x-shell>
