<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('rsvp_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_payment_intent_id')->unique();
            $table->integer('amount'); // Total amount in cents
            $table->integer('platform_fee'); // Platform fee in cents
            $table->integer('host_earnings'); // Host earnings in cents
            $table->string('currency')->default('usd');
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])->default('pending');
            $table->integer('refunded_amount')->default(0);
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('activity_id');
            $table->index('rsvp_id');
            $table->index('stripe_payment_intent_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
