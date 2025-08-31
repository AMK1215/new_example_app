<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasMediaUrls;

class Post extends Model
{
    use HasFactory, HasMediaUrls;

    protected $fillable = [
        'user_id',
        'content',
        'type',
        'media',
        'metadata',
        'is_public',
    ];

    protected $casts = [
        'media' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    public function allComments()
    {
        return $this->hasMany(Comment::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function getLikeCountAttribute()
    {
        return $this->likes()->count();
    }

    public function getCommentCountAttribute()
    {
        return $this->allComments()->count();
    }

    public function getShareCountAttribute()
    {
        return $this->shares()->count();
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the media URLs with proper domain handling
     */
    public function getMediaUrlsAttribute()
    {
        if (!$this->media || !is_array($this->media)) {
            return [];
        }

        return array_map(function ($mediaPath) {
            return $this->generateStorageUrl($mediaPath);
        }, $this->media);
    }
    
    /**
     * Scope to get posts with media (PostgreSQL safe)
     */
    public function scopeWithMedia($query)
    {
        return $query->whereNotNull('media')
                    ->whereRaw("media::text != '[]'")
                    ->whereRaw("media::text != 'null'");
    }
    
    /**
     * Fix video posts that have external URLs
     */
    public static function fixVideoPosts()
    {
        $videoPosts = self::where('type', 'video')
            ->where('media', 'like', '%sample-videos.com%')
            ->get();
            
        foreach ($videoPosts as $post) {
            // Look for local video files in storage
            $storage = Storage::disk('public');
            $postsDir = 'posts';
            
            // Get all video files in posts directory
            $videoFiles = collect($storage->files($postsDir))
                ->filter(function ($file) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    return in_array(strtolower($ext), ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']);
                })
                ->values();
            
            if ($videoFiles->count() > 0) {
                // Use the first available video file
                $newMedia = [$videoFiles->first()];
                $post->update(['media' => $newMedia]);
                \Log::info("Fixed video post {$post->id}: {$videoFiles->first()}");
            }
        }
        
        return $videoPosts->count();
    }
    
    /**
     * Clean up posts with external URLs by removing them
     */
    public static function cleanExternalUrls()
    {
        $postsWithExternalUrls = self::whereRaw("media::text LIKE '%http%'")
            ->whereRaw("media::text NOT LIKE '%localhost%'")
            ->get();
            
        foreach ($postsWithExternalUrls as $post) {
            // Remove external URLs from media array
            $cleanedMedia = array_filter($post->media, function($mediaPath) {
                return !filter_var($mediaPath, FILTER_VALIDATE_URL) || 
                       strpos($mediaPath, 'localhost') !== false;
            });
            
            if (empty($cleanedMedia)) {
                // If no media left, set type to text
                $post->update(['media' => [], 'type' => 'text']);
                \Log::info("Cleaned post {$post->id}: removed external URLs, set to text type");
            } else {
                $post->update(['media' => array_values($cleanedMedia)]);
                \Log::info("Cleaned post {$post->id}: removed external URLs, kept local media");
            }
        }
        
        return $postsWithExternalUrls->count();
    }
    
    /**
     * Convert existing posts to use full URLs instead of relative paths
     */
    public static function convertToFullUrls()
    {
        $postsWithRelativePaths = self::whereRaw("media::text NOT LIKE '%http%'")
            ->whereRaw("media::text != '[]'")
            ->get();
            
        $convertedCount = 0;
        
        foreach ($postsWithRelativePaths as $post) {
            $updatedMedia = [];
            $hasChanges = false;
            
            foreach ($post->media as $mediaPath) {
                if (!filter_var($mediaPath, FILTER_VALIDATE_URL)) {
                    // Convert relative path to full URL
                    $fullUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($mediaPath);
                    $updatedMedia[] = $fullUrl;
                    $hasChanges = true;
                    \Log::info("Converted post {$post->id}: {$mediaPath} -> {$fullUrl}");
                } else {
                    $updatedMedia[] = $mediaPath;
                }
            }
            
            if ($hasChanges) {
                $post->update(['media' => $updatedMedia]);
                $convertedCount++;
            }
        }
        
        return $convertedCount;
    }
}
