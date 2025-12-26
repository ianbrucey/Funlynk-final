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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('city', 50);
            $table->char('state_code', 2);
            $table->string('state', 50)->nullable();
            $table->string('zip', 10);
            $table->double('latitude');
            $table->double('longitude');
            $table->string('county', 50);
            $table->string('timezone', 50)->nullable();
            
            // Indexes for efficient lookups
            $table->index('city', 'idx_locations_city');
            $table->index('zip', 'idx_locations_zip');
            $table->index('state_code', 'idx_locations_state_code');
            $table->index(['city', 'state_code'], 'idx_locations_city_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

