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
        Schema::create('event_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('activity_id')
                ->constrained('activities', 'id')
                ->onDelete('cascade');
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users', 'id')
                ->onDelete('set null');
            $table->string('email');
            $table->string('source', 50)->nullable(); // 'instagram', 'facebook', 'twitter', 'direct'
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->timestamp('converted_to_rsvp_at')->nullable();
            $table->foreignUuid('rsvp_id')
                ->nullable()
                ->constrained('rsvps', 'id')
                ->onDelete('set null');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('activity_id');
            $table->index('email');
            $table->index('user_id');
            $table->index('converted_to_rsvp_at');
            $table->index('source');
            $table->unique(['activity_id', 'email'], 'unique_activity_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_interests');
    }
};
