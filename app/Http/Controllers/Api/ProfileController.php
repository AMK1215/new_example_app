<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        // Return the authenticated user's profile with proper URLs
        $user = $request->user()->load('profile');
        
        // Ensure profile URLs are properly generated
        if ($user->profile) {
            $profileData = $user->profile->toArray();
            $profileData['avatar_url'] = $user->profile->avatar_url;
            $profileData['cover_photo_url'] = $user->profile->cover_photo_url;
            $user->profile = $profileData;
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function getAllUsers(Request $request)
    {
        $users = User::with('profile')
                    ->where('id', '!=', $request->user()->id)
                    ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function show(Request $request, User $user)
    {
        if ($user->profile && $user->profile->is_private) {
            // Check if they are friends
            if (!$request->user()->isFriendsWith($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile is private'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $user->load(['profile', 'posts' => function($query) {
                $query->public()->latest()->limit(5);
            }])
        ]);
    }

    public function update(Request $request)
    {

        
        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string|max:255|unique:profiles,username,' . $request->user()->profile->id,
            'bio' => 'nullable|string|max:1000',
            'birth_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url',
            'social_links.twitter' => 'nullable|url',
            'social_links.instagram' => 'nullable|url',
            'social_links.linkedin' => 'nullable|url',
            'is_private' => 'nullable|in:0,1,true,false',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:8048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:9120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = $request->user()->profile;
        $data = $request->except(['avatar', 'cover_photo']);

        // Convert string boolean values to actual booleans
        if ($request->has('is_private')) {
            $data['is_private'] = filter_var($request->is_private, FILTER_VALIDATE_BOOLEAN);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($profile->avatar) {
                Storage::disk('public')->delete($profile->avatar);
            }
            
            // Generate unique filename with timestamp to ensure latest photo is shown
            $avatarFile = $request->file('avatar');
            $timestamp = now()->format('Y-m-d_H-i-s');
            $extension = $avatarFile->getClientOriginalExtension();
            $filename = "avatar_{$profile->user_id}_{$timestamp}.{$extension}";
            
            $data['avatar'] = $avatarFile->storeAs('avatars', $filename, 'public');
        }

        // Handle cover photo upload
        if ($request->hasFile('cover_photo')) {
            if ($profile->cover_photo) {
                Storage::disk('public')->delete($profile->cover_photo);
            }
            
            // Generate unique filename with timestamp to ensure latest photo is shown
            $coverFile = $request->file('cover_photo');
            $timestamp = now()->format('Y-m-d_H-i-s');
            $extension = $coverFile->getClientOriginalExtension();
            $filename = "cover_{$profile->user_id}_{$timestamp}.{$extension}";
            
            $data['cover_photo'] = $coverFile->storeAs('covers', $filename, 'public');
        }

        // Handle avatar removal
        if ($request->has('avatar') && $request->avatar === null) {
            if ($profile->avatar) {
                Storage::disk('public')->delete($profile->avatar);
            }
            $data['avatar'] = null;
        }

        // Handle cover photo removal
        if ($request->has('cover_photo') && $request->cover_photo === null) {
            if ($profile->cover_photo) {
                Storage::disk('public')->delete($profile->cover_photo);
            }
            $data['cover_photo'] = null;
        }

        $profile->update($data);

        // Return the updated profile (accessors will handle URL conversion)
        $updatedProfile = $profile->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $updatedProfile
        ]);
    }
}
