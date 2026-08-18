<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_dashboard_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analytics_dashboard_id')
                ->constrained('analytics_dashboards')
                ->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->timestamps();

            $table->index(['analytics_dashboard_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_dashboard_messages');
    }
};
