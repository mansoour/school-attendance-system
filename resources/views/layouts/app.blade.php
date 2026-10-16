<x-shell>
<div class="workspace-nav"><a href="{{ route('member') }}">{{ __('Overview') }}</a><a href="{{ route('profile.edit') }}">{{ __('Account') }}</a><a href="{{ route('security.edit') }}">{{ __('Security') }}</a>@can('admin.access')<a href="{{ route('admin.index') }}">{{ __('Administration') }}</a>@endcan<form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="text-button">{{ __('Log out') }}</button></form></div>
@isset($header)<div class="page-heading">{{ $header }}</div>@endisset
{{ $slot }}
</x-shell>
