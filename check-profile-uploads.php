
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Profile;
use App\Models\User;

echo "=== Checking Profile Upload Issues ===\n\n";

// Check what's in the database
echo "1. Database Analysis:\n";
$totalProfiles = Profile::count();
$profilesWithAvatar = Profile::whereNotNull('avatar')->count();
$profilesWithCover = Profile::whereNotNull('cover_photo')->count();
$profilesWithPlaceholderAvatar = Profile::where('avatar', 'like', '%placeholder%')->count();
$profilesWithPlaceholderCover = Profile::where('cover_photo', 'like', '%placeholder%')->count();

echo "   Total profiles: {$totalProfiles}\n";
echo "   Profiles with avatar: {$profilesWithAvatar}\n";
echo "   Profiles with cover: {$profilesWithCover}\n";
echo "   Profiles with placeholder avatars: {$profilesWithPlaceholderAvatar}\n";
echo "   Profiles with placeholder covers: {$profilesWithPlaceholderCover}\n\n";

// Check actual uploaded files in storage
echo "2. Storage Files Analysis:\n";
$avatarsDir = storage_path('app/public/avatars');
$coversDir = storage_path('app/public/covers');

if (is_dir($avatarsDir)) {
    $avatarFiles = glob($avatarsDir . '/*');
    echo "   Avatar files in storage: " . count($avatarFiles) . "\n";
    if (count($avatarFiles) > 0) {
        echo "   Recent avatar files:\n";
        foreach (array_slice($avatarFiles, -5) as $file) {
            $filename = basename($file);
            $filesize = filesize($file);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo "     - {$filename} ({$filesize} bytes, {$modified})\n";
        }
    }
} else {
    echo "   ❌ Avatars directory doesn't exist\n";
}

if (is_dir($coversDir)) {
    $coverFiles = glob($coversDir . '/*');
    echo "   Cover files in storage: " . count($coverFiles) . "\n";
    if (count($coverFiles) > 0) {
        echo "   Recent cover files:\n";
        foreach (array_slice($coverFiles, -5) as $file) {
            $filename = basename($file);
            $filesize = filesize($file);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo "     - {$filename} ({$filesize} bytes, {$modified})\n";
        }
    }
} else {
    echo "   ❌ Covers directory doesn't exist\n";
}

// Check for profiles that might have real uploaded files
echo "\n3. Looking for Real Uploaded Profile Images:\n";
$realAvatars = Profile::where('avatar', 'not like', '%placeholder%')
                     ->where('avatar', 'not like', '%via.placeholder%')
                     ->whereNotNull('avatar')
                     ->get();

if ($realAvatars->count() > 0) {
    echo "   Found " . $realAvatars->count() . " profiles with non-placeholder avatars:\n";
    foreach ($realAvatars as $profile) {
        echo "     Profile {$profile->id}: {$profile->avatar}\n";
    }
} else {
    echo "   ❌ No profiles found with real uploaded avatars\n";
}

$realCovers = Profile::where('cover_photo', 'not like', '%placeholder%')
                     ->where('cover_photo', 'not like', '%via.placeholder%')
                     ->whereNotNull('cover_photo')
                     ->get();

if ($realCovers->count() > 0) {
    echo "   Found " . $realCovers->count() . " profiles with non-placeholder covers:\n";
    foreach ($realCovers as $profile) {
        echo "     Profile {$profile->id}: {$profile->cover_photo}\n";
    }
} else {
    echo "   ❌ No profiles found with real uploaded covers\n";
}

// Try to match orphaned files to users
echo "\n4. Attempting to Match Files to Users:\n";
if (is_dir($avatarsDir)) {
    $avatarFiles = glob($avatarsDir . '/*');
    foreach ($avatarFiles as $file) {
        $filename = basename($file);
        $profile = Profile::where('avatar', 'like', "%{$filename}%")->first();
        if (!$profile) {
            echo "   Orphaned avatar file: {$filename}\n";
        }
    }
}

if (is_dir($coversDir)) {
    $coverFiles = glob($coversDir . '/*');
    foreach ($coverFiles as $file) {
        $filename = basename($file);
        $profile = Profile::where('cover_photo', 'like', "%{$filename}%")->first();
        if (!$profile) {
            echo "   Orphaned cover file: {$filename}\n";
        }
    }
}

// Check your specific user profile
$currentUser = User::find(1); // Assuming you are user ID 1
if ($currentUser && $currentUser->profile) {
    echo "\n5. Your Profile Analysis:\n";
    echo "   User ID: {$currentUser->id}\n";
    echo "   Name: {$currentUser->name}\n";
    echo "   Avatar: " . ($currentUser->profile->avatar ?? 'NULL') . "\n";
    echo "   Cover: " . ($currentUser->profile->cover_photo ?? 'NULL') . "\n";
    
    // Check if there are recent files that might belong to you
    if (is_dir($avatarsDir)) {
        $recentAvatars = array_filter(glob($avatarsDir . '/*'), function($file) {
            return filemtime($file) > strtotime('-1 day');
        });
        if (count($recentAvatars) > 0) {
            echo "   Recent avatar uploads (last 24h):\n";
            foreach ($recentAvatars as $file) {
                $filename = basename($file);
                $modified = date('Y-m-d H:i:s', filemtime($file));
                echo "     - {$filename} (uploaded: {$modified})\n";
            }
        }
    }
}

echo "\n=== Analysis Complete ===\n";
