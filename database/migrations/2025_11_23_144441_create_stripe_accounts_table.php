<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_account_id')->unique();
            $table->boolean('onboarding_complete')->default(false);
            $table->boolean('charges_enabled')->default(false);
            $table->boolean('payouts_enabled')->default(false);
            $table->json('requirements')->nullable();
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('stripe_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_accounts');
    }
};
