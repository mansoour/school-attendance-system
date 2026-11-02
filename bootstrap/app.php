<?php

use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SiteMaintenance;
use App\Support\ErrorReference;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        // Every logged error carries a short reference that the error page shows, so a
        // visitor can report it and the exact entry can be found in storage/logs.
        $exceptions->context(fn () => ['error_reference' => ErrorReference::current()]);

        // A refused action (e.g. deleting a grade that still has students) returns to the
        // previous page with its reason shown, instead of a blank error page.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $status = $e->getStatusCode();
            if ($request->expectsJson() || ! in_array($status, [400, 409, 422], true) || $request->isMethod('GET')) {
                return null;
            }
            $previous = url()->previous();
            if (! $previous || $previous === $request->fullUrl()) {
                return null;
            }
            $message = $e->getMessage() ?: __('This action could not be completed.');

            return redirect()->to($previous)->withInput($request->except(['password', 'password_confirmation', 'current_password']))->withErrors(['error' => $message]);
        });
    })->create();
