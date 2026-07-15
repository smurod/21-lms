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
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->default('🏆'); // emoji или путь

            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');
            $table->unsignedInteger('xp_reward')->default(0);

            // Условие получения
            $table->enum('condition_type', [
                'first_project',
                'projects_count',
                'reviews_count',
                'streak_days',
                'level_reached',
                'xp_earned',
                'perfect_score',
                'speed_completion',
                'help_others',
                'custom'
            ]);
            $table->unsignedInteger('condition_value')->nullable();
            $table->json('condition_params')->nullable(); // дополнительные параметры

            $table->boolean('is_hidden')->default(false); // секретные ачивки
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('condition_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
