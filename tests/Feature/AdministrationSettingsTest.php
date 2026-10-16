<?php

namespace Tests\Feature;

use App\Admin\Resources;
use App\Billing\Entitlements;
use App\Models\Feature;
use App\Models\TranslationRevision;
use App\Models\User;
use App\Settings\SecretStore;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdministrationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Notification::fake();
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('owner');

        return $user;
    }

    private function settings(): SettingsService
    {
        return app(SettingsService::class);
    }

    public function test_plans_start_disabled_and_can_be_enabled_without_losing_data(): void
    {
        $this->assertFalse($this->settings()->get('plans.enabled'));
        $this->get('/pricing')->assertNotFound();
        $user = $this->owner();
        $this->actingAs($user)->get('/admin/manage/plans')->assertNotFound();
        $this->getJson('/api/v1/me')->assertJsonPath('plans_enabled', false)->assertJsonPath('plan', null);
        $this->get('/')->assertDontSee(route('pricing'), false);
        $this->post('/admin/configuration/plans', ['plans_enabled' => 1])->assertRedirect(route('password.confirm'));
        $this->withSession(['auth.password_confirmed_at' => time()])->post('/admin/configuration/plans', ['plans_enabled' => 1])->assertSessionHasNoErrors();
        $this->get('/pricing')->assertOk();
        $this->get('/admin/manage/plans')->assertOk();
        $this->post('/admin/configuration/plans', ['plans_enabled' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('plans', 1);
        app(Entitlements::class)->consume($user, Feature::first()->code);
        $this->assertDatabaseCount('feature_usages', 0);
    }

    public function test_every_settings_section_renders_in_arabic_and_saves_validated_values(): void
    {
        $this->actingAs($this->owner())->withSession(['locale' => 'ar', 'auth.password_confirmed_at' => time()]);
        // Account language has precedence over the visitor preference.
        auth()->user()->update(['locale' => 'ar']);
        foreach (array_keys(SettingsRegistry::sections()) as $section) {
            $this->get('/admin/configuration/'.$section)->assertOk()->assertSee('dir="rtl"', false);
        }
        $this->post('/admin/configuration/settings', ['security_password_min' => 7])->assertSessionHasErrors('security_password_min');
        $this->post('/admin/configuration/settings', ['security_password_min' => 16, 'security_password_mixed' => 1, 'security_lockout_attempts' => 0])->assertSessionHasNoErrors();
        $this->assertSame(16, $this->settings()->get('security.password_min'));
        $this->post('/admin/configuration/styling', ['styling_light_primary' => 'red;display:none'])->assertSessionHasErrors('styling_light_primary');
        $this->post('/admin/configuration/social', ['branding_social_github' => 'javascript:alert(1)'])->assertSessionHasErrors('branding_social_github');
    }

    public function test_security_password_policy_applies_to_registration_and_changes(): void
    {
        $this->settings()->set('security.password_min', 16);
        $this->settings()->set('security.password_symbols', true);
        $this->post('/register', ['name' => 'Example', 'email' => 'example@example.test', 'password' => 'password12345678', 'password_confirmation' => 'password12345678', 'terms' => 1])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_disabled_member_mfa_prevents_both_setup_and_confirmation(): void
    {
        $this->settings()->set('security.member_two_factor_allowed', false);
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->post('/account/security/setup')->assertForbidden();
        $this->post('/account/security/confirm', ['code' => '123456'])->assertForbidden();
    }

    public function test_maintenance_blocks_visitors_but_staff_can_sign_in_and_work(): void
    {
        $this->settings()->set('maintenance.enabled', true);
        $this->get('/')->assertStatus(503);
        $this->get('/login')->assertOk();
        $staff = $this->owner();
        $this->post('/login', ['email' => $staff->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($staff);
        $this->post('/logout');
        $this->actingAs(User::factory()->create())->get('/app')->assertStatus(503);
        $this->actingAs($this->owner())->get('/admin/configuration/maintenance')->assertOk();
    }

    public function test_new_language_is_hidden_until_published_then_can_be_activated(): void
    {
        $this->actingAs($this->owner());
        $this->post('/admin/languages', ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'plural_forms' => 2])->assertSessionHasNoErrors();
        $this->getJson('/api/v1/catalogs/fr')->assertNotFound();
        $this->post('/admin/languages/fr/toggle')->assertUnprocessable();
        $this->post('/admin/translations', ['locale' => 'fr', 'rows' => [['key' => 'Welcome', 'value' => 'Bonjour']]])->assertSessionHasNoErrors();
        $draft = TranslationRevision::firstOrFail();
        $this->get('/?preview_translation='.$draft->id)->assertOk()->assertSee('lang="fr"', false);
        $this->post('/admin/translations/'.$draft->id.'/publish')->assertRedirect();
        $this->post('/admin/languages/fr/toggle')->assertSessionHasNoErrors();
        $this->getJson('/api/v1/catalogs/fr')->assertJsonPath('messages.Welcome', 'Bonjour');
        $this->post('/locale', ['locale' => 'fr'])->assertSessionHasNoErrors();
        $this->get('/login')->assertRedirect();
        $this->post('/admin/languages/en/toggle')->assertUnprocessable();
        $this->get('/admin/translations')->assertOk()->assertDontSee('Messages (JSON)');
    }

    public function test_translation_placeholders_cannot_be_lost(): void
    {
        $this->actingAs($this->owner())->post('/admin/translations', ['locale' => 'ar', 'rows' => [['key' => 'auth.locked', 'value' => 'Locked']]])->assertSessionHasErrors('rows.0.value');
        $this->assertDatabaseCount('translation_revisions', 0);
    }

    public function test_recaptcha_checks_action_hostname_score_and_replay(): void
    {
        $this->settings()->set('security.recaptcha_contact', true);
        app(SecretStore::class)->put('recaptcha_secret_key', 'test-secret');
        $input = ['name' => 'Example', 'email' => 'person@example.test', 'topic' => 'general', 'message' => 'Hello', 'recaptcha_token' => 'unique-token'];
        $result = ['success' => true, 'action' => 'contact', 'hostname' => parse_url(config('app.url'), PHP_URL_HOST), 'score' => 0.9];
        Http::fake(['www.google.com/*' => Http::sequence()->push(array_replace($result, ['action' => 'register']))->push(array_replace($result, ['hostname' => 'other.example']))->push(array_replace($result, ['score' => 0.1]))->push($result)->push($result)]);
        $this->post('/contact', $input)->assertSessionHasErrors('recaptcha_token');
        $this->post('/contact', $input)->assertSessionHasErrors('recaptcha_token');
        $this->post('/contact', $input)->assertSessionHasErrors('recaptcha_token');
        $this->post('/contact', $input)->assertRedirect();
        $this->assertDatabaseCount('contact_messages', 1);
        $this->post('/contact', $input)->assertSessionHasErrors('recaptcha_token');
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_integrations_are_encrypted_and_cloud_and_captcha_require_configuration(): void
    {
        $this->actingAs($this->owner())->withSession(['auth.password_confirmed_at' => time()]);
        $this->post('/admin/configuration/storage', ['storage_r2_enabled' => 1])->assertSessionHasErrors('storage_r2_enabled');
        $this->post('/admin/configuration/settings', ['security_recaptcha_contact' => 1])->assertSessionHasErrors('security_recaptcha_contact');
        $this->post('/admin/integrations', ['key' => 'backup_archive_password', 'value' => 'too-short'])->assertSessionHasErrors('value');
        $this->post('/admin/integrations', ['key' => 'gemini_api_key', 'value' => 'private-test-key'])->assertSessionHasNoErrors();
        $this->assertNotSame('private-test-key', DB::table('integration_secrets')->value('value'));
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['name' => 'models/test'])]);
        $this->post('/admin/integrations/gemini/test')->assertSessionHasNoErrors();
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'private-test-key'));
        $this->get('/admin/integrations')->assertOk()->assertDontSee('private-test-key');
    }

    public function test_arabic_administration_has_no_missing_interface_phrases(): void
    {
        $user = $this->owner();
        $user->update(['locale' => 'ar']);
        $this->settings()->set('plans.enabled', true);
        $this->actingAs($user);
        foreach (['/admin', '/admin/members', '/admin/roles', '/admin/support', '/admin/translations', '/admin/integrations', '/admin/health', '/admin/audit', '/admin/emails', '/account', '/account/security'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (array_keys(Resources::all()) as $resource) {
            $this->get('/admin/manage/'.$resource)->assertOk();
            $this->get('/admin/manage/'.$resource.'/edit')->assertOk();
        }
        $this->assertSame([], DB::table('missing_translations')->where('locale', 'ar')->pluck('key')->all());
    }

    public function test_resend_webhook_rejects_bad_signatures_and_handles_duplicate_events(): void
    {
        $key = random_bytes(32);
        app(SecretStore::class)->put('resend_webhook_secret', 'whsec_'.base64_encode($key));
        DB::table('email_logs')->insert(['recipient' => 'person@example.test', 'subject' => 'Test', 'status' => 'sent', 'provider_id' => 'email-1', 'created_at' => now(), 'updated_at' => now()]);
        $body = json_encode(['type' => 'email.delivered', 'created_at' => now()->toIso8601String(), 'data' => ['email_id' => 'email-1']]);
        $time = (string) time();
        $id = 'event-1';
        $signature = base64_encode(hash_hmac('sha256', $id.'.'.$time.'.'.$body, $key, true));
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_SVIX_ID' => $id, 'HTTP_SVIX_TIMESTAMP' => $time, 'HTTP_SVIX_SIGNATURE' => 'v1,bad'];
        $this->call('POST', '/webhooks/resend', [], [], [], $server, $body)->assertUnauthorized();
        $server['HTTP_SVIX_SIGNATURE'] = 'v1,'.$signature;
        $this->call('POST', '/webhooks/resend', [], [], [], $server, $body)->assertNoContent();
        $this->call('POST','/webhooks/resend',[],[],[],$server,$body)->assertNoContent();
        $this->assertDatabaseCount('webhook_receipts',1);
        $this->assertDatabaseHas('email_logs',['provider_id' => 'email-1', 'status' => 'delivered']);
    }
}
