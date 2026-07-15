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
        Schema::create('code_similarity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_a_id')->constrained('submissions')->onDelete('cascade');
            $table->foreignId('submission_b_id')->constrained('submissions')->onDelete('cascade');

            $table->decimal('similarity_percentage', 5, 2);
            $table->json('similarity_details')->nullable(); // какие части совпадают

            $table->timestamps();

            $table->unique(['submission_a_id', 'submission_b_id'], 'unique_similarity_pair');
            $table->index('similarity_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('code_similarity');
    }
};
