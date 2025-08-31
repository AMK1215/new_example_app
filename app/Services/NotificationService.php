<?php

namespace App\Services;

use App\Models\Notification;
use App\Events\NotificationSent;
use Illuminate\Database\Eloquent\Model;

class NotificationService
{
    /**
     * Create a notification
     */
    public function create(
        string $type,
        int $userId,
        ?int $senderId = null,
        ?Model $notifiable = null,
        array $data = []
    ): Notification {
        // Don't create notification if sender is the same as receiver
        if ($senderId && $senderId === $userId) {
            return new Notification(); // Return empty notification
        }

        // Check if similar notification already exists (to avoid spam)
        if ($this->shouldSkipNotification($type, $userId, $senderId, $notifiable)) {
            return new Notification(); // Return empty notification
        }

        $notification = Notification::create([
            'type' => $type,
            'user_id' => $userId,
            'sender_id' => $senderId,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notifiable_id' => $notifiable?->id,
            'data' => $data,
        ]);

        // Broadcast real-time notification
        broadcast(new NotificationSent($notification))->toOthers();

        return $notification;
    }

    /**
     * Create friend request notification
     */
    public function friendRequest(int $userId, int $senderId): Notification
    {
        return $this->create('friend_request', $userId, $senderId);
    }

    /**
     * Create friend accepted notification
     */
    public function friendAccepted(int $userId, int $senderId): Notification
    {
        return $this->create('friend_accepted', $userId, $senderId);
    }

    /**
     * Create post like notification
     */
    public function postLike(Model $post, int $senderId): Notification
    {
        return $this->create('post_like', $post->user_id, $senderId, $post);
    }

    /**
     * Create post comment notification
     */
    public function postComment(Model $post, Model $comment, int $senderId): Notification
    {
        return $this->create('post_comment', $post->user_id, $senderId, $post, [
            'comment_id' => $comment->id,
            'comment_content' => substr($comment->content, 0, 100),
        ]);
    }

    /**
     * Create post share notification
     */
    public function postShare(Model $post, int $senderId): Notification
    {
        return $this->create('post_share', $post->user_id, $senderId, $post);
    }

    /**
     * Create comment like notification
     */
    public function commentLike(Model $comment, int $senderId): Notification
    {
        return $this->create('comment_like', $comment->user_id, $senderId, $comment, [
            'post_id' => $comment->post_id,
        ]);
    }

    /**
     * Create mention notification
     */
    public function mention(Model $post, int $userId, int $senderId): Notification
    {
        return $this->create('mention', $userId, $senderId, $post);
    }

    /**
     * Create tag notification
     */
    public function tag(Model $post, int $userId, int $senderId): Notification
    {
        return $this->create('tag', $userId, $senderId, $post);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->count();
    }

    /**
     * Delete old read notifications (cleanup)
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        return Notification::where('read', true)
            ->where('read_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Check if we should skip creating this notification to avoid spam
     */
    private function shouldSkipNotification(
        string $type,
        int $userId,
        ?int $senderId,
        ?Model $notifiable
    ): bool {
        // For likes, only keep the most recent one per post/comment
        if (in_array($type, ['post_like', 'comment_like']) && $notifiable) {
            $existing = Notification::where('type', $type)
                ->where('user_id', $userId)
                ->where('sender_id', $senderId)
                ->where('notifiable_type', get_class($notifiable))
                ->where('notifiable_id', $notifiable->id)
                ->where('created_at', '>', now()->subHours(24)) // Only check last 24 hours
                ->exists();

            return $existing;
        }

        // For friend requests, don't create duplicate
        if ($type === 'friend_request') {
            $existing = Notification::where('type', 'friend_request')
                ->where('user_id', $userId)
                ->where('sender_id', $senderId)
                ->where('read', false) // Only unread ones
                ->exists();

            return $existing;
        }

        return false;
    }
}
