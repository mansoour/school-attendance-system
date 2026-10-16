<?php

namespace App\Localization;

use App\Models\TranslationRevision;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\FileLoader;

class CatalogLoader extends FileLoader
{
    public array $translatedKeys = [];

    public function load($locale, $group, $namespace = null)
    {
        $lines = parent::load($locale, $group, $namespace);
        if ($group !== '*' && ($namespace === null || $namespace === '*')) {
            foreach (Arr::dot($lines) as $key => $value) {
                if (is_string($value)) {
                    $this->translatedKeys[$locale][$group.'.'.$key] = true;
                }
            }
        }
        if ($group === '*' && $namespace === '*' && Schema::hasTable('translation_revisions')) {
            $revision = TranslationRevision::where('locale', $locale)->whereNotNull('published_at')->latest('published_at')->latest('id')->first();
            if (app()->bound('request') && ($preview = request()->attributes->get('translation_preview')) && $preview->locale === $locale) {
                $revision = $preview;
            }
            $localized = array_replace($lines, $revision?->messages ?? []);
            $this->translatedKeys[$locale] = array_fill_keys(array_keys($localized), true);

            return $locale === 'en' ? $localized : array_replace(parent::load('en', '*', '*'), $localized);
        }

        return $lines;
    }
}
