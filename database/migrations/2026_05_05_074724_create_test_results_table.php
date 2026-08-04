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
        Schema::create('test_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('queued'); // queued, running, passed, failed, error, cancelled
            $table->string('runner')->default('process_git_runtime_v1');
            $table->string('image')->nullable();
            $table->longText('command')->nullable();
            $table->string('commit_hash')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->integer('exit_code')->nullable();
            $table->decimal('score', 5, 2)->default(0);
            $table->longText('logs')->nullable();
            $table->string('artifacts_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['submission_id', 'status']);
            $table->index(['submission_id', 'created_at']);
        });

        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('test_run_id')->nullable()->constrained('test_runs')->nullOnDelete();
            $table->foreignId('project_test_id')->nullable()->constrained()->nullOnDelete();

            $table->string('test_name');
            $table->boolean('passed')->default(false);
            $table->text('error_message')->nullable();
            $table->longText('output')->nullable();

            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->unsignedInteger('points_earned')->default(0);
            $table->unsignedInteger('points_possible')->default(0);

            $table->timestamps();

            $table->index('submission_id');
            $table->index('test_run_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('test_runs');
    }
};
