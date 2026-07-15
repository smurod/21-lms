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
        Schema::create('learning_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();

            // Временные метрики
            $table->unsignedInteger('time_to_first_attempt_minutes')->nullable();
            $table->unsignedInteger('total_time_spent_minutes')->default(0);
            $table->unsignedInteger('active_coding_time_minutes')->default(0);

            // Попытки
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('hints_requested')->default(0);
            $table->unsignedInteger('ai_interactions')->default(0);

            // Паттерны ошибок
            $table->json('common_errors')->nullable(); // ['syntax_error', 'logic_error']
            $table->json('struggling_concepts')->nullable(); // ['loops', 'recursion']

            // Прогресс
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->enum('difficulty_perception', ['too_easy', 'just_right', 'too_hard'])->nullable();

            // Даты
            $table->timestamp('started_at')->nullable();
            $table->timestamp('first_submission_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'project_id'], 'unique_user_project_analytics');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_analytics');
    }
};
