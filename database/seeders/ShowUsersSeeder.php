<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class ShowUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📋 Test Users Created:');
        $this->command->info('=====================');
        
        $users = User::where('email', 'like', '%@example.com')->take(20)->get();
        
        foreach ($users as $user) {
            $this->command->info("• {$user->name} ({$user->email})");
        }
        
        if ($users->count() >= 20) {
            $this->command->info('... and more users');
        }
        
        $this->command->info('');
        $this->command->info('🔑 All users have password: password');
        $this->command->info('📱 You can login with any of these emails to test the app');
    }
}
