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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // UI настройки
            $table->enum('theme', ['light', 'dark', 'auto'])->default('light');
            $table->string('language', 5)->default('en');
            $table->boolean('compact_mode')->default(false);

            // Нотификации
            $table->boolean('email_notifications')->default(true);
            $table->boolean('push_notifications')->default(true);
            $table->json('notification_types')->nullable(); // какие типы включены

            // Обучение
            $table->enum('difficulty_preference', ['adaptive', 'easy', 'medium', 'hard'])->default('adaptive');
            $table->boolean('show_hints_automatically')->default(true);
            $table->boolean('ai_assistant_enabled')->default(true);

            // Приватность
            $table->boolean('profile_public')->default(true);
            $table->boolean('show_in_leaderboard')->default(true);
            $table->boolean('allow_peer_messages')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
