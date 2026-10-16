<x-admin-layout :title="__('Guardian communication')">
<div class="page-head"><img class="page-illustration" src="{{ asset('images/sized/guardian-communication.webp') }}" alt="" aria-hidden="true" width="560" height="336"><div><h1>{{ __('Guardian communication') }}</h1><p>{{ __('Contact the guardians of absent and late students by WhatsApp, phone or email. Each contact is logged.') }}</p></div>
<div class="page-actions"><span class="pill tone-absent">{{ $records->where('status','absent')->count() }} {{ __('school.status.absent') }}</span><span class="pill tone-late">{{ $records->where('status','late')->count() }} {{ __('school.status.late') }}</span></div></div>
<form method="get" class="toolbar">
<label>{{ __('Date') }}<input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"></label>
<label>{{ __('Grade') }}<select name="grade"><option value="">{{ __('All grades') }}</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(request('grade')==$grade->id)>{{ $grade->name }}</option>@endforeach</select></label>
<label>{{ __('Class') }}<select name="classroom"><option value="">{{ __('All classes') }}</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(request('classroom')==$class->id)>{{ $class->label() }}</option>@endforeach</select></label>
<label>{{ __('Status') }}<select name="status"><option value="">{{ __('Absent and late') }}</option>@foreach(['absent','late','excused'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ __('school.status.'.$status) }}</option>@endforeach</select></label>
<button><x-ui-icon name="search"/>{{ __('Show') }}</button>
</form>
<div class="table-wrap"><table><thead><tr><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th>{{ __('Status') }}</th><th>{{ __('Guardian') }}</th><th>{{ __('Contact') }}</th><th>{{ __('Contacted') }}</th></tr></thead><tbody>
@forelse($records as $record)
@php($student=$record->student)
<tr><td>@can('students.view')<a href="{{ route('school.students.show',$student) }}"><strong>{{ $student->name }}</strong></a>@else<strong>{{ $student->name }}</strong>@endcan</td>
<td>{{ $record->classroom?->label() }}</td>
<td><span class="pill tone-{{ $record->status }}">{{ __('school.status.'.$record->status) }}</span></td>
<td class="small">{{ $student->guardian_name ?? '—' }}<br><span class="muted" dir="ltr">{{ $student->guardian_phone ?: __('No phone') }}</span></td>
<td>@include('school.partials.contact-buttons',['student'=>$student,'date'=>$date,'status'=>$record->status])</td>
<td>@if($list=$contacts->get($student->id))@foreach($list as $contact)<span class="pill tone-excused" title="{{ $contact->created_at->format('H:i') }}">{{ __('school.channel.'.$contact->channel) }}</span> @endforeach @else<span class="muted small">{{ __('Not yet') }}</span>@endif</td></tr>
@empty<tr><td colspan="6" class="empty"><x-ui-icon name="circle-check"/><br>{{ __('No absent or late students for this day.') }}</td></tr>@endforelse
</tbody></table></div>
</x-admin-layout>
