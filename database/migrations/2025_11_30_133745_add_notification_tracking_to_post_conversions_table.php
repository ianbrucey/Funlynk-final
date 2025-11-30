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
        Schema::table('post_conversions', function (Blueprint $table) {
            $table->integer('interested_users_notified')->default(0)->after('event_id');
            $table->integer('invited_users_notified')->default(0)->after('interested_users_notified');
            $table->timestamp('notification_sent_at')->nullable()->after('invited_users_notified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_conversions', function (Blueprint $table) {
            $table->dropColumn(['interested_users_notified', 'invited_users_notified', 'notification_sent_at']);
        });
    }
};
