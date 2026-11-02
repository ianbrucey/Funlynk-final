<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poster_bundle_platform_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('poster_post_bundles')->cascadeOnDelete();
            $table->foreignId('platform_account_id')->nullable()->constrained('poster_platform_accounts')->nullOnDelete();
            $table->string('platform'); // Platform enum value
            $table->text('adapted_caption')->nullable();
            // [{kind, path, format, public_url}] — output of MediaNormalizer.
            $table->json('normalized_assets')->nullable();
            $table->string('status')->default('pending'); // PlatformPostStatus
            $table->string('platform_post_id')->nullable();
            $table->string('platform_post_url')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();

            $table->unique(['bundle_id', 'platform']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poster_bundle_platform_posts');
    }
};
