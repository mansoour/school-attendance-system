<?php

namespace Tests\Feature;

use App\Admin\Resources;
use App\Models\BrandingAsset;
use App\Models\TranslationRevision;
use App\Models\User;
use App\Notifications\MemberNotification;
use App\Security\Totp;
use App\Services\Security\TwoFactorService;
use App\Settings\SecretStore;
use App\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StarterKitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // These starter tests describe the English interface; the product default is Arabic.
        app(SettingsService::class)->set('appearance.locale', 'en');
        Notification::fake();
    }

    private function staff(string $role = 'owner'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        app(SettingsService::class)->set('security.admin_two_factor_required', false);

        return $user;
    }

    public function test_public_pages_and_rtl_render_without_domain_dependencies(): void
    {
        foreach (['/', '/contact', '/pages/about', '/pages/help', '/pages/privacy', '/pages/terms'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->withSession(['locale' => 'ar'])->get('/')->assertOk()->assertSee('dir="rtl"', false)->assertSee('بالتواصل نعزز الانتظام');
        $this->get('/api/v1/catalogs/ar')->assertOk()->assertJsonPath('locale', 'ar');
        foreach (['/api/v1/institutions', '/api/v1/applications/university', '/api/v1/extension/session', '/api/v1/profile/education'] as $url) {
            $this->getJson($url)->assertNotFound();
        }
    }

    public function test_registration_requires_terms_and_honors_registration_setting(): void
    {
        $data = ['name' => 'Member', 'email' => 'member@example.test', 'password' => 'secure-password123', 'password_confirmation' => 'secure-password123'];
        $this->post('/register', $data)->assertSessionHasErrors('terms');
        app(SettingsService::class)->set('security.registration_open', false);
        $this->post('/register', $data + ['terms' => true])->assertForbidden();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_mfa_sign_in_requires_code_and_rejects_replay(): void
    {
        $totp = app(Totp::class);
        $secret = $totp->generateSecret();
        $user = User::factory()->create(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
        $this->post('/two-factor-challenge', ['code' => 'bad'])->assertSessionHasErrors('code');
        $code = $totp->codeAt($secret, $totp->timeStep());
        $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertFalse(app(TwoFactorService::class)->verify($user->fresh(), $code));
        $this->assertNotSame($secret, DB::table('users')->where('id', $user->id)->value('two_factor_secret'));
    }

    public function test_recovery_codes_are_single_use_and_setup_requires_password_confirmation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/account/security/setup')->assertRedirect(route('password.confirm'));
        $totp = app(Totp::class);
        $secret = $totp->generateSecret();
        $service = app(TwoFactorService::class);
        $codes = $service->confirm($user, $secret, $totp->codeAt($secret, $totp->timeStep()));
        $this->assertCount(8, $codes);
        $this->assertTrue($service->verify($user->fresh(), $codes[0]));
        $this->assertFalse($service->verify($user->fresh(), $codes[0]));
    }

    public function test_staff_must_enroll_in_mfa_and_members_cannot_enter_admin(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member)->get('/admin')->assertForbidden();
        $member->assignRole('owner');
        $this->actingAs($member->fresh())->get('/admin')->assertOk();
        app(SettingsService::class)->set('security.admin_two_factor_required', true);
        $this->actingAs($member->fresh())->get('/admin')->assertRedirect(route('security.edit'));
    }

    public function test_admin_can_enable_and_disable_staff_mfa_requirement_in_settings(): void
    {
        $owner = $this->staff();
        $settings = [
            'security_registration_open' => true,
            'security_email_verification' => true,
            'security_admin_two_factor_required' => true,
            'security_recovery_codes' => true,
            'security_lockout_attempts' => 10,
            'security_lockout_minutes' => 15,
        ];
        $this->actingAs($owner)->withSession(['auth.password_confirmed_at' => time()])
            ->post('/admin/configuration/settings', $settings)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue(app(SettingsService::class)->get('security.admin_two_factor_required'));
        $this->get('/admin')->assertRedirect(route('security.edit'));

        $owner->forceFill(['two_factor_secret' => app(Totp::class)->generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $settings['security_admin_two_factor_required'] = false;
        $this->actingAs($owner->fresh())->withSession(['admin_mfa.passed_for' => $owner->id])
            ->post('/admin/configuration/settings', $settings)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertFalse(app(SettingsService::class)->get('security.admin_two_factor_required'));
    }

    public function test_admin_modules_are_permission_scoped(): void
    {
        $staff = $this->staff('support');
        $this->actingAs($staff)->get('/admin/support')->assertOk();
        $this->get('/admin/integrations')->assertForbidden();
        $this->get('/admin/manage/pages')->assertForbidden();
        $this->post('/admin/manage/pages', ['slug' => 'unauthorized'])->assertForbidden();
    }

    public function test_all_owner_admin_views_render(): void
    {
        $this->actingAs($this->staff());
        foreach (['/admin', '/admin/members', '/admin/roles', '/admin/support', '/admin/translations', '/admin/integrations', '/admin/health', '/admin/audit', '/admin/emails', '/admin/configuration/settings', '/admin/configuration/branding', '/admin/configuration/homepage', '/admin/configuration/staff-alerts'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (array_keys(Resources::all()) as $resource) {
            $this->get('/admin/manage/'.$resource)->assertOk();
            $this->get('/admin/manage/'.$resource.'/edit')->assertOk();
        }
    }

    public function test_content_is_validated_and_markdown_cannot_execute_html(): void
    {
        $this->actingAs($this->staff())->post('/admin/manage/pages', [
            'slug' => 'example', 'title' => ['en' => 'Example'], 'body' => ['en' => '<script>alert(1)</script> [bad](javascript:alert(1))'], 'is_published' => true,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->get('/pages/example')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('href="javascript:', false);
        $this->post('/admin/manage/site-notices', ['message' => ['en' => 'bad'], 'link_url' => 'javascript:alert(1)'])->assertSessionHasErrors('link_url');
    }

    public function test_secrets_require_confirmation_and_are_encrypted_and_masked(): void
    {
        $this->actingAs($this->staff())->post('/admin/integrations', ['key' => 'mail_password', 'value' => 'sensitive-value'])->assertRedirect(route('password.confirm'));
        $this->withSession(['auth.password_confirmed_at' => time()])->post('/admin/integrations', ['key' => 'mail_password', 'value' => 'sensitive-value'])->assertSessionHasNoErrors();
        $this->assertNotSame('sensitive-value', DB::table('integration_secrets')->value('value'));
        $this->assertSame('sensitive-value', app(SecretStore::class)->get('mail_password'));
        $this->get('/admin/integrations')->assertDontSee('sensitive-value');
        $this->assertStringNotContainsString('sensitive-value', json_encode(DB::table('audit_logs')->get()));
    }

    public function test_translation_drafts_are_private_until_published_and_can_be_restored(): void
    {
        $user = $this->staff('translator');
        $this->actingAs($user)->post('/admin/translations', ['locale' => 'en', 'rows' => [['key' => 'Welcome', 'value' => 'Hello again.']]])->assertSessionHasNoErrors();
        $draft = TranslationRevision::firstOrFail();
        $this->get('/api/v1/catalogs/en')->assertJsonPath('version', 0);
        $this->post('/admin/translations/'.$draft->id.'/publish')->assertRedirect();
        $this->get('/login')->assertRedirect();
        $this->get('/api/v1/catalogs/en')->assertJsonPath('messages.Welcome', 'Hello again.');
        auth()->logout();
        $this->get('/login')->assertOk()->assertSee('Hello again.');
    }

    public function test_notification_reads_are_owned_and_security_preferences_cannot_be_disabled(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $id = (string) Str::uuid();
        $other->notifications()->create(['id' => $id, 'type' => MemberNotification::class, 'data' => ['event' => 'welcome', 'parameters' => []]]);
        $this->actingAs($user)->postJson('/api/v1/notifications/'.$id.'/read')->assertNotFound();
        $user->forceFill(['notification_preferences' => ['welcome' => ['database' => true, 'mail' => false], 'security' => ['database' => false, 'mail' => false]]])->save();
        $this->assertSame(['database'], (new MemberNotification('welcome'))->via($user->fresh()));
        $this->assertSame(['database', 'mail'], (new MemberNotification('security'))->via($user->fresh()));
    }

    public function test_support_messages_and_resolution_are_audited(): void
    {
        $this->post('/contact', ['name' => 'Visitor', 'email' => 'visitor@example.test', 'topic' => 'general', 'message' => 'Please help.'])->assertRedirect();
        $this->assertDatabaseCount('contact_messages', 1);
        $this->actingAs($this->staff('support'))->patch('/admin/support/1', ['resolved' => true])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'support.updated']);
    }

    public function test_owner_role_cannot_be_deleted(): void
    {
        $this->actingAs($this->staff())->withSession(['auth.password_confirmed_at' => time()]);
        $this->delete('/admin/roles/'.Role::findByName('owner')->id)->assertConflict();
        $this->post('/admin/roles', ['name' => 'owner', 'permissions' => []])->assertForbidden();
    }

    public function test_branding_upload_is_validated_and_localized(): void
    {
        Storage::fake('public');
        $this->actingAs($this->staff())->withSession(['auth.password_confirmed_at' => time()]);
        $this->post('/admin/configuration/branding', ['branding_name_en' => 'Neutral', 'branding_name_ar' => 'محايد', 'branding_color' => '#315a50', 'branding_logo_en' => UploadedFile::fake()->image('logo.png')])->assertSessionHasNoErrors();
        $path = app(SettingsService::class)->get('branding.logo.en');
        $asset = BrandingAsset::findOrFail(substr($path, 6));
        Storage::disk('public')->assertExists($asset->path);
        $this->get('/')->assertSee('Neutral');
    }
}
