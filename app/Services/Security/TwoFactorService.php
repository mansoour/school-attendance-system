<?php

namespace App\Services\Security;

use App\Models\User;
use App\Notifications\SecurityNotice;
use App\Security\Totp;
use App\Services\Audit\AuditLogger;
use App\Settings\SettingsService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Authenticator-app two-factor sign-in, for staff and (when allowed) every member.
 */
class TwoFactorService
{
    public const RECOVERY_CODE_COUNT = 8;

    public function __construct(
        private readonly Totp $totp,
        private readonly AuditLogger $audit,
    ) {}

    public function newSecret(): string
    {
        return $this->totp->generateSecret();
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $uri = $this->totp->provisioningUri($secret, $user->email, (string) config('app.name'));
        $writer = new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd));

        return $writer->writeString($uri);
    }

    /**
     * Confirms setup with a code from the app. Returns the plain recovery codes
     * (shown once) or null when the code is wrong.
     *
     * @return list<string>|null
     */
    public function confirm(User $user, string $secret, string $code): ?array
    {
        $step = $this->totp->verify($secret, $code);

        if ($step === null) {
            return null;
        }

        $codes = $this->recoveryCodesEnabled() ? $this->makeRecoveryCodes() : [];

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map($this->hashCode(...), $codes),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_step' => $step,
        ])->save();

        $this->audit->record('mfa.enabled', $user, actor: $user);
        SecurityNotice::send($user, 'two_factor_enabled');

        return $codes;
    }

    /**
     * Accepts a current authenticator code or an unused recovery code.
     */
    public function verify(User $user, string $input): bool
    {
        if (! $user->hasTwoFactorEnabled()) {
            return false;
        }

        return DB::transaction(function () use ($user, $input) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->getKey());

            $step = $this->totp->verify((string) $locked->two_factor_secret, $input, $locked->two_factor_last_step);

            if ($step !== null) {
                $locked->forceFill(['two_factor_last_step' => $step])->save();

                return true;
            }

            if (! $this->recoveryCodesEnabled()) {
                return false;
            }

            $hash = $this->hashCode($input);
            $remaining = $locked->two_factor_recovery_codes ?? [];

            if (! in_array($hash, $remaining, true)) {
                return false;
            }

            $locked->forceFill(['two_factor_recovery_codes' => array_values(array_diff($remaining, [$hash]))])->save();
            $this->audit->record('mfa.recovery_code_used', $locked, ['remaining' => count($remaining) - 1], actor: $locked);

            return true;
        });
    }

    /**
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->makeRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => array_map($this->hashCode(...), $codes)])->save();
        $this->audit->record('mfa.recovery_codes_regenerated', $user, actor: $user);

        return $codes;
    }

    /**
     * Whether recovery codes are offered and accepted (Settings → Security).
     */
    public function recoveryCodesEnabled(): bool
    {
        return (bool) app(SettingsService::class)->get('security.recovery_codes');
    }

    public function reset(User $user, string $via): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null,
        ])->save();

        $this->audit->record('mfa.reset', $user, ['via' => $via]);
        SecurityNotice::send($user, 'two_factor_disabled');
    }

    /**
     * @return list<string>
     */
    private function makeRecoveryCodes(): array
    {
        return array_map(
            fn () => Str::lower(Str::random(5).'-'.Str::random(5)),
            range(1, self::RECOVERY_CODE_COUNT),
        );
    }

    private function hashCode(string $code): string
    {
        return hash('sha256', Str::lower(trim($code)));
    }
}
