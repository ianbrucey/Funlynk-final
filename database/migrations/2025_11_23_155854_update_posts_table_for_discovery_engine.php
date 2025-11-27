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
        Schema::table('posts', function (Blueprint $table) {
            // Rename/restructure content fields
            $table->renameColumn('content', 'title');
            $table->text('description')->nullable()->after('title');
            
            // Add new fields
            $table->string('time_hint')->nullable()->after('approximate_time');
            $table->enum('status', ['active', 'expired', 'converted'])->default('active')->after('expires_at');
            $table->timestamp('conversion_suggested_at')->nullable()->after('conversion_triggered_at');
            
            // Rename columns
            $table->renameColumn('evolved_to_event_id', 'converted_to_activity_id');
            
            // Add index on status
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->renameColumn('title', 'content');
            $table->dropColumn(['description', 'time_hint', 'status', 'conversion_suggested_at']);
            $table->renameColumn('converted_to_activity_id', 'evolved_to_event_id');
            $table->dropIndex(['status']);
        });
    }
};
