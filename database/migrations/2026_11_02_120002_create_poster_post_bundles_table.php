<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poster_post_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('caption');
            $table->string('source')->default('drive'); // drive|manual|api
            $table->string('drive_folder_id')->nullable();
            // Drive file ids consumed into this bundle — the intake dedupe key.
            $table->json('drive_file_ids')->nullable();
            // [{kind: image|video, name, mime, drive_id, local_path}]
            $table->json('assets')->nullable();
            $table->string('state')->default('draft'); // PostBundleState
            $table->timestamp('submitted_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posting_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poster_post_bundles');
    }
};
