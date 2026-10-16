<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentNote extends Model
{
    use Audited;

    public const CATEGORIES = ['general', 'health', 'counseling', 'behavior', 'attendance'];

    protected $fillable = ['school_id', 'student_id', 'attendance_record_id', 'author_id', 'category', 'body', 'visible_to_guardian'];

    protected function casts(): array
    {
        return ['visible_to_guardian' => 'boolean'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
