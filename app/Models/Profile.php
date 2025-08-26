<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'bio',
        'avatar',
        'cover_photo',
        'birth_date',
        'location',
        'website',
        'social_links',
        'is_private',
    ];

    protected $casts = [
        'social_links' => 'array',
        'birth_date' => 'date',
        'is_private' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) {
            return asset('images/default-avatar.png');
        }
        
        // If it's already a full URL, return as is
        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }
        
        // Convert storage path to public URL
        return asset('storage/' . $this->avatar);
    }

    public function getCoverPhotoUrlAttribute()
    {
        if (!$this->cover_photo) {
            return asset('images/default-cover.jpg');
        }
        
        // If it's already a full URL, return as is
        if (filter_var($this->cover_photo, FILTER_VALIDATE_URL)) {
            return $this->cover_photo;
        }
        
        // Convert storage path to public URL
        return asset('storage/' . $this->cover_photo);
    }
}
