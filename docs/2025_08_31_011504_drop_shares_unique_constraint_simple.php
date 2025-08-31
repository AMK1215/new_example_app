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
        Schema::table('shares', function (Blueprint $table) {
            try {
                // Try different possible constraint names
                $table->dropUnique(['user_id', 'post_id', 'share_type']);
            } catch (\Exception $e) {
                // If that fails, try with explicit name
                try {
                    $table->dropUnique('shares_user_id_post_id_share_type_unique');
                } catch (\Exception $e2) {
                    // Use raw SQL to drop any unique constraints on these columns
                    DB::statement('ALTER TABLE shares DROP CONSTRAINT IF EXISTS shares_user_id_post_id_share_type_unique');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            // Restore the unique constraint if needed
            $table->unique(['user_id', 'post_id', 'share_type']);
        });
    }
};
