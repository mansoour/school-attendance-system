<?php

namespace App\Billing;

use App\Models\Feature;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Settings\SettingsService;
use Illuminate\Support\Facades\DB;

class Entitlements
{
    public function planFor(User $user): ?Plan
    {
        if (! app(SettingsService::class)->get('plans.enabled')) {
            return null;
        }

        return Subscription::query()->current()->where('user_id', $user->id)->latest('starts_at')->first()?->plan ?? Plan::where('is_default', true)->first();
    }

    public function allows(User $user, string $code): bool
    {
        return Feature::where('code', $code)->where('is_enabled', true)->exists() && (! app(SettingsService::class)->get('plans.enabled') || ($this->planFor($user)?->features()->where('code', $code)->exists() ?? false));
    }

    public function consume(User $user, string $code): void
    {
        if (! app(SettingsService::class)->get('plans.enabled')) {
            abort_unless($this->allows($user, $code), 403);

            return;
        }
        DB::transaction(function () use ($user, $code) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->allows($user, $code), 403);
            $feature = $this->planFor($user)->features()->where('code', $code)->firstOrFail();
            $key = ['user_id' => $user->id, 'feature_code' => $code, 'period' => now()->format('Y-m')];
            DB::table('feature_usages')->insertOrIgnore($key + ['used' => 0]);
            $query = DB::table('feature_usages')->where($key);
            if ($feature->pivot->allowance !== null) {
                $query->where('used', '<', $feature->pivot->allowance);
            }
            abort_unless($query->increment('used') === 1, 429, __('Monthly allowance reached.'));
        });
    }
}
