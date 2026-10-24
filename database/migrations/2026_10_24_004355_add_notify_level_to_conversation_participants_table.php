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
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->string('notify_level', 20)->default('default');
        });

        DB::table('conversation_participants')->where('is_muted', true)->update(['notify_level' => 'muted']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn('notify_level');
        });
    }
};
