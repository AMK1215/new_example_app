<?php

namespace App\Events;

use App\Models\Friendship;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $friendship;

    /**
     * Create a new event instance.
     */
    public function __construct(Friendship $friendship)
    {
        $this->friendship = $friendship;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->friendship->friend_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'friendship' => [
                'id' => $this->friendship->id,
                'user' => [
                    'id' => $this->friendship->user->id,
                    'name' => $this->friendship->user->name,
                    'avatar' => $this->friendship->user->profile?->avatar_url,
                ],
                'status' => $this->friendship->status,
                'created_at' => $this->friendship->created_at->toISOString(),
                'type' => 'friend_request_received'
            ]
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'friendship.request_received';
    }
}
