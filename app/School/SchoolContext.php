<?php

namespace App\School;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the school the signed-in staff member works in.
 * Staff are bound to their own school; the system administrator chooses one (or all).
 */
class SchoolContext
{
    public function user(): ?User
    {
        return Auth::user();
    }

    /** The active school, or null when the system administrator views all schools. */
    public function school(): ?School
    {
        $user = $this->user();
        if (! $user) {
            return null;
        }
        if ($user->isSystemAdmin()) {
            $id = session('school_id');

            return $id ? School::find($id) : null;
        }

        return $user->school;
    }

    public function id(): ?int
    {
        return $this->school()?->id;
    }

    public function isAllSchools(): bool
    {
        return $this->user()?->isSystemAdmin() && ! session('school_id');
    }

    /** Restricts a query to the active school. */
    public function scope(Builder $query, string $column = 'school_id'): Builder
    {
        if ($this->isAllSchools()) {
            return $query;
        }

        return $query->where($query->getModel()->qualifyColumn($column), $this->id() ?? 0);
    }

    /** Aborts unless the model belongs to the active school (or the admin sees all). */
    public function authorize(Model $model): void
    {
        if ($this->isAllSchools()) {
            return;
        }
        abort_unless((int) $model->getAttribute('school_id') === (int) $this->id(), 404);
    }

    /** The school id new records are written to; aborts when none is selected. */
    public function requireId(): int
    {
        $id = $this->id();
        abort_if($id === null, 422, __('Choose a school first.'));

        return $id;
    }
}
