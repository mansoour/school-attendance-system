<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use Audited;

    protected $fillable = ['name', 'code', 'city', 'phone', 'email', 'day_starts_at', 'late_after_minutes', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'late_after_minutes' => 'integer'];
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class)->orderBy('sort_order');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
