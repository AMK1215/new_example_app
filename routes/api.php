<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\MessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Broadcasting authentication
    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Posts
    Route::apiResource('posts', PostController::class);
    Route::post('/posts/{post}/like', [PostController::class, 'like']);
    Route::get('/users/{user}/posts', [PostController::class, 'userPosts']);
    Route::post('/posts/fix-videos', [PostController::class, 'fixVideoPosts']);
    Route::post('/posts/clean-external-urls', [PostController::class, 'cleanExternalUrls']);
    Route::post('/posts/convert-to-full-urls', [PostController::class, 'convertToFullUrls']);
    
    // Comments
    Route::get('/posts/{post}/comments', [PostController::class, 'comments']);
    Route::post('/posts/{post}/comments', [PostController::class, 'storeComment']);
    Route::put('/comments/{comment}', [PostController::class, 'updateComment']);
    Route::delete('/comments/{comment}', [PostController::class, 'deleteComment']);
    Route::post('/comments/{comment}/like', [PostController::class, 'likeComment']);
    
    // Profiles
    Route::get('/profiles', [ProfileController::class, 'index']);
    Route::get('/profiles/{user}', [ProfileController::class, 'show']);
    Route::put('/profiles', [ProfileController::class, 'update']);
    
    // Friendships
    Route::get('/friends', [FriendshipController::class, 'index']);
    Route::post('/friends/{user}', [FriendshipController::class, 'sendRequest']);
    Route::put('/friendships/{friendship}', [FriendshipController::class, 'respondToRequest']);
    Route::delete('/friendships/{friendship}', [FriendshipController::class, 'remove']);
    Route::get('/friendships/pending', [FriendshipController::class, 'pendingRequests']);
    Route::get('/friendships/status/{user}', [FriendshipController::class, 'getStatus']);
    Route::get('/friends/suggested', [FriendshipController::class, 'suggestedFriends']);
    
    // Messages
    Route::get('/conversations', [MessageController::class, 'conversations']);
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::post('/conversations/start/{user}', [MessageController::class, 'startConversation']);
    Route::post('/conversations', [MessageController::class, 'createGroup']);
    Route::post('/conversations/{conversation}/add', [MessageController::class, 'addToGroup']);
    Route::post('/conversations/{conversation}/remove', [MessageController::class, 'removeFromGroup']);
    Route::post('/conversations/{conversation}/leave', [MessageController::class, 'leaveGroup']);
    Route::post('/conversations/{conversation}/mute', [MessageController::class, 'mute']);
    Route::post('/conversations/{conversation}/unmute', [MessageController::class, 'unmute']);
    Route::delete('/messages/{message}', [MessageController::class, 'deleteMessage']);
    Route::put('/messages/{message}', [MessageController::class, 'editMessage']);
    Route::post('/conversations/{conversation}/read', [MessageController::class, 'markAsRead']);
    
    // Chat typing indicators and search
    Route::post('/conversations/{conversation}/typing', [MessageController::class, 'typing']);
    Route::post('/conversations/{conversation}/stop-typing', [MessageController::class, 'stopTyping']);
    Route::get('/conversations/search', [MessageController::class, 'searchConversations']);
});
