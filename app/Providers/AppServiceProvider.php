<?php

namespace App\Providers;

use App\Localization\CatalogLoader;
use App\Localization\CatalogTranslator;
use App\Models\EmailLog;
use App\School\GuardianLinker;
use App\Settings\RuntimeSettings;
use App\Settings\SecretStore;
use App\Settings\SettingsService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('translation.loader', fn ($loader, $app) => new CatalogLoader($app['files'], [base_path('vendor/laravel/framework/src/Illuminate/Translation/lang'), lang_path()]));
        $this->app->extend('translator', function ($original, $app) {
            $translator = new CatalogTranslator($app['translation.loader'], $app['config']['app.locale']);
            $translator->setFallback($app['config']['app.fallback_locale']);

            return $translator;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @date($value), @dateLong($value) and @dateTime($value): Hijri with Gregorian alongside.
        Blade::directive('date', fn ($e) => "<?php echo \\App\\Support\\Dates::html($e); ?>");
        Blade::directive('dateLong', fn ($e) => "<?php echo \\App\\Support\\Dates::html($e, true); ?>");
        Blade::directive('dateTime', fn ($e) => "<?php echo \\App\\Support\\Dates::html($e, false, true); ?>");
        $this->app->booted(function () {
            if (app()->runningInConsole() && array_intersect($_SERVER['argv'] ?? [], ['config:cache', 'optimize', 'package:discover'])) {
                return;
            }
            $database = config('database.connections.sqlite.database');
            if (app()->runningInConsole() && config('database.default') === 'sqlite' && $database !== ':memory:' && ! is_file($database)) {
                return;
            }
            if (Schema::hasTable('integration_secrets')) {
                app(RuntimeSettings::class)->apply();
                $secrets = app(SecretStore::class);
                foreach (['mail_password' => 'mail.mailers.smtp.password', 'storage_access_key' => 'filesystems.disks.s3.key', 'storage_secret' => 'filesystems.disks.s3.secret'] as $key => $configuration) {
                    if ($value = $secrets->get($key)) {
                        config([$configuration => $value]);
                    }
                }
            }
        });
        Password::defaults(function () {
            $settings = app(SettingsService::class);
            $rule = Password::min($settings->get('security.password_min'));
            if ($settings->get('security.password_mixed')) {
                $rule->mixedCase();
            }
            if ($settings->get('security.password_numbers')) {
                $rule->numbers();
            }
            if ($settings->get('security.password_symbols')) {
                $rule->symbols();
            }

            return $rule;
        });
        Event::listen(Verified::class, fn ($event) => app(GuardianLinker::class)->activate($event->user));
        Event::listen(MessageSent::class, function ($event) {
            EmailLog::create(['provider_id' => $event->message->getHeaders()->get('X-Resend-Email-ID')?->getBodyAsString(), 'recipient' => implode(', ', array_map(fn ($address) => $address->getAddress(), $event->message->getTo())), 'subject' => $event->message->getSubject() ?? '', 'status' => config('mail.default') === 'log' ? 'logged' : 'sent']);
        });
    }
}
