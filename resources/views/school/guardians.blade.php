<x-admin-layout :title="__('Guardian accounts')">
<div class="page-head"><div><h1>{{ __('Guardian accounts') }}</h1><p>{{ __('Guardians who registered and the students they are linked to.') }}</p></div>
<div class="page-actions">@foreach(['pending','approved','rejected'] as $s)<a class="pill tone-{{ $s }}" href="{{ route('school.guardians.index',['status'=>$s]) }}">{{ __('school.link_status.'.$s) }}</a>@endforeach<a class="pill" href="{{ route('school.guardians.index') }}">{{ __('All') }}</a></div></div>
<div class="table-wrap"><table><thead><tr><th>{{ __('Guardian') }}</th><th>{{ __('Student') }}</th><th>{{ __('Relation') }}</th><th>{{ __('Email verified') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($links as $link)
<tr><td><strong>{{ $link->guardian_name }}</strong><br><small class="muted" dir="ltr">{{ $link->email }} · {{ $link->phone }}</small></td>
<td><a href="{{ route('school.students.show',$link->student_id) }}">{{ $link->student_name }}</a><br><small class="muted" dir="ltr">{{ $link->national_id }}</small></td>
<td>{{ __('school.relation.'.$link->relation) }}</td><td>{{ $link->email_verified_at ? __('Yes') : __('No') }}</td>
<td><span class="pill tone-{{ $link->status }}">{{ __('school.link_status.'.$link->status) }}</span></td>
<td><form method="post" action="{{ route('school.guardians.decide',[$link->user_id,$link->student_id]) }}" class="contact-buttons">@csrf
@if($link->status!=='approved')<button class="tiny" name="decision" value="approved"><x-ui-icon name="check"/>{{ __('Approve') }}</button>@endif
@if($link->status!=='rejected')<button class="tiny secondary" name="decision" value="rejected"><x-ui-icon name="x"/>{{ __('Reject') }}</button>@endif</form></td></tr>
@empty<tr><td colspan="6" class="empty">{{ __('No guardian accounts found.') }}</td></tr>@endforelse
</tbody></table></div>{{ $links->links() }}
</x-admin-layout>
