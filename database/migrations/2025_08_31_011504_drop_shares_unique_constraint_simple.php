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
            // Drop the existing unique constraint that's preventing multiple timeline shares
            $table->dropUnique(['user_id', 'post_id', 'share_type']);
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
