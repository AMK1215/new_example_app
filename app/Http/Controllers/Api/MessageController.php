<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\NewMessage;
use App\Events\UserTyping;
use App\Events\UserOnlineStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class MessageController extends Controller
{
    public function conversations(Request $request)
    {
        try {
            $user = $request->user();
            
            $conversations = $user->conversations()
                                 ->with([
                                     'users.profile', 
                                     'lastMessage.user.profile',
                                     'messages' => function($query) {
                                         $query->latest()->limit(1)->with('user.profile');
                                     }
                                 ])
                                 ->withPivot('last_read_at', 'is_muted')
                                 ->orderBy('updated_at', 'desc')
                                 ->get();

            // Add unread count and ensure latest message for each conversation
            $conversations->each(function($conversation) use ($user) {
                try {
                    $conversation->unread_count = $conversation->unreadCountForUser($user->id);
                    
                    // Ensure we have a latest message - fallback to messages collection if lastMessage is null
                    if (!$conversation->lastMessage && $conversation->messages && $conversation->messages->count() > 0) {
                        $conversation->latest_message = $conversation->messages->first();
                    } else if ($conversation->lastMessage) {
                        $conversation->latest_message = $conversation->lastMessage;
                    }
                } catch (\Exception $e) {
                    \Log::error('Error calculating unread count: ' . $e->getMessage());
                    $conversation->unread_count = 0;
                }
            });

            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in conversations method: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading conversations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function messages(Request $request, Conversation $conversation)
    {
        // Check if user is part of this conversation
        if (!$conversation->users->contains($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $messages = $conversation->messages()
                                ->with('user.profile')
                                ->orderBy('created_at', 'asc')
                                ->paginate(50);

        // Mark messages as read
        $conversation->users()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    public function store(Request $request, Conversation $conversation)
    {
        try {
            // Check if user is part of this conversation
            if (!$conversation->users->contains($request->user()->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:5000',
                'type' => 'in:text,image,video,audio,file',
                'media' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            \Log::info('Creating message', [
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'content' => $request->content,
                'type' => $request->type ?? 'text'
            ]);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'content' => $request->content,
                'type' => $request->type ?? 'text',
                'media' => $request->media,
            ]);

            \Log::info('Message created successfully', ['message_id' => $message->id]);

            // Broadcast the new message event
            try {
                event(new NewMessage($message));
                \Log::info('Message broadcasted successfully');
            } catch (\Exception $e) {
                \Log::error('Failed to broadcast message: ' . $e->getMessage());
            }

            // Update conversation timestamp
            $conversation->touch();

            // Update user's last read time
            $conversation->users()->updateExistingPivot($request->user()->id, [
                'last_read_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message->load('user.profile')
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error in store method: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    public function startConversation(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot start a conversation with yourself'
            ], 400);
        }

        // Check if conversation already exists
        $existingConversation = Conversation::where('type', 'private')
                                          ->whereHas('users', function($query) use ($request) {
                                              $query->where('user_id', $request->user()->id);
                                          })
                                          ->whereHas('users', function($query) use ($user) {
                                              $query->where('user_id', $user->id);
                                          })
                                          ->first();

        if ($existingConversation) {
            return response()->json([
                'success' => true,
                'message' => 'Conversation already exists',
                'data' => $existingConversation->load(['users.profile', 'lastMessage.user'])
            ]);
        }

        // Create new private conversation
        $conversation = Conversation::create([
            'type' => 'private',
        ]);

        // Attach users to conversation
        $conversation->users()->attach([
            $request->user()->id => ['last_read_at' => now()],
            $user->id => ['last_read_at' => now()]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation started successfully',
            'data' => $conversation->load(['users.profile'])
        ], 201);
    }

    public function createGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_ids' => 'required|array|min:2',
            'user_ids.*' => 'exists:users,id',
            'avatar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Add current user to the group
        $userIds = array_merge($request->user_ids, [$request->user()->id]);
        $userIds = array_unique($userIds);

        // Create group conversation
        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $request->name,
            'avatar' => $request->avatar,
        ]);

        // Attach users to conversation
        $conversation->users()->attach($userIds, ['last_read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully',
            'data' => $conversation->load(['users.profile'])
        ], 201);
    }

    public function addToGroup(Request $request, Conversation $conversation)
    {
        // Check if user is admin of the group
        if (!$conversation->isGroup() || !$conversation->users->contains($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Add users to group
        $conversation->users()->attach($request->user_ids, ['last_read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Users added to group successfully',
            'data' => $conversation->load(['users.profile'])
        ]);
    }

    public function removeFromGroup(Request $request, Conversation $conversation)
    {
        // Check if user is admin of the group
        if (!$conversation->isGroup() || !$conversation->users->contains($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Remove user from group
        $conversation->users()->detach($request->user_id);

        return response()->json([
            'success' => true,
            'message' => 'User removed from group successfully'
        ]);
    }

    public function leaveGroup(Request $request, Conversation $conversation)
    {
        if (!$conversation->isGroup()) {
            return response()->json([
                'success' => false,
                'message' => 'This is not a group conversation'
            ], 400);
        }

        // Remove user from group
        $conversation->users()->detach($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Left group successfully'
        ]);
    }

    public function markAsRead(Request $request, Conversation $conversation)
    {
        // Check if user is part of this conversation
        if (!$conversation->users->contains($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Update last read time
        $conversation->users()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as read'
        ]);
    }

    public function mute(Request $request, Conversation $conversation)
    {
        // Check if user is part of this conversation
        if (!$conversation->users->contains($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $conversation->users()->updateExistingPivot($request->user()->id, [
            'is_muted' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation muted'
        ]);
    }

    public function unmute(Request $request, Conversation $conversation)
    {
        // Check if user is part of this conversation
        if (!$conversation->users->contains($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $conversation->users()->updateExistingPivot($request->user()->id, [
            'is_muted' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation unmuted'
        ]);
    }

    public function deleteMessage(Request $request, Message $message)
    {
        // Check if user owns the message
        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    public function editMessage(Request $request, Message $message)
    {
        // Check if user owns the message
        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $message->update([
            'content' => $request->content,
            'is_edited' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message updated successfully',
            'data' => $message->load('user.profile')
        ]);
    }

    public function typing(Request $request, Conversation $conversation)
    {
        // Check if user is part of this conversation
        if (!$conversation->users->contains($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $user = $request->user();
        
        // Broadcast typing indicator
        event(new UserTyping($conversation->id, $user->id, $user->name, true));

        // Set a cache key to auto-stop typing after 5 seconds
        $cacheKey = "typing:{$conversation->id}:{$user->id}";
        Cache::put($cacheKey, true, now()->addSeconds(5));

        return response()->json([
            'success' => true,
            'message' => 'Typing indicator sent'
        ]);
    }

    public function stopTyping(Request $request, Conversation $conversation)
    {
        // Check if user is part of this conversation
        if (!$conversation->users->contains($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $user = $request->user();
        
        // Broadcast stop typing indicator
        event(new UserTyping($conversation->id, $user->id, $user->name, false));

        // Remove typing cache
        $cacheKey = "typing:{$conversation->id}:{$user->id}";
        Cache::forget($cacheKey);

        return response()->json([
            'success' => true,
            'message' => 'Typing indicator stopped'
        ]);
    }



    public function searchConversations(Request $request)
    {
        try {
            $user = $request->user();
            $query = $request->get('query', '');
            
            if (empty($query)) {
                return $this->conversations($request);
            }

            $conversations = $user->conversations()
                                 ->with(['users.profile', 'lastMessage.user'])
                                 ->withPivot('last_read_at', 'is_muted')
                                 ->where(function($q) use ($query) {
                                     $q->whereHas('users', function($userQuery) use ($query) {
                                         $userQuery->where('name', 'like', "%{$query}%")
                                                  ->orWhere('email', 'like', "%{$query}%");
                                     });
                                 })
                                 ->orderBy('updated_at', 'desc')
                                 ->get();

            // Add unread count for each conversation
            $conversations->each(function($conversation) use ($user) {
                try {
                    $conversation->unread_count = $conversation->unreadCountForUser($user->id);
                } catch (\Exception $e) {
                    \Log::error('Error calculating unread count in search: ' . $e->getMessage());
                    $conversation->unread_count = 0;
                }
            });

            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in searchConversations method: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error searching conversations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
