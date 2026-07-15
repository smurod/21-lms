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
        Schema::create('project_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('depends_on_project_id')->constrained('projects')->onDelete('cascade');

            // Тип зависимости
            $table->enum('dependency_type', ['required', 'recommended'])->default('required');

            $table->timestamps();

            $table->unique(['project_id', 'depends_on_project_id'], 'unique_project_dependency');
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_dependencies');
    }
};
