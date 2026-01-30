<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add the new payment_type column
        Schema::table('activities', function (Blueprint $table) {
            $table->string('payment_type', 20)->default('free')->after('current_attendees');
        });

        // Step 2: Migrate existing data from is_paid to payment_type
        DB::statement("UPDATE activities SET payment_type = CASE WHEN is_paid = true THEN 'online' ELSE 'free' END");

        // Step 3: Drop the old constraint
        DB::statement('ALTER TABLE activities DROP CONSTRAINT IF EXISTS valid_price');

        // Step 4: Drop the is_paid column
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('is_paid');
        });

        // Step 5: Add the new constraint that supports all three payment types
        DB::statement("
            ALTER TABLE activities ADD CONSTRAINT valid_price CHECK (
                (payment_type = 'free' AND price_cents IS NULL) OR
                (payment_type IN ('online', 'at_door') AND price_cents > 0)
            )
        ");

        // Step 6: Add index for payment_type
        Schema::table('activities', function (Blueprint $table) {
            $table->index('payment_type', 'idx_activities_payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Add is_paid column back
        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('current_attendees');
        });

        // Step 2: Migrate data back
        DB::statement("UPDATE activities SET is_paid = CASE WHEN payment_type = 'online' THEN true ELSE false END");

        // Step 3: Drop the new constraint
        DB::statement('ALTER TABLE activities DROP CONSTRAINT IF EXISTS valid_price');

        // Step 4: Drop the index
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_payment_type');
        });

        // Step 5: Drop payment_type column
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });

        // Step 6: Restore original constraint
        DB::statement("
            ALTER TABLE activities ADD CONSTRAINT valid_price CHECK (
                (is_paid = FALSE AND price_cents IS NULL) OR
                (is_paid = TRUE AND price_cents > 0)
            )
        ");

        // Step 7: Add back the is_paid index
        Schema::table('activities', function (Blueprint $table) {
            $table->index('is_paid', 'idx_activities_is_paid');
        });
    }
};
