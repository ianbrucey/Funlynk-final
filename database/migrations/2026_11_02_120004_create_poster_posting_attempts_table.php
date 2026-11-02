<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poster_posting_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_platform_post_id')->constrained('poster_bundle_platform_posts')->cascadeOnDelete();
            $table->string('platform'); // denormalized for reporting
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('status'); // started|succeeded|failed
            $table->text('error')->nullable();
            $table->json('request_meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['bundle_platform_post_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poster_posting_attempts');
    }
};
