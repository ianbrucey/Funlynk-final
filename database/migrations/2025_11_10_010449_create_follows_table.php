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
        Schema::create('follows', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('follower_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->foreignUuid('following_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->timestampTz('created_at')->useCurrent();

            // Constraints
            $table->unique(['follower_id', 'following_id']);

            // Indexes
            $table->index('follower_id', 'idx_follows_follower');
            $table->index('following_id', 'idx_follows_following');
        });

        // Add check constraint to prevent self-following
        DB::statement('ALTER TABLE follows ADD CONSTRAINT no_self_follow CHECK (follower_id != following_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
