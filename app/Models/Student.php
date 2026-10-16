<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use Audited;

    public const RELATIONS = ['father', 'mother', 'brother', 'sister', 'grandparent', 'uncle', 'aunt', 'other'];

    protected $fillable = ['school_id', 'classroom_id', 'name', 'national_id', 'email', 'phone', 'guardian_name', 'guardian_phone', 'guardian_email', 'guardian_relation', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'guardian_student')->withPivot(['relation', 'status', 'approved_at'])->withTimestamps();
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function excuses(): HasMany
    {
        return $this->hasMany(Excuse::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class);
    }

    public function approvedGuardians()
    {
        return $this->guardians()->wherePivot('status', 'approved')->get();
    }

    /** Normalizes Arabic and Latin names so registration matching tolerates common spelling variants. */
    public static function normalizeName(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));
        $name = str_replace(['أ', 'إ', 'آ', 'ة', 'ى', 'ـ'], ['ا', 'ا', 'ا', 'ه', 'ي', ''], $name);
        $name = preg_replace('/[\x{064B}-\x{0652}]/u', '', $name);

        return preg_replace('/\s+/u', ' ', $name);
    }

    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', strtr((string) $phone, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));

        return ltrim(preg_replace('/^(00)?966/', '', $digits), '0');
    }

    /** International digits for wa.me links (Saudi local numbers get the 966 prefix). */
    public static function whatsappNumber(?string $phone): ?string
    {
        $local = self::normalizePhone($phone);
        if ($local === '') {
            return null;
        }

        return strlen($local) === 9 ? '966'.$local : $local;
    }
}
