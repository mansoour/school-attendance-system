<x-admin-layout :title="__('My children')">
<div class="page-head"><img class="page-illustration" src="{{ asset('images/sized/guardian.webp') }}" alt="" aria-hidden="true" width="192" height="192"><div><h1>{{ __('My children') }}</h1><p>{{ __('Follow your children’s attendance, send excuses and read the school’s notes.') }}</p></div></div>
@if($children->isEmpty())<div class="banner"><x-ui-icon name="user-plus"/><p>{{ __('No student is linked to your account yet. Add your child below.') }}</p></div>@endif
<div class="stat-grid">
@foreach($children as $child)
@php($c=$counts->get($child->id,collect()))
<div class="card"><div class="child-card"><span class="avatar">{{ mb_substr($child->name,0,1) }}</span><div><strong>{{ $child->name }}</strong><br><small class="muted">{{ $child->school?->name }} · {{ $child->classroom?->label() }}</small></div></div>
@if($child->pivot->status==='approved')
<div class="contact-buttons" style="margin-top:12px"><span class="pill tone-absent">{{ $c->whereIn('status',['absent'])->sum('total') }} {{ __('school.status.absent') }}</span><span class="pill tone-excused">{{ $c->where('status','excused')->sum('total') }} {{ __('school.status.excused') }}</span><span class="pill tone-late">{{ $c->where('status','late')->sum('total') }} {{ __('school.status.late') }}</span></div><small class="muted">{{ __('Last 30 days') }}</small>
@else<p style="margin-top:10px"><span class="pill tone-{{ $child->pivot->status }}">{{ __('school.link_status.'.$child->pivot->status) }}</span></p>
@if(!auth()->user()->hasVerifiedEmail())<small class="muted">{{ __('Verify your email address to activate the link.') }}</small>@endif @endif
</div>
@endforeach
</div>
<div class="grid-3">
<div class="stack-sm">
<section class="card"><h2><x-ui-icon name="calendar"/>{{ __('Absences and late arrivals') }}</h2>
<div class="table-wrap"><table><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Student') }}</th><th>{{ __('Status') }}</th><th>{{ __('Excuse') }}</th></tr></thead><tbody>
@forelse($records as $record)
@php($excuse=$record->excuses->last())
<tr><td>@date($record->date)</td><td>{{ $record->student?->name }}</td><td><span class="pill tone-{{ $record->status }}">{{ __('school.status.'.$record->status) }}</span></td>
<td>@if($excuse)<span class="pill tone-{{ $excuse->status }}">{{ __('school.excuse_status.'.$excuse->status) }}</span>@if($excuse->review_note)<br><small>{{ $excuse->review_note }}</small>@endif @if($excuse->status==='rejected')<br><a class="small" href="{{ route('school.guardian.excuse',$record) }}">{{ __('Send another excuse') }}</a>@endif
@elseif(in_array($record->status,['absent','late']))<a class="button tiny" href="{{ route('school.guardian.excuse',$record) }}"><x-ui-icon name="file-text"/>{{ __('Send excuse') }}</a>@else<span class="muted">—</span>@endif</td></tr>
@empty<tr><td colspan="4" class="empty"><x-ui-icon name="circle-check"/><br>{{ __('No absences or late arrivals recorded.') }}</td></tr>@endforelse
</tbody></table></div></section>
<section class="card"><h2><x-ui-icon name="notebook-pen"/>{{ __('Notes from the school') }}</h2>
<ul class="list">@forelse($notes as $note)<li><span class="list-icon"><x-ui-icon name="notebook-pen"/></span><div><strong class="small">{{ $note->student?->name }}</strong> <span class="pill">{{ __('school.note_category.'.$note->category) }}</span><p style="margin:6px 0;color:var(--ink)">{{ $note->body }}</p><small>{{ \App\Support\Dates::both($note->created_at) }}</small></div></li>@empty<li class="small muted">{{ __('No notes yet.') }}</li>@endforelse</ul></section>
</div>
<form method="post" action="{{ route('school.guardian.link') }}" class="card stack-sm">@csrf<h2><x-ui-icon name="user-plus"/>{{ __('Add a child') }}</h2>
<label class="field">{{ __('Student national ID') }}<input name="student_national_id" required dir="ltr" inputmode="numeric" value="{{ old('student_national_id') }}"></label>
@if(str_contains($matchMode,'name'))<label class="field">{{ __('Student full name') }}<input name="student_name" required value="{{ old('student_name') }}"></label>@endif
@if(str_contains($matchMode,'phone'))<label class="field">{{ __('Student mobile number') }}<input name="student_phone" required dir="ltr" value="{{ old('student_phone') }}"></label>@endif
<label class="field">{{ __('Relation to the student') }}<select name="relation">@foreach(\App\Models\Student::RELATIONS as $relation)<option value="{{ $relation }}">{{ __('school.relation.'.$relation) }}</option>@endforeach</select></label>
<button><x-ui-icon name="check"/>{{ __('Add') }}</button></form>
</div>
</x-admin-layout>
