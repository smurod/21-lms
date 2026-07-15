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
        Schema::create('user_learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('learning_path_id')->constrained()->onDelete('cascade');

            $table->unsignedInteger('current_position')->default(0); // текущий проект в пути
            $table->decimal('progress_percentage', 5, 2)->default(0);

            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            $table->unique(['user_id', 'learning_path_id'], 'unique_user_learning_path');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_learning_paths');
    }
};
