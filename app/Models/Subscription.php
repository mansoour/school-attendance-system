<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member on a plan for a period. Assigned by staff for now ("manual"); a payment
 * provider will add "payment" ones through verified webhooks only.
 */
#[Fillable(['user_id', 'plan_id', 'status', 'source', 'starts_at', 'ends_at', 'notes', 'created_by'])]
class Subscription extends Model
{
    public const ACTIVE = 'active';

    public const CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * In effect now: active, started, not yet ended.
     *
     * @param  Builder<Subscription>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('status', self::ACTIVE)->where('starts_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
