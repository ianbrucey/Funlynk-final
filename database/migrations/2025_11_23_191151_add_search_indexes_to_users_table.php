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
        // Add GIN index for full-text search on display_name and username
        \DB::statement(
            "CREATE INDEX users_search_idx ON users 
             USING gin(to_tsvector('english', 
                coalesce(display_name, '') || ' ' || coalesce(username, '')
             ))"
        );

        // Add GIN index for interests JSON array (cast text to jsonb)
        \DB::statement(
            'CREATE INDEX users_interests_idx ON users USING gin((interests::jsonb) jsonb_path_ops)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement('DROP INDEX IF EXISTS users_search_idx');
        \DB::statement('DROP INDEX IF EXISTS users_interests_idx');
    }
};
