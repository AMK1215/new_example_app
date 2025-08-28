<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Friendship;
use App\Events\FriendRequestReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FriendshipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get accepted friendships
        $friends = $user->friends()->with('profile')->paginate(20);
        \Log::info('Friends query result', ['count' => $friends->count(), 'total' => $friends->total()]);
        
        // Get pending friend requests
        $pendingRequests = $user->pendingFriends()->with('profile')->get();
        
        // Get sent friend requests
        $sentRequests = Friendship::where('user_id', $user->id)
                                ->where('status', 'pending')
                                ->with('friend.profile')
                                ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'friends' => $friends,
                'pending_requests' => $pendingRequests,
                'sent_requests' => $sentRequests,
            ]
        ]);
    }

    public function sendRequest(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send a friend request to yourself'
            ], 400);
        }

        // Check if friendship already exists
        $existingFriendship = Friendship::where(function($query) use ($request, $user) {
            $query->where('user_id', $request->user()->id)
                  ->where('friend_id', $user->id);
        })->orWhere(function($query) use ($request, $user) {
            $query->where('user_id', $user->id)
                  ->where('friend_id', $request->user()->id);
        })->first();

        if ($existingFriendship) {
            if ($existingFriendship->status === 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already friends with this user'
                ], 400);
            } elseif ($existingFriendship->status === 'pending') {
                if ($existingFriendship->user_id === $request->user()->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Friend request already sent'
                    ], 400);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'This user has already sent you a friend request'
                    ], 400);
                }
            } elseif ($existingFriendship->status === 'blocked') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send friend request to blocked user'
                ], 400);
            }
        }

        // Create new friendship request
        $friendship = Friendship::create([
            'user_id' => $request->user()->id,
            'friend_id' => $user->id,
            'status' => 'pending',
        ]);

        // Broadcast the friend request event
        event(new FriendRequestReceived($friendship->load('user.profile')));

        return response()->json([
            'success' => true,
            'message' => 'Friend request sent successfully',
            'data' => $friendship->load('friend.profile')
        ], 201);
    }

    public function respondToRequest(Request $request, Friendship $friendship)
    {
        // Verify the friendship belongs to the authenticated user
        if ($friendship->friend_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:accept,reject,block',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $action = $request->action;

        switch ($action) {
            case 'accept':
                \Log::info('Accepting friendship', ['friendship_id' => $friendship->id, 'user_id' => $request->user()->id]);
                $friendship->update([
                    'status' => 'accepted',
                    'accepted_at' => now(),
                ]);
                \Log::info('Friendship updated', ['new_status' => $friendship->fresh()->status]);
                
                // Broadcast the friendship status change
                event(new FriendshipStatusChanged($friendship->fresh(), 'accepted'));
                
                $message = 'Friend request accepted successfully';
                break;

            case 'reject':
                // Broadcast the friendship status change before deleting
                event(new FriendshipStatusChanged($friendship, 'rejected'));
                
                $friendship->delete();
                $message = 'Friend request rejected';
                break;

            case 'block':
                $friendship->update(['status' => 'blocked']);
                
                // Broadcast the friendship status change
                event(new FriendshipStatusChanged($friendship->fresh(), 'blocked'));
                
                $message = 'User blocked successfully';
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action'
                ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $friendship->fresh()
        ]);
    }

    public function remove(Request $request, Friendship $friendship)
    {
        // Verify the friendship belongs to the authenticated user
        if ($friendship->user_id !== $request->user()->id && $friendship->friend_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Friendship removed successfully'
        ]);
    }

    public function block(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot block yourself'
            ], 400);
        }

        // Check if friendship exists
        $friendship = Friendship::where(function($query) use ($request, $user) {
            $query->where('user_id', $request->user()->id)
                  ->where('friend_id', $user->id);
        })->orWhere(function($query) use ($request, $user) {
            $query->where('user_id', $user->id)
                  ->where('friend_id', $request->user()->id);
        })->first();

        if ($friendship) {
            $friendship->update(['status' => 'blocked']);
        } else {
            $friendship = Friendship::create([
                'user_id' => $request->user()->id,
                'friend_id' => $user->id,
                'status' => 'blocked',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully',
            'data' => $friendship
        ]);
    }

    public function unblock(Request $request, User $user)
    {
        $friendship = Friendship::where('user_id', $request->user()->id)
                               ->where('friend_id', $user->id)
                               ->where('status', 'blocked')
                               ->first();

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'User is not blocked'
            ], 400);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'User unblocked successfully'
        ]);
    }

    public function suggestions(Request $request)
    {
        $user = $request->user();
        
        // Get users who are not friends and haven't been sent/received requests
        $suggestions = User::where('id', '!=', $user->id)
                          ->whereDoesntHave('friendships', function($query) use ($user) {
                              $query->where('user_id', $user->id)
                                    ->orWhere('friend_id', $user->id);
                          })
                          ->with('profile')
                          ->inRandomOrder()
                          ->limit(10)
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }

    public function pendingRequests(Request $request)
    {
        $user = $request->user();
        
        // Get pending friend requests received by the user
        $pendingRequests = Friendship::where('friend_id', $user->id)
                                   ->where('status', 'pending')
                                   ->with('user.profile')
                                   ->get();

        return response()->json([
            'success' => true,
            'data' => $pendingRequests
        ]);
    }

    public function getStatus(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        if ($currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot check friendship status with yourself'
            ], 400);
        }

        // Find existing friendship
        $friendship = Friendship::where(function($query) use ($currentUser, $user) {
            $query->where('user_id', $currentUser->id)
                  ->where('friend_id', $user->id);
        })->orWhere(function($query) use ($currentUser, $user) {
            $query->where('user_id', $user->id)
                  ->where('friend_id', $currentUser->id);
        })->first();

        if (!$friendship) {
            return response()->json([
                'success' => true,
                'data' => ['status' => 'none']
            ]);
        }

        $status = 'none';
        if ($friendship->status === 'accepted') {
            $status = 'friends';
        } elseif ($friendship->status === 'pending') {
            if ($friendship->user_id === $currentUser->id) {
                $status = 'pending_sent';
            } else {
                $status = 'pending_received';
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $status,
                'friendshipId' => $friendship->id
            ]
        ]);
    }

    public function suggestedFriends(Request $request)
    {
        $user = $request->user();
        
        // Get users who are not friends and haven't been sent/received requests
        $suggestions = User::where('id', '!=', $user->id)
                          ->whereDoesntHave('friendships', function($query) use ($user) {
                              $query->where('user_id', $user->id)
                                    ->orWhere('friend_id', $user->id);
                          })
                          ->with('profile')
                          ->inRandomOrder()
                          ->limit(10)
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }
}
