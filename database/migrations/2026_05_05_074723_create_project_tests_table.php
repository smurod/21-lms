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
        Schema::create('project_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->string('name'); // "Test basic functionality"
            $table->text('description')->nullable();
            $table->longText('test_code'); // код теста

            $table->unsignedInteger('points')->default(10); // вес теста
            $table->unsignedInteger('order_position')->default(0);
            $table->boolean('is_hidden')->default(false); // скрыт от студента

            $table->enum('test_type', ['unit', 'integration', 'performance', 'style'])->default('unit');

            $table->timestamps();

            $table->index(['project_id', 'order_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_tests');
    }
};
