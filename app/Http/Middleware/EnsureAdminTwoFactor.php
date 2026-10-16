<?php

namespace App\Http\Middleware;

use App\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactor
{
    public const SESSION_KEY = 'admin_mfa.passed_for';

    public function handle(Request $request, Closure $next): Response
    {
        if (app(SettingsService::class)->get('security.admin_two_factor_required') &&
            (! $request->user()->hasTwoFactorEnabled() || $request->session()->get(self::SESSION_KEY) !== $request->user()->id)) {
            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
