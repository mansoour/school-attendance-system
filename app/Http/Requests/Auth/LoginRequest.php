<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Notifications\SecurityNotice;
use App\Services\Audit\AuditLogger;
use App\Settings\SettingsService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Checks the credentials without signing in (a second step may follow). Wrong passwords
     * count towards the account lockout set in Settings → Security; a locked account is
     * refused even with the right password until the lock ends.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::query()->where('email', $this->string('email')->lower()->value())->first();

        if ($user?->isLockedOut()) {
            throw ValidationException::withMessages([
                'email' => trans('auth.locked', ['minutes' => (int) ceil(now()->diffInSeconds($user->locked_until) / 60)]),
            ]);
        }

        if (! $user || ! Auth::getProvider()->validateCredentials($user, ['password' => $this->string('password')->value()])) {
            RateLimiter::hit($this->throttleKey());
            event(new Failed('web', $user, $this->only('email', 'password')));

            if ($user) {
                $this->countFailure($user);
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        if ($user->failed_login_attempts > 0 || $user->locked_until !== null) {
            $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();
        }

        return $user;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    private function countFailure(User $user): void
    {
        $settings = app(SettingsService::class);
        $limit = (int) $settings->get('security.lockout_attempts');
        $attempts = $user->failed_login_attempts + 1;

        if ($limit > 0 && $attempts >= $limit) {
            $minutes = (int) $settings->get('security.lockout_minutes');
            $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => now()->addMinutes($minutes)])->save();
            app(AuditLogger::class)->record('auth.account_locked', $user, ['attempts' => $attempts, 'minutes' => $minutes, 'ip' => $this->ip()], result: 'failure');
            SecurityNotice::send($user, 'account_locked', ['minutes' => $minutes]);

            return;
        }

        $user->forceFill(['failed_login_attempts' => $attempts])->save();
    }
}
