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

            $table->string('name');
            $table->text('description')->nullable();
            // Hidden/public server-side test script. It is written outside the
            // student repository and mounted read-only into the test sandbox.
            $table->longText('test_code');

            $table->unsignedInteger('points')->default(10);
            $table->unsignedInteger('order_position')->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->enum('test_type', ['unit', 'integration', 'performance', 'style'])->default('unit');
            $table->unsignedInteger('timeout_seconds')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'order_position']);
            $table->index(['project_id', 'is_hidden']);
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
