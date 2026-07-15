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
        Schema::create('reviewer_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->foreignId('rated_by')->constrained('users')->onDelete('cascade'); // кто оценил

            $table->unsignedTinyInteger('helpfulness_score'); // 1-5
            $table->unsignedTinyInteger('accuracy_score'); // 1-5
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->unique(['review_id', 'rated_by'], 'unique_review_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviewer_ratings');
    }
};
