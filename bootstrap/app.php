<?php

use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SiteMaintenance;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SetLocale::class, SiteMaintenance::class]);
        $middleware->alias(['admin.mfa' => EnsureAdminTwoFactor::class]);
        $middleware->validateCsrfTokens(except: ['webhooks/resend']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
