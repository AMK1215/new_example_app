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
        // Find and drop all unique constraints on shares table that involve user_id, post_id, share_type
        $constraints = DB::select("
            SELECT constraint_name 
            FROM information_schema.table_constraints 
            WHERE table_name = 'shares' 
            AND constraint_type = 'UNIQUE'
            AND constraint_name LIKE '%user_id%'
        ");
        
        foreach ($constraints as $constraint) {
            try {
                DB::statement("ALTER TABLE shares DROP CONSTRAINT {$constraint->constraint_name}");
                echo "Dropped constraint: {$constraint->constraint_name}\n";
            } catch (\Exception $e) {
                echo "Could not drop constraint {$constraint->constraint_name}: " . $e->getMessage() . "\n";
            }
        }
        
        // Also try to drop by common naming patterns
        $possibleNames = [
            'shares_user_id_post_id_share_type_unique',
            'shares_unique_non_timeline',
            'shares_user_id_post_id_share_type_key'
        ];
        
        foreach ($possibleNames as $name) {
            try {
                DB::statement("ALTER TABLE shares DROP CONSTRAINT IF EXISTS {$name}");
            } catch (\Exception $e) {
                // Ignore errors
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore a basic unique constraint if needed
        Schema::table('shares', function (Blueprint $table) {
            try {
                $table->unique(['user_id', 'post_id', 'share_type']);
            } catch (\Exception $e) {
                // Ignore if constraint already exists
            }
        });
    }
};
