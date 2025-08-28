<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Profile;
use App\Models\User;

echo "=== Debugging Profile Image Issues ===\n\n";

try {
    // Check current APP_URL
    echo "1. Environment Check:\n";
    echo "   APP_URL: " . config('app.url') . "\n";
    echo "   APP_ENV: " . config('app.env') . "\n\n";
    
    // Check profiles with avatars
    echo "2. Checking Profiles with Avatars:\n";
    $profiles = Profile::whereNotNull('avatar')->get();
    
    foreach ($profiles as $profile) {
        echo "   Profile ID: {$profile->id}\n";
        echo "   Avatar (raw): {$profile->avatar}\n";
        echo "   Avatar URL: {$profile->avatar_url}\n";
        
        // Check if file actually exists
        $storagePath = storage_path('app/public/' . ltrim($profile->avatar, 'storage/'));
        $fileExists = file_exists($storagePath);
        echo "   File exists: " . ($fileExists ? "✅ YES" : "❌ NO") . "\n";
        echo "   Storage path: {$storagePath}\n\n";
    }
    
    // Check profiles with cover photos
    echo "3. Checking Profiles with Cover Photos:\n";
    $profilesWithCovers = Profile::whereNotNull('cover_photo')->get();
    
    foreach ($profilesWithCovers as $profile) {
        echo "   Profile ID: {$profile->id}\n";
        echo "   Cover (raw): {$profile->cover_photo}\n";
        echo "   Cover URL: {$profile->cover_photo_url}\n";
        
        // Check if file actually exists
        $storagePath = storage_path('app/public/' . ltrim($profile->cover_photo, 'storage/'));
        $fileExists = file_exists($storagePath);
        echo "   File exists: " . ($fileExists ? "✅ YES" : "❌ NO") . "\n";
        echo "   Storage path: {$storagePath}\n\n";
    }
    
    // List actual files in storage directories
    echo "4. Checking Storage Directories:\n";
    
    $avatarsDir = storage_path('app/public/avatars');
    if (is_dir($avatarsDir)) {
        $avatarFiles = glob($avatarsDir . '/*');
        echo "   Avatars directory: " . count($avatarFiles) . " files\n";
        foreach (array_slice($avatarFiles, 0, 5) as $file) {
            echo "     - " . basename($file) . "\n";
        }
    } else {
        echo "   ❌ Avatars directory doesn't exist\n";
    }
    
    $coversDir = storage_path('app/public/covers');
    if (is_dir($coversDir)) {
        $coverFiles = glob($coversDir . '/*');
        echo "   Covers directory: " . count($coverFiles) . " files\n";
        foreach (array_slice($coverFiles, 0, 5) as $file) {
            echo "     - " . basename($file) . "\n";
        }
    } else {
        echo "   ❌ Covers directory doesn't exist\n";
    }
    
    echo "\n5. Fixing Profile Image URLs:\n";
    
    // Fix profile avatars
    $fixedAvatars = 0;
    foreach ($profiles as $profile) {
        $oldAvatar = $profile->avatar;
        $newAvatar = $oldAvatar;
        
        // Remove any incorrect prefixes
        $newAvatar = str_replace('storage/', '', $newAvatar);
        $newAvatar = str_replace('http://localhost/storage/', '', $newAvatar);
        $newAvatar = str_replace('https://localhost/storage/', '', $newAvatar);
        $newAvatar = str_replace('http://luckymillion.online/storage/', '', $newAvatar);
        $newAvatar = str_replace('https://luckymillion.online/storage/', '', $newAvatar);
        
        // Ensure it starts with avatars/ if it's an avatar
        if (strpos($newAvatar, 'avatars/') !== 0 && $newAvatar) {
            $newAvatar = 'avatars/' . basename($newAvatar);
        }
        
        if ($oldAvatar !== $newAvatar) {
            $profile->update(['avatar' => $newAvatar]);
            echo "   Fixed avatar: {$oldAvatar} -> {$newAvatar}\n";
            $fixedAvatars++;
        }
    }
    
    // Fix profile cover photos
    $fixedCovers = 0;
    foreach ($profilesWithCovers as $profile) {
        $oldCover = $profile->cover_photo;
        $newCover = $oldCover;
        
        // Remove any incorrect prefixes
        $newCover = str_replace('storage/', '', $newCover);
        $newCover = str_replace('http://localhost/storage/', '', $newCover);
        $newCover = str_replace('https://localhost/storage/', '', $newCover);
        $newCover = str_replace('http://luckymillion.online/storage/', '', $newCover);
        $newCover = str_replace('https://luckymillion.online/storage/', '', $newCover);
        
        // Ensure it starts with covers/ if it's a cover
        if (strpos($newCover, 'covers/') !== 0 && $newCover) {
            $newCover = 'covers/' . basename($newCover);
        }
        
        if ($oldCover !== $newCover) {
            $profile->update(['cover_photo' => $newCover]);
            echo "   Fixed cover: {$oldCover} -> {$newCover}\n";
            $fixedCovers++;
        }
    }
    
    echo "\n✅ Profile Image Fix Complete!\n";
    echo "   Fixed {$fixedAvatars} avatars\n";
    echo "   Fixed {$fixedCovers} cover photos\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit(1);
}
