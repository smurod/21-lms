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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();

            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            $table->unsignedInteger('max_members')->default(10);
            $table->unsignedBigInteger('total_xp')->default(0);

            $table->boolean('is_public')->default(true);
            $table->string('invite_code')->unique()->nullable();

            $table->timestamps();

            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
