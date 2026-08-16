<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = $request->query('all') ? $user->notifications() : $user->unreadNotifications();
        
        $notifications = $query->take(50)->get()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notifikasi',
                'subtitle' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'time' => $notification->created_at->diffForHumans(),
                'isSeen' => $notification->read_at !== null,
                'color' => $notification->data['type'] ?? 'primary',
                'icon' => $notification->data['icon'] ?? 'ri-notification-3-line',
            ];
        });

        return response()->json($notifications);
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ]);

        $request->user()->unreadNotifications->whereIn('id', $request->ids)->markAsRead();

        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca']);
    }

    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
        }

        return response()->json(['message' => 'Notifikasi dihapus']);
    }
}
