<?php

namespace App\Localization;

use App\Models\Locale;

class LocaleRegistry
{
    public function codes(): array
    {
        return Locale::where('is_active', true)->pluck('code')->all();
    }
}
