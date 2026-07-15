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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // LMS
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->longText('instructions');

            // GitLab
            $table->unsignedBigInteger('gitlab_project_id')->nullable()->unique(); // nullable merged from 2026_06_20_203454_make_gitlab_project_id_nullable
            $table->string('gitlab_sync_status')->default('synced'); // merged from 2026_06_22_add_gitlab_sync_status_to_projects
            $table->string('repository_url')->nullable();
            $table->string('default_branch')->default('main');

            // Structure
            $table->foreignId('course_id')->nullable();
            $table->foreignId('module_id')->nullable();

            // Difficulty
            $table->string('difficulty')->default('beginner');
            $table->unsignedTinyInteger('min_level')->default(1); // merged from 2026_05_23_000002_add_min_level_to_projects_table
            $table->unsignedInteger('estimated_hours')->default(5);
            $table->unsignedInteger('order_position')->default(0);

            // Runtime
            $table->string('language')->nullable();
            $table->json('runtime')->nullable();

            // Review
            $table->boolean('requires_peer_review')->default(true);
            $table->unsignedInteger('required_reviews_count')->default(2);

            // Scoring
            $table->unsignedInteger('xp_reward')->default(100);
            $table->unsignedInteger('passing_score')->default(70);

            // Publication
            $table->boolean('is_published')->default(false);
            $table->boolean('is_mandatory')->default(true);

            // Hints
            $table->longText('hints')->nullable();

            // Extra
            $table->string('language_version')->nullable();
            $table->string('submission_type')->default('git');
            $table->json('allowed_file_extensions')->nullable();
            $table->unsignedInteger('max_file_size_mb')->default(10);
            $table->boolean('has_automated_tests')->default(false);
            $table->string('test_file_path')->nullable();
            $table->unsignedInteger('test_timeout_seconds')->default(30);
            $table->json('docker_config')->nullable();

            // Metadata
            $table->foreignId('created_by')->nullable();
            $table->json('tags')->nullable();
            $table->json('learning_outcomes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
