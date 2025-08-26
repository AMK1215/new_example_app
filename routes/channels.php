<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;
use App\Models\Post;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel for posts feed
Broadcast::channel('posts', function ($user) {
    return true; // Anyone can listen to posts
});

// Channel for specific post updates (likes, comments)
Broadcast::channel('post.{id}', function ($user, $id) {
    return true; // Anyone can listen to post updates
});

// Private channel for conversations
Broadcast::channel('conversation.{id}', function ($user, $id) {
    $conversation = Conversation::find($id);
    if (!$conversation) {
        return false;
    }
    
    // Check if user is part of the conversation
    return $conversation->users->contains($user->id);
});

// Private channel for user notifications
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel for user online status
Broadcast::channel('user.status', function ($user) {
    return true; // Anyone can listen to user status updates
});
