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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Content
            $table->text('content');
            $table->text('tags')->nullable(); // Array of tags (stored as JSON)

            // Location (optional but recommended)
            $table->string('location_name')->nullable();
            // location_coordinates will be added as PostGIS GEOGRAPHY type after table creation
            $table->string('geo_hash', 12)->nullable(); // For efficient proximity queries

            // Temporal metadata
            $table->timestamp('approximate_time')->nullable(); // "tonight", "this weekend", etc.
            $table->timestamp('expires_at')->default(DB::raw('NOW() + INTERVAL \'48 hours\''));

            // Mood/vibe tagging
            $table->string('mood', 50)->nullable(); // creative, social, active, chill, adventurous

            // Evolution tracking (foreign key will be added after activities table exists)
            $table->uuid('evolved_to_event_id')->nullable();
            $table->timestamp('conversion_triggered_at')->nullable();

            // Engagement metrics
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('reaction_count')->default(0);

            // Timestamps
            $table->timestampsTz();

            // Indexes
            $table->index('user_id', 'idx_posts_user');
            $table->index('geo_hash', 'idx_posts_geo_hash');
            $table->index('expires_at', 'idx_posts_expires_at');
            $table->index('created_at', 'idx_posts_created_at');
        });

        // Add PostGIS geography column for location coordinates
        DB::statement('ALTER TABLE posts ADD COLUMN location_coordinates GEOGRAPHY(POINT, 4326)');
        DB::statement('CREATE INDEX idx_posts_location ON posts USING GIST(location_coordinates)');

        // Note: Partial index for active posts would require an immutable function
        // We'll rely on the regular expires_at index for now
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
