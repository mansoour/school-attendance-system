<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\StoresDateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use Audited, StoresDateOnly;

    protected array $dateOnly = ['date'];

    public const STATUSES = ['present', 'absent', 'late', 'excused'];

    protected $fillable = ['school_id', 'student_id', 'classroom_id', 'date', 'status', 'late_minutes', 'note', 'recorded_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'late_minutes' => 'integer'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function excuses(): HasMany
    {
        return $this->hasMany(Excuse::class);
    }
}
