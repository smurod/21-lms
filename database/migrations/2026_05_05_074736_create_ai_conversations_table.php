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
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('submission_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('conversation_type', ['hint', 'debug', 'explain', 'review'])->default('hint');

            $table->longText('user_message');
            $table->longText('ai_response');
            $table->longText('code_context')->nullable(); // код студента

            $table->json('metadata')->nullable(); // модель AI, tokens, etc.

            $table->boolean('was_helpful')->nullable(); // фидбек от студента
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('submission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
