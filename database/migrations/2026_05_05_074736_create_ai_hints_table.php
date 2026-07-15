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
        Schema::create('ai_hints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->unsignedTinyInteger('hint_level'); // 1 = легкая, 5 = почти решение
            $table->text('hint_text');
            $table->json('trigger_conditions')->nullable(); // когда показывать

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['project_id', 'hint_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_hints');
    }
};
