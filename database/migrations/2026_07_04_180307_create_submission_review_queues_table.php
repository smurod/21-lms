<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CHANGED for migrate:fresh:
     *   - submission_id is now NULLABLE — BookingService creates queue
     *     entries without a submission (e.g. direct "submit for review"),
     *     the old NOT NULL constraint crashed every assignment.
     */
    public function up(): void
    {
        Schema::create('submission_review_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->nullable()->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_id')->nullable()->constrained('calendar_slots')->cascadeOnDelete();
            $table->string('status')->default('waiting'); // waiting, assigned, cancelled
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['status', 'position']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_review_queues');
    }
};
