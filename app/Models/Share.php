<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'share_type',
        'content',
        'privacy',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who shared the post.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post that was shared.
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Scope to get shares by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('share_type', $type);
    }

    /**
     * Scope to get public shares.
     */
    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }

    /**
     * Scope to get shares by privacy level.
     */
    public function scopeByPrivacy($query, $privacy)
    {
        return $query->where('privacy', $privacy);
    }

    /**
     * Get share count for a specific post.
     */
    public static function getShareCountForPost($postId)
    {
        return static::where('post_id', $postId)->count();
    }

    /**
     * Check if user has already shared a post.
     */
    public static function hasUserSharedPost($userId, $postId, $shareType = null)
    {
        $query = static::where('user_id', $userId)->where('post_id', $postId);
        
        if ($shareType) {
            $query->where('share_type', $shareType);
        }
        
        return $query->exists();
    }
}
