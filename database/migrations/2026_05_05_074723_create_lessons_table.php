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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');

            $table->string('title');
            $table->string('slug');
            $table->longText('content'); // Markdown

            $table->enum('content_type', ['text', 'video', 'interactive'])->default('text');
            $table->string('video_url')->nullable();
            $table->unsignedInteger('estimated_minutes')->default(10);

            $table->unsignedInteger('order_position')->default(0);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_free')->default(false); // для preview

            $table->timestamps();

            $table->unique(['module_id', 'slug']);
            $table->index(['module_id', 'order_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
