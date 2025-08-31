<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Friendship;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    /**
     * Debug friendship status between users
     */
    public function debugFriendship(Request $request, $targetUserId)
    {
        $currentUserId = $request->user()->id;
        
        // Find all friendship records between these users
        $friendships = Friendship::where(function($query) use ($currentUserId, $targetUserId) {
            $query->where('user_id', $currentUserId)
                  ->where('friend_id', $targetUserId);
        })->orWhere(function($query) use ($currentUserId, $targetUserId) {
            $query->where('user_id', $targetUserId)
                  ->where('friend_id', $currentUserId);
        })->get();

        return response()->json([
            'success' => true,
            'data' => [
                'current_user_id' => $currentUserId,
                'target_user_id' => $targetUserId,
                'friendships_found' => $friendships->toArray(),
                'count' => $friendships->count(),
            ]
        ]);
    }

    /**
     * Clean up invalid friendships
     */
    public function cleanupFriendships(Request $request)
    {
        $currentUserId = $request->user()->id;
        
        // Find duplicates or invalid friendships
        $duplicates = Friendship::where('user_id', $currentUserId)
            ->orWhere('friend_id', $currentUserId)
            ->get()
            ->groupBy(function($friendship) {
                $userIds = [$friendship->user_id, $friendship->friend_id];
                sort($userIds);
                return implode('-', $userIds);
            })
            ->filter(function($group) {
                return $group->count() > 1;
            });

        $deletedCount = 0;
        foreach ($duplicates as $group) {
            // Keep the most recent one, delete the rest
            $toDelete = $group->sortByDesc('created_at')->slice(1);
            foreach ($toDelete as $friendship) {
                $friendship->delete();
                $deletedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$deletedCount} duplicate friendship records",
            'deleted_count' => $deletedCount
        ]);
    }
}