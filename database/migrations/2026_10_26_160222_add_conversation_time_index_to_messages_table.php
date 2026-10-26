<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Speeds up "latest N messages" and "older than this message" paging in long chats.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at', 'id'], 'messages_conversation_time_index');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_time_index');
        });
    }
};
