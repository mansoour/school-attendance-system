<?php

namespace App\Integrations;

use App\Settings\SecretStore;
use App\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class Recaptcha
{
    public function validate(Request $request, string $action): void
    {
        $settings = app(SettingsService::class);
        if (! $settings->get('security.recaptcha_'.$action)) {
            return;
        }
        $valid = false;
        if (is_string($token = $request->input('recaptcha_token')) && strlen($token) <= 4096) {
            try {
                $result = Http::asForm()->timeout(10)->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => app(SecretStore::class)->get('recaptcha_secret_key'), 'response' => $token, 'remoteip' => $request->ip(),
                ])->throw()->json();
                $valid = ($result['success'] ?? false) && ($result['action'] ?? '') === $action
                    && ($result['hostname'] ?? '') === parse_url(config('app.url'), PHP_URL_HOST)
                    && ($result['score'] ?? 0) >= $settings->get('security.recaptcha_score') / 10;
                if ($valid) {
                    $valid = Cache::add('recaptcha.used.'.hash('sha256', $token), true, 180);
                }
            } catch (\Throwable) {
                $valid = false;
            }
        }
        if (! $valid) {
            throw ValidationException::withMessages(['recaptcha_token' => __('Verification failed. Please try again.')]);
        }
    }
}
