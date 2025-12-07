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
        Schema::table('groups', function (Blueprint $table) {
            $table->string('location_name', 255)->nullable()->after('member_count');
        });

        // Add PostGIS geography column for spatial queries
        DB::statement('ALTER TABLE groups ADD COLUMN location_coordinates geography(Point, 4326)');

        // Add spatial index for location queries
        DB::statement('CREATE INDEX groups_location_coordinates_idx ON groups USING GIST (location_coordinates)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS groups_location_coordinates_idx');
        DB::statement('ALTER TABLE groups DROP COLUMN IF EXISTS location_coordinates');

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('location_name');
        });
    }
};
