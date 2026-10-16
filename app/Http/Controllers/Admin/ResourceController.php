<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Resources;
use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ResourceController extends Controller
{
    public function index(string $resource)
    {
        [$model, $permission, $fields] = Resources::get($resource);
        Gate::authorize($permission);

        return view('admin.resources', ['resource' => $resource, 'records' => $model::latest('id')->paginate(20), 'fields' => $fields]);
    }

    public function edit(string $resource, ?int $id = null)
    {
        [$model, $permission, $fields] = Resources::get($resource);
        Gate::authorize($permission);

        return view('admin.edit', ['resource' => $resource, 'record' => $id ? $model::findOrFail($id) : new $model, 'fields' => $fields]);
    }

    public function save(Request $request, string $resource, ?int $id = null)
    {
        [$model, $permission, $rules] = Resources::get($resource);
        Gate::authorize($permission);
        $record = $id ? $model::findOrFail($id) : new $model;
        foreach (['slug', 'code', 'event'] as $unique) {
            if (isset($rules[$unique])) {
                $rules[$unique] = [...explode('|', $rules[$unique]), Rule::unique($record->getTable(), $unique)->ignore($record->id)];
            }
        }
        $attributes = collect(array_keys($rules))->mapWithKeys(fn ($field) => [$field => __(ucwords(str_replace(['.', '_'], ' ', $field)))])->all();
        $data = $request->validate($rules, [], $attributes);
        if ($resource === 'email-templates' && ($data['event'] === 'security' || $record->event === 'security')) {
            $data['event'] = 'security';
            $data['is_active'] = true;
        }
        DB::transaction(function () use ($record, $resource, $data) {
            $record->fill($data)->save();
            app(AuditLogger::class)->record($resource.'.saved', $record);
        });

        return redirect()->route('admin.resources', $resource)->with('status', __('Saved.'));
    }

    public function destroy(string $resource, int $id)
    {
        [$model, $permission] = Resources::get($resource);
        Gate::authorize($permission);
        $record = $model::findOrFail($id);
        abort_if($resource === 'email-templates' && $record->event === 'security', 403);
        app(AuditLogger::class)->record($resource.'.deleted', $record);
        $record->delete();

        return back()->with('status', __('Deleted.'));
    }
}
