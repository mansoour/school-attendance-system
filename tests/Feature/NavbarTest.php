<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NavbarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_branding_subtabs_are_not_duplicated_in_the_main_menu(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $response = $this->actingAs($owner)->get('/admin/configuration/icons')->assertOk();
        preg_match('/<aside id="admin-sidebar".*?<\/aside>/s', $response->getContent(), $sidebar);
        $this->assertMatchesRegularExpression('/href="'.preg_quote(route('admin.settings', 'branding'), '/').'"\s+aria-current="page"/', $sidebar[0]);
        foreach (['icons', 'cookies', 'social', 'styling', 'fonts'] as $section) {
            $this->assertStringNotContainsString(route('admin.settings', $section), $sidebar[0]);
            $response->assertSee(route('admin.settings', $section), false);
        }
        $response->assertSee('class="site-header admin-header"', false);
    }

    public function test_language_dropdown_only_offers_other_languages_and_has_flags(): void
    {
        $response = $this->get('/')->assertOk();
        preg_match('/<div id="navbar-languages".*?<\/div>/s', $response->getContent(), $menu);
        $this->assertStringContainsString('name="locale" value="ar"', $menu[0]);
        $this->assertStringNotContainsString('name="locale" value="en"', $menu[0]);
        $this->assertStringContainsString('class="language-flag"', $menu[0]);
        $response = $this->withSession(['locale' => 'ar'])->get('/')->assertOk();
        preg_match('/<div id="navbar-languages".*?<\/div>/s', $response->getContent(), $menu);
        $this->assertStringContainsString('name="locale" value="en"', $menu[0]);
        $this->assertStringNotContainsString('name="locale" value="ar"', $menu[0]);
    }

    public function test_dark_theme_selector_is_valid_css_and_both_palettes_are_rendered(): void
    {
        $this->get('/')->assertOk()->assertSee('html[data-theme=dark] {', false)
            ->assertDontSee('html[data-theme=&quot;dark&quot;]', false)
            ->assertSee('--surface:#102219', false)->assertSee('--surface:#FFFFFF', false)
            ->assertSee('data-theme-choice="dark"', false);
    }

    public function test_dropdown_and_unread_count_are_owned_and_mark_all_read_preserves_other_accounts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach ([$user, $user, $other] as $recipient) {
            $recipient->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'test', 'data' => ['event' => 'welcome']]);
        }
        $this->actingAs($user)->get('/app')->assertOk()->assertSee('navbarNotifications(2)', false)
            ->assertSee('id="navbar-notifications"', false)->assertSee('View all notifications');
        $this->getJson('/api/v1/notifications')->assertJsonPath('unread_count', 2);
        $this->postJson('/api/v1/notifications/read-all')->assertNoContent();
        $this->getJson('/api/v1/notifications')->assertJsonPath('unread_count', 0);
        $this->assertSame(1, $other->unreadNotifications()->count());
        $this->get('/app')->assertOk()->assertSee('navbarNotifications(0)', false);
    }
}
