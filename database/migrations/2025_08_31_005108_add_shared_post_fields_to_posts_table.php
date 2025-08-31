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
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_shared')->default(false);
            $table->foreignId('shared_post_id')->nullable()->constrained('posts')->onDelete('cascade');
            $table->text('share_content')->nullable(); // User's comment when sharing
            
            $table->index(['is_shared', 'created_at']);
            $table->index(['shared_post_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['shared_post_id']);
            $table->dropColumn(['is_shared', 'shared_post_id', 'share_content']);
        });
    }
};
