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
            $table->string('emoji')->nullable()->after('avatar_url');
            $table->string('schedule_text')->nullable()->after('description');
            $table->string('meetup_label')->default('Session')->after('schedule_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['emoji', 'schedule_text', 'meetup_label']);
        });
    }
};
