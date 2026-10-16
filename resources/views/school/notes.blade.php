<x-admin-layout :title="__('Student notes')">
<div class="page-head"><div><h1>{{ __('Student notes') }}</h1><p>{{ __('Health, counseling, behavior and attendance notes. Shared notes are visible to the guardian.') }}</p></div></div>
<div class="grid-3">
<section class="card">
<form method="get" class="toolbar">
<label class="grow">{{ __('Student') }}<input name="q" value="{{ request('q') }}" placeholder="{{ __('Name or national ID') }}"></label>
<label>{{ __('Category') }}<select name="category"><option value="">{{ __('All') }}</option>@foreach(\App\Models\StudentNote::CATEGORIES as $c)<option value="{{ $c }}" @selected($category===$c)>{{ __('school.note_category.'.$c) }}</option>@endforeach</select></label>
<button><x-ui-icon name="search"/>{{ __('Search') }}</button></form>
<ul class="list">@forelse($notes as $note)
<li><span class="list-icon"><x-ui-icon :name="match($note->category){'health'=>'heart-pulse','counseling'=>'user-round','behavior'=>'shield-alert','attendance'=>'calendar-check',default=>'notebook-pen'}"/></span>
<div style="flex:1"><a href="{{ route('school.students.show',$note->student) }}"><strong class="small">{{ $note->student?->name }}</strong></a> <span class="muted small">{{ $note->student?->classroom?->label() }}</span><br><span class="pill">{{ __('school.note_category.'.$note->category) }}</span>@if($note->visible_to_guardian) <span class="pill tone-violet">{{ __('Shared with guardian') }}</span>@endif
<p style="margin:6px 0;color:var(--ink)">{{ $note->body }}</p><small>{{ $note->author?->name }} · {{ \App\Support\Dates::both($note->created_at) }}</small></div>
@if(auth()->id()===$note->author_id || auth()->user()->can('employees.manage'))@can('notes.manage')<form method="post" action="{{ route('school.notes.destroy',$note) }}" onsubmit="return confirm('{{ __('Delete this note?') }}')">@csrf @method('DELETE')<button class="tiny secondary" aria-label="{{ __('Delete') }}"><x-ui-icon name="trash-2"/></button></form>@endcan @endif</li>
@empty<li class="empty">{{ __('No notes yet.') }}</li>@endforelse</ul>
{{ $notes->links() }}</section>
@can('notes.manage')
<section class="card"><h2><x-ui-icon name="notebook-pen"/>{{ __('Add a note') }}</h2>
<form method="post" action="{{ route('school.notes.store') }}" class="stack-sm">@csrf
<label class="field" x-data>{{ __('Student') }}<input list="student-list" x-on:input="$refs.id.value=(document.querySelector('#student-list option[value=\''+$event.target.value.replace(/'/g,'')+'\']')||{}).dataset?.id||''" required placeholder="{{ __('Type to search') }}"><input type="hidden" name="student_id" x-ref="id"></label>
<datalist id="student-list">@foreach($students as $student)<option value="{{ $student->name }} — {{ $student->national_id }}" data-id="{{ $student->id }}"></option>@endforeach</datalist>
<label class="field">{{ __('Category') }}<select name="category">@foreach(\App\Models\StudentNote::CATEGORIES as $c)<option value="{{ $c }}">{{ __('school.note_category.'.$c) }}</option>@endforeach</select></label>
<label class="field">{{ __('Note') }}<textarea name="body" rows="4" required maxlength="3000"></textarea></label>
<label class="check-row"><input type="checkbox" name="visible_to_guardian" value="1">{{ __('Share with guardian') }}</label>
<button><x-ui-icon name="check"/>{{ __('Add note') }}</button></form></section>
@endcan
</div>
</x-admin-layout>
