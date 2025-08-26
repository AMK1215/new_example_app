<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function friendships()
    {
        return $this->hasMany(Friendship::class);
    }

    public function friends()
    {
        // Get all accepted friendships where this user is either the sender or receiver
        $friendshipIds = $this->friendships()
            ->where('status', 'accepted')
            ->get()
            ->map(function($friendship) {
                return $friendship->user_id === $this->id ? $friendship->friend_id : $friendship->user_id;
            });
        
        return User::whereIn('id', $friendshipIds);
    }

    public function pendingFriends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
                    ->wherePivot('status', 'pending');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Helper methods
    public function isFriendsWith($userId)
    {
        return $this->friendships()
                    ->where('friend_id', $userId)
                    ->where('status', 'accepted')
                    ->exists();
    }

    public function hasPendingFriendRequestFrom($userId)
    {
        return $this->friendships()
                    ->where('friend_id', $userId)
                    ->where('status', 'pending')
                    ->exists();
    }

    public function hasSentFriendRequestTo($userId)
    {
        return $this->friendships()
                    ->where('user_id', $userId)
                    ->where('status', 'pending')
                    ->exists();
    }
}
