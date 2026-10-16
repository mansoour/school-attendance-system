<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Grade extends Model
{
    use Audited;

    public const STAGES = ['kindergarten', 'primary', 'intermediate', 'secondary'];

    protected $fillable = ['school_id', 'name', 'stage', 'sort_order'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class)->orderBy('name');
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(Student::class, Classroom::class);
    }
}
