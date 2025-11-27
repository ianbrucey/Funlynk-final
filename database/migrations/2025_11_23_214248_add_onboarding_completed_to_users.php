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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable()->after('email_verified_at');
        });

        // Backfill for existing users with location - they've effectively completed onboarding
        DB::statement(
            "UPDATE users SET onboarding_completed_at = created_at WHERE location_coordinates IS NOT NULL AND onboarding_completed_at IS NULL"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed_at');
        });
    }
};
