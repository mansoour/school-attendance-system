<x-shell>
@php($settings=app(\App\Settings\SettingsService::class))
@php($locale=app()->getLocale())
<section class="home-hero">
<div><p class="eyebrow"><span class="status-dot"></span> {{ __('Communication builds commitment') }}</p>
<h1>{{ $settings->get('homepage.title.'.$locale) ?: $settings->get('homepage.title.en') }}</h1>
<p class="hero-description">{{ $settings->get('homepage.description.'.$locale) ?: $settings->get('homepage.description.en') }}</p>
<div class="hero-actions">@auth<a class="button" href="{{ route('dashboard') }}">{{ __('Go to dashboard') }} <x-ui-icon name="arrow-up-right"/></a>@else<a class="button" href="{{ route('login') }}"><x-ui-icon name="log-in"/>{{ __('Sign in') }}</a>@if($settings->get('security.registration_open'))<a class="button secondary" href="{{ route('register') }}"><x-ui-icon name="user-plus"/>{{ __('Guardian registration') }}</a>@endif @endauth</div>
<p class="quiet">{{ __('School staff accounts are created by the school administration.') }}</p></div>
<div class="home-art" aria-hidden="true">
<div class="stat tone-present"><span class="stat-icon"><x-ui-icon name="calendar-check"/></span><div><strong>{{ __('Daily attendance') }}</strong><span>{{ __('Record absences and late arrivals for every class in minutes.') }}</span></div></div>
<div class="stat tone-violet"><span class="stat-icon"><x-ui-icon name="message-circle"/></span><div><strong>{{ __('Instant guardian contact') }}</strong><span>{{ __('Notifications, WhatsApp, calls and email from one screen.') }}</span></div></div>
<div class="stat tone-excused"><span class="stat-icon"><x-ui-icon name="file-check"/></span><div><strong>{{ __('Online excuses') }}</strong><span>{{ __('Guardians send excuses and the right staff member reviews them.') }}</span></div></div>
<div class="stat tone-late"><span class="stat-icon"><x-ui-icon name="chart-column"/></span><div><strong>{{ __('Reports and statistics') }}</strong><span>{{ __('Absence rates by grade and class, exportable to Excel and PDF.') }}</span></div></div>
</div>
</section>
<section class="home-roles">
@foreach(['principal'=>'crown','vice_principal'=>'users-round','student_affairs'=>'file-text','health_guide'=>'heart-pulse','counselor'=>'user-round','guardian'=>'users'] as $role=>$icon)
<div class="role-card"><span class="role-icon"><x-ui-icon :name="$icon"/></span><strong>{{ __('school.roles.'.$role) }}</strong><small>{{ __('school.role_descriptions.'.$role) }}</small></div>
@endforeach
</section>
</x-shell>
