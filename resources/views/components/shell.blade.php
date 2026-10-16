@props(['title' => null, 'admin' => false, 'heading' => null])
@php
$settings=app(\App\Settings\SettingsService::class); $locale=app()->getLocale();
$brand=$settings->get('branding.name.'.$locale) ?: $settings->get('branding.name.en');
$logo=$settings->get('branding.logo.'.$locale) ?: $settings->get('branding.logo.en');
$assets=app(\App\Support\BrandingStorage::class);
$languages=\App\Models\Locale::where('is_active',true)->get();
$direction=\App\Models\Locale::find($locale)?->direction ?? 'ltr';
@endphp
<!DOCTYPE html><html lang="{{ $locale }}" dir="{{ $direction }}" data-default-theme="{{ $settings->get('appearance.theme') }}" data-dark-enabled="{{ $settings->get('appearance.dark_enabled') ? '1':'0' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title ? $title.' | ' : '' }}{{ $brand }}</title>
<script>
(() => { const root=document.documentElement; let choice=root.dataset.defaultTheme; try { choice=localStorage.getItem('theme') || choice; } catch {} const dark=root.dataset.darkEnabled==='1' && (choice==='dark' || (choice==='system' && matchMedia('(prefers-color-scheme: dark)').matches)); root.dataset.theme=dark?'dark':'light'; root.classList.toggle('dark',dark); root.style.colorScheme=dark?'dark':'light'; })();
</script>
<link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
@if($settings->get('branding.favicon'))<link rel="icon" href="{{ $assets->url($settings->get('branding.favicon')) }}">@endif
@if($settings->get('branding.touch_icon'))<link rel="apple-touch-icon" href="{{ $assets->url($settings->get('branding.touch_icon')) }}">@endif
<style>
@foreach(['light','dark'] as $theme)
{{ $theme==='light'?':root':'html[data-theme=dark]' }} {
@foreach(['primary'=>'brand','cream'=>'paper','accent'=>'accent','text'=>'ink','sage'=>'sage','muted'=>'muted','border'=>'line','surface'=>'surface'] as $token=>$variable)
--{{ $variable }}:{{ $settings->get('styling.'.$theme.'.'.$token) }};
@endforeach
}
@endforeach
:root { --body-font:{{ $settings->get('branding.font_body')==='inter' ? 'Starter Inter' : 'system-ui' }}; --heading-font:{{ ['intertight'=>'Starter Inter Tight','inter'=>'Starter Inter','system'=>'system-ui'][$settings->get('branding.font_heading')] ?? 'system-ui' }}; --arabic-font:{{ $settings->get('branding.font_arabic')==='ibm' ? 'Starter IBM Plex Sans Arabic' : 'Tahoma' }}; }
</style>
@vite(['resources/css/app.css','resources/css/theme.css','resources/js/app.js'])</head>
<body class="{{ $admin ? 'admin-body' : '' }}" x-data="{sidebarOpen:false}">
<header class="site-header {{ $admin?'admin-header':'' }}">
@if($admin)<button type="button" class="icon-button mobile-menu" @click="sidebarOpen=!sidebarOpen" :aria-expanded="sidebarOpen" aria-controls="admin-sidebar" aria-label="{{ __('Toggle navigation') }}"><x-ui-icon name="menu"/></button>@endif
<a class="brand" href="{{ route('home') }}">@if($logo)<img src="{{ $assets->url($logo) }}" alt="{{ $brand }}" class="brand-logo">@else<span class="brand-mark"><x-ui-icon name="heart-handshake"/></span><span>{{ $brand }}</span>@endif</a>
@if(!$admin)<nav aria-label="{{ __('Main navigation') }}"><a href="{{ route('home') }}">{{ __('Home') }}</a><a href="{{ route('pages.show','about') }}">{{ __('About') }}</a><a href="{{ route('contact') }}">{{ __('Contact') }}</a></nav>@else<span class="admin-heading">{{ $heading ?? __('Administration') }}</span>@endif
<div class="header-actions">
@auth<x-navbar-notifications/>@endauth
@if($settings->get('appearance.dark_enabled'))<div class="dropdown" x-data="{open:false}" @click.outside="open=false" @keydown.escape.window="open=false"><button type="button" class="icon-button" @click="open=!open" :aria-expanded="open" aria-label="{{ __('Theme') }}"><x-ui-icon name="sun-moon"/></button><div class="dropdown-menu" x-show="open" x-cloak>@foreach(['light'=>'Light','dark'=>'Dark','system'=>'System'] as $theme=>$label)<button type="button" class="text-button" data-theme-choice="{{ $theme }}" @click="open=false">{{ __($label) }}</button>@endforeach</div></div>@endif
@if($languages->where('code','!=',$locale)->isNotEmpty())
<div class="dropdown language-dropdown" x-data="{open:false}" @click.outside="open=false" @keydown.escape.window="open=false">
<button type="button" class="text-button language-control" @click="open=!open" :aria-expanded="open" aria-controls="navbar-languages"><x-ui-icon name="languages"/><span>{{ __('Language') }}</span><x-ui-icon name="chevron-down"/></button>
<div id="navbar-languages" class="dropdown-menu" x-show="open" x-cloak>
@foreach($languages->where('code','!=',$locale) as $language)<form method="post" action="{{ route('locale') }}">@csrf<input type="hidden" name="locale" value="{{ $language->code }}"><button class="text-button" lang="{{ $language->code }}"><x-language-flag :code="$language->code"/><span>{{ $language->native_name }}</span></button></form>@endforeach
</div></div>
@endif
@auth
<div class="dropdown user-dropdown" x-data="{open:false}" @click.outside="open=false" @keydown.escape.window="open=false"><button type="button" class="user-control" @click="open=!open" :aria-expanded="open"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><span class="user-name">{{ auth()->user()->name }}</span><x-ui-icon name="chevron-down"/></button><div class="dropdown-menu" x-show="open" x-cloak><div class="dropdown-identity"><strong>{{ auth()->user()->name }}</strong><small dir="ltr">{{ auth()->user()->email }}</small></div>@can('school.access')<a href="{{ route('school.dashboard') }}"><x-ui-icon name="school"/>{{ __('Dashboard') }}</a>@endcan<a href="{{ route('profile.edit') }}"><x-ui-icon name="user"/>{{ __('Account') }}</a><a href="{{ route('security.edit') }}"><x-ui-icon name="shield-check"/>{{ __('Security') }}</a>@can('admin.access')<a href="{{ route('admin.index') }}"><x-ui-icon name="settings"/>{{ __('Administration') }}</a>@endcan<form method="post" action="{{ route('logout') }}">@csrf<button class="text-button"><x-ui-icon name="log-out"/>{{ __('Log Out') }}</button></form></div></div>
@else<a class="button small secondary" href="{{ route('login') }}"><x-ui-icon name="log-in"/>{{ __('Sign in') }}</a>@if($settings->get('security.registration_open'))<a class="button small" href="{{ route('register') }}"><x-ui-icon name="user-plus"/>{{ __('Guardian registration') }}</a>@endif @endauth
</div></header>
@if(!$admin)@foreach(['above','below'] as $position)@foreach(\App\Models\SiteNotice::showing($position) as $notice)<div class="notice" x-data="{visible: !localStorage.getItem('notice-{{ $notice['id'] }}')}" x-show="visible">{{ $notice['message'] }} @if($notice['link_url'])<a href="{{ $notice['link_url'] }}">{{ $notice['link_label'] }}</a>@endif @if($notice['dismissible'])<button type="button" aria-label="{{ __('Dismiss') }}" @click="visible=false;localStorage.setItem('notice-{{ $notice['id'] }}','1')"><x-ui-icon name="x"/></button>@endif</div>@endforeach @endforeach @endif
<main class="{{ $admin?'admin-main':'container' }}">
@if(!$admin)@include('partials.feedback')@endif
{{ $slot }}
</main>
@if(!$admin)<footer><a class="brand" href="{{ route('home') }}">{{ $brand }}</a><span>{{ __('Communication builds commitment') }}</span><div><a href="{{ route('pages.show','help') }}">{{ __('Help') }}</a><a href="{{ route('pages.show','privacy') }}">{{ __('Privacy') }}</a><a href="{{ route('pages.show','terms') }}">{{ __('Terms') }}</a></div><div class="social-links">@foreach(['x','instagram','linkedin','youtube','facebook','tiktok','github','whatsapp'] as $social)@if($url=$settings->get('branding.social.'.$social))<a href="{{ $url }}" rel="noopener noreferrer" aria-label="{{ ucfirst($social) }}"><x-ui-icon name="external-link"/>{{ ucfirst($social) }}</a>@endif @endforeach</div><small>&copy; {{ date('Y') }} {{ $brand }}</small></footer>@endif
@if($settings->get('branding.cookie_enabled'))<aside class="cookie-notice" x-data="{visible:!localStorage.getItem('cookie-notice-accepted')}" x-show="visible" x-cloak><x-ui-icon name="cookie"/><p>{{ $settings->get('branding.cookie_text.'.$locale) ?: __('We use essential cookies to keep you signed in, protect forms and remember your choices.') }} <a href="{{ route('pages.show','cookies') }}">{{ __('Cookie policy') }}</a></p><button type="button" @click="localStorage.setItem('cookie-notice-accepted','1');visible=false">{{ __('Accept') }}</button></aside>@endif
</body></html>
