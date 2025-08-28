<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Profile;

echo "=== Profile Check ===\n\n";

$users = User::with('profile')->limit(5)->get();

echo "Total users: " . User::count() . "\n";
echo "Users with profiles: " . User::whereHas('profile')->count() . "\n";
echo "Users without profiles: " . User::whereDoesntHave('profile')->count() . "\n\n";

echo "First 5 users:\n";
foreach ($users as $user) {
    echo "User #{$user->id}: {$user->name} - ";
    if ($user->profile) {
        echo "Profile exists (ID: {$user->profile->id})\n";
        if ($user->profile->avatar) {
            echo "  Avatar: {$user->profile->avatar}\n";
        }
        if ($user->profile->cover_photo) {
            echo "  Cover: {$user->profile->cover_photo}\n";
        }
    } else {
        echo "NO PROFILE\n";
    }
}

// Check if any profiles have missing users
$orphanedProfiles = Profile::whereDoesntHave('user')->count();
echo "\nOrphaned profiles (no user): $orphanedProfiles\n";

?>
