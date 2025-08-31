<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Who shared
            $table->foreignId('post_id')->constrained()->onDelete('cascade'); // What post was shared
            $table->enum('share_type', ['timeline', 'story', 'message', 'copy_link'])->default('timeline');
            $table->text('content')->nullable(); // Optional comment when sharing
            $table->enum('privacy', ['public', 'friends', 'only_me'])->default('public');
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['post_id', 'created_at']);
            $table->unique(['user_id', 'post_id', 'share_type']); // Prevent duplicate shares of same type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
