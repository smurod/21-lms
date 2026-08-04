<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Project;
use App\Models\Admin\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Submission> */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        $score = $this->faker->randomFloat(2, 45, 100);
        $total = 22;
        $passed = max(0, min($total, (int) round($total * ($score / 100))));

        return [
            'user_id' => User::factory(),
            'project_id' => Project::query()->inRandomOrder()->value('id'),
            'submission_type' => 'git',
            'git_url' => null,
            'git_commit_hash' => null,
            'code_content' => null,
            'status' => 'in_progress',
            'tests_passed' => $passed,
            'tests_total' => $total,
            'test_score' => $score,
            'test_output' => "Factory generated test output: {$passed}/{$total} tests passed.",
            'test_details' => [],
            'review_score' => null,
            'reviews_received' => 0,
            'final_score' => null,
            'is_plagiarized' => false,
            'submitted_at' => now()->subDays($this->faker->numberBetween(1, 30)),
            'tested_at' => null,
            'reviewed_at' => null,
            'completed_at' => null,
            'attempt_number' => 1,
            'execution_time_ms' => $this->faker->numberBetween(250, 8000),
            'memory_used_mb' => $this->faker->numberBetween(8, 256),
        ];
    }
}
