<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;
use App\Events\CommentCreated;
use App\Events\CommentDeleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with([
                        'user.profile', 
                        'likes', 
                        'comments.user.profile', 
                        'shares.user.profile',
                        'sharedPost' => function($query) {
                            $query->with(['user.profile', 'likes', 'comments', 'shares']);
                        }
                    ])
                    ->public()
                    ->latest()
                    ->paginate(10);

        // Ensure all posts have proper media URLs
        $posts->getCollection()->transform(function ($post) {
            if ($post->media) {
                $post->media = $post->media_urls;
            }
            
            // Force load sharedPost relationship if missing
            if ($post->is_shared && !$post->relationLoaded('sharedPost')) {
                $post->load(['sharedPost.user.profile', 'sharedPost.likes', 'sharedPost.comments']);
            }
            
            // Handle shared post media
            if ($post->is_shared && $post->sharedPost && $post->sharedPost->media) {
                $post->sharedPost->media = $post->sharedPost->media_urls;
            }
            
            // Debug logging for shared posts in index and ensure proper serialization
            if ($post->is_shared) {
                \Log::info('Shared post debug in index', [
                    'post_id' => $post->id,
                    'shared_post_id' => $post->shared_post_id,
                    'has_shared_post_relation' => $post->relationLoaded('sharedPost'),
                    'shared_post_exists' => $post->sharedPost ? 'yes' : 'no'
                ]);
                
                // Ensure the sharedPost relationship is properly accessible for JSON serialization
                if ($post->sharedPost) {
                    $post->setRelation('sharedPost', $post->sharedPost);
                }
            }
            
            return $post;
        });

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:5000',
            'type' => 'in:text,image,video,link',
            'media.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:10240',
            'is_public' => 'nullable|in:0,1,true,false',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $media = [];
        $detectedType = 'text'; // Default type
        
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                // Store with original file extension
                $extension = $file->getClientOriginalExtension();
                $filename = uniqid() . '.' . $extension;
                $path = $file->storeAs('posts', $filename, 'public');
                
                // Store the full URL instead of just the path
                $fullUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                $media[] = $fullUrl;
                
                // Auto-detect media type based on file extension
                if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $detectedType = 'image';
                } elseif (in_array(strtolower($extension), ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'])) {
                    $detectedType = 'video';
                }
                
                // Debug logging
                \Log::info("File uploaded: {$file->getClientOriginalName()} -> {$path} -> {$fullUrl} (Type: {$detectedType})");
            }
        }

        // Use detected type if not explicitly provided, or if it's more specific
        $finalType = $request->type ?? $detectedType;
        if ($detectedType !== 'text' && $request->type === 'text') {
            $finalType = $detectedType; // Override text with detected media type
        }

        $post = Post::create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'type' => $finalType,
            'media' => $media,
            'is_public' => filter_var($request->is_public, FILTER_VALIDATE_BOOLEAN),
        ]);

        // Load the post with relationships and ensure proper media URLs
        $postData = $post->load(['user.profile', 'likes', 'comments.user.profile']);
        if ($postData->media) {
            $postData->media = $postData->media_urls;
        }

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $postData
        ], 201);
    }

    public function show(Post $post)
    {
        if (!$post->is_public && $post->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Ensure post has proper media URLs
        if ($post->media) {
            $post->media = $post->media_urls;
        }

        return response()->json([
            'success' => true,
            'data' => $post->load(['user.profile', 'likes', 'comments.user.profile'])
        ]);
    }

    public function update(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $post->update($request->only(['content', 'is_public']));

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post->load(['user.profile', 'likes', 'comments.user.profile'])
        ]);
    }

    public function destroy(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Delete media files
        if ($post->media) {
            foreach ($post->media as $mediaPath) {
                Storage::disk('public')->delete($mediaPath);
            }
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }

    public function like(Request $request, Post $post)
    {
        $existingLike = Like::where('user_id', $request->user()->id)
                            ->where('post_id', $post->id)
                            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $message = 'Post unliked';
        } else {
            Like::create([
                'user_id' => $request->user()->id,
                'post_id' => $post->id,
                'type' => $request->type ?? 'like',
            ]);
            $message = 'Post liked';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'liked' => !$existingLike,
                'like_count' => $post->fresh()->like_count
            ]
        ]);
    }

    /**
     * Get posts for a specific user
     */
    public function userPosts(Request $request, $userId)
    {
        $posts = Post::with([
                        'user.profile', 
                        'likes', 
                        'comments.user.profile',
                        'shares.user.profile',
                        'sharedPost' => function($query) {
                            $query->with(['user.profile', 'likes', 'comments', 'shares']);
                        }
                    ])
                    ->where('user_id', $userId)
                    ->latest()
                    ->paginate(10);

        // Ensure all posts have proper media URLs
        $posts->getCollection()->transform(function ($post) {
            if ($post->media) {
                $post->media = $post->media_urls;
            }
            
            // Force load sharedPost relationship if missing
            if ($post->is_shared && !$post->relationLoaded('sharedPost')) {
                $post->load(['sharedPost.user.profile', 'sharedPost.likes', 'sharedPost.comments']);
            }
            
            // Handle shared post media
            if ($post->is_shared && $post->sharedPost && $post->sharedPost->media) {
                $post->sharedPost->media = $post->sharedPost->media_urls;
            }
            
            // Debug logging for shared posts and ensure proper serialization
            if ($post->is_shared) {
                \Log::info('Shared post debug in userPosts', [
                    'post_id' => $post->id,
                    'shared_post_id' => $post->shared_post_id,
                    'has_shared_post_relation' => $post->relationLoaded('sharedPost'),
                    'shared_post_exists' => $post->sharedPost ? 'yes' : 'no',
                    'shared_post_data' => $post->sharedPost ? [
                        'id' => $post->sharedPost->id,
                        'content' => $post->sharedPost->content,
                        'media_count' => $post->sharedPost->media ? count($post->sharedPost->media) : 0
                    ] : null
                ]);
                
                // Ensure the sharedPost relationship is properly accessible for JSON serialization
                if ($post->sharedPost) {
                    $post->setRelation('sharedPost', $post->sharedPost);
                }
            }
            
            return $post;
        });

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }
    
    /**
     * Fix existing video posts with external URLs
     */
    public function fixVideoPosts()
    {
        $fixedCount = Post::fixVideoPosts();
        
        return response()->json([
            'success' => true,
            'message' => "Fixed {$fixedCount} video post(s)",
            'data' => ['fixed_count' => $fixedCount]
        ]);
    }
    
    /**
     * Clean up posts with external URLs
     */
    public function cleanExternalUrls()
    {
        $cleanedCount = Post::cleanExternalUrls();
        
        return response()->json([
            'success' => true,
            'message' => "Cleaned {$cleanedCount} post(s) with external URLs",
            'data' => ['cleaned_count' => $cleanedCount]
        ]);
    }
    
    /**
     * Convert existing posts to use full URLs
     */
    public function convertToFullUrls()
    {
        $convertedCount = Post::convertToFullUrls();
        
        return response()->json([
            'success' => true,
            'message' => "Converted {$convertedCount} post(s) to use full URLs",
            'data' => ['converted_count' => $convertedCount]
        ]);
    }

    /**
     * Debug shared posts
     */
    public function debugSharedPosts(Request $request)
    {
        $sharedPosts = Post::where('is_shared', true)
                          ->with(['sharedPost', 'user.profile'])
                          ->get();
        
        $debug = [];
        foreach ($sharedPosts as $post) {
            $debug[] = [
                'id' => $post->id,
                'shared_post_id' => $post->shared_post_id,
                'has_shared_post' => $post->sharedPost ? true : false,
                'shared_post_content' => $post->sharedPost ? $post->sharedPost->content : null,
                'shared_by' => $post->user->name ?? 'Unknown'
            ];
        }
        
        return response()->json([
            'success' => true,
            'shared_posts_count' => count($sharedPosts),
            'debug_data' => $debug
        ]);
    }

    /**
     * Get comments for a post
     */
    public function comments(Request $request, Post $post)
    {
        $comments = $post->comments()
            ->with(['user.profile', 'likes', 'replies.user.profile', 'replies.likes'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    /**
     * Store a new comment
     */
    public function storeComment(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        $comment->load(['user.profile', 'likes']);

        // Broadcast the comment creation event
        event(new CommentCreated($comment));

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $comment
        ]);
    }

    /**
     * Update a comment
     */
    public function updateComment(Request $request, $commentId)
    {
        $comment = Comment::findOrFail($commentId);

        // Check if user owns the comment
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment->update([
            'content' => $request->content,
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        $comment->load(['user.profile', 'likes']);

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => $comment
        ]);
    }

    /**
     * Delete a comment
     */
    public function deleteComment(Request $request, $commentId)
    {
        $comment = Comment::findOrFail($commentId);

        // Check if user owns the comment or the post
        if ($comment->user_id !== $request->user()->id && $comment->post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $postId = $comment->post_id;
        $commentId = $comment->id;
        
        $comment->delete();

        // Broadcast the comment deletion event
        event(new CommentDeleted($commentId, $postId));

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }

    /**
     * Like/unlike a comment
     */
    public function likeComment(Request $request, $commentId)
    {
        $comment = Comment::findOrFail($commentId);

        $existingLike = Like::where('user_id', $request->user()->id)
                            ->where('comment_id', $comment->id)
                            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $message = 'Comment unliked';
        } else {
            Like::create([
                'user_id' => $request->user()->id,
                'comment_id' => $comment->id,
                'type' => $request->type ?? 'like',
            ]);
            $message = 'Comment liked';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'liked' => !$existingLike,
                'like_count' => $comment->fresh()->like_count
            ]
        ]);
    }
}
