<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable;

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            $user->notifications()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
    }

    protected $fillable = ['name', 'email', 'password', 'locale', 'school_id', 'phone', 'job_title', 'is_active'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_last_step', 'notification_preferences'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'two_factor_secret' => 'encrypted', 'two_factor_recovery_codes' => 'encrypted:array', 'two_factor_confirmed_at' => 'datetime', 'two_factor_last_step' => 'integer', 'locked_until' => 'datetime', 'notification_preferences' => 'array', 'terms_accepted_at' => 'datetime', 'marketing_opt_in' => 'boolean', 'marketing_opt_in_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function isLockedOut(): bool
    {
        return $this->locked_until?->isFuture() ?? false;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** Students linked to this guardian account. */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student')->withPivot(['relation', 'status', 'approved_at'])->withTimestamps();
    }

    public function approvedChildren(): BelongsToMany
    {
        return $this->children()->wherePivot('status', 'approved');
    }

    public function isSystemAdmin(): bool
    {
        return $this->hasRole('owner');
    }

    public function isGuardian(): bool
    {
        return $this->hasRole('guardian');
    }
}
