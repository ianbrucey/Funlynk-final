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
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->index(); // private, group, public
            $table->nullableUuidMorphs('conversationable'); // Post, Activity
            $table->timestamp('last_message_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member'); // member, admin
            $table->boolean('is_muted')->default(false);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete(); // Sender
            $table->uuid('reply_to_message_id')->nullable();
            $table->text('body')->nullable();
            $table->string('type')->default('text'); // text, image, system
            $table->string('attachment_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Add self-referencing foreign key after table creation
        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('reply_to_message_id')->references('id')->on('messages')->nullOnDelete();
        });

        Schema::create('message_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('message_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('reaction'); // 👍, ❤️, etc.
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'reaction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
