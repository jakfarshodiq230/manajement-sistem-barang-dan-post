<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([], 401);
        }

        $userId = $user->id;

        // 1. Ultra-fast metadata check using single indexed aggregate query (< 1ms execution time)
        $meta = DB::table('notifications')
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $userId)
            ->selectRaw('COUNT(*) as total_count, COUNT(CASE WHEN read_at IS NULL THEN 1 END) as unread_count, MAX(created_at) as max_created, MAX(read_at) as max_read')
            ->first();

        $hash = md5(($meta->unread_count ?? 0) . '_' . ($meta->total_count ?? 0) . '_' . ($meta->max_created ?? '') . '_' . ($meta->max_read ?? ''));

        $clientHash = $request->query('hash') ?: $request->header('If-None-Match');
        $clientHash = trim((string) $clientHash, '"');

        // If client already has the latest notification state, return lightweight not_modified immediately
        if ($clientHash && $clientHash === $hash) {
            return response()->json([
                'not_modified' => true,
                'hash' => $hash,
                'unread_count' => (int) ($meta->unread_count ?? 0),
            ], 200)->header('ETag', '"' . $hash . '"');
        }

        // 2. Only fetch, hydrate, and map actual records when there are actual changes
        $query = $request->query('all') ? $user->notifications() : $user->unreadNotifications();
        
        $notifications = $query->take(20)->get()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notifikasi',
                'subtitle' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'time' => $notification->created_at ? $notification->created_at->diffForHumans() : '',
                'isSeen' => $notification->read_at !== null,
                'color' => $notification->data['type'] ?? 'primary',
                'icon' => $notification->data['icon'] ?? 'ri-notification-3-line',
            ];
        });

        if ($request->has('hash')) {
            return response()->json([
                'not_modified' => false,
                'hash' => $hash,
                'unread_count' => (int) ($meta->unread_count ?? 0),
                'data' => $notifications,
            ], 200)->header('ETag', '"' . $hash . '"');
        }

        return response()->json($notifications)->header('ETag', '"' . $hash . '"');
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
