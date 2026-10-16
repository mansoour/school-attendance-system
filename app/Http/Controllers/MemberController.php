<?php

namespace App\Http\Controllers;

use App\Billing\Entitlements;
use App\Models\ContactMessage;
use App\Notifications\MemberNotification;
use App\Settings\SettingsService;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function me(Request $request, Entitlements $entitlements)
    {
        return response()->json(['plans_enabled' => (bool) app(SettingsService::class)->get('plans.enabled'), 'user' => $request->user()->only('id', 'name', 'email', 'locale'), 'plan' => $entitlements->planFor($request->user())?->localized('name'), 'preferences' => $request->user()->notification_preferences ?? []]);
    }

    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20)->through(fn ($n) => ['id' => $n->id, 'message' => __('notifications.'.$n->data['event']), 'read_at' => $n->read_at, 'created_at' => $n->created_at]);

        return response()->json($notifications->toArray() + ['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->noContent();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }

    public function preferences(Request $request)
    {
        $rules = ['preferences' => 'required|array:welcome,plan_changed,support_received'];
        foreach (array_diff(MemberNotification::EVENTS, ['security']) as $event) {
            $rules['preferences.'.$event] = 'sometimes|array:database,mail';
            $rules['preferences.'.$event.'.database'] = 'required_with:preferences.'.$event.'|boolean';
            $rules['preferences.'.$event.'.mail'] = 'required_with:preferences.'.$event.'|boolean';
        }
        $data = $request->validate($rules);
        $request->user()->forceFill(['notification_preferences' => $data['preferences']])->save();

        return response()->noContent();
    }

    public function export(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            echo json_encode(['account' => $request->user()->only('name', 'email', 'locale', 'created_at'), 'subscriptions' => $request->user()->subscriptions()->get(['plan_id', 'status', 'starts_at', 'ends_at']), 'support_messages' => ContactMessage::where('user_id', $request->user()->id)->get(['topic', 'message', 'created_at'])], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'account.json', ['Content-Type' => 'application/json']);
    }
}
