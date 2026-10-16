<x-admin-layout><div class="page-title"><x-ui-icon name="key-round"/><h1>{{ __('Roles and permissions') }}</h1></div><p>{{ __('Create roles and choose the permissions each role grants.') }}</p>
<details class="settings-card" @if(old('form_role')==='new') open @endif><summary><x-ui-icon name="plus"/> {{ __('Add role') }}</summary>
@include('admin.role-form',['role'=>new \Spatie\Permission\Models\Role])
</details>
@foreach($roles as $role)<article class="settings-card"><div class="title-row"><h2>{{ in_array($role->name,['owner','support','billing','translator','content_reviewer']) ? __(ucfirst($role->name)) : $role->name }}</h2><span class="badge">{{ __('Assigned members') }}: {{ $role->users_count }}</span></div>
@if($role->name==='owner')<p>{{ __('The owner role is protected.') }}</p>
@else<details @if(old('form_role')===(string)$role->id) open @endif><summary><x-ui-icon name="pencil"/> {{ __('Edit role') }}</summary>@include('admin.role-form',['role'=>$role])</details>
<form method="post" action="{{ route('admin.roles.delete',$role) }}" x-data @submit.prevent="if(confirm({{ Illuminate\Support\Js::from(__('Delete this role and remove its assignments?')) }})) $el.submit()">@csrf @method('DELETE')
<p class="field-help">{{ __('Deleting this role removes it from all assigned members. Their accounts and other roles are preserved.') }}</p><button class="text-button danger"><x-ui-icon name="trash-2"/>{{ __('Delete role') }}</button></form>
@endif</article>@endforeach</x-admin-layout>
