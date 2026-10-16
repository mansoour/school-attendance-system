<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * Stores the listed date columns as plain Y-m-d strings so daily lookups and
 * unique keys compare reliably on every database engine.
 */
trait StoresDateOnly
{
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->dateOnly, true) && $value !== null && $value !== '') {
            $value = Carbon::parse($value)->toDateString();
            $this->attributes[$key] = $value;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }
}
