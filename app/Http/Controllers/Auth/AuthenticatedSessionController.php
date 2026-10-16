<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        // "Sign in" from inside the app returns there afterwards (only app addresses).
        $return = (string) $request->query('return', '');
        if (preg_match('#^/app(/[A-Za-z0-9/_\-]*)?$#', $return)) {
            $request->session()->put('url.intended', url($return));
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request. Accounts with two-factor sign-in turned
     * on are signed in only after the code step.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        if ($user->hasTwoFactorEnabled()) {
            return TwoFactorLoginController::begin($request, $user);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
