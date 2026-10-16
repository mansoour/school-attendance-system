<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Settings\SecretStore;
use App\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IntegrationController extends Controller
{
    public const CARDS = [
        'resend' => ['title' => 'Email (Resend)', 'icon' => 'mail', 'help' => 'Transactional email for verification, password resets and notifications.', 'keys' => ['resend_api_key' => 'Resend API key', 'resend_webhook_secret' => 'Resend webhook signing secret']],
        'gemini' => ['title' => 'AI (Gemini)', 'icon' => 'sparkles', 'help' => 'Server-side AI connection for future features.', 'keys' => ['gemini_api_key' => 'Gemini API key']],
        'backup' => ['title' => 'Backup encryption', 'icon' => 'archive', 'help' => 'Keep this password outside the server. Older archives retain their original password.', 'keys' => ['backup_archive_password' => 'Backup archive password']],
        'r2' => ['title' => 'File storage (Cloudflare R2)', 'icon' => 'cloud', 'help' => 'Store new uploads in your own S3-compatible bucket.', 'keys' => ['r2_access_key_id' => 'R2 access key ID', 'r2_secret_access_key' => 'R2 secret access key']],
        'recaptcha' => ['title' => 'Google reCAPTCHA v3', 'icon' => 'shield-check', 'help' => 'Invisible checks for registration and contact forms.', 'keys' => ['recaptcha_site_key' => 'reCAPTCHA site key', 'recaptcha_secret_key' => 'reCAPTCHA secret key']],
    ];

    public function index(SecretStore $secrets)
    {
        return view('admin.integrations', ['cards' => self::CARDS, 'configured' => $secrets->configured()]);
    }

    public function save(Request $request, SecretStore $secrets)
    {
        $data = $request->validate(['key' => ['required', Rule::in(SecretStore::KEYS)], 'value' => 'required|string|max:4096']);
        if ($data['key'] === 'backup_archive_password') {
            $request->validate(['value' => 'min:12']);
        }
        $secrets->put($data['key'], $data['value']);
        Artisan::call('queue:restart');
        app(AuditLogger::class)->record('integrations.updated');

        return back()->with('status', __('Saved.'));
    }

    public function remove(Request $request, SecretStore $secrets)
    {
        $data = $request->validate(['key' => ['required', Rule::in(SecretStore::KEYS)]]);
        $secrets->put($data['key'], null);
        Artisan::call('queue:restart');
        app(AuditLogger::class)->record('integrations.removed');

        return back()->with('status', __('Saved.'));
    }

    public function model(Request $request)
    {
        $data = $request->validate(['model' => 'required|regex:/^[a-zA-Z0-9._-]+$/|max:100']);
        app(SettingsService::class)->set('integrations.gemini_model', $data['model']);

        return back()->with('status', __('Saved.'));
    }

    public function test(Request $request, string $provider, SecretStore $secrets)
    {
        abort_unless(in_array($provider, ['resend', 'gemini', 'r2']), 404);
        try {
            if ($provider === 'resend') {
                // The selected mail driver is intentional: local development remains on log.
                Mail::raw(__('This is a test email from your application.'), fn ($message) => $message->to($request->user()->email)->subject(__('Test email')));
            } elseif ($provider === 'gemini') {
                abort_unless($secrets->get('gemini_api_key'), 422);
                Http::timeout(15)->withHeaders(['x-goog-api-key' => $secrets->get('gemini_api_key')])->get('https://generativelanguage.googleapis.com/v1beta/models/'.app(SettingsService::class)->get('integrations.gemini_model'))->throw();
            } else {
                abort_unless($secrets->get('r2_access_key_id') && $secrets->get('r2_secret_access_key'), 422);
                $path = 'connection-tests/'.Str::uuid().'.txt';
                try {
                    Storage::disk('r2')->put($path, 'connection test');
                    throw_unless(Storage::disk('r2')->get($path) === 'connection test', new \RuntimeException);
                } finally {
                    Storage::disk('r2')->delete($path);
                }
            }
        } catch (\Throwable $exception) {
            // Provider exceptions can contain keys or signed URLs; do not expose their text on screen.
            // The log keeps the reason (with secrets masked) so the administrator can fix the setup.
            $reason = preg_replace(['/re_[A-Za-z0-9_]+/', '/whsec_[A-Za-z0-9_+\/=]+/', '/AIza[0-9A-Za-z_-]+/'], '[hidden]', $exception->getMessage());
            Log::warning('Integration test failed', ['provider' => $provider, 'mailer' => config('mail.default'), 'from' => config('mail.from.address'), 'error' => class_basename($exception).': '.mb_substr($reason, 0, 500)]);

            return back()->withErrors(['integration' => __('The connection test failed. Check the configuration and try again.')]);
        }

        return back()->with('status', $provider === 'resend' && config('mail.default') === 'log' ? __('Test email written to the local mail log.') : __('Connection test successful.'));
    }
}
