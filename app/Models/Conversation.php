<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'avatar',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
                    ->withPivot('last_read_at', 'is_muted')
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function unreadCountForUser($userId)
    {
        $userPivot = $this->users()->where('user_id', $userId)->first();
        
        if (!$userPivot || !$userPivot->pivot) {
            return $this->messages()->count();
        }
        
        $lastRead = $userPivot->pivot->last_read_at;
        
        if (!$lastRead) {
            return $this->messages()->count();
        }
        
        return $this->messages()->where('created_at', '>', $lastRead)->count();
    }

    public function isGroup()
    {
        return $this->type === 'group';
    }

    public function isPrivate()
    {
        return $this->type === 'private';
    }
}
