<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Get user's notifications
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::with(['sender.profile', 'notifiable'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Transform notifications for frontend
        $notifications->getCollection()->transform(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'message' => $notification->formatted_message,
                'icon' => $notification->icon,
                'color' => $notification->color,
                'read' => $notification->read,
                'created_at' => $notification->created_at,
                'read_at' => $notification->read_at,
                'sender' => $notification->sender ? [
                    'id' => $notification->sender->id,
                    'name' => $notification->sender->name,
                    'avatar_url' => $notification->sender->profile->avatar_url ?? null,
                ] : null,
                'data' => $notification->data,
                'url' => $this->getNotificationUrl($notification),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get unread notification count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        // Ensure user owns the notification
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(Request $request, Notification $notification): JsonResponse
    {
        // Ensure user owns the notification
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as unread',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->unread()
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        // Ensure user owns the notification
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'All read notifications deleted',
        ]);
    }

    /**
     * Get notification URL for navigation
     */
    private function getNotificationUrl(Notification $notification): ?string
    {
        return match($notification->type) {
            'friend_request' => '/friends/requests',
            'friend_accepted' => "/profile/{$notification->sender_id}",
            'post_like', 'post_comment', 'post_share' => "/posts/{$notification->notifiable_id}",
            'comment_like' => "/posts/" . ($notification->data['post_id'] ?? null),
            'mention', 'tag' => "/posts/" . ($notification->data['post_id'] ?? null),
            default => null,
        };
    }
}
