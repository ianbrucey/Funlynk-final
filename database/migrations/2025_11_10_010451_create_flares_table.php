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
        Schema::create('flares', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description');
            $table->string('activity_type', 50);
            $table->string('location_name')->nullable();
            // location_coordinates will be added as PostGIS GEOGRAPHY type after table creation
            $table->timestampTz('preferred_time')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->text('tags')->nullable(); // Array of tags (stored as JSON)
            $table->string('status', 20)->default('active'); // active, fulfilled, expired
            $table->timestampTz('expires_at')->nullable();

            $table->foreignUuid('converted_activity_id')
                ->nullable()
                ->constrained('activities', 'id')
                ->nullOnDelete();

            $table->timestampsTz();

            // Indexes
            $table->index('user_id', 'idx_flares_user');
            $table->index('activity_type', 'idx_flares_type');
            $table->index('status', 'idx_flares_status');
        });

        // Add PostGIS geography column for location coordinates
        DB::statement('ALTER TABLE flares ADD COLUMN location_coordinates GEOGRAPHY(POINT, 4326)');
        DB::statement('CREATE INDEX idx_flares_location ON flares USING GIST(location_coordinates)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flares');
    }
};
