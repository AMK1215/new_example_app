<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friendship extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isBlocked()
    {
        return $this->status === 'blocked';
    }
}
