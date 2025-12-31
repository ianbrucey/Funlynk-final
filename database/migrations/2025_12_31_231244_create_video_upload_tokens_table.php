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
        Schema::create('video_upload_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('video_id')->constrained('videos')->cascadeOnDelete();

            // Token for verification
            $table->string('token', 64)->unique();

            // Upload destination
            $table->string('upload_path', 500);

            // Constraints
            $table->unsignedBigInteger('max_file_size_bytes')->default(104857600); // 100MB
            $table->json('allowed_mime_types')->default('["video/mp4", "video/quicktime", "video/webm"]');

            // Lifecycle
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_upload_tokens');
    }
};
