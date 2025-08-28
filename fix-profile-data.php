<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Profile;
use App\Models\User;

echo "=== Fixing Profile Data Issues ===\n\n";

try {
    // 1. Clean up fake/placeholder data
    echo "1. Cleaning up fake profile data...\n";
    
    $fakePlaceholderAvatars = Profile::where('avatar', 'like', '%?text=%')->get();
    $fakePlaceholderCovers = Profile::where('cover_photo', 'like', '%?text=%')->get();
    
    echo "   Found {$fakePlaceholderAvatars->count()} profiles with fake avatar data\n";
    echo "   Found {$fakePlaceholderCovers->count()} profiles with fake cover data\n";
    
    // Clear fake data
    foreach ($fakePlaceholderAvatars as $profile) {
        $profile->update(['avatar' => null]);
    }
    
    foreach ($fakePlaceholderCovers as $profile) {
        $profile->update(['cover_photo' => null]);
    }
    
    echo "   ✅ Cleaned up fake profile data\n\n";
    
    // 2. Check for any real uploaded files that might exist elsewhere
    echo "2. Checking for real uploaded files...\n";
    
    // Check if files exist in other common upload locations
    $possibleLocations = [
        '/var/www/html/new-example-app/public/uploads/',
        '/var/www/html/new-example-app/public/images/',
        '/var/www/html/new-example-app/storage/uploads/',
        '/var/www/html/new-example-app/storage/app/uploads/',
    ];
    
    foreach ($possibleLocations as $location) {
        if (is_dir($location)) {
            $files = glob($location . '*');
            if (count($files) > 0) {
                echo "   Found " . count($files) . " files in: {$location}\n";
                foreach (array_slice($files, 0, 5) as $file) {
                    echo "     - " . basename($file) . "\n";
                }
            }
        }
    }
    
    // 3. Set up your profile properly
    echo "\n3. Setting up your profile (User ID: 1)...\n";
    
    $yourUser = User::find(1);
    if ($yourUser) {
        echo "   Your user: {$yourUser->name} ({$yourUser->email})\n";
        
        if (!$yourUser->profile) {
            // Create profile if it doesn't exist
            $profile = Profile::create(['user_id' => $yourUser->id]);
            echo "   ✅ Created profile for your user\n";
        } else {
            echo "   Profile exists\n";
        }
        
        // Ensure profile has clean data
        $yourUser->profile->update([
            'avatar' => null,
            'cover_photo' => null
        ]);
        
        echo "   ✅ Your profile is ready for new uploads\n";
    }
    
    // 4. Check profile upload controller
    echo "\n4. Profile upload process check...\n";
    
    // Show what the correct storage structure should be
    $avatarsDir = storage_path('app/public/avatars');
    $coversDir = storage_path('app/public/covers');
    
    if (!is_dir($avatarsDir)) {
        mkdir($avatarsDir, 0755, true);
        echo "   ✅ Created avatars directory: {$avatarsDir}\n";
    }
    
    if (!is_dir($coversDir)) {
        mkdir($coversDir, 0755, true);
        echo "   ✅ Created covers directory: {$coversDir}\n";
    }
    
    // Set proper permissions
    chmod($avatarsDir, 0755);
    chmod($coversDir, 0755);
    
    echo "   ✅ Storage directories ready\n";
    
    // 5. Summary
    echo "\n5. Summary:\n";
    $cleanProfiles = Profile::whereNull('avatar')->whereNull('cover_photo')->count();
    $profilesWithData = Profile::whereNotNull('avatar')->orWhereNotNull('cover_photo')->count();
    
    echo "   Clean profiles (no images): {$cleanProfiles}\n";
    echo "   Profiles with data: {$profilesWithData}\n";
    echo "   Your profile is ready for uploads\n";
    
    echo "\n✅ Profile data cleanup complete!\n";
    echo "\nNext steps:\n";
    echo "1. Try uploading a profile image through the UI\n";
    echo "2. Check if it saves properly to the database\n";
    echo "3. Verify the file appears in storage/app/public/avatars/\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
