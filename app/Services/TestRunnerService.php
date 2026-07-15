<?php

namespace App\Services;

use App\Models\Admin\Submission;

class TestRunnerService
{
    /**
     * Run automated tests for a submission.
     * MVP: mock runner – in production replace with Docker CI.
     */
    public function run(Submission $submission): Submission
    {
        $submission->update(['status' => 'testing']);

        // Mock: 85-100% pass, 18-24 tests
        $testsTotal = rand(18, 24);
        $testsPassed = rand((int)($testsTotal * 0.8), $testsTotal);
        $score = round($testsPassed / $testsTotal * 100, 2);

        $project = $submission->project;
        $passingScore = $project->passing_score ?? 70;

        $submission->update([
            'tests_total' => $testsTotal,
            'tests_passed' => $testsPassed,
            'test_score' => $score,
            'test_output' => "Mock test run: {$testsPassed}/{$testsTotal} passed ({$score}%)",
            'test_details' => [
                'runner' => 'mock_v1',
                'timestamp' => now()->toIso8601String(),
            ],
            'tested_at' => now(),
            'status' => $score >= $passingScore ? 'tested' : 'failed',
        ]);

        return $submission->fresh();
    }

    public function passes(Submission $submission): bool
    {
        return ($submission->test_score ?? 0) >= ($submission->project->passing_score ?? 70);
    }
}
