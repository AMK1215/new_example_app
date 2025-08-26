<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;

class TestConversationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users
        $users = User::with('profile')->take(5)->get();
        
        if ($users->count() < 2) {
            $this->command->info('Need at least 2 users to create conversations');
            return;
        }

        // Create a private conversation between first two users
        $conversation1 = Conversation::create([
            'type' => 'private',
        ]);

        $conversation1->users()->attach([
            $users[0]->id => ['last_read_at' => now()],
            $users[1]->id => ['last_read_at' => now()]
        ]);

        // Add some messages
        Message::create([
            'conversation_id' => $conversation1->id,
            'user_id' => $users[0]->id,
            'content' => 'Hey! How are you doing?',
            'type' => 'text',
        ]);

        Message::create([
            'conversation_id' => $conversation1->id,
            'user_id' => $users[1]->id,
            'content' => 'I\'m doing great! Thanks for asking. How about you?',
            'type' => 'text',
        ]);

        Message::create([
            'conversation_id' => $conversation1->id,
            'user_id' => $users[0]->id,
            'content' => 'Pretty good! Just working on some projects.',
            'type' => 'text',
        ]);

        // Create a group conversation
        $conversation2 = Conversation::create([
            'type' => 'group',
            'name' => 'Project Team',
        ]);

        $conversation2->users()->attach([
            $users[0]->id => ['last_read_at' => now()],
            $users[1]->id => ['last_read_at' => now()],
            $users[2]->id => ['last_read_at' => now()]
        ]);

        // Add some group messages
        Message::create([
            'conversation_id' => $conversation2->id,
            'user_id' => $users[0]->id,
            'content' => 'Welcome everyone to the project team!',
            'type' => 'text',
        ]);

        Message::create([
            'conversation_id' => $conversation2->id,
            'user_id' => $users[1]->id,
            'content' => 'Thanks! Excited to work with you all.',
            'type' => 'text',
        ]);

        $this->command->info('Created 2 test conversations with messages');
    }
}
