<?php

namespace App\Events;

use App\Models\Like;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $like;

    /**
     * Create a new event instance.
     */
    public function __construct(Like $like)
    {
        $this->like = $like;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('post.' . $this->like->post_id),
            new PrivateChannel('user.' . $this->like->post->user_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'like' => [
                'id' => $this->like->id,
                'post_id' => $this->like->post_id,
                'user' => [
                    'id' => $this->like->user->id,
                    'name' => $this->like->user->name,
                    'avatar' => $this->like->user->profile?->avatar_url,
                ],
                'created_at' => $this->like->created_at->toISOString(),
                'type' => 'post_liked'
            ]
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'post.liked';
    }
}
