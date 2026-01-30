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
        Schema::create('recurring_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();

            // Event template details
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location_name')->nullable();
            $table->geography('location_coordinates', subtype: 'point', srid: 4326)->nullable();

            // Recurrence pattern
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->unsignedTinyInteger('interval')->default(1); // Every X weeks/days/months
            $table->json('days_of_week')->nullable(); // ["monday", "wednesday", "friday"]
            $table->unsignedTinyInteger('day_of_month')->nullable(); // For monthly: 1-31

            // Time settings
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable(); // Alternative to end_time

            // Generation settings
            $table->unsignedTinyInteger('generate_weeks_ahead')->default(4);
            $table->date('last_generated_until')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('paused_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['group_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_schedules');
    }
};
