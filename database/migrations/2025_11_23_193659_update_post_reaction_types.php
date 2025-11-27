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
        // Update existing 'join_me' reactions to 'invite_friends'
        DB::table('post_reactions')
            ->where('reaction_type', 'join_me')
            ->update(['reaction_type' => 'invite_friends']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: change 'invite_friends' back to 'join_me'
        DB::table('post_reactions')
            ->where('reaction_type', 'invite_friends')
            ->update(['reaction_type' => 'join_me']);
    }
};
