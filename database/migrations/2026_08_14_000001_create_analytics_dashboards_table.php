<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('datalens_dashboard_id')->unique();
            $table->string('workbook_id');
            $table->string('connection_id')->nullable();
            $table->string('title');
            $table->text('prompt');
            $table->string('dashboard_url');
            $table->string('embed_url');
            $table->json('charts')->nullable();
            $table->string('status')->default('ready');
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_dashboards');
    }
};
