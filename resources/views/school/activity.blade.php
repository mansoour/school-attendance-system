<x-admin-layout :title="__('Activity log')">
@php($activity=\App\Http\Controllers\School\ActivityController::class)
<div class="page-head"><div><h1>{{ __('Activity log') }}</h1><p>{{ __('Every change made by the school’s accounts is recorded here.') }}</p></div></div>
<form method="get" class="toolbar">
<label class="grow">{{ __('Action') }}<select name="action" onchange="this.form.submit()"><option value="">{{ __('All actions') }}</option>@foreach($actions as $value=>$label)<option value="{{ $value }}" @selected(request('action')===$value)>{{ $label }}</option>@endforeach</select></label>
<label>{{ __('Date') }}<input type="date" name="date" value="{{ request('date') }}"></label>
<button><x-ui-icon name="search"/>{{ __('Search') }}</button></form>
<div class="table-wrap"><table><thead><tr><th>{{ __('Time') }}</th><th>{{ __('User') }}</th><th>{{ __('Action') }}</th><th>{{ __('Record') }}</th><th>{{ __('Result') }}</th></tr></thead><tbody>
@forelse($logs as $log)
<tr><td class="small">@dateTime($log->created_at)</td><td>{{ $log->actor?->name ?? __('System') }}</td><td><span class="pill tone-{{ str_ends_with($log->action,'.deleted') ? 'absent' : (str_ends_with($log->action,'.created') ? 'excused' : 'present') }}">{{ $activity::label($log->action) }}</span></td><td class="small">{{ $activity::target($log->target_type) }} {{ $log->target_id ? '#'.$log->target_id : '' }}</td><td class="small">{{ $log->result === 'success' ? __('Succeeded') : __('Failed') }}</td></tr>
@empty<tr><td colspan="5" class="empty">{{ __('No activity yet.') }}</td></tr>@endforelse
</tbody></table></div>{{ $logs->links() }}
</x-admin-layout>
