<?php

namespace App\School;

use Carbon\CarbonInterface;

/**
 * The school week: Sunday to Thursday. Friday and Saturday are the weekend,
 * so no attendance is recorded on them.
 */
class Calendar
{
    /** ISO-8601 weekday numbers (1 = Monday … 7 = Sunday) that are school days. */
    public static function schoolDays(): array
    {
        return config('school.days', [7, 1, 2, 3, 4]);
    }

    public static function isSchoolDay(CarbonInterface $date): bool
    {
        return in_array($date->dayOfWeekIso, self::schoolDays(), true);
    }

    /** The given day if it is a school day, otherwise the most recent school day before it. */
    public static function lastSchoolDay(CarbonInterface $date): CarbonInterface
    {
        $day = $date->copy()->startOfDay();
        for ($i = 0; $i < 7 && ! self::isSchoolDay($day); $i++) {
            $day = $day->subDay();
        }

        return $day;
    }

    /** Number of school days between two dates, inclusive. */
    public static function countSchoolDays(CarbonInterface $from, CarbonInterface $to): int
    {
        $count = 0;
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day = $day->addDay()) {
            $count += self::isSchoolDay($day) ? 1 : 0;
        }

        return $count;
    }
}
