<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Global search for users and posts
     */
    public function globalSearch(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'type' => 'nullable|string|in:all,users,posts',
            'limit' => 'nullable|integer|min:1|max:20'
        ]);

        $query = $request->get('query');
        $type = $request->get('type', 'all');
        $limit = $request->get('limit', 10);
        
        $results = [
            'query' => $query,
            'users' => [],
            'posts' => [],
            'total' => 0
        ];

        // Search Users
        if ($type === 'all' || $type === 'users') {
            $users = User::with(['profile'])
                ->where(function($q) use ($query) {
                    $q->where('name', 'ILIKE', "%{$query}%")
                      ->orWhere('email', 'ILIKE', "%{$query}%")
                      ->orWhereHas('profile', function($profileQuery) use ($query) {
                          $profileQuery->where('bio', 'ILIKE', "%{$query}%")
                                      ->orWhere('location', 'ILIKE', "%{$query}%");
                      });
                })
                ->where('id', '!=', auth()->id()) // Exclude current user
                ->limit($limit)
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->profile?->avatar ? url('storage/' . $user->profile->avatar) : null,
                        'bio' => $user->profile?->bio,
                        'location' => $user->profile?->location,
                        'type' => 'user'
                    ];
                });

            $results['users'] = $users;
        }

        // Search Posts
        if ($type === 'all' || $type === 'posts') {
            $posts = Post::with(['user.profile', 'likes', 'comments'])
                ->where('content', 'ILIKE', "%{$query}%")
                ->where('privacy', 'public') // Only search public posts
                ->where('is_shared', false) // Exclude shared posts for cleaner results
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($post) {
                    return [
                        'id' => $post->id,
                        'content' => strlen($post->content) > 100 ? 
                                   substr($post->content, 0, 100) . '...' : 
                                   $post->content,
                        'full_content' => $post->content,
                        'type' => $post->type,
                        'media' => $post->media ? array_map(function($media) {
                            return str_starts_with($media, 'http') ? $media : url('storage/' . $media);
                        }, $post->media) : [],
                        'user' => [
                            'id' => $post->user->id,
                            'name' => $post->user->name,
                            'avatar' => $post->user->profile?->avatar ? url('storage/' . $post->user->profile->avatar) : null,
                        ],
                        'created_at' => $post->created_at->diffForHumans(),
                        'likes_count' => $post->likes->count(),
                        'comments_count' => $post->comments->count(),
                        'post_type' => 'post'
                    ];
                });

            $results['posts'] = $posts;
        }

        $results['total'] = count($results['users']) + count($results['posts']);

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Search users only
     */
    public function searchUsers(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $query = $request->get('query');
        $limit = $request->get('limit', 20);

        $users = User::with(['profile'])
            ->where(function($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('email', 'ILIKE', "%{$query}%");
            })
            ->where('id', '!=', auth()->id())
            ->limit($limit)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->profile?->avatar ? url('storage/' . $user->profile->avatar) : null,
                    'bio' => $user->profile?->bio,
                    'location' => $user->profile?->location,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users,
            'total' => count($users)
        ]);
    }

    /**
     * Search posts only
     */
    public function searchPosts(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $query = $request->get('query');
        $limit = $request->get('limit', 20);

        $posts = Post::with(['user.profile', 'likes', 'comments'])
            ->where('content', 'ILIKE', "%{$query}%")
            ->where('privacy', 'public')
            ->where('is_shared', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($post) {
                return [
                    'id' => $post->id,
                    'content' => strlen($post->content) > 150 ? 
                               substr($post->content, 0, 150) . '...' : 
                               $post->content,
                    'full_content' => $post->content,
                    'type' => $post->type,
                    'media' => $post->media ? array_map(function($media) {
                        return str_starts_with($media, 'http') ? $media : url('storage/' . $media);
                    }, $post->media) : [],
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'avatar' => $post->user->profile?->avatar ? url('storage/' . $post->user->profile->avatar) : null,
                    ],
                    'created_at' => $post->created_at->diffForHumans(),
                    'likes_count' => $post->likes->count(),
                    'comments_count' => $post->comments->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $posts,
            'total' => count($posts)
        ]);
    }

    /**
     * Get search suggestions (for autocomplete)
     */
    public function suggestions(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:50'
        ]);

        $query = $request->get('query');

        // Get recent searches for this user (you can implement this later)
        $suggestions = [];

        // Get popular users
        $popularUsers = User::with('profile')
            ->where('name', 'ILIKE', "%{$query}%")
            ->where('id', '!=', auth()->id())
            ->limit(5)
            ->get()
            ->map(function($user) {
                return [
                    'text' => $user->name,
                    'type' => 'user',
                    'id' => $user->id
                ];
            });

        $suggestions = $popularUsers->toArray();

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }
}
