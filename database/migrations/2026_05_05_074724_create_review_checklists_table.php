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
        Schema::create('review_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->string('item_key'); // code_quality, documentation, etc.
            $table->string('item_label');
            $table->text('description')->nullable();
            $table->unsignedInteger('weight')->default(1); // вес критерия
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('order_position')->default(0);

            $table->timestamps();

            $table->unique(['project_id', 'item_key'], 'unique_project_checklist_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_checklists');
    }
};
