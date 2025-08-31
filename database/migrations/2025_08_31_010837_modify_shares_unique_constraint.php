<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL for PostgreSQL-specific functionality
        DB::statement('DROP INDEX IF EXISTS shares_user_id_post_id_share_type_unique');
        
        // Create a partial unique index that excludes timeline shares
        DB::statement('CREATE UNIQUE INDEX shares_unique_non_timeline 
                       ON shares (user_id, post_id, share_type) 
                       WHERE share_type != \'timeline\'');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the partial unique index
        DB::statement('DROP INDEX IF EXISTS shares_unique_non_timeline');
        
        // Restore the original unique constraint
        Schema::table('shares', function (Blueprint $table) {
            $table->unique(['user_id', 'post_id', 'share_type']);
        });
    }
};
