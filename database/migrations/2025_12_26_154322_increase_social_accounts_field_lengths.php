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
        Schema::table('social_accounts', function (Blueprint $table) {
            // Change varchar(255) to text for fields that can be very long
            $table->text('provider_email')->nullable()->change();
            $table->text('avatar_url')->nullable()->change();
            $table->text('name')->nullable()->change();
            $table->text('nickname')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->string('provider_email')->nullable()->change();
            $table->string('avatar_url')->nullable()->change();
            $table->string('name')->nullable()->change();
            $table->string('nickname')->nullable()->change();
        });
    }
};
