<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

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

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the media URLs - now returns the stored URLs directly
     */
    public function getMediaUrlsAttribute()
    {
        if (!$this->media) {
            return [];
        }

        return array_map(function ($mediaPath) {
            // If it's already a full URL, return it directly
            if (filter_var($mediaPath, FILTER_VALIDATE_URL)) {
                // Check if it's a local storage URL (localhost or your domain)
                if (strpos($mediaPath, 'localhost') !== false || 
                    strpos($mediaPath, config('app.url')) !== false) {
                    return $mediaPath; // Return local storage URLs
                } else {
                    // External URL - log warning and return as is
                    \Log::warning("External URL detected: {$mediaPath}");
                    return $mediaPath;
                }
            }

            // If it's a relative path, convert to full URL
            if (pathinfo($mediaPath, PATHINFO_EXTENSION)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($mediaPath);
            }

            // If no extension, try to find the actual file with extensions
            $storage = \Illuminate\Support\Facades\Storage::disk('public');
            $directory = dirname($mediaPath);
            $filename = basename($mediaPath);
            
            // Common media extensions to try (images + videos)
            $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
            
            // For video posts, prioritize video extensions
            if ($this->type === 'video') {
                $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
                $extensions = array_merge($videoExtensions, array_diff($extensions, $videoExtensions));
            }
            
            foreach ($extensions as $ext) {
                $fullPath = $directory . '/' . $filename . '.' . $ext;
                if ($storage->exists($fullPath)) {
                    \Log::info("Found local file: {$fullPath} for media path: {$mediaPath}");
                    return $storage->url($fullPath);
                }
            }
            
            // If no file found, return the original path (this will help with debugging)
            \Log::warning("Could not find media file for path: {$mediaPath}");
            
            // Try to construct a URL anyway for debugging
            $constructedUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($mediaPath);
            \Log::warning("Constructed URL: {$constructedUrl}");
            
            return $constructedUrl;
        }, $this->media);
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
