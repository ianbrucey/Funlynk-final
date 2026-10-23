<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->where('privacy_level', 'friends')->update(['privacy_level' => 'followers']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data rename only; 'followers' is the accepted value going forward.
    }
};
