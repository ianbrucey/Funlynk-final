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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('username', 50)->unique();
            $table->string('display_name', 100);
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->text('bio')->nullable();
            $table->text('profile_image_url')->nullable();
            $table->string('location_name')->nullable();
            $table->json('location_coordinates')->nullable();
            $table->json('interests')->nullable();
            $table->boolean('is_host')->default(false);
            $table->string('stripe_account_id')->nullable();
            $table->boolean('stripe_onboarding_complete')->default(false);
            $table->unsignedInteger('follower_count')->default(0);
            $table->unsignedInteger('following_count')->default(0);
            $table->unsignedInteger('activity_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('privacy_level', 20)->default('public');
            $table->rememberToken();
            $table->timestampsTz();

            $table->index('username', 'idx_users_username');
            $table->index('stripe_account_id', 'idx_users_stripe_account');
            $table->index('is_host', 'idx_users_is_host');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')
                ->nullable()
                ->index()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
