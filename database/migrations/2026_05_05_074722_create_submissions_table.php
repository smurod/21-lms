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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            // Тип и содержимое


            $table->enum('submission_type', ['file', 'zip', 'git', 'inline', 'multiple_files']);
            $table->string('git_url')->nullable();
            $table->string('git_commit_hash')->nullable();
            $table->longText('code_content')->nullable(); // для inline

            // Статусы
            $table->enum('status', [
                'pending',      // ждет обработки
                'queued',       // в очереди на тесты
                'testing',      // выполняются тесты
                'tested',       // тесты завершены
                'in_review',    // на peer review
                'reviewed',     // проверено
                'passed',       // принято
                'failed',       // провалено
                'resubmitted',  // переотправлено
                'in_progress'   // в работе (создаётся SubscriptionController; merged from 2026_07_09_000001)
            ])->default('pending');

            // Результаты автотестов
            $table->unsignedInteger('tests_passed')->default(0);
            $table->unsignedInteger('tests_total')->default(0);
            $table->decimal('test_score', 5, 2)->default(0); // 0-100
            $table->longText('test_output')->nullable();
            $table->json('test_details')->nullable(); // детали каждого теста

            // Результаты peer review
            $table->decimal('review_score', 5, 2)->nullable(); // среднее от проверяющих
            $table->unsignedInteger('reviews_received')->default(0);

            // Финальная оценка
            $table->decimal('final_score', 5, 2)->nullable(); // взвешенная (тесты + review)
            $table->boolean('is_plagiarized')->default(false);

            // Временные метки
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('tested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Попытка номер
            $table->unsignedInteger('attempt_number')->default(1);

            // Execution logs
            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->unsignedInteger('memory_used_mb')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'project_id']);
            $table->index('status');
            $table->index('submitted_at');
            $table->index(['project_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
