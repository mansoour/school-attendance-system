<?php

namespace Tests\Feature;

use App\Support\Credits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The MIT license requires the developer credit to be kept. These checks fail
 * the build if the credit is removed from the license, notice or page source.
 */
class CreditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_credit_is_kept(): void
    {
        $this->assertSame('Mansoour', Credits::DEVELOPER);
        $this->assertSame('https://github.com/mansoour', Credits::GITHUB);
        $this->assertSame('https://mansoour.com', Credits::WEBSITE);
        $this->assertStringContainsString('Mansoour', file_get_contents(base_path('LICENSE')));
        $this->assertStringContainsString('https://mansoour.com', file_get_contents(base_path('NOTICE.md')));

        $this->seed();
        $this->get('/')->assertOk()->assertSee('<!-- '.Credits::line().' -->', false)->assertSee('name="author"', false);
    }
}
