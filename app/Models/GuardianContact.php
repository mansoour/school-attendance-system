<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\StoresDateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianContact extends Model
{
    use Audited, StoresDateOnly;

    protected array $dateOnly = ['absence_date'];

    public const CHANNELS = ['whatsapp', 'call', 'email'];

    protected $fillable = ['school_id', 'student_id', 'user_id', 'channel', 'absence_date', 'note'];

    protected function casts(): array
    {
        return ['absence_date' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
