<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Friendship;
use Faker\Factory as Faker;

class TestFriendshipsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get all users
        $users = User::all();
        
        if ($users->count() === 0) {
            $this->command->error('No users found. Please run TestUsersSeeder first.');
            return;
        }

        $this->command->info('Creating test friendships...');

        // Create friendships (each user will have 5-15 friends)
        $friendshipCount = 0;
        $maxFriendships = $users->count() * 10; // Average 10 friendships per user
        
        foreach ($users as $user) {
            // Each user will have 5-15 friends
            $friendCount = $faker->numberBetween(5, 15);
            
            // Get random users to be friends with
            $potentialFriends = $users->where('id', '!=', $user->id)->random(min($friendCount, $users->count() - 1));
            
            foreach ($potentialFriends as $friend) {
                // Check if friendship already exists
                $existingFriendship = Friendship::where(function($query) use ($user, $friend) {
                    $query->where('user_id', $user->id)
                          ->where('friend_id', $friend->id);
                })->orWhere(function($query) use ($user, $friend) {
                    $query->where('user_id', $friend->id)
                          ->where('friend_id', $user->id);
                })->first();
                
                if (!$existingFriendship) {
                    // Randomly decide friendship status
                    $status = $faker->randomElement(['accepted', 'pending', 'accepted']);
                    
                    Friendship::create([
                        'user_id' => $user->id,
                        'friend_id' => $friend->id,
                        'status' => $status,
                        'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                        'updated_at' => $faker->dateTimeBetween('-1 year', 'now'),
                    ]);
                    
                    $friendshipCount++;
                    
                    // If accepted, also create the reverse friendship
                    if ($status === 'accepted') {
                        Friendship::create([
                            'user_id' => $friend->id,
                            'friend_id' => $user->id,
                            'status' => 'accepted',
                            'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                            'updated_at' => $faker->dateTimeBetween('-1 year', 'now'),
                        ]);
                        $friendshipCount++;
                    }
                }
            }
            
            // Progress indicator
            if ($user->id % 10 === 0) {
                $this->command->info("Processed user {$user->id}/{$users->count()}...");
            }
        }

        $this->command->info('✅ Successfully created ' . $friendshipCount . ' friendships!');
        $this->command->info('🤝 Users now have realistic social connections');
        $this->command->info('📱 Perfect for testing friend-related features');
        
        // Show statistics
        $this->command->info('');
        $this->command->info('📊 Friendship Statistics:');
        $this->command->info('   • Total friendships: ' . Friendship::count());
        $this->command->info('   • Accepted friendships: ' . Friendship::where('status', 'accepted')->count());
        $this->command->info('   • Pending friendships: ' . Friendship::where('status', 'pending')->count());
        $this->command->info('   • Average friends per user: ' . round(Friendship::where('status', 'accepted')->count() / $users->count(), 1));
        
        // Show some sample friendships
        $this->command->info('');
        $this->command->info('🤝 Sample Friendships:');
        $sampleFriendships = Friendship::with(['user', 'friend'])->where('status', 'accepted')->take(5)->get();
        foreach ($sampleFriendships as $friendship) {
            $this->command->info("   • {$friendship->user->name} ↔ {$friendship->friend->name}");
        }
    }
}
