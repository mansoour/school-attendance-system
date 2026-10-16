<x-admin-layout :title="__('Send excuse')">
<div class="page-head"><div><h1>{{ __('Send excuse') }}</h1><p>{{ $record->student->name }} · {{ $record->date->toDateString() }} · <span class="pill tone-{{ $record->status }}">{{ __('school.status.'.$record->status) }}</span></p></div></div>
<form method="post" action="{{ route('school.guardian.excuse.store',$record) }}" enctype="multipart/form-data" class="card stack-sm" style="max-width:640px">@csrf
<label class="field">{{ __('Excuse type') }}<select name="type">@foreach(\App\Models\Excuse::TYPES as $type)<option value="{{ $type }}" @selected(old('type')===$type)>{{ __('school.excuse_type.'.$type) }}</option>@endforeach</select></label>
<label class="field">{{ __('Reason') }}<textarea name="reason" rows="4" required maxlength="2000">{{ old('reason') }}</textarea></label>
<label class="field">{{ __('Attachment, such as a medical report (optional)') }}<input type="file" name="attachment" accept=".pdf,image/*"></label>
<div class="form-actions"><a class="button secondary" href="{{ route('school.guardian.index') }}">{{ __('Cancel') }}</a><button><x-ui-icon name="send"/>{{ __('Send') }}</button></div>
</form>
</x-admin-layout>
