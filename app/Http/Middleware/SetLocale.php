<?php

namespace App\Http\Middleware;

use App\Localization\LocaleRegistry;
use App\Models\TranslationRevision;
use App\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $default = app(SettingsService::class)->get('appearance.locale');
        $locale = $request->user()?->locale ?? $request->session()->get('locale') ?? $request->header('X-Locale', $default);
        $codes = app(LocaleRegistry::class)->codes();
        app()->setLocale(in_array($locale, $codes, true) ? $locale : (in_array($default, $codes, true) ? $default : 'en'));
        if ($request->user()?->can('translations.edit') && ($preview = $request->query('preview_translation'))) {
            if ($revision = TranslationRevision::find($preview)) {
                $request->attributes->set('translation_preview', $revision);
                app()->setLocale($revision->locale);
            }
        }
        app('translator')->setLoaded([]);

        return $next($request);
    }
}
