<?php

namespace App\Http\Middleware;

use App\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;

class SiteMaintenance
{
    public function handle(Request $request, Closure $next)
    {
        $settings = app(SettingsService::class);
        if ($settings->get('maintenance.enabled') && ! $request->user()?->can('admin.access') && ! $request->routeIs('login', 'logout', 'two-factor.login', 'locale', 'password.*', 'verification.*') && ! $request->is('login', 'confirm-password', 'two-factor-challenge', 'webhooks/*', 'branding-assets/*', 'up')) {
            return response()->view('maintenance', ['message' => $settings->get('maintenance.message.'.app()->getLocale()) ?: __('We are making a few improvements. Please come back soon.')], 503)->header('Retry-After', '300');
        }

        return $next($request);
    }
}
