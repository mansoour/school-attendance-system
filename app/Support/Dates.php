<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use IntlDateFormatter;

/**
 * School dates: Hijri (Umm al-Qura) is the primary calendar, Gregorian is shown alongside.
 */
class Dates
{
    private static function carbon(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value);
    }

    public static function hijri(mixed $value, bool $weekday = false): string
    {
        $date = self::carbon($value);
        if (! $date) {
            return '';
        }
        $locale = app()->getLocale() === 'ar' ? 'ar_SA' : 'en';
        $pattern = ($weekday ? 'EEEE، ' : '').'d MMMM y';
        $formatter = new IntlDateFormatter($locale.'@calendar=islamic-umalqura;numbers=latn', IntlDateFormatter::LONG, IntlDateFormatter::NONE, config('app.school_timezone', 'Asia/Riyadh'), IntlDateFormatter::TRADITIONAL, app()->getLocale() === 'ar' ? $pattern : str_replace('، ', ', ', $pattern));

        return $formatter->format($date->copy()->setTime(12, 0)).(app()->getLocale() === 'ar' ? ' هـ' : ' AH');
    }

    public static function gregorian(mixed $value): string
    {
        $date = self::carbon($value);

        return $date ? $date->format('Y-m-d').(app()->getLocale() === 'ar' ? ' م' : '') : '';
    }

    /** Plain text: "5 جمادى الأولى 1448 هـ (2026-10-16 م)". Used in exports and messages. */
    public static function both(mixed $value, bool $weekday = false): string
    {
        $date = self::carbon($value);

        return $date ? self::hijri($date, $weekday).' ('.self::gregorian($date).')' : '';
    }

    /** HTML for pages: Hijri first, Gregorian underneath in a smaller tone. */
    public static function html(mixed $value, bool $weekday = false, bool $time = false): string
    {
        $date = self::carbon($value);
        if (! $date) {
            return '—';
        }
        $extra = $time ? ' · '.$date->format('H:i') : '';

        return '<span class="date-dual"><span class="date-hijri">'.e(self::hijri($date, $weekday)).'</span><small class="date-greg" dir="ltr">'.e(self::gregorian($date).$extra).'</small></span>';
    }
}
