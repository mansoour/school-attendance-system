<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use Audited;

    protected $fillable = ['school_id', 'grade_id', 'name', 'capacity', 'supervisor_id'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class)->orderBy('name');
    }

    public function label(): string
    {
        return trim(($this->grade?->name ?? '').' / '.$this->name, ' /');
    }
}
