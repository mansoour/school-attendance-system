<?php

namespace App\Http\Middleware;

use App\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;

class PlansEnabled
{
    public function handle(Request $request, Closure $next)
    {
        $resource = $request->route('resource');
        if (! $resource || in_array($resource, ['plans', 'features'])) {
            abort_unless(app(SettingsService::class)->get('plans.enabled'), 404);
        }

        return $next($request);
    }
}
