<?php

namespace App\Models\Concerns;

use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Records every create, update and delete of the model in the audit log.
 */
trait Audited
{
    protected static function bootAudited(): void
    {
        $prefix = fn (Model $model) => str($model->getTable())->singular()->toString();
        static::created(fn (Model $model) => app(AuditLogger::class)->record($prefix($model).'.created', $model));
        static::updated(function (Model $model) use ($prefix) {
            $changes = array_diff(array_keys($model->getChanges()), ['updated_at']);
            if ($changes) {
                app(AuditLogger::class)->record($prefix($model).'.updated', $model, ['changed' => array_values($changes)]);
            }
        });
        static::deleted(fn (Model $model) => app(AuditLogger::class)->record($prefix($model).'.deleted', $model, ['label' => $model->getAttribute('name')]));
    }
}
