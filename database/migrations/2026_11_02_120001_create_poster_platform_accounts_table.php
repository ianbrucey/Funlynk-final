<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poster_platform_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // Platform enum value
            $table->string('label')->nullable();
            $table->string('handle')->nullable();
            // Platform-side id of the posting target (Page id, IG user id,
            // Threads user id, YouTube channel id, TikTok open id, X user id).
            $table->string('external_id')->nullable();
            $table->json('external_meta')->nullable();
            // Encrypted at rest (see model casts). Nullable: Phase 1 ships
            // with no accounts or credentials at all.
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->string('status')->default('pending_setup'); // pending_setup|connected|disabled
            $table->timestamps();

            $table->index(['platform', 'status']);
            $table->index(['platform', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poster_platform_accounts');
    }
};
