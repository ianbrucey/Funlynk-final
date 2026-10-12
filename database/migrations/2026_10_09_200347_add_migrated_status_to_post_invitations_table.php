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
        DB::statement('ALTER TABLE post_invitations DROP CONSTRAINT IF EXISTS post_invitations_status_check');
        DB::statement("
            ALTER TABLE post_invitations ADD CONSTRAINT post_invitations_status_check
            CHECK (status IN ('pending', 'viewed', 'reacted', 'ignored', 'migrated'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE post_invitations SET status = 'pending' WHERE status = 'migrated'");
        DB::statement('ALTER TABLE post_invitations DROP CONSTRAINT IF EXISTS post_invitations_status_check');
        DB::statement("
            ALTER TABLE post_invitations ADD CONSTRAINT post_invitations_status_check
            CHECK (status IN ('pending', 'viewed', 'reacted', 'ignored'))
        ");
    }
};
