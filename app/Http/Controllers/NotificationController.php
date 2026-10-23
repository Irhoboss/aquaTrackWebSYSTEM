<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function summary(Request $request)
    {
        return response()->json([
            'unread_count' => $request->user()->inbox()->whereNull('read_at')->count(),
        ]);
    }

    public function index(Request $request)
    {
        return view('alerts.notifications', ['notifications' => $request->user()->inbox()->latest()->paginate(20)]);
    }

    public function read(Request $request, UserNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => $notification->read_at ?? now()]);
        return $notification->url && $request->boolean('go') ? redirect($notification->url) : back();
    }

    public function readAll(Request $request)
    {
        $request->user()->inbox()->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'All notifications marked as read.');
    }
}
