<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
        $user1 = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
        ]);

        $user2 = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
        ]);

        $user3 = User::create([
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create test posts
        $post1 = Post::create([
            'user_id' => $user1->id,
            'content' => 'Hello everyone! This is my first post on our social app! 🎉',
            'is_public' => true,
        ]);

        $post2 = Post::create([
            'user_id' => $user2->id,
            'content' => 'Excited to be part of this amazing community! Looking forward to connecting with everyone.',
            'is_public' => true,
        ]);

        $post3 = Post::create([
            'user_id' => $user3->id,
            'content' => 'Just finished setting up our new social platform. The real-time features are incredible! 🚀',
            'is_public' => true,
        ]);

        // Create test likes
        Like::create([
            'user_id' => $user2->id,
            'post_id' => $post1->id,
            'type' => 'like',
        ]);

        Like::create([
            'user_id' => $user3->id,
            'post_id' => $post1->id,
            'type' => 'like',
        ]);

        Like::create([
            'user_id' => $user1->id,
            'post_id' => $post2->id,
            'type' => 'like',
        ]);

        // Create test comments
        Comment::create([
            'user_id' => $user2->id,
            'post_id' => $post1->id,
            'content' => 'Welcome to the platform, John! 👋',
        ]);

        Comment::create([
            'user_id' => $user3->id,
            'post_id' => $post1->id,
            'content' => 'Great to see you here!',
        ]);

        $this->command->info('Test data created successfully!');
        $this->command->info('Users: john@example.com, jane@example.com, bob@example.com (password: password)');
        $this->command->info('Posts and likes created for testing broadcasting.');
    }
}
