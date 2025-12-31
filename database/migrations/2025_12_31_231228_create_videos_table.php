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
        Schema::create('videos', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic ownership (Post, Activity, User, Group)
            $table->string('videoable_type');
            $table->uuid('videoable_id');
            $table->index(['videoable_type', 'videoable_id']);

            // Uploader (always a user)
            $table->foreignUuid('uploader_id')->constrained('users')->cascadeOnDelete();

            // Storage paths
            $table->string('storage_provider', 50)->default('s3');
            $table->string('original_path', 500);
            $table->string('hls_path', 500)->nullable();
            $table->string('thumbnail_path', 500)->nullable();

            // Video metadata (extracted after upload)
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('aspect_ratio', 20)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('mime_type', 100)->nullable();

            // Processing status
            $table->string('status', 50)->default('pending_upload');
            // Values: pending_upload, uploading, uploaded, processing, ready, failed, deleted
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->unsignedInteger('processing_attempts')->default(0);

            // HLS variants available
            $table->json('available_qualities')->default('[]');

            // Content moderation
            $table->string('moderation_status', 50)->default('pending');
            $table->text('moderation_notes')->nullable();

            // Engagement metrics (denormalized for feed performance)
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);

            // Visibility
            $table->string('visibility', 50)->default('public');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('moderation_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
