<x-admin-layout :title="__('Notifications')">
<div class="page-head"><div><h1>{{ __('Notifications') }}</h1></div>
<form method="post" action="{{ route('school.notifications.read-all') }}" class="page-actions">@csrf<button class="secondary"><x-ui-icon name="check-check"/>{{ __('Mark all read') }}</button>@if(auth()->user()->can('settings.manage'))<a class="button secondary" href="{{ route('admin.settings','school-notifications') }}"><x-ui-icon name="settings"/>{{ __('Notification settings') }}</a>@endif</form></div>
<section class="card"><ul class="list">
@forelse($notifications as $notification)
<li style="{{ $notification->read_at ? '' : 'background:var(--s-present-bg)' }}"><span class="list-icon"><x-ui-icon name="bell"/></span>
<div style="flex:1"><a href="{{ route('school.notifications.open',$notification->id) }}"><strong class="small">{{ __('notifications.'.($notification->data['event'] ?? 'welcome'), $notification->data['parameters'] ?? []) }}</strong></a><small>{{ $notification->created_at->locale(app()->getLocale())->diffForHumans() }} · {{ \App\Support\Dates::both($notification->created_at) }}</small></div>
@unless($notification->read_at)<span class="unread-dot" aria-label="{{ __('Unread') }}"></span>@endunless</li>
@empty<li class="empty empty-notifications"><x-ui-icon name="inbox"/><br>{{ __('No notifications yet.') }}</li>@endforelse
</ul>{{ $notifications->links() }}</section>
</x-admin-layout>
