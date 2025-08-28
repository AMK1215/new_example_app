<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasMediaUrls;

class Profile extends Model
{
    use HasFactory, HasMediaUrls;

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
        return $this->generateStorageUrl($this->avatar);
    }

    public function getCoverPhotoUrlAttribute()
    {
        return $this->generateStorageUrl($this->cover_photo);
    }
}
