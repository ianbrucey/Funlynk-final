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
        Schema::create('group_asks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 80);
            $table->text('why');
            $table->unsignedBigInteger('target_cents');
            $table->date('deadline')->nullable();
            $table->string('payment_url', 500);
            $table->string('status', 20)->default('proposed'); // proposed, open, completed, cancelled, declined
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decline_reason', 300)->nullable();
            $table->foreignUuid('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->string('closing_note', 500)->nullable();
            $table->timestamps();

            $table->index(['group_id', 'status']);
        });

        Schema::create('group_ask_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_ask_id')->constrained('group_asks')->cascadeOnDelete();
            $table->string('kind', 10); // in, out
            $table->unsignedBigInteger('amount_cents');
            $table->string('counterparty', 80)->nullable();
            $table->string('description', 120)->nullable();
            $table->string('note', 200)->nullable();
            $table->string('item_photo_path')->nullable();
            $table->string('receipt_photo_path')->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignUuid('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 300)->nullable();
            $table->timestamps();

            $table->index(['group_ask_id', 'kind']);
        });

        Schema::create('group_ask_chips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_ask_id')->constrained('group_asks')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['group_ask_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_ask_chips');
        Schema::dropIfExists('group_ask_entries');
        Schema::dropIfExists('group_asks');
    }
};
