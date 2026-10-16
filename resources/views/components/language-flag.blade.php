@props(['code'])
@if(str_starts_with($code,'en'))
<svg class="language-flag" viewBox="0 0 60 40" aria-hidden="true"><rect width="60" height="40" fill="#012169"/><path d="M0 0 60 40M60 0 0 40" stroke="#fff" stroke-width="9"/><path d="M0 0 60 40M60 0 0 40" stroke="#c8102e" stroke-width="3"/><path d="M30 0V40M0 20H60" stroke="#fff" stroke-width="13"/><path d="M30 0V40M0 20H60" stroke="#c8102e" stroke-width="7"/></svg>
@elseif(str_starts_with($code,'ar'))
<svg class="language-flag" viewBox="0 0 60 40" aria-hidden="true"><rect width="60" height="40" fill="#006c35"/><text x="30" y="20" text-anchor="middle" fill="#fff" font-size="8" font-family="'Starter IBM Plex Sans Arabic',sans-serif">لا إله إلا الله محمد رسول الله</text><path d="M13 28H46Q48 28 48 26M17 26V30" fill="none" stroke="#fff" stroke-width="1.6"/></svg>
@else<x-ui-icon name="globe"/>@endif
