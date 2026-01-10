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
        Schema::create('social_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('activity_id')
                ->constrained('activities', 'id')
                ->onDelete('cascade');
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users', 'id')
                ->onDelete('set null');
            $table->string('platform', 50); // 'instagram', 'facebook', 'twitter', 'whatsapp', 'copy_link'
            $table->string('referral_code', 20)->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('conversions')->default(0); // RSVPs attributed to this share
            $table->timestamps();

            // Indexes
            $table->index('activity_id');
            $table->index('user_id');
            $table->index('platform');
            $table->index('referral_code');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_shares');
    }
};
