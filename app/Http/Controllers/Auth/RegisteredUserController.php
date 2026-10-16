<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Integrations\Recaptcha;
use App\Models\Student;
use App\Models\User;
use App\Notifications\MemberNotification;
use App\School\GuardianLinker;
use App\Settings\SettingsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
            'phone' => ['required', 'string', 'max:40'],
            'relation' => ['required', Rule::in(Student::RELATIONS)],
            'student_national_id' => ['required', 'string', 'max:20'],
            'student_name' => [str_contains($mode = app(SettingsService::class)->get('guardians.match_mode'), 'name') ? 'required' : 'nullable', 'string', 'max:200'],
            'student_phone' => [str_contains($mode, 'phone') ? 'required' : 'nullable', 'string', 'max:40'],
        ]);
        $linker = app(GuardianLinker::class);
        $student = $linker->match($request->student_national_id, $request->student_name, $request->student_phone);
        if (! $student) {
            throw ValidationException::withMessages(['student_national_id' => __('No student matches these details. Check them or contact the school.')]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);
        $user->assignRole('guardian');
        $linker->request($user, $student, $request->relation);

        $user->forceFill(['locale' => app()->getLocale(), 'terms_accepted_at' => now(), 'marketing_opt_in' => $request->boolean('marketing'), 'marketing_opt_in_at' => $request->boolean('marketing') ? now() : null])->save();
        if (! app(SettingsService::class)->get('security.email_verification')) {
            $user->markEmailAsVerified();
            $linker->activate($user);
        } else {
            event(new Registered($user));
        }
        $user->notify(new MemberNotification('welcome'));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(route('dashboard', absolute: false));
    }
}
