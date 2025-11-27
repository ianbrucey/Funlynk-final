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
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            
            $table->foreignUuid('comment_id')
                ->constrained('comments', 'id')
                ->cascadeOnDelete();
            
            $table->string('reaction_type')->default('like'); // like, helpful, funny
            
            $table->timestampsTz();
            
            // Prevent duplicate reactions from same user on same comment
            $table->unique(['user_id', 'comment_id'], 'unique_user_comment_reaction');
            
            // Indexes
            $table->index('comment_id', 'idx_comment_reactions_comment');
            $table->index('user_id', 'idx_comment_reactions_user');
            $table->index('reaction_type', 'idx_comment_reactions_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
    }
};
