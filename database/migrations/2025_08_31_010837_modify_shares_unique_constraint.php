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
        Schema::table('shares', function (Blueprint $table) {
            // Drop the existing unique constraint
            $table->dropUnique(['user_id', 'post_id', 'share_type']);
            
            // Add new unique constraint that excludes timeline shares
            // This allows multiple timeline shares but prevents duplicates for other types
            $table->unique(['user_id', 'post_id', 'share_type'], 'shares_unique_non_timeline')
                  ->where('share_type', '!=', 'timeline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            // Drop the conditional unique constraint
            $table->dropUnique('shares_unique_non_timeline');
            
            // Restore the original unique constraint
            $table->unique(['user_id', 'post_id', 'share_type']);
        });
    }
};
