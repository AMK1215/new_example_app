<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Post;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    /**
     * Debug image URLs to check if they're generating correctly
     */
    public function imageUrls(Request $request)
    {
        $debug = [
            'app_url' => config('app.url'),
            'app_env' => config('app.env'),
            'request_host' => $request->getHost(),
            'request_scheme' => $request->getScheme(),
            'profiles' => [],
            'posts' => [],
        ];
        
        // Check profile images
        $profiles = Profile::whereNotNull('avatar')
                          ->orWhereNotNull('cover_photo')
                          ->limit(5)
                          ->get();
        
        foreach ($profiles as $profile) {
            $debug['profiles'][] = [
                'id' => $profile->id,
                'avatar_raw' => $profile->avatar,
                'avatar_url' => $profile->avatar_url,
                'cover_photo_raw' => $profile->cover_photo,
                'cover_photo_url' => $profile->cover_photo_url,
            ];
        }
        
        // Check post media
        $posts = Post::withMedia()
                    ->limit(5)
                    ->get();
        
        foreach ($posts as $post) {
            $debug['posts'][] = [
                'id' => $post->id,
                'media_raw' => $post->media,
                'media_urls' => $post->media_urls,
            ];
        }
        
        return response()->json([
            'success' => true,
            'debug' => $debug
        ]);
    }
}
