<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->notification->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Load relationships if not loaded
        if (!$this->notification->relationLoaded('sender')) {
            $this->notification->load(['sender.profile']);
        }

        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'message' => $this->notification->formatted_message,
            'icon' => $this->notification->icon,
            'color' => $this->notification->color,
            'read' => $this->notification->read,
            'created_at' => $this->notification->created_at,
            'sender' => $this->notification->sender ? [
                'id' => $this->notification->sender->id,
                'name' => $this->notification->sender->name,
                'avatar_url' => $this->notification->sender->profile->avatar_url ?? null,
            ] : null,
            'data' => $this->notification->data,
            'url' => $this->getNotificationUrl(),
        ];
    }

    /**
     * Get notification URL for navigation
     */
    private function getNotificationUrl(): ?string
    {
        return match($this->notification->type) {
            'friend_request' => '/friends/requests',
            'friend_accepted' => "/profile/{$this->notification->sender_id}",
            'post_like', 'post_comment', 'post_share' => "/posts/{$this->notification->notifiable_id}",
            'comment_like' => "/posts/" . ($this->notification->data['post_id'] ?? null),
            'mention', 'tag' => "/posts/" . ($this->notification->data['post_id'] ?? null),
            default => null,
        };
    }
}
