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
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->integer('amount'); // может быть отрицательным (штрафы)
            $table->string('reason'); // project_completed, achievement_unlocked, etc.

            // Polymorphic relation к источнику XP
            $table->morphs('source'); // source_type, source_id

            $table->text('description')->nullable();
            $table->unsignedBigInteger('balance_after'); // баланс после операции

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xp_transactions');
    }
};
