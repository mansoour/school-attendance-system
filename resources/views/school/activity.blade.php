<x-admin-layout :title="__('Activity log')">
<div class="page-head"><div><h1>{{ __('Activity log') }}</h1><p>{{ __('Every change made by the school’s accounts is recorded here.') }}</p></div></div>
<form method="get" class="toolbar">
<label class="grow">{{ __('Action') }}<input name="action" value="{{ request('action') }}" placeholder="attendance_record, student, excuse…" dir="ltr"></label>
<label>{{ __('Date') }}<input type="date" name="date" value="{{ request('date') }}"></label>
<button><x-ui-icon name="search"/>{{ __('Search') }}</button></form>
<div class="table-wrap"><table><thead><tr><th>{{ __('Time') }}</th><th>{{ __('User') }}</th><th>{{ __('Action') }}</th><th>{{ __('Record') }}</th><th>{{ __('Details') }}</th></tr></thead><tbody>
@forelse($logs as $log)
<tr><td class="small" dir="ltr">@dateTime($log->created_at)</td><td>{{ $log->actor?->name ?? __('System') }}</td><td><code dir="ltr">{{ $log->action }}</code></td><td class="small muted" dir="ltr">{{ class_basename((string) $log->target_type) }} {{ $log->target_id ? '#'.$log->target_id : '' }}</td><td class="small muted" dir="ltr">{{ $log->metadata ? \Illuminate\Support\Str::limit(json_encode($log->metadata, JSON_UNESCAPED_UNICODE), 120) : '' }}</td></tr>
@empty<tr><td colspan="5" class="empty">{{ __('No activity yet.') }}</td></tr>@endforelse
</tbody></table></div>{{ $logs->links() }}
</x-admin-layout>
