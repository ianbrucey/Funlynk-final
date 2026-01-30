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
        Schema::table('groups', function (Blueprint $table) {
            // Content creation permissions
            $table->enum('post_permission', ['everyone', 'admins'])->default('everyone')->after('auto_approve_members');
            $table->enum('event_permission', ['everyone', 'admins'])->default('admins')->after('post_permission');
        });

        Schema::table('posts', function (Blueprint $table) {
            // Pinned posts for announcements
            $table->boolean('is_pinned')->default(false)->after('posted_as_group');
            $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            $table->foreignUuid('pinned_by')->nullable()->after('pinned_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['post_permission', 'event_permission']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['pinned_by']);
            $table->dropColumn(['is_pinned', 'pinned_at', 'pinned_by']);
        });
    }
};
