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
        Schema::create('post_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')
                ->constrained('posts')
                ->onDelete('cascade');
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('reaction_type', 20); // im_down, join_me, interested
            $table->timestampTz('created_at')->useCurrent();

            // Unique constraint: one reaction per user per post
            $table->unique(['post_id', 'user_id']);

            // Indexes
            $table->index('post_id', 'idx_post_reactions_post');
            $table->index('user_id', 'idx_post_reactions_user');
            $table->index('reaction_type', 'idx_post_reactions_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_reactions');
    }
};
