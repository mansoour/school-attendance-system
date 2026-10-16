<x-admin-layout :title="__('Grades')">
<div class="page-head"><div><h1>{{ __('Grades') }}</h1><p>{{ __('School grades, for example “Fourth primary”. Each grade contains one or more classes.') }}</p></div></div>
<div class="grid-3">
<div class="table-wrap"><table><thead><tr><th>{{ __('Order') }}</th><th>{{ __('Grade') }}</th><th>{{ __('Stage') }}</th><th class="num">{{ __('Classes') }}</th><th class="num">{{ __('Students') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($grades as $grade)
<tr><td class="num">{{ $grade->sort_order }}</td><td><strong>{{ $grade->name }}</strong></td><td><span class="pill tone-present">{{ __('school.stage.'.$grade->stage) }}</span></td><td class="num"><a href="{{ route('school.classes.index',['grade'=>$grade->id]) }}">{{ $grade->classrooms_count }}</a></td><td class="num">{{ $grade->students_count }}</td>
<td><details class="row-details"><summary>{{ __('Edit') }}</summary>
<form method="post" action="{{ route('school.grades.update',$grade) }}">@csrf @method('PUT')<input name="name" value="{{ $grade->name }}" required><select name="stage">@foreach(\App\Models\Grade::STAGES as $stage)<option value="{{ $stage }}" @selected($grade->stage===$stage)>{{ __('school.stage.'.$stage) }}</option>@endforeach</select><input type="number" name="sort_order" value="{{ $grade->sort_order }}" min="0" max="999"><button class="tiny">{{ __('Save') }}</button></form>
<form method="post" action="{{ route('school.grades.destroy',$grade) }}" onsubmit="return confirm('{{ __('Delete this grade and its classes?') }}')">@csrf @method('DELETE')<button class="tiny secondary"><x-ui-icon name="trash-2"/>{{ __('Delete') }}</button></form></details></td></tr>
@empty<tr><td colspan="6" class="empty">{{ __('No grades yet.') }}</td></tr>@endforelse
</tbody></table></div>
<form method="post" action="{{ route('school.grades.store') }}" class="card stack-sm">@csrf<h2><x-ui-icon name="plus"/>{{ __('Add grade') }}</h2>
<label class="field">{{ __('Grade name') }}<input name="name" required maxlength="100" placeholder="{{ __('e.g. Fourth primary') }}"></label>
<label class="field">{{ __('Stage') }}<select name="stage">@foreach(\App\Models\Grade::STAGES as $stage)<option value="{{ $stage }}" @selected($stage==='primary')>{{ __('school.stage.'.$stage) }}</option>@endforeach</select></label>
<label class="field">{{ __('Display order') }}<input type="number" name="sort_order" min="0" max="999" value="{{ $grades->count()+1 }}"></label>
<button><x-ui-icon name="check"/>{{ __('Add') }}</button></form>
</div>
</x-admin-layout>
