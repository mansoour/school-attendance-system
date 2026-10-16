<?php

namespace App\Support;

use App\Localization\LocaleRegistry;

/**
 * Admin-edited text stored once per locale (`{"en": "...", "ar": "..."}`), for content
 * that is data rather than interface copy: plans, features, pages, the cookie policy.
 * A model lists its fields in LOCALIZED; they are cast to arrays.
 */
trait HasLocalizedFields
{
    public function initializeHasLocalizedFields(): void
    {
        $this->mergeCasts(array_fill_keys(static::LOCALIZED, 'array'));
    }

    /**
     * The field in the requested (default: current) locale, else English, else any
     * locale that has it.
     */
    public function localized(string $field, ?string $locale = null): ?string
    {
        $values = array_filter((array) ($this->{$field} ?? []), fn ($v) => is_string($v) && trim($v) !== '');

        return $values[$locale ?? app()->getLocale()] ?? $values[config('app.fallback_locale', 'en')] ?? (reset($values) ?: null);
    }

    /**
     * Validation rules for a localized field: English required (or optional), every
     * active locale accepted.
     *
     * @return array<string, mixed>
     */
    public static function localizedRules(string $field, bool $required = true, int $max = 255): array
    {
        $rules = [$field => ['array']];

        foreach (app(LocaleRegistry::class)->codes() as $code) {
            $rules["{$field}.{$code}"] = [$required && $code === config('app.fallback_locale', 'en') ? 'required' : 'nullable', 'string', "max:{$max}"];
        }

        return $rules;
    }
}
