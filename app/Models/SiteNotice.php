<?php

namespace App\Models;

use App\Support\HasLocalizedFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A notice bar shown on every page of the site, above or below the header, between
 * optional start and end dates. The live ones are cached as plain arrays.
 */
#[Fillable(['message', 'link_url', 'link_label', 'tone', 'position', 'starts_at', 'ends_at', 'is_active', 'dismissible', 'sort_order', 'updated_by'])]
class SiteNotice extends Model
{
    use HasLocalizedFields;

    public const LOCALIZED = ['message', 'link_label'];

    public const TONES = ['info', 'success', 'warning', 'urgent'];

    public const POSITIONS = ['above', 'below'];

    private const CACHE_KEY = 'starter.site_notices.v1';

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'dismissible' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * scheduled | live | ended | off
     */
    public function status(): string
    {
        return match (true) {
            ! $this->is_active => 'off',
            $this->starts_at !== null && $this->starts_at->isFuture() => 'scheduled',
            $this->ends_at !== null && $this->ends_at->isPast() => 'ended',
            default => 'live',
        };
    }

    /**
     * Notices to show now at one position, in the current language.
     *
     * @return list<array{id: string, message: string, link_url: string|null, link_label: string|null, tone: string, dismissible: bool}>
     */
    public static function showing(string $position): array
    {
        // Active notices that have not ended, cached briefly; their dates are checked on every page.
        $notices = Cache::remember(self::CACHE_KEY, now()->addMinutes(5), fn () => self::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('sort_order')->orderByDesc('id')->get()
            ->map(fn (self $notice) => [
                'id' => $notice->id.'-'.$notice->updated_at?->timestamp,
                'message' => $notice->message, 'link_url' => $notice->link_url, 'link_label' => $notice->link_label,
                'tone' => $notice->tone, 'position' => $notice->position, 'dismissible' => $notice->dismissible,
                'starts_at' => $notice->starts_at?->getTimestamp(), 'ends_at' => $notice->ends_at?->getTimestamp(),
            ])->all());

        $now = now()->getTimestamp();
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');
        $text = fn (?array $values) => ($values[$locale] ?? null) ?: ($values[$fallback] ?? null) ?: null;

        return array_values(array_map(fn (array $notice) => [
            'id' => $notice['id'],
            'message' => (string) $text($notice['message']),
            'link_url' => $notice['link_url'],
            'link_label' => $text($notice['link_label']),
            'tone' => $notice['tone'],
            'dismissible' => $notice['dismissible'],
        ], array_filter($notices, fn (array $notice) => $notice['position'] === $position
            && ($notice['starts_at'] === null || $notice['starts_at'] <= $now)
            && ($notice['ends_at'] === null || $notice['ends_at'] > $now)
            && filled($text($notice['message'])))));
    }
}
