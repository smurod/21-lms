<?php

namespace Database\Seeders;

use App\Models\Admin\Project;
use App\Models\Admin\Review;
use App\Models\Admin\Submission;
use App\Models\User;
use App\Services\GitlabService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class Test2ReviewSeeder extends Seeder
{
    public function run(GitlabService $gitlab): void
    {
        $student = User::where('email', 'test@gmail.com')->firstOrFail();
        $reviewer = User::where('email', 'test2@gmail.com')->firstOrFail();
        $project = Project::where('slug', 'simple-bash-utils')->firstOrFail();

        $gitUrl = $this->studentRepositoryUrl($gitlab, $student, $project);

        $submission = Submission::updateOrCreate(
            [
                'user_id' => $student->id,
                'project_id' => $project->id,
                'attempt_number' => 1,
            ],
            [
                'submission_type' => 'git',
                'git_url' => $gitUrl,
                'status' => 'in_review',
                'tests_passed' => 21,
                'tests_total' => 22,
                'test_score' => 95.45,
                'test_output' => "Seeded ready P2P review for test2.\n21/22 tests passed.",
                'test_details' => [
                    ['name' => 's21_cat basic flags', 'status' => 'passed'],
                    ['name' => 's21_grep regex combinations', 'status' => 'passed'],
                    ['name' => 'memory leak smoke test', 'status' => 'failed'],
                ],
                'submitted_at' => now()->subHours(2),
                'tested_at' => now()->subMinutes(90),
            ]
        );

        Review::updateOrCreate(
            [
                'submission_id' => $submission->id,
                'reviewer_id' => $reviewer->id,
            ],
            [
                'score' => null,
                'feedback' => null,
                'private_notes' => null,
                'checklist_data' => null,
                'time_spent_minutes' => null,
                'confidence_score' => 1.00,
                'is_mentor_review' => false,
                'is_auto_assigned' => false,
                'is_appeal_review' => false,
                'status' => 'pending',
                'is_calibration' => false,
                'accuracy_score' => null,
                'started_at' => now()->subHour(),
                'completed_at' => now()->addHours(23),
            ]
        );

        $this->command?->info('Seeded ready P2P review: reviewer test2@gmail.com, student test@gmail.com, project SimpleBashUtils.');
    }

    private function studentRepositoryUrl(GitlabService $gitlab, User $student, Project $project): ?string
    {
        try {
            $gitlab->ensureUserAccount($student);
            $repository = $gitlab->createStudentProjectRepository(
                token: $student->connectedGitlabToken(),
                projectData: $project->toArray(),
                ownerName: $student->username ?? $student->email,
                attemptNumber: 1,
            );

            return $repository['http_url_to_repo']
                ?? $repository['web_url']
                ?? $project->repository_url;
        } catch (\Throwable $e) {
            Log::warning('Seeded test2 review repository creation failed, falling back to template repository URL', [
                'student_id' => $student->id,
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return $project->repository_url;
        }
    }
}
