<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * A short code identifying the current request's error. It is written to the log
 * entry and shown on the error page, so a reported error can be found quickly.
 */
class ErrorReference
{
    private static ?string $current = null;

    public static function current(): string
    {
        return self::$current ??= strtoupper(Str::random(8));
    }

    public static function existing(): ?string
    {
        return self::$current;
    }
}
