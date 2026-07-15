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
        Schema::create('submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');

            $table->string('original_filename');
            $table->string('stored_filename'); // уникальное имя
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('file_hash')->nullable(); // для антиплагиата

            $table->timestamps();

            $table->index('submission_id');
            $table->index('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_files');
    }
};
