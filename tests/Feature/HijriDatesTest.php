<?php

namespace Tests\Feature;

use App\Support\Dates;
use Tests\TestCase;

class HijriDatesTest extends TestCase
{
    public function test_dates_show_hijri_first_with_gregorian_alongside(): void
    {
        app()->setLocale('ar');
        $this->assertSame('5 جمادى الأولى 1448 هـ', Dates::hijri('2026-10-16'));
        $this->assertSame('5 جمادى الأولى 1448 هـ (2026-10-16 م)', Dates::both('2026-10-16'));
        $this->assertStringContainsString('date-hijri', Dates::html('2026-10-16'));

        app()->setLocale('en');
        $this->assertSame('5 Jumada I 1448 AH (2026-10-16)', Dates::both('2026-10-16'));
    }
}
