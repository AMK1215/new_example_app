<?php

/**
 * Quick script to test PostgreSQL JSON queries
 * Run this on your DigitalOcean server to verify the fixes work
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Post;
use App\Models\Profile;

echo "Testing PostgreSQL JSON queries...\n";

try {
    // Test Profile queries (these should work fine)
    echo "1. Testing Profile queries...\n";
    $profilesWithAvatar = Profile::whereNotNull('avatar')->count();
    $profilesWithCover = Profile::whereNotNull('cover_photo')->count();
    echo "   - Profiles with avatar: {$profilesWithAvatar}\n";
    echo "   - Profiles with cover photo: {$profilesWithCover}\n";
    
    // Test Post queries with the new scope
    echo "2. Testing Post queries with new scope...\n";
    $postsWithMedia = Post::withMedia()->count();
    echo "   - Posts with media: {$postsWithMedia}\n";
    
    // Test getting a few posts with their media URLs
    echo "3. Testing media URL generation...\n";
    $posts = Post::withMedia()->limit(3)->get();
    
    foreach ($posts as $post) {
        echo "   - Post {$post->id}:\n";
        echo "     Raw media: " . json_encode($post->media) . "\n";
        echo "     Media URLs: " . json_encode($post->media_urls) . "\n";
    }
    
    // Test profile URL generation
    echo "4. Testing profile URL generation...\n";
    $profiles = Profile::whereNotNull('avatar')->limit(3)->get();
    
    foreach ($profiles as $profile) {
        echo "   - Profile {$profile->id}:\n";
        echo "     Raw avatar: {$profile->avatar}\n";
        echo "     Avatar URL: {$profile->avatar_url}\n";
    }
    
    echo "\n✅ All tests passed! Database queries are working correctly.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit(1);
}
