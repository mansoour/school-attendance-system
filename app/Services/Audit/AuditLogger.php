<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records privileged actions: who did what to which record, and the outcome.
 * Metadata is for safe context only; secret-looking keys are always dropped.
 */
class AuditLogger
{
    private const REDACTED_KEYS = '/pass|secret|token|key|national_id|passport/i';

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(string $action, ?Model $target = null, array $metadata = [], string $result = 'success', ?User $actor = null): AuditLog
    {
        $actor ??= Auth::user();

        return AuditLog::create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey() !== null ? (string) $target->getKey() : null,
            'result' => $result,
            'metadata' => $this->redact($metadata) ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function redact(array $metadata): array
    {
        $safe = [];

        foreach ($metadata as $key => $value) {
            if (preg_match(self::REDACTED_KEYS, (string) $key)) {
                continue;
            }

            $safe[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $safe;
    }
}
