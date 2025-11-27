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
        Schema::table('comments', function (Blueprint $table) {
            // Add polymorphic columns as nullable first
            $table->string('commentable_type')->nullable()->after('user_id');
            $table->uuid('commentable_id')->nullable()->after('commentable_type');
            
            // Add depth column for threading
            $table->unsignedTinyInteger('depth')->default(0)->after('parent_comment_id');
            
            // Add soft deletes
            $table->softDeletes();
        });
        
        // Migrate existing data: convert activity_id to polymorphic relationship
        DB::table('comments')
            ->whereNotNull('activity_id')
            ->update([
                'commentable_type' => 'App\\Models\\Activity',
                'commentable_id' => DB::raw('activity_id'),
            ]);
        
        // Now make columns non-nullable
        Schema::table('comments', function (Blueprint $table) {
            $table->string('commentable_type')->nullable(false)->change();
            $table->uuid('commentable_id')->nullable(false)->change();
            
            // Add indexes for polymorphic relationship and depth
            $table->index(['commentable_type', 'commentable_id'], 'idx_comments_commentable');
            $table->index('depth', 'idx_comments_depth');
        });
        
        // Drop old activity_id column
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropIndex('idx_comments_activity');
            $table->dropColumn('activity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Drop polymorphic columns and indexes
            $table->dropIndex('idx_comments_commentable');
            $table->dropIndex('idx_comments_depth');
            $table->dropColumn(['commentable_type', 'commentable_id', 'depth']);
            $table->dropSoftDeletes();
            
            // Re-add activity_id column
            $table->foreignUuid('activity_id')
                ->after('user_id')
                ->constrained('activities', 'id')
                ->cascadeOnDelete();
            
            // Re-add index
            $table->index('activity_id', 'idx_comments_activity');
        });
    }
};
