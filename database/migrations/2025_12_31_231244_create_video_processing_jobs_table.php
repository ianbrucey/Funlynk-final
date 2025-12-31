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
        Schema::create('video_processing_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('video_id')->constrained('videos')->cascadeOnDelete();

            $table->string('job_type', 50); // transcode, thumbnail, analyze

            // Job status
            $table->string('status', 50)->default('queued');
            // Values: queued, processing, completed, failed, retrying

            // FFmpeg details
            $table->string('input_path', 500)->nullable();
            $table->string('output_path', 500)->nullable();
            $table->text('ffmpeg_command')->nullable();

            // Progress tracking
            $table->unsignedTinyInteger('progress_percent')->default(0);

            // Timing
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Error handling
            $table->text('error_message')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->unsignedTinyInteger('attempt_number')->default(1);

            // Worker info
            $table->string('worker_id', 100)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_processing_jobs');
    }
};
