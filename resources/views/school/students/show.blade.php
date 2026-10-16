<x-admin-layout :title="$student->name">
<div class="page-head"><div class="child-card"><span class="avatar">{{ mb_substr($student->name,0,1) }}</span><div><h1>{{ $student->name }}</h1><p><span dir="ltr">{{ $student->national_id }}</span> · {{ $student->classroom?->label() ?? __('Without class') }}</p></div></div>
<div class="page-actions">@can('students.manage')<a class="button secondary" href="{{ route('school.students.edit',$student) }}"><x-ui-icon name="pencil"/>{{ __('Edit') }}</a>@endcan</div></div>
<div class="stat-grid">
@foreach(['absent'=>'user-x','late'=>'clock','excused'=>'file-check','present'=>'circle-check'] as $status=>$icon)
<div class="stat tone-{{ $status }}"><span class="stat-icon"><x-ui-icon :name="$icon"/></span><div><strong>{{ $counts[$status] ?? 0 }}</strong><span>{{ __('school.status.'.$status) }}</span></div></div>
@endforeach
</div>
<div class="grid-3">
<div class="stack-sm">
<section class="card"><h2><x-ui-icon name="calendar"/>{{ __('Absence and lateness record') }}</h2>
<div class="table-wrap"><table><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Status') }}</th><th>{{ __('Note') }}</th><th>{{ __('Excuse') }}</th><th>{{ __('Recorded by') }}</th></tr></thead><tbody>
@forelse($records as $record)
<tr><td>@date($record->date)</td><td><span class="pill tone-{{ $record->status }}">{{ __('school.status.'.$record->status) }}@if($record->late_minutes) · {{ $record->late_minutes }} {{ __('min') }}@endif</span></td><td class="small">{{ $record->note }}</td>
<td>@if($excuse=$record->excuses->last())<span class="pill tone-{{ $excuse->status }}">{{ __('school.excuse_status.'.$excuse->status) }}</span>@elseif($record->status==='absent')
@can('excuses.review')<details class="row-details"><summary>{{ __('Add excuse') }}</summary><form method="post" action="{{ route('school.excuses.store') }}" enctype="multipart/form-data">@csrf<input type="hidden" name="attendance_record_id" value="{{ $record->id }}"><select name="type">@foreach(\App\Models\Excuse::TYPES as $type)<option value="{{ $type }}">{{ __('school.excuse_type.'.$type) }}</option>@endforeach</select><textarea name="reason" rows="2" required placeholder="{{ __('Reason') }}"></textarea><input type="file" name="attachment" accept=".pdf,image/*"><label class="check-row"><input type="checkbox" name="approve" value="1" checked>{{ __('Approve now') }}</label><button class="tiny">{{ __('Save') }}</button></form></details>@else<span class="muted">—</span>@endcan
@else<span class="muted">—</span>@endif</td><td class="small muted">{{ $record->recorder?->name }}</td></tr>
@empty<tr><td colspan="5" class="empty">{{ __('No absences or late arrivals recorded.') }}</td></tr>@endforelse
</tbody></table></div></section>
<section class="card"><h2><x-ui-icon name="notebook-pen"/>{{ __('Notes') }}</h2>
@can('notes.manage')<form method="post" action="{{ route('school.notes.store') }}" class="form-grid" style="margin-bottom:14px">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}">
<label>{{ __('Category') }}<select name="category">@foreach(\App\Models\StudentNote::CATEGORIES as $category)<option value="{{ $category }}">{{ __('school.note_category.'.$category) }}</option>@endforeach</select></label>
<label class="full">{{ __('Note') }}<textarea name="body" rows="2" required maxlength="3000"></textarea></label>
<label class="check-row"><input type="checkbox" name="visible_to_guardian" value="1">{{ __('Share with guardian') }}</label><div class="form-actions" style="margin:0"><button class="tiny"><x-ui-icon name="check"/>{{ __('Add note') }}</button></div></form>@endcan
<ul class="list">@forelse($notes as $note)<li><span class="list-icon"><x-ui-icon name="notebook-pen"/></span><div><span class="pill">{{ __('school.note_category.'.$note->category) }}</span>@if($note->visible_to_guardian) <span class="pill tone-violet">{{ __('Shared with guardian') }}</span>@endif<p style="margin:6px 0;color:var(--ink)">{{ $note->body }}</p><small>{{ $note->author?->name }} · {{ \App\Support\Dates::both($note->created_at) }}</small></div></li>@empty<li class="small muted">{{ __('No notes yet.') }}</li>@endforelse</ul></section>
</div>
<div class="stack-sm">
<section class="card"><h2><x-ui-icon name="users"/>{{ __('Guardian') }}</h2>
<p style="color:var(--ink);margin:0">{{ $student->guardian_name ?? '—' }} @if($student->guardian_relation)<span class="pill">{{ __('school.relation.'.$student->guardian_relation) }}</span>@endif</p>
<p class="small" dir="ltr" style="text-align:start">{{ $student->guardian_phone }} {{ $student->guardian_email }}</p>
@include('school.partials.contact-buttons',['student'=>$student,'date'=>today(),'status'=>'absent'])
<h3 class="small" style="margin-top:16px">{{ __('Linked guardian accounts') }}</h3>
<ul class="list">@forelse($student->guardians as $guardian)<li><div><strong class="small">{{ $guardian->name }}</strong> <span class="pill tone-{{ $guardian->pivot->status }}">{{ __('school.link_status.'.$guardian->pivot->status) }}</span><small dir="ltr">{{ $guardian->email }} · {{ $guardian->phone }}</small><small>{{ __('school.relation.'.$guardian->pivot->relation) }}</small></div></li>@empty<li class="small muted">{{ __('No guardian account linked yet.') }}</li>@endforelse</ul></section>
<section class="card"><h2><x-ui-icon name="file-check"/>{{ __('Excuses') }}</h2><ul class="list">@forelse($excuses as $excuse)<li><div><span class="pill tone-{{ $excuse->status }}">{{ __('school.excuse_status.'.$excuse->status) }}</span> <strong class="small">{{ \App\Support\Dates::both($excuse->date) }} · {{ __('school.excuse_type.'.$excuse->type) }}</strong><p class="small" style="margin:4px 0">{{ $excuse->reason }}</p><small>{{ $excuse->submitter?->name }}</small></div></li>@empty<li class="small muted">{{ __('No excuses yet.') }}</li>@endforelse</ul></section>
<section class="card"><h2><x-ui-icon name="phone"/>{{ __('Contact history') }}</h2><ul class="list">@forelse($contacts as $contact)<li><div><span class="pill">{{ __('school.channel.'.$contact->channel) }}</span><small>{{ $contact->user?->name }} · {{ \App\Support\Dates::both($contact->created_at) }} {{ $contact->created_at->format('H:i') }}</small></div></li>@empty<li class="small muted">{{ __('No contact recorded yet.') }}</li>@endforelse</ul></section>
</div></div>
</x-admin-layout>
