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

    /**
     * Test creating a simple friendship record
     */
    public function testCreateFriendship(Request $request, $targetUserId)
    {
        $currentUserId = $request->user()->id;
        
        try {
            // First delete any existing friendship
            Friendship::where(function($query) use ($currentUserId, $targetUserId) {
                $query->where('user_id', $currentUserId)
                      ->where('friend_id', $targetUserId);
            })->orWhere(function($query) use ($currentUserId, $targetUserId) {
                $query->where('user_id', $targetUserId)
                      ->where('friend_id', $currentUserId);
            })->delete();

            // Create new friendship
            $friendship = Friendship::create([
                'user_id' => $currentUserId,
                'friend_id' => $targetUserId,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test friendship created successfully',
                'data' => $friendship
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Debug pending requests for current user
     */
    public function debugPendingRequests(Request $request)
    {
        $user = $request->user();
        
        // Get all friendships for this user
        $allFriendships = Friendship::where('user_id', $user->id)
                                   ->orWhere('friend_id', $user->id)
                                   ->with('user', 'friend')
                                   ->get();
        
        // Get specifically pending requests received
        $pendingReceived = Friendship::where('friend_id', $user->id)
                                   ->where('status', 'pending')
                                   ->with('user')
                                   ->get();
        
        // Get specifically pending requests sent
        $pendingSent = Friendship::where('user_id', $user->id)
                                ->where('status', 'pending')
                                ->with('friend')
                                ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'all_friendships_count' => $allFriendships->count(),
                'pending_received_count' => $pendingReceived->count(),
                'pending_sent_count' => $pendingSent->count(),
                'all_friendships' => $allFriendships->map(function($f) use ($user) {
                    return [
                        'id' => $f->id,
                        'user_id' => $f->user_id,
                        'friend_id' => $f->friend_id,
                        'status' => $f->status,
                        'direction' => $f->user_id === $user->id ? 'sent' : 'received',
                        'other_user' => $f->user_id === $user->id ? $f->friend->name : $f->user->name,
                        'created_at' => $f->created_at
                    ];
                }),
                'pending_received' => $pendingReceived->map(function($f) {
                    return [
                        'id' => $f->id,
                        'from_user_id' => $f->user_id,
                        'from_user_name' => $f->user->name,
                        'status' => $f->status,
                        'created_at' => $f->created_at
                    ];
                }),
                'pending_sent' => $pendingSent->map(function($f) {
                    return [
                        'id' => $f->id,
                        'to_user_id' => $f->friend_id,
                        'to_user_name' => $f->friend->name,
                        'status' => $f->status,
                        'created_at' => $f->created_at
                    ];
                })
            ]
        ]);
    }

    /**
     * Check specific friendship between users 1 and 2
     */
    public function checkUsers1And2()
    {
        // Get all friendships between users 1 and 2
        $friendships = Friendship::where(function($query) {
            $query->where('user_id', 1)->where('friend_id', 2);
        })->orWhere(function($query) {
            $query->where('user_id', 2)->where('friend_id', 1);
        })->with('user', 'friend')->get();

        // Get pending requests TO user 1
        $pendingToUser1 = Friendship::where('friend_id', 1)
                                   ->where('status', 'pending')
                                   ->with('user')
                                   ->get();

        // Get pending requests FROM user 2
        $pendingFromUser2 = Friendship::where('user_id', 2)
                                     ->where('status', 'pending')
                                     ->with('friend')
                                     ->get();

        return response()->json([
            'success' => true,
            'message' => 'Checking friendship between users 1 and 2',
            'data' => [
                'all_friendships_1_and_2' => $friendships->map(function($f) {
                    return [
                        'id' => $f->id,
                        'user_id' => $f->user_id,
                        'friend_id' => $f->friend_id,
                        'status' => $f->status,
                        'user_name' => $f->user->name ?? 'Unknown',
                        'friend_name' => $f->friend->name ?? 'Unknown',
                        'direction' => $f->user_id === 2 ? 'User 2 → User 1' : 'User 1 → User 2',
                        'created_at' => $f->created_at,
                        'updated_at' => $f->updated_at
                    ];
                }),
                'pending_to_user_1_count' => $pendingToUser1->count(),
                'pending_to_user_1' => $pendingToUser1->map(function($f) {
                    return [
                        'id' => $f->id,
                        'from_user_id' => $f->user_id,
                        'from_user_name' => $f->user->name ?? 'Unknown',
                        'status' => $f->status,
                        'created_at' => $f->created_at
                    ];
                }),
                'pending_from_user_2_count' => $pendingFromUser2->count(),
                'pending_from_user_2' => $pendingFromUser2->map(function($f) {
                    return [
                        'id' => $f->id,
                        'to_user_id' => $f->friend_id,
                        'to_user_name' => $f->friend->name ?? 'Unknown',
                        'status' => $f->status,
                        'created_at' => $f->created_at
                    ];
                })
            ]
        ]);
    }

    /**
     * Fix the notification issue and recreate the friendship
     */
    public function fixUsers1And2()
    {
        try {
            \DB::beginTransaction();
            
            // Clean up any existing friendships between users 1 and 2
            $deletedCount = Friendship::where(function($query) {
                $query->where('user_id', 1)->where('friend_id', 2);
            })->orWhere(function($query) {
                $query->where('user_id', 2)->where('friend_id', 1);
            })->delete();

            // Create a clean friendship request from user 2 to user 1
            $friendship = Friendship::create([
                'user_id' => 2,  // sender
                'friend_id' => 1, // receiver
                'status' => 'pending',
            ]);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully fixed and recreated friendship request',
                'data' => [
                    'deleted_friendships' => $deletedCount,
                    'new_friendship' => [
                        'id' => $friendship->id,
                        'user_id' => $friendship->user_id,
                        'friend_id' => $friendship->friend_id,
                        'status' => $friendship->status,
                        'direction' => 'User 2 → User 1',
                        'created_at' => $friendship->created_at
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to fix friendship: ' . $e->getMessage()
            ], 500);
        }
    }
}