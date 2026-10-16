<x-shell>
@php($settings=app(\App\Settings\SettingsService::class))
@php($locale=app()->getLocale())
<section class="home-hero">
<div><p class="eyebrow"><span class="status-dot"></span> {{ __('Communication builds commitment') }}</p>
<h1>{{ $settings->get('homepage.title.'.$locale) ?: $settings->get('homepage.title.en') }}</h1>
<p class="hero-description">{{ $settings->get('homepage.description.'.$locale) ?: $settings->get('homepage.description.en') }}</p>
<div class="hero-actions">@auth<a class="button" href="{{ route('dashboard') }}">{{ __('Go to dashboard') }} <x-ui-icon name="arrow-up-right"/></a>@else<a class="button" href="{{ route('login') }}"><x-ui-icon name="log-in"/>{{ __('Sign in') }}</a>@if($settings->get('security.registration_open'))<a class="button secondary" href="{{ route('register') }}"><x-ui-icon name="user-plus"/>{{ __('Guardian registration') }}</a>@endif @endauth</div>
<p class="quiet">{{ __('School staff accounts are created by the school administration.') }}</p></div>
<div class="home-visual"><img src="{{ asset('images/sized/hero.webp') }}" alt="{{ __('Students walking into school, connected with their families through Tawasul') }}" width="1100" height="963" fetchpriority="high"></div>
</section>

<section class="home-features">
<div class="stat tone-present"><span class="stat-icon"><x-ui-icon name="calendar-check"/></span><div><strong>{{ __('Daily attendance') }}</strong><span>{{ __('Record absences and late arrivals for every class in minutes.') }}</span></div></div>
<div class="stat tone-violet"><span class="stat-icon"><x-ui-icon name="message-circle"/></span><div><strong>{{ __('Instant guardian contact') }}</strong><span>{{ __('Notifications, WhatsApp, calls and email from one screen.') }}</span></div></div>
<div class="stat tone-excused"><span class="stat-icon"><x-ui-icon name="file-check"/></span><div><strong>{{ __('Online excuses') }}</strong><span>{{ __('Guardians send excuses and the right staff member reviews them.') }}</span></div></div>
<div class="stat tone-late"><span class="stat-icon"><x-ui-icon name="chart-column"/></span><div><strong>{{ __('Reports and statistics') }}</strong><span>{{ __('Absence rates by grade and class, exportable to Excel and PDF.') }}</span></div></div>
</section>

<section class="home-section"><p class="eyebrow">{{ __('One platform for the whole school') }}</p><h2>{{ __('A dedicated dashboard for every role') }}</h2></section>
<section class="home-roles">
@foreach(['principal','vice_principal','student_affairs','health_guide','counselor','guardian'] as $role)
<div class="role-card"><img class="role-img" src="{{ asset('images/sized/'.$role.'.webp') }}" alt="" width="96" height="96" loading="lazy"><strong>{{ __('school.roles.'.$role) }}</strong><small>{{ __('school.role_descriptions.'.$role) }}</small></div>
@endforeach
</section>

<section class="home-split">
<img src="{{ asset('images/sized/guardian-communication.webp') }}" alt="" width="560" height="336" loading="lazy">
<div><p class="eyebrow">{{ __('For guardians') }}</p><h2>{{ __('Know right away, act right away') }}</h2><p>{{ __('Guardians are notified as soon as an absence or late arrival is recorded, can send the excuse with a medical report in seconds, and follow the school’s notes about their children.') }}</p>@guest @if($settings->get('security.registration_open'))<a class="button" href="{{ route('register') }}"><x-ui-icon name="user-plus"/>{{ __('Guardian registration') }}</a>@endif @endguest</div>
</section>
<section class="home-split reverse">
<img src="{{ asset('images/sized/reports.webp') }}" alt="" width="560" height="336" loading="lazy">
<div><p class="eyebrow">{{ __('For school staff') }}</p><h2>{{ __('Clear numbers for better decisions') }}</h2><p>{{ __('Daily attendance per class, repeated-absence alerts, counseling and health notes, and reports by grade and class exportable to Excel and PDF.') }}</p></div>
</section>
</x-shell>
