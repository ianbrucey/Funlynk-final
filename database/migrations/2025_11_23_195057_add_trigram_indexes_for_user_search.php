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
        // Enable pg_trgm extension for trigram-based ILIKE optimization
        \DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Drop the old full-text search index (we're switching to ILIKE)
        \DB::statement('DROP INDEX IF EXISTS users_search_idx');

        // Add GIN trigram indexes for fast ILIKE queries on display_name and username
        \DB::statement(
            'CREATE INDEX users_display_name_trgm_idx ON users USING gin (display_name gin_trgm_ops)'
        );
        \DB::statement(
            'CREATE INDEX users_username_trgm_idx ON users USING gin (username gin_trgm_ops)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement('DROP INDEX IF EXISTS users_display_name_trgm_idx');
        \DB::statement('DROP INDEX IF EXISTS users_username_trgm_idx');

        // Restore the full-text search index
        \DB::statement(
            "CREATE INDEX users_search_idx ON users 
             USING gin(to_tsvector('english', 
                coalesce(display_name, '') || ' ' || coalesce(username, '')
             ))"
        );
    }
};
