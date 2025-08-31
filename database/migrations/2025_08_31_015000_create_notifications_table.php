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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // friend_request, post_like, post_comment, post_share, mention, etc.
            $table->text('data'); // JSON data for notification details
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Who receives the notification
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('cascade'); // Who triggered the notification
            $table->morphs('notifiable'); // Related model (post, comment, etc.)
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'read'], 'notifications_user_read_idx');
            $table->index(['user_id', 'created_at'], 'notifications_user_created_idx');
            $table->index(['notifiable_type', 'notifiable_id'], 'notifications_notifiable_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
