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
        Schema::table('comments', function (Blueprint $table) {
            // Add edited_at field
            $table->timestamp('edited_at')->nullable()->after('is_edited');
            
            // Remove media field if it exists
            if (Schema::hasColumn('comments', 'media')) {
                $table->dropColumn('media');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Remove edited_at field
            $table->dropColumn('edited_at');
            
            // Add back media field
            $table->json('media')->nullable();
        });
    }
};
