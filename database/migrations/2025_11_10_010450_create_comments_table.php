<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('activity_id')
                ->constrained('activities', 'id')
                ->cascadeOnDelete();

            $table->foreignUuid('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->uuid('parent_comment_id')->nullable();

            $table->text('content');
            $table->boolean('is_edited')->default(false);
            $table->boolean('is_deleted')->default(false);

            $table->timestampsTz();

            // Indexes
            $table->index('activity_id', 'idx_comments_activity');
            $table->index('user_id', 'idx_comments_user');
            $table->index('parent_comment_id', 'idx_comments_parent');
            $table->index('created_at', 'idx_comments_created');
        });

        // Add self-referencing foreign key after table creation
        Schema::table('comments', function (Blueprint $table) {
            $table->foreign('parent_comment_id')
                ->references('id')
                ->on('comments')
                ->cascadeOnDelete();
        });

        // Add check constraint to ensure content is not empty
        DB::statement('ALTER TABLE comments ADD CONSTRAINT content_not_empty CHECK (LENGTH(TRIM(content)) > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
