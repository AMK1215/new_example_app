<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Create 50 test users
        for ($i = 1; $i <= 50; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $fullName = $firstName . ' ' . $lastName;
            $email = strtolower($firstName . $lastName . $i) . '@example.com';
            $username = strtolower($firstName . $lastName . $i);

            // Create user
            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'password' => Hash::make('password'),
            ]);

            // Create profile with only existing columns
            Profile::create([
                'user_id' => $user->id,
                'username' => $username,
                'bio' => $faker->sentence(10),
                'avatar' => $faker->optional(0.3)->imageUrl(200, 200, 'people'),
                'cover_photo' => $faker->optional(0.2)->imageUrl(800, 400, 'nature'),
                'birth_date' => $faker->optional(0.7)->date('Y-m-d', '-18 years'),
                'location' => $faker->city() . ', ' . $faker->state(),
                'website' => $faker->optional(0.3)->url(),
                'social_links' => json_encode([
                    'twitter' => $faker->optional(0.4)->url(),
                    'linkedin' => $faker->optional(0.4)->url(),
                    'instagram' => $faker->optional(0.4)->url(),
                    'facebook' => $faker->optional(0.4)->url(),
                    'github' => $faker->optional(0.3)->url()
                ]),
                'is_private' => $faker->boolean(20), // 20% chance of private profile
                'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
                'updated_at' => $faker->dateTimeBetween('-1 year', 'now'),
            ]);

            // Progress indicator
            if ($i % 10 === 0) {
                $this->command->info("Created {$i}/50 users...");
            }
        }

        $this->command->info('✅ Successfully created 50 test users!');
        $this->command->info('📧 All users have password: password');
        $this->command->info('🔑 Login with any email from the list above');
        $this->command->info('👥 Users have realistic profiles with bio, location, social links, etc.');
        
        // Show sample users
        $this->command->info('');
        $this->command->info('📋 Sample test users:');
        $sampleUsers = User::with('profile')->take(5)->get();
        foreach ($sampleUsers as $user) {
            $this->command->info("   • {$user->name} ({$user->email}) - @{$user->profile->username}");
        }
        $this->command->info('   ... and 45 more users');
    }
}
