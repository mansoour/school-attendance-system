<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Classroom;
use App\Models\Grade;
use App\School\Calendar;
use App\School\SchoolContext;
use Illuminate\Support\Carbon;

abstract class Controller extends BaseController
{
    protected function context(): SchoolContext
    {
        return app(SchoolContext::class);
    }

    /** Grades with their classes for filter menus in the active school. */
    protected function gradeOptions()
    {
        return $this->context()->scope(Grade::query())->with('classrooms')->orderBy('sort_order')->orderBy('name')->get();
    }

    protected function classOptions()
    {
        return $this->context()->scope(Classroom::query())->with('grade')->get()->sortBy(fn ($c) => sprintf('%05d-%s', $c->grade?->sort_order ?? 0, $c->name))->values();
    }

    /** A day for daily attendance screens: weekends move back to the last school day (Thursday). */
    protected function schoolDate(?string $value): Carbon
    {
        $date = $this->date($value);
        if (! Calendar::isSchoolDay($date)) {
            session()->now('status', __('Friday and Saturday are the weekend. Showing the last school day.'));
        }

        return Carbon::instance(Calendar::lastSchoolDay($date));
    }

    protected function date(?string $value, ?Carbon $default = null): Carbon
    {
        try {
            return $value ? Carbon::parse($value)->startOfDay() : ($default ?? today());
        } catch (\Throwable) {
            return $default ?? today();
        }
    }
}
