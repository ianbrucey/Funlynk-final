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
        Schema::create('guest_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('activity_id')
                ->constrained('activities', 'id')
                ->onDelete('cascade');
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users', 'id')
                ->onDelete('cascade');
            $table->string('guest_token', 64); // Cookie-based identifier
            $table->string('source', 50)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('activity_id');
            $table->index('guest_token');
            $table->index('user_id');
            $table->index('created_at');
            $table->unique(['activity_id', 'guest_token'], 'unique_activity_guest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_bookmarks');
    }
};
