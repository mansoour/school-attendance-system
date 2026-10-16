<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Locale;
use App\Models\TranslationRevision;
use App\Services\Audit\AuditLogger;
use App\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TranslationController extends Controller
{
    private function source(): array
    {
        return json_decode(file_get_contents(lang_path('en.json')), true) ?: [];
    }

    private function base(string $locale): array
    {
        return is_file(lang_path($locale.'.json')) ? json_decode(file_get_contents(lang_path($locale.'.json')), true) : [];
    }

    public function index()
    {
        $source = $this->source();
        $languages = Locale::all()->map(function ($language) use ($source) {
            $live = TranslationRevision::where('locale', $language->code)->whereNotNull('published_at')->latest('id')->first();
            $catalog = array_replace($this->base($language->code), $live?->messages ?? []);
            $language->progress = count($source) ? (int) round(count(array_filter(array_intersect_key($catalog, $source), fn ($v) => $v !== '')) / count($source) * 100) : 100;
            $language->live_version = $live?->id;
            $language->draft_count = TranslationRevision::where('locale', $language->code)->whereNull('published_at')->count();
            $language->missing_count = DB::table('missing_translations')->where('locale', $language->code)->count();

            return $language;
        });

        return view('admin.translations', compact('languages'));
    }

    public function create(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'regex:/^[a-z]{2,3}(-[A-Z]{2})?$/', 'unique:locales,code'], 'name' => 'required|string|max:100', 'native_name' => 'required|string|max:100', 'direction' => 'required|in:ltr,rtl', 'plural_forms' => 'required|integer|min:1|max:6']);
        Locale::create($data + ['is_active' => false]);

        return back()->with('status', __('Language added. Translate and publish before activating it.'));
    }

    public function toggle(Locale $locale)
    {
        abort_if($locale->code === 'en' || $locale->code === app(SettingsService::class)->get('appearance.locale'), 422, __('The default language must stay active.'));
        if (! $locale->is_active) {
            abort_unless($locale->code === 'ar' || TranslationRevision::where('locale', $locale->code)->whereNotNull('published_at')->exists(), 422, __('Publish a translation before activating this language.'));
        }
        $locale->update(['is_active' => ! $locale->is_active]);
        app(AuditLogger::class)->record('language.visibility.updated', $locale);

        return back()->with('status', __('Saved.'));
    }

    public function edit(Request $request, Locale $locale)
    {
        $revision = TranslationRevision::where('locale', $locale->code)->latest('id')->first();
        $catalog = array_replace($this->base($locale->code), $revision?->messages ?? []);
        $source = collect($this->source())->filter(fn ($value, $key) => ! $request->query('q') || mb_stripos($key, $request->query('q')) !== false || mb_stripos($catalog[$key] ?? '', $request->query('q')) !== false);
        $page = max(1, (int) $request->query('page', 1));
        $rows = new LengthAwarePaginator($source->forPage($page, 30), $source->count(), 30, $page, ['path' => $request->url(), 'query' => $request->query()]);
        $revisions = TranslationRevision::where('locale', $locale->code)->latest('id')->limit(10)->get();

        return view('admin.translation-edit', compact('locale', 'catalog', 'rows', 'revisions'));
    }

    public function save(Request $request)
    {
        $data = $request->validate(['locale' => ['required', Rule::exists('locales', 'code')], 'rows' => 'required|array|max:50', 'rows.*.key' => 'required|string|max:500', 'rows.*.value' => 'nullable|string|max:5000']);
        $source = $this->source();
        $previous = TranslationRevision::where('locale', $data['locale'])->latest('id')->first();
        $messages = $previous?->messages ?? [];
        foreach ($data['rows'] as $index => $row) {
            abort_unless(array_key_exists($row['key'], $source), 422);
            $value = $row['value'] ?? '';
            if ($value === '') {
                unset($messages[$row['key']]);

                continue;
            }
            preg_match_all('/(?<![a-zA-Z]):[a-zA-Z_][a-zA-Z0-9_]*/', $source[$row['key']], $expected);
            preg_match_all('/(?<![a-zA-Z]):[a-zA-Z_][a-zA-Z0-9_]*/', $value, $actual);
            sort($expected[0]);
            sort($actual[0]);
            if ($expected[0] !== $actual[0]) {
                throw ValidationException::withMessages(['rows.'.$index.'.value' => __('Keep all placeholders from the source text.')]);
            }
            $messages[$row['key']] = $value;
        }
        TranslationRevision::create(['locale' => $data['locale'], 'messages' => $messages, 'created_by' => $request->user()->id]);

        return back()->with('status',__('Draft saved.'));
    }
}
