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
        Schema::table('likes', function (Blueprint $table) {
            // Make post_id nullable and add comment_id
            $table->foreignId('post_id')->nullable()->change();
            $table->foreignId('comment_id')->nullable()->after('post_id');
            
            // Add foreign key constraint for comment_id
            $table->foreign('comment_id')->references('id')->on('comments')->onDelete('cascade');
            
            // Update unique constraint to allow either post_id or comment_id
            $table->dropUnique(['user_id', 'post_id']);
            $table->unique(['user_id', 'post_id', 'comment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            // Remove comment_id
            $table->dropForeign(['comment_id']);
            $table->dropColumn('comment_id');
            
            // Make post_id required again
            $table->foreignId('post_id')->nullable(false)->change();
            
            // Restore original unique constraint
            $table->dropUnique(['user_id', 'post_id', 'comment_id']);
            $table->unique(['user_id', 'post_id']);
        });
    }
};
