<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Http\Requests\Admin\TwoFactorCodeRequest;
use App\Models\User;
use App\Services\Security\TwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * The second sign-in step for accounts with two-factor sign-in on. The password step
 * leaves only the account id in the session; nobody is signed in until the code passes.
 */
class TwoFactorLoginController extends Controller
{
    private const PENDING = 'login.two_factor';

    /** The code step must follow the password step within this many seconds. */
    private const EXPIRES_AFTER = 600;

    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public static function begin(Request $request, User $user): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->put(self::PENDING, ['id' => $user->getKey(), 'remember' => $request->boolean('remember'), 'at' => time()]);

        return redirect()->route('two-factor.login');
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', ['recoveryAllowed' => $this->twoFactor->recoveryCodesEnabled()]);
    }

    public function store(TwoFactorCodeRequest $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $this->twoFactor->verify($user, $request->validated('code'))) {
            throw ValidationException::withMessages(['code' => __('admin.mfa.invalid_code')]);
        }

        $remember = (bool) $request->session()->pull(self::PENDING)['remember'];
        Auth::login($user, $remember);
        $request->session()->regenerate();
        // The code was checked at sign-in, so administration does not ask again.
        $request->session()->put(EnsureAdminTwoFactor::SESSION_KEY, $user->getKey());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::PENDING);

        if (! is_array($pending) || time() - (int) $pending['at'] > self::EXPIRES_AFTER) {
            $request->session()->forget(self::PENDING);

            return null;
        }

        $user = User::query()->find($pending['id']);

        return $user?->hasTwoFactorEnabled() ? $user : null;
    }
}
