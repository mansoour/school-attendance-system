<?php

namespace App\Http\Controllers;

use App\Integrations\Recaptcha;
use App\Localization\LocaleRegistry;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\TranslationRevision;
use App\Models\User;
use App\Notifications\MemberNotification;
use App\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicController extends Controller
{
    public function home()
    {
        return view('public.home');
    }

    public function page(string $slug)
    {
        return view('public.page', ['page' => Page::where('slug', $slug)->where('is_published', true)->firstOrFail()]);
    }

    public function contact(Request $request)
    {
        app(Recaptcha::class)->validate($request, 'contact');
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:255', 'topic' => 'required|in:general,account,privacy,other', 'message' => 'required|string|max:10000']);
        ContactMessage::create($data + ['user_id' => $request->user()?->id]);
        $roles = app(SettingsService::class)->get('staff_alerts.support_roles');
        if ($roles) {
            User::role($roles)->permission('support.access')->each(fn ($user) => $user->notify(new MemberNotification('support_received')));
        }

        return back()->with('status', __('Your message has been received.'));
    }

    public function locale(Request $request)
    {
        $data = $request->validate(['locale' => ['required', Rule::in(app(LocaleRegistry::class)->codes())]]);
        $request->session()->put('locale', $data['locale']);
        $request->user()?->update($data);

        return $request->expectsJson() ? response()->noContent() : back();
    }

    public function catalog(string $locale)
    {
        abort_unless(in_array($locale, app(LocaleRegistry::class)->codes()), 404);
        $revision = TranslationRevision::where('locale', $locale)->whereNotNull('published_at')->latest('published_at')->latest('id')->first();
        $messages = is_file(lang_path($locale.'.json')) ? json_decode(file_get_contents(lang_path($locale.'.json')), true) : [];

        return response()->json(['locale' => $locale, 'version' => $revision?->id ?? 0, 'messages' => array_replace($messages, $revision?->messages ?? [])]);
    }
}
