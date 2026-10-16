<x-shell :title="$page->localized('title')">
@if($page->slug==='about')<div class="page-banner"><img src="{{ asset('images/sized/about.webp') }}" alt="{{ __('A welcoming school entrance with a heart above the door') }}" width="1400" height="700"></div>@endif
<article class="reading"><h1>{{ $page->localized('title') }}</h1><div class="prose">{!! \Illuminate\Support\Str::markdown($page->localized('body') ?? '', ['html_input'=>'escape','allow_unsafe_links'=>false]) !!}</div></article></x-shell>
