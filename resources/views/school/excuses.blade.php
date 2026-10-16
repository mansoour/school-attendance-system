<x-admin-layout :title="__('Excuses')">
<div class="page-head"><div><h1>{{ __('Excuses') }}</h1><p>{{ __('Review excuses sent by guardians and record excuses received at school.') }}</p></div>
<div class="page-actions">@foreach(['pending','approved','rejected'] as $s)<a class="pill tone-{{ $s }}" href="{{ route('school.excuses.index',['status'=>$s]) }}">{{ __('school.excuse_status.'.$s) }}: {{ $counts[$s] ?? 0 }}</a>@endforeach<a class="pill" href="{{ route('school.excuses.index') }}">{{ __('All') }}</a></div></div>
<div class="grid-3">
<section class="card"><h2><x-ui-icon name="file-check"/>{{ $status ? __('school.excuse_status.'.$status) : __('All excuses') }}</h2>
<div class="table-wrap"><table><thead><tr><th>{{ __('Student') }}</th><th>{{ __('Date') }}</th><th>{{ __('Type') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($excuses as $excuse)
<tr><td><a href="{{ route('school.students.show',$excuse->student) }}"><strong>{{ $excuse->student?->name }}</strong></a><br><small class="muted">{{ $excuse->student?->classroom?->label() }}</small></td>
<td class="small">@date($excuse->date)</td><td><span class="pill">{{ __('school.excuse_type.'.$excuse->type) }}</span></td>
<td class="small">{{ $excuse->reason }}@if($excuse->attachment_path)<br><a href="{{ route('school.excuses.attachment',$excuse) }}"><x-ui-icon name="paperclip"/>{{ __('Attachment') }}</a>@endif<br><small class="muted">{{ __('By') }} {{ $excuse->submitter?->name }}</small></td>
<td><span class="pill tone-{{ $excuse->status }}">{{ __('school.excuse_status.'.$excuse->status) }}</span>@if($excuse->reviewer)<br><small class="muted">{{ $excuse->reviewer->name }}</small>@endif @if($excuse->review_note)<br><small>{{ $excuse->review_note }}</small>@endif</td>
<td>@can('excuses.review')<details class="row-details"><summary>{{ $excuse->status==='pending' ? __('Review') : __('Change') }}</summary><form method="post" action="{{ route('school.excuses.review',$excuse) }}">@csrf @method('PATCH')<textarea name="review_note" rows="2" placeholder="{{ __('Note (optional)') }}"></textarea><div class="contact-buttons"><button class="tiny" name="status" value="approved"><x-ui-icon name="check"/>{{ __('Approve') }}</button><button class="tiny secondary" name="status" value="rejected"><x-ui-icon name="x"/>{{ __('Reject') }}</button></div></form></details>@endcan</td></tr>
@empty<tr><td colspan="6" class="empty">{{ __('No excuses found.') }}</td></tr>@endforelse
</tbody></table></div>{{ $excuses->links() }}</section>
@can('excuses.review')
<section class="card"><h2><x-ui-icon name="file-text"/>{{ __('Record an excuse') }}</h2>
<form method="get" class="field" style="margin-bottom:12px"><label class="field">{{ __('Absences on') }}<input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()"></label></form>
@if($unexcused->isEmpty())<p class="small muted">{{ __('No unexcused absences on this day.') }}</p>@else
<form method="post" action="{{ route('school.excuses.store') }}" enctype="multipart/form-data" class="stack-sm">@csrf
<label class="field">{{ __('Student') }}<select name="attendance_record_id" required>@foreach($unexcused as $record)<option value="{{ $record->id }}">{{ $record->student?->name }} — {{ $record->student?->classroom?->label() }}</option>@endforeach</select></label>
<label class="field">{{ __('Type') }}<select name="type">@foreach(\App\Models\Excuse::TYPES as $type)<option value="{{ $type }}">{{ __('school.excuse_type.'.$type) }}</option>@endforeach</select></label>
<label class="field">{{ __('Reason') }}<textarea name="reason" rows="3" required maxlength="2000"></textarea></label>
<label class="field">{{ __('Attachment (optional)') }}<input type="file" name="attachment" accept=".pdf,image/*"></label>
<label class="check-row"><input type="checkbox" name="approve" value="1" checked>{{ __('Approve now') }}</label>
<button><x-ui-icon name="check"/>{{ __('Save excuse') }}</button></form>@endif
</section>
@endcan
</div>
</x-admin-layout>
