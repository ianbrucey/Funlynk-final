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
        Schema::create('post_conversions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')
                ->constrained('posts')
                ->onDelete('cascade');
            $table->foreignUuid('event_id')
                ->constrained('activities', 'id')
                ->onDelete('cascade');

            $table->string('trigger_type', 20); // manual, automatic, threshold
            $table->unsignedInteger('reactions_at_conversion')->default(0);
            $table->unsignedInteger('comments_at_conversion')->default(0);
            $table->unsignedInteger('views_at_conversion')->default(0);
            $table->float('rsvp_conversion_rate')->nullable(); // % of reactors who RSVP'd

            $table->timestampTz('created_at')->useCurrent();

            // Indexes
            $table->index('post_id', 'idx_post_conversions_post');
            $table->index('event_id', 'idx_post_conversions_event');
            $table->index('trigger_type', 'idx_post_conversions_trigger');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_conversions');
    }
};
