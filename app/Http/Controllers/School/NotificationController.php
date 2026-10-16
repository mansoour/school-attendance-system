<?php

namespace App\Http\Controllers\School;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('school.notifications', ['notifications' => $request->user()->notifications()->latest()->paginate(25)]);
    }

    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;

        // Only follow links that point back to this application.
        if (is_string($url) && str_starts_with($url, url('/'))) {
            return redirect($url);
        }

        return redirect()->route('school.notifications');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', __('All notifications marked as read.'));
    }
}
