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
        Schema::create('user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // Проекты
            $table->unsignedInteger('projects_started')->default(0);
            $table->unsignedInteger('projects_completed')->default(0);
            $table->unsignedInteger('projects_failed')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);

            // Review
            $table->unsignedInteger('reviews_given')->default(0);
            $table->unsignedInteger('reviews_received')->default(0);
            $table->decimal('average_review_score', 5, 2)->default(0);

            // Время
            $table->unsignedInteger('total_learning_hours')->default(0);
            $table->unsignedInteger('current_streak_days')->default(0);
            $table->unsignedInteger('longest_streak_days')->default(0);

            // Социальное
            $table->unsignedInteger('discussions_created')->default(0);
            $table->unsignedInteger('helpful_replies')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stats');
    }
};
