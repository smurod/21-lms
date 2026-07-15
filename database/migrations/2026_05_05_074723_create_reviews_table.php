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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');

            // Оценка
            $table->unsignedTinyInteger('score'); // 0-100
            $table->longText('feedback')->nullable();
            $table->longText('private_notes')->nullable(); // для менторов

            // Чек-лист (JSON)
            $table->json('checklist_data')->nullable();

            // Метрики проверяющего
            $table->unsignedInteger('time_spent_minutes')->nullable();
            $table->decimal('confidence_score', 3, 2)->default(1.00); // вес этой проверки 0-1

            // Типы проверки
            $table->boolean('is_mentor_review')->default(false);
            $table->boolean('is_auto_assigned')->default(true);
            $table->boolean('is_appeal_review')->default(false); // проверка после апелляции

            // Статус
            $table->enum('status', ['pending', 'in_progress', 'completed', 'disputed'])->default('pending');

            // Валидация проверки (для обучения проверяющих)
            $table->boolean('is_calibration')->default(false); // эталонная для обучения
            $table->decimal('accuracy_score', 3, 2)->nullable(); // насколько совпало с эталоном

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'reviewer_id'], 'unique_submission_reviewer');
            $table->index('reviewer_id');
            $table->index(['submission_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
