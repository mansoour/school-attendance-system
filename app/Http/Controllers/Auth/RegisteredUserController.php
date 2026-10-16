<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Integrations\Recaptcha;
use App\Models\User;
use App\Notifications\MemberNotification;
use App\Settings\SettingsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        abort_unless(app(SettingsService::class)->get('security.registration_open'), 403);

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        app(Recaptcha::class)->validate($request, 'register');
        abort_unless(app(SettingsService::class)->get('security.registration_open'), 403);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
            'marketing' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->forceFill(['locale' => app()->getLocale(), 'terms_accepted_at' => now(), 'marketing_opt_in' => $request->boolean('marketing'), 'marketing_opt_in_at' => $request->boolean('marketing') ? now() : null])->save();
        if (! app(SettingsService::class)->get('security.email_verification')) {
            $user->markEmailAsVerified();
        } else {
            event(new Registered($user));
        }
        $user->notify(new MemberNotification('welcome'));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(route('dashboard', absolute: false));
    }
}
