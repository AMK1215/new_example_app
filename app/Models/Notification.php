<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'data',
        'user_id',
        'sender_id',
        'notifiable_type',
        'notifiable_id',
        'read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user who receives the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who triggered the notification
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the related model (post, comment, etc.)
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        if (!$this->read) {
            $this->update([
                'read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(): void
    {
        $this->update([
            'read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('read', true);
    }

    /**
     * Scope for notifications by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get formatted notification message
     */
    public function getFormattedMessageAttribute(): string
    {
        $senderName = $this->sender->name ?? 'Someone';
        
        return match($this->type) {
            'friend_request' => "{$senderName} sent you a friend request",
            'friend_accepted' => "{$senderName} accepted your friend request",
            'post_like' => "{$senderName} liked your post",
            'post_comment' => "{$senderName} commented on your post",
            'post_share' => "{$senderName} shared your post",
            'comment_like' => "{$senderName} liked your comment",
            'mention' => "{$senderName} mentioned you in a post",
            'tag' => "{$senderName} tagged you in a post",
            default => "{$senderName} interacted with your content",
        };
    }

    /**
     * Get notification icon based on type
     */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'friend_request', 'friend_accepted' => 'user-plus',
            'post_like', 'comment_like' => 'heart',
            'post_comment' => 'message-circle',
            'post_share' => 'share-2',
            'mention', 'tag' => 'at-sign',
            default => 'bell',
        };
    }

    /**
     * Get notification color based on type
     */
    public function getColorAttribute(): string
    {
        return match($this->type) {
            'friend_request', 'friend_accepted' => 'blue',
            'post_like', 'comment_like' => 'red',
            'post_comment' => 'green',
            'post_share' => 'purple',
            'mention', 'tag' => 'yellow',
            default => 'gray',
        };
    }
}
