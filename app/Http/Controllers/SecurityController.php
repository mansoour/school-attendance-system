<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Services\Security\TwoFactorService;
use App\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SecurityController extends Controller
{
    public function edit(Request $request, TwoFactorService $mfa)
    {
        return view('security', ['svg' => $request->session()->has('mfa.setup') ? $mfa->qrCodeSvg($request->user(), $request->session()->get('mfa.setup.secret')) : null]);
    }

    public function setup(Request $request, TwoFactorService $mfa)
    {
        abort_unless(app(SettingsService::class)->get('security.member_two_factor_allowed') || $request->user()->can('admin.access'), 403);
        abort_if($request->user()->hasTwoFactorEnabled(), 409);
        $request->session()->put('mfa.setup', ['secret' => $mfa->newSecret(), 'at' => time()]);

        return back();
    }

    public function confirm(Request $request, TwoFactorService $mfa)
    {
        abort_unless(app(SettingsService::class)->get('security.member_two_factor_allowed') || $request->user()->can('admin.access'), 403);
        $data = $request->validate(['code' => 'required|string|max:20']);
        $pending = $request->session()->get('mfa.setup');
        abort_unless($pending && time() - $pending['at'] < 600 && ! $request->user()->hasTwoFactorEnabled(), 403);
        $codes = $mfa->confirm($request->user(), $pending['secret'], $data['code']);
        if ($codes === null) {
            throw ValidationException::withMessages(['code' => __('Invalid authenticator code.')]);
        }
        $request->session()->forget('mfa.setup');
        $request->session()->put(EnsureAdminTwoFactor::SESSION_KEY, $request->user()->id);

        return back()->with('recovery_codes', $codes);
    }

    public function challenge(Request $request, TwoFactorService $mfa)
    {
        $request->validate(['code' => 'required|string|max:20']);
        if (! $mfa->verify($request->user(), $request->string('code')->value())) {
            throw ValidationException::withMessages(['code' => __('Invalid authenticator code.')]);
        }
        $request->session()->put(EnsureAdminTwoFactor::SESSION_KEY, $request->user()->id);

        return redirect()->route('admin.index');
    }

    public function destroy(Request $request, TwoFactorService $mfa)
    {
        $mfa->reset($request->user(), 'account');
        $request->session()->forget(EnsureAdminTwoFactor::SESSION_KEY);

        return back()->with('status', __('Two-factor authentication disabled.'));
    }

    public function recovery(Request $request, TwoFactorService $mfa)
    {
        abort_unless($mfa->recoveryCodesEnabled(), 403);
        abort_unless($request->user()->hasTwoFactorEnabled(), 409);

        return back()->with('recovery_codes', $mfa->regenerateRecoveryCodes($request->user()));
    }
}
