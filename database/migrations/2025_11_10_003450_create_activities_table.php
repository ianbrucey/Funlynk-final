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
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('host_id')
                ->constrained('users', 'id')
                ->onDelete('cascade');

            $table->string('title');
            $table->text('description');
            $table->string('activity_type', 50); // sports, music, social, etc.
            $table->string('location_name');
            // location_coordinates will be added as PostGIS GEOGRAPHY type after table creation

            $table->timestampTz('start_time');
            $table->timestampTz('end_time')->nullable();

            $table->unsignedInteger('max_attendees')->nullable(); // NULL for unlimited
            $table->unsignedInteger('current_attendees')->default(0);

            $table->boolean('is_paid')->default(false);
            $table->unsignedInteger('price_cents')->nullable(); // Price in cents, NULL for free
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_price_id')->nullable(); // Stripe Price object ID

            $table->boolean('is_public')->default(true);
            $table->boolean('requires_approval')->default(false);

            $table->text('tags')->nullable(); // Array of tags (stored as JSON)
            $table->text('images')->nullable(); // Array of image URLs (stored as JSON)

            $table->string('status', 20)->default('active'); // active, cancelled, completed

            // Post-to-Event Conversion (E04 integration)
            $table->foreignUuid('originated_from_post_id')
                ->nullable()
                ->constrained('posts', 'id')
                ->nullOnDelete();
            $table->timestampTz('conversion_date')->nullable();

            $table->timestampsTz();

            // Indexes
            $table->index('host_id', 'idx_activities_host');
            $table->index('start_time', 'idx_activities_start_time');
            $table->index('activity_type', 'idx_activities_type');
            $table->index('status', 'idx_activities_status');
            $table->index('is_paid', 'idx_activities_is_paid');
        });

        // Add PostGIS geography column for location coordinates
        DB::statement('ALTER TABLE activities ADD COLUMN location_coordinates GEOGRAPHY(POINT, 4326) NOT NULL');
        DB::statement('CREATE INDEX idx_activities_location ON activities USING GIST(location_coordinates)');

        // Add check constraints
        DB::statement('
            ALTER TABLE activities ADD CONSTRAINT valid_price CHECK (
                (is_paid = FALSE AND price_cents IS NULL) OR
                (is_paid = TRUE AND price_cents > 0)
            )
        ');

        DB::statement('
            ALTER TABLE activities ADD CONSTRAINT valid_times CHECK (
                end_time IS NULL OR end_time > start_time
            )
        ');

        DB::statement('
            ALTER TABLE activities ADD CONSTRAINT valid_attendees CHECK (
                max_attendees IS NULL OR max_attendees > 0
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
