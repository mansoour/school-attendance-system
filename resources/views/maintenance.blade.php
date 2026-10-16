<!DOCTYPE html><html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar'?'rtl':'ltr' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ __('Maintenance') }}</title><link rel="icon" type="image/png" href="{{ asset('images/sized/brand-64.png') }}"><link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;color:#1e293b;font-family:'Starter IBM Plex Sans Arabic','Starter Inter',sans-serif;text-align:center;background:linear-gradient(135deg,#e6f7f6 0%,#f1ebff 55%,#fdeef6 100%)}
main{max-width:540px;background:#fff;border-radius:28px;padding:32px 28px 36px;box-shadow:0 18px 40px rgba(76,61,140,.16)}
img{width:min(260px,70vw);height:auto;display:block;margin:0 auto 10px}
h1{font-size:1.7rem;margin:0 0 8px;background:linear-gradient(135deg,#0e9aa7,#8b5cf6);-webkit-background-clip:text;background-clip:text;color:transparent}p{color:#64748b;margin:0 0 22px;line-height:1.8}
a{display:inline-block;background:linear-gradient(135deg,#0e9aa7,#8b5cf6);color:#fff;padding:12px 26px;border-radius:12px;text-decoration:none;font-weight:700}
small{display:block;margin-top:22px;color:#94a3b8;font-size:.75rem}small a{all:unset;color:#8b5cf6;cursor:pointer}
</style></head><body><main><img src="{{ asset('images/sized/maintenance.webp') }}" alt=""><h1>{{ __('We will be back soon.') }}</h1><p>{{ app(\App\Settings\SettingsService::class)->get('maintenance.message.'.app()->getLocale()) ?: __('The site is temporarily unavailable while we make improvements.') }}</p><a href="{{ route('login') }}">{{ __('Staff sign in') }}</a></main></body></html>
