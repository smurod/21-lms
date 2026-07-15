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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->unsignedInteger('order_position')->default(0);
            $table->boolean('is_published')->default(false);

            $table->timestamps();

            $table->unique(['course_id', 'slug']);
            $table->index(['course_id', 'order_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
