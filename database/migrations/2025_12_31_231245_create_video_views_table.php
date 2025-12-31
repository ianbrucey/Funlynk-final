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
        Schema::create('video_views', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('video_id')->constrained('videos')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            // View metadata
            $table->timestamp('viewed_at')->useCurrent();
            $table->unsignedInteger('watch_duration_seconds')->nullable();
            $table->boolean('completed')->default(false);

            // Device/context
            $table->string('device_type', 50)->nullable(); // mobile, desktop, tablet
            $table->string('referrer', 255)->nullable(); // feed, profile, direct, search

            // IP for rate limiting (hashed for privacy)
            $table->string('ip_hash', 64)->nullable();

            // Indexes
            $table->index('viewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_views');
    }
};
