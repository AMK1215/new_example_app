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
            // Add edited_at field if it doesn't exist
            if (!Schema::hasColumn('comments', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('is_edited');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            if (Schema::hasColumn('comments', 'edited_at')) {
                $table->dropColumn('edited_at');
            }
        });
    }
};
