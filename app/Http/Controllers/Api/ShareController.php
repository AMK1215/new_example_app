<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Share;
use App\Events\PostShared;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShareController extends Controller
{
    /**
     * Share a post
     */
    public function sharePost(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'share_type' => 'required|in:timeline,story,message,copy_link',
            'content' => 'nullable|string|max:1000',
            'privacy' => 'nullable|in:public,friends,only_me',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if post exists and is accessible
        if (!$post->is_public && $post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found or not accessible'
            ], 404);
        }

        // For timeline shares, allow multiple shares (like Facebook)
        // For other types, check for duplicates
        if ($request->share_type !== 'timeline') {
            $existingShare = Share::where('user_id', $request->user()->id)
                                  ->where('post_id', $post->id)
                                  ->where('share_type', $request->share_type)
                                  ->first();

            if ($existingShare) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already shared this post'
                ], 409);
            }
        }

        // Create the share
        $share = Share::create([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
            'share_type' => $request->share_type,
            'content' => $request->content,
            'privacy' => $request->privacy ?? 'public',
        ]);

        // If sharing to timeline, create a new post entry
        $timelinePost = null;
        if ($request->share_type === 'timeline') {
            $timelinePost = Post::create([
                'user_id' => $request->user()->id,
                'content' => $request->content ?: '', // Empty string if no content provided
                'type' => 'shared',
                'is_public' => $request->privacy === 'public',
                'is_shared' => true,
                'shared_post_id' => $post->id,
                'share_content' => $request->content,
            ]);
            
            // Load the timeline post with relationships
            $timelinePost->load(['user.profile', 'sharedPost.user.profile', 'sharedPost.likes', 'sharedPost.comments']);
        }

        // Load the share with relationships
        $shareData = $share->load(['user.profile', 'post.user.profile']);

        // Broadcast the share event (for real-time updates)
        broadcast(new PostShared($shareData))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Post shared successfully',
            'data' => [
                'share' => $shareData,
                'timeline_post' => $timelinePost
            ]
        ], 201);
    }

    /**
     * Get shares for a specific post
     */
    public function getPostShares(Request $request, Post $post)
    {
        $shares = $post->shares()
                      ->with(['user.profile'])
                      ->public()
                      ->latest()
                      ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $shares
        ]);
    }

    /**
     * Get user's shares
     */
    public function getUserShares(Request $request)
    {
        $shares = $request->user()
                         ->shares()
                         ->with(['post.user.profile'])
                         ->latest()
                         ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $shares
        ]);
    }

    /**
     * Unshare a post
     */
    public function unsharePost(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'share_type' => 'required|in:timeline,story,message,copy_link',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $share = Share::where('user_id', $request->user()->id)
                     ->where('post_id', $post->id)
                     ->where('share_type', $request->share_type)
                     ->first();

        if (!$share) {
            return response()->json([
                'success' => false,
                'message' => 'Share not found'
            ], 404);
        }

        $share->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post unshared successfully'
        ]);
    }

    /**
     * Get share statistics for a post
     */
    public function getShareStats(Request $request, Post $post)
    {
        $stats = [
            'total_shares' => $post->share_count,
            'timeline_shares' => $post->shares()->byType('timeline')->count(),
            'story_shares' => $post->shares()->byType('story')->count(),
            'message_shares' => $post->shares()->byType('message')->count(),
            'copy_link_shares' => $post->shares()->byType('copy_link')->count(),
            'user_has_shared' => Share::hasUserSharedPost($request->user()->id, $post->id),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Copy post link
     */
    public function copyLink(Request $request, Post $post)
    {
        // Generate shareable link
        $shareableLink = config('app.url') . "/posts/{$post->id}";

        // Log the copy link action
        Share::create([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
            'share_type' => 'copy_link',
            'content' => null,
            'privacy' => 'public',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Link copied successfully',
            'data' => [
                'link' => $shareableLink
            ]
        ]);
    }
}
