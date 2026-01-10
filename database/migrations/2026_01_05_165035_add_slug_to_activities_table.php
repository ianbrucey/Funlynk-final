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
        Schema::table('activities', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
            $table->string('location_address')->nullable()->after('location_name');
            $table->text('what_to_bring')->nullable()->after('description');
            $table->text('additional_notes')->nullable()->after('what_to_bring');
            $table->string('cover_image_url')->nullable()->after('images');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'location_address', 'what_to_bring', 'additional_notes', 'cover_image_url']);
        });
    }
};
