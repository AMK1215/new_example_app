<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;
use Faker\Factory as Faker;

class TestPostsSeeder extends Seeder
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

        $this->command->info('Creating test posts...');

        // Create 100-200 posts
        $postCount = $faker->numberBetween(100, 200);
        
        for ($i = 1; $i <= $postCount; $i++) {
            $user = $users->random();
            $postType = $faker->randomElement(['text', 'image', 'video', 'link']);
            
            // Generate realistic post content
            $content = $this->generatePostContent($faker, $postType);
            
            $post = Post::create([
                'user_id' => $user->id,
                'content' => $content,
                'type' => $postType,
                'media' => $postType !== 'text' ? $this->generateMedia($faker, $postType) : [],
                'is_public' => $faker->boolean(85), // 85% public posts
                'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
            ]);

            // Add likes (0-15 likes per post)
            $likeCount = $faker->numberBetween(0, 15);
            $likeUsers = $users->random(min($likeCount, $users->count()));
            
            foreach ($likeUsers as $likeUser) {
                if ($likeUser->id !== $user->id) { // Don't like own posts
                    Like::create([
                        'user_id' => $likeUser->id,
                        'post_id' => $post->id,
                        'type' => $faker->randomElement(['like', 'love', 'haha', 'wow', 'sad', 'angry']),
                        'created_at' => $faker->dateTimeBetween($post->created_at, 'now'),
                    ]);
                }
            }

            // Add comments (0-8 comments per post)
            $commentCount = $faker->numberBetween(0, 8);
            $commentUsers = $users->random(min($commentCount, $users->count()));
            
            foreach ($commentUsers as $commentUser) {
                if ($commentUser->id !== $user->id) { // Don't comment on own posts
                    Comment::create([
                        'user_id' => $commentUser->id,
                        'post_id' => $post->id,
                        'content' => $this->generateCommentContent($faker),
                        'created_at' => $faker->dateTimeBetween($post->created_at, 'now'),
                    ]);
                }
            }

            // Progress indicator
            if ($i % 25 === 0) {
                $this->command->info("Created {$i}/{$postCount} posts...");
            }
        }

        $this->command->info('✅ Successfully created ' . $postCount . ' test posts!');
        $this->command->info('❤️ Posts have realistic likes and comments');
        $this->command->info('📱 Perfect for testing the social app features');
        
        // Show statistics
        $this->command->info('');
        $this->command->info('📊 Statistics:');
        $this->command->info('   • Total posts: ' . Post::count());
        $this->command->info('   • Total likes: ' . Like::count());
        $this->command->info('   • Total comments: ' . Comment::count());
        $this->command->info('   • Average likes per post: ' . round(Like::count() / Post::count(), 1));
        $this->command->info('   • Average comments per post: ' . round(Comment::count() / Post::count(), 1));
    }

    private function generatePostContent($faker, $type)
    {
        $contents = [
            'text' => [
                'Just had the most amazing day! 🌟',
                'Can\'t believe how fast time flies...',
                'Working on some exciting new projects! 💻',
                'Beautiful weather today, perfect for a walk 🚶‍♀️',
                'Sometimes you just need to take a step back and breathe',
                'Great meeting with the team today! Collaboration is key 🤝',
                'Learning something new every day 📚',
                'Grateful for all the amazing people in my life ❤️',
                'Coffee is life ☕',
                'Weekend vibes are the best vibes ✨',
                'New goals, new beginnings 🎯',
                'Music has the power to change everything 🎵',
                'Traveling opens your mind to new possibilities ✈️',
                'Food brings people together 🍕',
                'Technology is advancing so fast! 🤖',
                'Nature is the best therapy 🌿',
                'Success is a journey, not a destination 🚀',
                'Every challenge makes us stronger 💪',
                'Creativity flows when you least expect it 🎨',
                'Friendship is one of life\'s greatest gifts 👥'
            ],
            'image' => [
                'Check out this amazing photo I took today! 📸',
                'Beautiful sunset captured on my walk 🌅',
                'New artwork I\'ve been working on 🎨',
                'Look at this incredible view! 🏔️',
                'Food photography is my new passion 🍽️',
                'Street art that caught my eye 🎭',
                'Nature\'s beauty never ceases to amaze me 🌸',
                'Architecture that tells a story 🏛️',
                'My furry friend being adorable 🐕',
                'Travel memories captured in pixels 📱'
            ],
            'video' => [
                'Quick video from my morning workout 💪',
                'Behind the scenes of my latest project 🎬',
                'Amazing street performance I witnessed 🎵',
                'My cooking adventure in the kitchen 👨‍🍳',
                'Travel vlog from my recent trip ✈️',
                'Tutorial on something I learned today 📚',
                'Fun moments with friends 🎉',
                'Nature sounds that are so relaxing 🌊',
                'My pet doing something hilarious 😂',
                'Creative process in action 🎨'
            ],
            'link' => [
                'Interesting article I just read 📰',
                'Amazing project I discovered online 🌐',
                'Useful resource for everyone 📚',
                'Inspiring story that touched my heart ❤️',
                'Great tool I found for productivity ⚡',
                'Educational content worth sharing 🎓',
                'Creative work that blew my mind 🤯',
                'Technology news that excites me 🚀',
                'Health and wellness tips 💪',
                'Environmental initiative I support 🌍'
            ]
        ];

        $typeContents = $contents[$type] ?? $contents['text'];
        $baseContent = $faker->randomElement($typeContents);
        
        // Sometimes add more text
        if ($faker->boolean(30)) {
            $baseContent .= ' ' . $faker->sentence(5, 15);
        }
        
        return $baseContent;
    }

    private function generateCommentContent($faker)
    {
        $comments = [
            'Amazing! 👏',
            'Love this! ❤️',
            'So true! 👍',
            'Beautiful! ✨',
            'Thanks for sharing! 🙏',
            'This is incredible! 🤩',
            'I can relate to this! 😊',
            'Great work! 🎉',
            'Keep it up! 💪',
            'This made my day! 🌟',
            'Absolutely! 💯',
            'Well said! 👌',
            'I needed to hear this! 🙌',
            'This is exactly what I was thinking! 🤔',
            'You\'re doing great! 🚀',
            'Love the energy! ⚡',
            'This is so inspiring! 💫',
            'Thank you for this! 🙏',
            'You\'re amazing! 🌈',
            'This resonates with me! 🎯'
        ];

        $comment = $faker->randomElement($comments);
        
        // Sometimes add more text
        if ($faker->boolean(20)) {
            $comment .= ' ' . $faker->sentence(3, 8);
        }
        
        return $comment;
    }

    private function generateMedia($faker, $type)
    {
        if ($type === 'image') {
            // Use more reliable image sources
            $imageSources = [
                'https://picsum.photos/800/600?random=' . $faker->numberBetween(1, 1000),
                'https://source.unsplash.com/800x600/?nature&sig=' . $faker->numberBetween(1, 1000),
                'https://source.unsplash.com/800x600/?landscape&sig=' . $faker->numberBetween(1, 1000),
                'https://source.unsplash.com/800x600/?city&sig=' . $faker->numberBetween(1, 1000),
            ];
            return [$faker->randomElement($imageSources)];
        } elseif ($type === 'video') {
            // For video posts, use actual video URLs or image placeholders
            $videoSources = [
                'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4',
                'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            ];
            return [$faker->randomElement($videoSources)];
        } elseif ($type === 'link') {
            return [$faker->url()];
        }
        
        return [];
    }
}
