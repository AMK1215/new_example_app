<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Profile;

class TestDataSummarySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎉 SOCIAL APP TEST DATA SUMMARY 🎉');
        $this->command->info('=====================================');
        $this->command->info('');
        
        // User Statistics
        $this->command->info('👥 USERS & PROFILES:');
        $this->command->info('   • Total Users: ' . User::count());
        $this->command->info('   • Total Profiles: ' . Profile::count());
        $this->command->info('   • Users with Profiles: ' . User::has('profile')->count());
        $this->command->info('');
        
        // Content Statistics
        $this->command->info('📝 CONTENT:');
        $this->command->info('   • Total Posts: ' . Post::count());
        $this->command->info('   • Total Likes: ' . Like::count());
        $this->command->info('   • Total Comments: ' . Comment::count());
        $this->command->info('   • Average Likes per Post: ' . round(Like::count() / max(Post::count(), 1), 1));
        $this->command->info('   • Average Comments per Post: ' . round(Comment::count() / max(Post::count(), 1), 1));
        $this->command->info('');
        
        // Social Statistics
        $this->command->info('🤝 SOCIAL CONNECTIONS:');
        $this->command->info('   • Total Friendships: ' . Friendship::count());
        $this->command->info('   • Accepted Friendships: ' . Friendship::where('status', 'accepted')->count());
        $this->command->info('   • Pending Friend Requests: ' . Friendship::where('status', 'pending')->count());
        $this->command->info('   • Average Friends per User: ' . round(Friendship::where('status', 'accepted')->count() / max(User::count(), 1), 1));
        $this->command->info('');
        
        // Post Types
        $this->command->info('📱 POST TYPES:');
        $postTypes = Post::selectRaw('type, count(*) as count')->groupBy('type')->get();
        foreach ($postTypes as $type) {
            $this->command->info("   • {$type->type}: {$type->count} posts");
        }
        $this->command->info('');
        
        // Like Types
        $this->command->info('❤️ LIKE TYPES:');
        $likeTypes = Like::selectRaw('type, count(*) as count')->groupBy('type')->get();
        foreach ($likeTypes as $type) {
            $this->command->info("   • {$type->type}: {$type->count} likes");
        }
        $this->command->info('');
        
        // Sample Users for Testing
        $this->command->info('🧪 TESTING CREDENTIALS:');
        $this->command->info('   All users have password: password');
        $this->command->info('');
        $this->command->info('📧 Sample Login Emails:');
        $sampleUsers = User::where('email', 'like', '%@example.com')->take(10)->get();
        foreach ($sampleUsers as $user) {
            $this->command->info("   • {$user->email} ({$user->name})");
        }
        $this->command->info('');
        
        // Testing Instructions
        $this->command->info('🚀 READY FOR TESTING:');
        $this->command->info('   1. Start your React frontend: npm run dev');
        $this->command->info('   2. Login with any email above + password: password');
        $this->command->info('   3. Test posts, likes, comments, and real-time features');
        $this->command->info('   4. Test friend requests and social connections');
        $this->command->info('   5. Test the mobile-first Create Post modal');
        $this->command->info('');
        
        $this->command->info('🎯 Your social app is now loaded with realistic test data!');
        $this->command->info('📱 Perfect for testing mobile UX, real-time features, and social interactions');
    }
}
