<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\StoresDateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Excuse extends Model
{
    use Audited, StoresDateOnly;

    protected array $dateOnly = ['date'];

    public const TYPES = ['medical', 'family', 'travel', 'official', 'other'];

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = ['school_id', 'student_id', 'attendance_record_id', 'date', 'type', 'reason', 'attachment_path', 'status', 'submitted_by', 'reviewed_by', 'reviewed_at', 'review_note'];

    protected function casts(): array
    {
        return ['date' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
