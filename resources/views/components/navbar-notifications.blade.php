@php($unread=auth()->user()->unreadNotifications()->count())
@php($recentNotifications=auth()->user()->notifications()->latest()->limit(8)->get())
<div class="dropdown notifications-dropdown" x-data="navbarNotifications({{ $unread }})" @click.outside="open=false" @keydown.escape.window="open=false" @notifications-updated.window="count=$event.detail.unreadCount; if(count===0) allRead=true">
<button type="button" class="icon-button notification-button" @click="open=!open" :aria-expanded="open" aria-controls="navbar-notifications" aria-label="{{ __('Notifications') }}"><x-ui-icon name="bell"/><span class="notification-count" x-show="count>0" x-text="count>99?'99+':count" x-cloak></span></button>
<section id="navbar-notifications" class="dropdown-menu notification-menu" x-show="open" x-cloak aria-label="{{ __('Notifications') }}">
<div class="notification-menu-heading"><strong>{{ __('Notifications') }}</strong><button type="button" class="text-button" @click="markAllRead()" :disabled="busy || count===0"><x-ui-icon name="check-check"/>{{ __('Mark all read') }}</button></div>
<div class="notification-list">
@forelse($recentNotifications as $notification)
<a class="notification-item" :class="{'is-unread':!allRead && {{ $notification->read_at?'false':'true' }}}" href="{{ isset($notification->data['url']) && auth()->user()->can('school.access') ? route('school.notifications.open',$notification->id) : url('/app/notifications') }}"><span class="notification-item-icon"><x-ui-icon name="bell"/></span><span><span class="notification-message">{{ __('notifications.'.($notification->data['event'] ?? 'welcome'), $notification->data['parameters'] ?? []) }}</span><time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->locale(app()->getLocale())->diffForHumans() }}</time></span><span class="unread-dot" x-show="!allRead && {{ $notification->read_at?'false':'true' }}" aria-label="{{ __('Unread') }}"></span></a>
@empty<div class="notification-empty"><x-ui-icon name="inbox"/><p>{{ __('No notifications yet.') }}</p></div>@endforelse
</div><p class="field-error notification-error" x-show="failed" role="alert" x-cloak>{{ __('Could not update notifications. Please try again.') }}</p>
<a class="notification-view-all" href="{{ auth()->user()->can('school.access') ? route('school.notifications') : url('/app/notifications') }}">{{ __('View all notifications') }}<x-ui-icon name="arrow-right"/></a>
</section></div>
