<?php

namespace App\Localization;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\Translator;

class CatalogTranslator extends Translator
{
    private array $seen = [];

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $value = parent::get($key, $replace, $locale, $fallback);
        $locale ??= $this->getLocale();
        $loader = $this->getLoader();
        if ($locale !== 'en' && is_string($key) && strlen($key) <= 500 && $loader instanceof CatalogLoader && ! isset($loader->translatedKeys[$locale][$key]) && ! isset($this->seen[$locale][$key])) {
            $this->seen[$locale][$key] = true;
            if (Schema::hasTable('missing_translations')) {
                DB::table('missing_translations')->updateOrInsert(['fingerprint' => hash('sha256', $locale.'|'.$key)], ['locale' => $locale, 'key' => $key, 'last_seen_at' => now()]);
            }
        }

        return $value;
    }
}
