<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('depends_on_course_id')->constrained('courses')->cascadeOnDelete();
            $table->integer('order_position')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'depends_on_course_id']);
            $table->index(['course_id', 'order_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_dependencies');
    }
};
