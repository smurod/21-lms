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
        Schema::create('plagiarism_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');

            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->decimal('similarity_score', 5, 2)->default(0); // 0-100

            $table->foreignId('matched_submission_id')->nullable()->constrained('submissions')->nullOnDelete();
            $table->json('matched_submissions')->nullable(); // массив похожих

            $table->enum('verdict', ['clear', 'suspicious', 'plagiarized'])->nullable();
            $table->text('details')->nullable();

            $table->boolean('reviewed_by_mentor')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('submission_id');
            $table->index(['status', 'verdict']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plagiarism_checks');
    }
};
