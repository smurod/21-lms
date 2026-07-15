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
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();

            // Polymorphic - может быть к проекту, курсу, etc.
            $table->morphs('discussable');

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('title');
            $table->longText('content');

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_solved')->default(false);

            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discussions');
    }
};
