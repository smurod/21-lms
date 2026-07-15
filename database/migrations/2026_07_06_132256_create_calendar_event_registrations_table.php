<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * School 21: "на мероприятия участники могут записываться".
 * Pivot: who registered to attend which event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['calendar_event_id', 'user_id'], 'unique_event_registration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_registrations');
    }
};
