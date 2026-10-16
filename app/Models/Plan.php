<?php

namespace App\Models;

use App\Support\HasLocalizedFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An admin-editable plan: free, or a price per billing period, and what it includes. The
 * server decides what a member may use from their plan (App\Billing\Entitlements),
 * never the client.
 */
#[Fillable(['code', 'name', 'description', 'is_free', 'prices', 'discount', 'currency', 'is_default', 'is_public', 'on_sale', 'sort_order'])]
class Plan extends Model
{
    use HasLocalizedFields;

    public const LOCALIZED = ['name', 'description'];

    /** Billing periods and their length in months. */
    public const PERIODS = ['month' => 1, 'half_year' => 6, 'year' => 12];

    protected function casts(): array
    {
        return ['is_free' => 'boolean', 'prices' => 'array', 'discount' => 'array', 'is_default' => 'boolean', 'is_public' => 'boolean', 'on_sale' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * Billing periods offered, with their price (null: not set yet), in period order.
     *
     * @return array<string, float|null>
     */
    public function offeredPrices(): array
    {
        if ($this->is_free) {
            return [];
        }

        $offered = [];
        foreach (array_keys(self::PERIODS) as $period) {
            $entry = $this->prices[$period] ?? null;

            if ($entry && ($entry['active'] ?? false)) {
                $offered[$period] = isset($entry['price']) && $entry['price'] !== '' ? (float) $entry['price'] : null;
            }
        }

        return $offered;
    }

    /**
     * The discount in force now: a percentage, its label and end date; null when there is
     * none, or outside its dates.
     *
     * @return array{percent: int, label: array<string, string>, ends_on: string|null}|null
     */
    public function activeDiscount(): ?array
    {
        $discount = $this->discount;
        $percent = (int) ($discount['percent'] ?? 0);
        $today = now()->toDateString();

        if ($this->is_free || $percent <= 0
            || (filled($discount['starts_on'] ?? null) && $today < $discount['starts_on'])
            || (filled($discount['ends_on'] ?? null) && $today > $discount['ends_on'])) {
            return null;
        }

        return ['percent' => min($percent, 90), 'label' => (array) ($discount['label'] ?? []), 'ends_on' => $discount['ends_on'] ?? null];
    }

    public function hasPrice(): bool
    {
        return $this->is_free || array_filter($this->offeredPrices(), fn (?float $price) => $price !== null) !== [];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class)->withPivot('allowance')->orderBy('features.sort_order');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @param  Builder<Plan>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public static function default(): ?self
    {
        return self::query()->where('is_default', true)->first();
    }
}
