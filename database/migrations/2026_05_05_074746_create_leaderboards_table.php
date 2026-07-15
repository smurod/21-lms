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
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->enum('period', ['daily', 'weekly', 'monthly', 'all_time'])->default('all_time');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            $table->unsignedBigInteger('xp_earned')->default(0);
            $table->unsignedInteger('projects_completed')->default(0);
            $table->unsignedInteger('reviews_given')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);

            $table->unsignedInteger('rank')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'period', 'period_start'], 'unique_user_period_leaderboard');
            $table->index(['period', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboards');
    }
};
