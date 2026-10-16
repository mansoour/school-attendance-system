<?php

namespace App\Models;

use App\Support\HasLocalizedFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Something a plan can include, by a stable code the application checks
 * (App\Billing\Entitlements::allows()), with an optional allowance per plan.
 */
#[Fillable(['code', 'name', 'description', 'is_enabled', 'sort_order'])]
class Feature extends Model
{
    use HasLocalizedFields;

    public const LOCALIZED = ['name', 'description'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class)->withPivot('allowance');
    }
}
