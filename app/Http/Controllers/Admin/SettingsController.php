<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Locale;
use App\Services\Audit\AuditLogger;
use App\Settings\SecretStore;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsService;
use App\Support\BrandingStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    private function definition(string $section): array
    {
        $definition = SettingsRegistry::sections()[$section] ?? null;
        abort_unless($definition, 404);
        Gate::authorize($definition['permission']);
        if ($section === 'appearance') {
            $definition['fields']['appearance.locale']['options'] = Locale::where('is_active', true)->pluck('native_name', 'code')->all();
            $definition['fields']['appearance.locale']['rules'] = 'in:'.implode(',', array_keys($definition['fields']['appearance.locale']['options']));
        }

        return $definition;
    }

    public function edit(string $section)
    {
        $definition = $this->definition($section);

        return view('admin.settings', ['section' => $section, 'definition' => $definition, 'values' => collect($definition['fields'])->map(fn ($field, $key) => app(SettingsService::class)->get($key))]);
    }

    public function save(Request $request, string $section)
    {
        $definition = $this->definition($section);
        $rules = [];
        $attributes = [];
        foreach ($definition['fields'] as $key => $field) {
            $input = str_replace('.', '_', $key);
            $rules[$input] = 'sometimes|'.$field['rules'];
            $attributes[$input] = __($field['label']);
            if ($field['type'] === 'roles') {
                $rules[$input.'.*'] = ['string', Rule::exists('roles', 'name')];
            }
            if ($field['type'] === 'file') {
                $rules[$input.'_remove'] = 'sometimes|boolean';
            }
        }
        $data = $request->validate($rules, [], $attributes);
        $settings = app(SettingsService::class);
        $secrets = app(SecretStore::class);
        if ($request->boolean('storage_r2_enabled') && (! $secrets->get('r2_access_key_id') || ! $secrets->get('r2_secret_access_key') || ! ($data['storage_r2_account'] ?? $settings->get('storage.r2_account')) || ! ($data['storage_r2_bucket'] ?? $settings->get('storage.r2_bucket')))) {
            throw ValidationException::withMessages(['storage_r2_enabled' => __('Complete the R2 account, bucket and access keys before enabling cloud storage.')]);
        }
        if (($request->boolean('security_recaptcha_contact') || $request->boolean('security_recaptcha_register')) && (! $secrets->get('recaptcha_site_key') || ! $secrets->get('recaptcha_secret_key'))) {
            throw ValidationException::withMessages(['security_recaptcha_contact' => __('Save both reCAPTCHA keys in Integrations first.')]);
        }
        DB::transaction(function () use ($definition, $data, $request, $settings, $section) {
            foreach ($definition['fields'] as $key => $field) {
                $input = str_replace('.', '_', $key);
                if ($field['type'] === 'file' && $request->boolean($input.'_remove')) {
                    $settings->set($key, null);

                    continue;
                }
                if (! array_key_exists($input, $data)) {
                    continue;
                }
                $value = $data[$input];
                if ($field['type'] === 'file') {
                    if (! $request->hasFile($input)) {
                        continue;
                    } $value = app(BrandingStorage::class)->store($request->file($input));
                } elseif ($field['type'] === 'boolean') {
                    $value = $request->boolean($input);
                } elseif ($field['type'] === 'number') {
                    $value = (int) $value;
                }
                $settings->set($key, $value);
            }
            if ($section === 'staff-alerts') {
                $settings->set('staff_alerts.support_roles', $data['staff_alerts_support_roles'] ?? []);
            }
            app(AuditLogger::class)->record($section.'.updated');
        });

        return back()->with('status', __('Saved.'));
    }

    public function reset(string $section)
    {
        abort_unless(in_array($section, ['styling', 'fonts']), 404);
        $definition = $this->definition($section);
        foreach ($definition['fields'] as $key => $field) {
            app(SettingsService::class)->set($key, $field['default']);
        }
        app(AuditLogger::class)->record($section.'.reset');

        return back()->with('status',__('Defaults restored.'));
    }
}
