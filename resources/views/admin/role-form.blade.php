<form class="stack" method="post" action="{{ $role->exists ? route('admin.roles.update',$role) : route('admin.roles.save') }}">
@csrf @if($role->exists) @method('PATCH') @endif
<input type="hidden" name="form_role" value="{{ $role->id ?? 'new' }}">
@php($submitted=old('form_role')===(string)($role->id ?? 'new'))
<label>{{ __('Role name') }}<input name="name" value="{{ $submitted ? (is_string(old('name')) ? old('name') : '') : $role->name }}" required maxlength="80"></label>
<fieldset><legend>{{ __('Permissions') }}</legend><div class="checkbox-grid">
@foreach($permissions as $permission)<label class="check-label"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($submitted ? in_array($permission->name,(array)old('permissions',[])) : ($role->exists && $role->hasPermissionTo($permission)))><span>{{ __($permission->name) }}</span></label>@endforeach
</div></fieldset><button><x-ui-icon name="save"/>{{ __('Save role') }}</button>
</form>
