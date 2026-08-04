<?php

namespace Database\Seeders;

use App\Models\Admin\Project;
use App\Models\Admin\ProjectTest;
use App\Models\Admin\Review;
use App\Models\Admin\Submission;
use App\Models\CalendarSlot;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\XpTransaction;
use App\Services\GitlabService;
use App\Services\TestRunnerService;
use App\Services\XpService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AutotestWorkflowSeeder extends Seeder
{
    private const PASSWORD = 'school21';
    private const BRANCH = 'main';

    public function run(GitlabService $gitlab, TestRunnerService $tests, XpService $xp): void
    {
        $students = [
            'pass' => $this->user('autotest-pass@example.test', 'autotest-pass', 'Autotest Passing Cadet', $gitlab),
            'fail' => $this->user('autotest-fail@example.test', 'autotest-fail', 'Autotest Failing Cadet', $gitlab),
            'p2p' => $this->user('autotest-p2p@example.test', 'autotest-p2p', 'P2P Then Autotest Cadet', $gitlab),
        ];
        $reviewer = $this->user('autotest-reviewer@example.test', 'autotest-reviewer', 'Autotest Reviewer', $gitlab);

        $autotestOnly = $this->project(
            gitlab: $gitlab,
            title: 'Autotest Shell Challenge',
            slug: 'autotest-shell-challenge',
            requiresPeerReview: false,
            requiredReviews: 0,
        );

        $p2pThenAutotest = $this->project(
            gitlab: $gitlab,
            title: 'P2P Then Autotest Challenge',
            slug: 'p2p-then-autotest-challenge',
            requiresPeerReview: true,
            requiredReviews: 1,
        );

        $this->runAutotestOnlyPass($gitlab, $tests, $xp, $students['pass'], $autotestOnly);
        $this->runAutotestOnlyFail($gitlab, $tests, $students['fail'], $autotestOnly);
        $this->runP2pThenAutotestPass($gitlab, $tests, $xp, $students['p2p'], $reviewer, $p2pThenAutotest);

        $this->command?->info('Seeded real GitLab autotest workflow: pass, fail, and P2P-then-autotest scenarios.');
    }

    private function user(string $email, string $username, string $name, GitlabService $gitlab): User
    {
        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $registeredAt = now()->subDays(14)->setTime(10, 0);

        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        if ($user->exists && method_exists($user, 'trashed') && $user->trashed()) {
            $user->restore();
        }

        $user->forceFill([
            'name' => $name,
            'username' => $username,
            'email_verified_at' => $registeredAt,
            'password' => Hash::make(self::PASSWORD),
            'level' => max(1, (int) $user->level),
            'total_xp' => (int) $user->total_xp,
            'created_at' => $registeredAt,
            'updated_at' => $registeredAt,
        ])->save();
        $user->syncRoles([$role]);

        $gitlab->ensureUserAccount($user, self::PASSWORD);
        $this->activity($user, 'user_registered', 'auth', $user, $registeredAt, [
            'scenario' => 'real_autotest_workflow',
        ]);
        $this->activity($user, 'gitlab_account_ready', 'gitlab', $user, $registeredAt->copy()->addMinutes(2), [
            'username' => $username,
            'token_scopes' => config('services.gitlab.user_token_scopes', ['api', 'write_repository']),
        ]);

        return $user;
    }

    private function project(GitlabService $gitlab, string $title, string $slug, bool $requiresPeerReview, int $requiredReviews): Project
    {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'description' => 'Production-like seed project with real GitLab repository files and a real shell autotest command.',
            'instructions' => "Create solution.txt with exactly: 42\n\nAutotest command: bash tests/run.sh",
            'hints' => ['Write 42 into solution.txt'],
            'difficulty' => 'beginner',
            'min_level' => 1,
            'estimated_hours' => 1,
            'order_position' => 900 + ($requiresPeerReview ? 2 : 1),
            'language' => 'bash',
            'language_version' => '5',
            'submission_type' => 'git',
            'allowed_file_extensions' => ['.txt', '.sh', '.md'],
            'max_file_size_mb' => 2,
            'has_automated_tests' => true,
            'test_file_path' => 'bash tests/run.sh',
            'test_timeout_seconds' => 30,
            'docker_config' => null,
            'xp_reward' => $requiresPeerReview ? 180 : 120,
            'passing_score' => 100,
            'requires_peer_review' => $requiresPeerReview,
            'required_reviews_count' => $requiredReviews,
            'is_published' => true,
            'is_mandatory' => false,
            'tags' => ['autotest', 'gitlab', 'real-runner'],
            'learning_outcomes' => ['Understand real GitLab-based autotest flow'],
            'default_branch' => self::BRANCH,
            'runtime' => ['command' => 'bash tests/run.sh', 'timeout' => 30],
            'gitlab_sync_status' => 'synced',
        ];

        $gitlabProject = $this->ensureGitlabProject($gitlab, $title, $slug, $data['description']);
        $this->writeAutotestFiles($gitlab, (int) $gitlabProject['id'], $data, self::BRANCH, null, false);

        $project = Project::updateOrCreate(
            ['slug' => $slug],
            [
                ...$data,
                'gitlab_project_id' => $gitlabProject['id'],
                'repository_url' => $gitlabProject['web_url'] ?? rtrim((string) config('services.gitlab.url'), '/') . '/' . $slug,
                'default_branch' => $gitlabProject['default_branch'] ?? self::BRANCH,
            ]
        );

        $this->seedProjectTests($project);

        return $project->fresh('tests');
    }

    private function seedProjectTests(Project $project): void
    {
        ProjectTest::updateOrCreate(
            [
                'project_id' => $project->id,
                'name' => 'Hidden functional check',
            ],
            [
                'description' => 'Server-side hidden test. Verifies that solution.txt contains exactly 42.',
                'test_code' => <<<'SH'
#!/usr/bin/env bash
set -euo pipefail

cd "$WORKSPACE"

if [ ! -f solution.txt ]; then
  echo "FAIL: solution.txt is missing"
  exit 1
fi

actual="$(tr -d '[:space:]' < solution.txt)"
if [ "$actual" != "42" ]; then
  echo "FAIL: expected solution.txt to contain 42, got '$actual'"
  exit 1
fi

echo "PASS: hidden functional check accepted solution.txt"
SH,
                'points' => 100,
                'order_position' => 10,
                'is_hidden' => true,
                'test_type' => 'integration',
                'timeout_seconds' => 30,
            ]
        );
    }

    private function runAutotestOnlyPass(GitlabService $gitlab, TestRunnerService $tests, XpService $xp, User $student, Project $project): void
    {
        $enrolledAt = now()->subDays(10)->setTime(10, 15);
        $submittedAt = $enrolledAt->copy()->addDays(2)->setTime(16, 20);
        $repoUrl = $this->studentRepo($gitlab, $student, $project, true);

        $submission = $this->submission($student, $project, $repoUrl, 'in_progress', $enrolledAt, $submittedAt);
        $this->lockSubmissionCommit($gitlab, $submission);
        $this->activity($student, 'project_enrolled', 'project', $project, $enrolledAt, ['project' => $project->slug]);
        $this->activity($student, 'project_submitted', 'project', $submission, $submittedAt, ['git_url' => $repoUrl]);

        \App\Jobs\RunSubmissionTestsJob::dispatchSync($submission->id);
        $submission->refresh();
        if (! $tests->passes($submission)) {
            throw new \RuntimeException('AutotestWorkflowSeeder expected pass scenario to pass, got failure: ' . $submission->test_output);
        }

        $submission->update([
            'status' => 'passed',
            'submitted_at' => $submittedAt,
            'final_score' => $submission->test_score,
            'completed_at' => $submission->tested_at,
        ]);
        $this->awardXpOnce($xp, $submission, "Real autotest project '{$project->title}' passed");
        $this->activity($student, 'autotests_passed', 'testing', $submission, Carbon::parse($submission->tested_at), [
            'tests_passed' => $submission->tests_passed,
            'tests_total' => $submission->tests_total,
            'score' => $submission->test_score,
        ]);
    }

    private function runAutotestOnlyFail(GitlabService $gitlab, TestRunnerService $tests, User $student, Project $project): void
    {
        $enrolledAt = now()->subDays(9)->setTime(11, 30);
        $submittedAt = $enrolledAt->copy()->addDay()->setTime(18, 5);
        $repoUrl = $this->studentRepo($gitlab, $student, $project, false);

        $submission = $this->submission($student, $project, $repoUrl, 'in_progress', $enrolledAt, $submittedAt);
        $this->lockSubmissionCommit($gitlab, $submission);
        $this->activity($student, 'project_enrolled', 'project', $project, $enrolledAt, ['project' => $project->slug]);
        $this->activity($student, 'project_submitted', 'project', $submission, $submittedAt, ['git_url' => $repoUrl]);

        \App\Jobs\RunSubmissionTestsJob::dispatchSync($submission->id);
        $submission->refresh();
        if ($tests->passes($submission)) {
            throw new \RuntimeException('AutotestWorkflowSeeder expected fail scenario to fail, got pass.');
        }

        $submission->update([
            'status' => 'failed',
            'submitted_at' => $submittedAt,
            'final_score' => $submission->test_score,
            'completed_at' => $submission->tested_at,
        ]);
        $this->activity($student, 'autotests_failed', 'testing', $submission, Carbon::parse($submission->tested_at), [
            'tests_passed' => $submission->tests_passed,
            'tests_total' => $submission->tests_total,
            'score' => $submission->test_score,
        ]);
    }

    private function runP2pThenAutotestPass(GitlabService $gitlab, TestRunnerService $tests, XpService $xp, User $student, User $reviewer, Project $project): void
    {
        $enrolledAt = now()->subDays(8)->setTime(9, 45);
        $submittedAt = $enrolledAt->copy()->addDays(2)->setTime(15, 40);
        $reviewStart = $submittedAt->copy()->addDay()->setTime(13, 0);
        $reviewEnd = $reviewStart->copy()->addMinutes(55);
        $repoUrl = $this->studentRepo($gitlab, $student, $project, true);

        $submission = $this->submission($student, $project, $repoUrl, 'in_review', $enrolledAt, $submittedAt);
        $this->lockSubmissionCommit($gitlab, $submission);
        $this->activity($student, 'project_enrolled', 'project', $project, $enrolledAt, ['project' => $project->slug]);
        $this->activity($student, 'project_submitted', 'project', $submission, $submittedAt, ['git_url' => $repoUrl]);

        CalendarSlot::updateOrCreate(
            [
                'user_id' => $reviewer->id,
                'project_id' => $project->id,
                'date' => $reviewStart->toDateString(),
                'start_time' => $reviewStart->format('H:i'),
            ],
            [
                'end_time' => $reviewStart->copy()->addHour()->format('H:i'),
                'status' => 'booked',
                'booked_by_user_id' => $student->id,
                'notes' => 'Real workflow seed: P2P before autotest.',
            ]
        );

        $review = Review::updateOrCreate(
            [
                'submission_id' => $submission->id,
                'reviewer_id' => $reviewer->id,
            ],
            [
                'score' => 95,
                'feedback' => 'P2P passed. Solution is simple and matches task requirements. Autotests can run now.',
                'private_notes' => 'Seeded P2P-before-autotest scenario.',
                'checklist_data' => $this->checklistData($project),
                'time_spent_minutes' => $reviewStart->diffInMinutes($reviewEnd),
                'confidence_score' => 0.95,
                'is_mentor_review' => false,
                'is_auto_assigned' => false,
                'is_appeal_review' => false,
                'status' => 'completed',
                'started_at' => $reviewStart,
                'completed_at' => $reviewEnd,
            ]
        );
        $review->forceFill(['created_at' => $reviewStart, 'updated_at' => $reviewEnd])->save();
        $this->activity($reviewer, 'review_completed', 'review', $review, $reviewEnd, [
            'student_username' => $student->username,
            'project' => $project->slug,
            'score' => 95,
        ]);

        $submission->update([
            'status' => 'reviewed',
            'review_score' => 95,
            'reviews_received' => 1,
            'reviewed_at' => $reviewEnd,
        ]);

        \App\Jobs\RunSubmissionTestsJob::dispatchSync($submission->id);
        $submission->refresh();
        if (! $tests->passes($submission)) {
            throw new \RuntimeException('AutotestWorkflowSeeder expected P2P+autotest scenario to pass, got failure: ' . $submission->test_output);
        }

        $finalScore = round((95 + (float) $submission->test_score) / 2, 2);
        $submission->update([
            'status' => 'passed',
            'review_score' => 95,
            'reviews_received' => 1,
            'final_score' => $finalScore,
            'completed_at' => $submission->tested_at,
        ]);
        $this->awardXpOnce($xp, $submission, "P2P + real autotest project '{$project->title}' passed");
        $this->activity($student, 'p2p_then_autotests_passed', 'testing', $submission, Carbon::parse($submission->tested_at), [
            'review_score' => 95,
            'test_score' => $submission->test_score,
            'final_score' => $finalScore,
        ]);
    }

    private function lockSubmissionCommit(GitlabService $gitlab, Submission $submission): void
    {
        $submission->loadMissing(['project', 'user']);
        $hash = $gitlab->latestCommitHash(
            repositoryUrl: $submission->git_url,
            branch: $submission->project?->default_branch ?: self::BRANCH,
            token: $submission->user?->connectedGitlabToken(),
        );

        if ($hash) {
            $submission->forceFill(['git_commit_hash' => $hash])->save();
        }
    }

    private function submission(User $student, Project $project, string $repoUrl, string $status, Carbon $enrolledAt, Carbon $submittedAt): Submission
    {
        $submission = Submission::updateOrCreate(
            [
                'user_id' => $student->id,
                'project_id' => $project->id,
                'attempt_number' => 1,
            ],
            [
                'submission_type' => 'git',
                'git_url' => $repoUrl,
                'git_commit_hash' => null,
                'status' => $status,
                'tests_passed' => 0,
                'tests_total' => 0,
                'test_score' => 0,
                'test_output' => null,
                'test_details' => null,
                'review_score' => null,
                'reviews_received' => 0,
                'final_score' => null,
                'submitted_at' => $submittedAt,
                'tested_at' => null,
                'reviewed_at' => null,
                'completed_at' => null,
            ]
        );
        $submission->forceFill(['created_at' => $enrolledAt, 'updated_at' => $submittedAt])->save();

        return $submission;
    }

    private function studentRepo(GitlabService $gitlab, User $student, Project $project, bool $correctSolution): string
    {
        $student->refresh();
        $path = 'school21-' . $project->slug;
        $existing = collect($gitlab->getProjects($student->connectedGitlabToken()))->firstWhere('path', $path);

        $repo = $existing ?: $gitlab->createProjectWithConfig(
            name: $project->title,
            path: $path,
            description: 'Student repository generated by AutotestWorkflowSeeder.',
            defaultBranch: self::BRANCH,
            visibility: 'private',
            initializeWithReadme: true,
            token: $student->connectedGitlabToken(),
        );

        $this->writeAutotestFiles($gitlab, (int) $repo['id'], $project->toArray(), $repo['default_branch'] ?? self::BRANCH, $student->connectedGitlabToken(), $correctSolution);

        return $repo['http_url_to_repo'] ?? $repo['web_url'] ?? rtrim((string) config('services.gitlab.url'), '/') . '/' . $student->username . '/' . $path . '.git';
    }

    private function ensureGitlabProject(GitlabService $gitlab, string $title, string $slug, string $description): array
    {
        $existing = collect($gitlab->getProjects())->firstWhere('path', $slug);
        if ($existing) {
            return $existing;
        }

        try {
            return $gitlab->createProjectWithConfig(
                name: $title,
                path: $slug,
                description: $description,
                defaultBranch: self::BRANCH,
                visibility: config('services.gitlab.visibility', 'private'),
                initializeWithReadme: true,
            );
        } catch (\RuntimeException $e) {
            $existing = collect($gitlab->getProjects())->firstWhere('path', $slug);
            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }

    private function writeAutotestFiles(GitlabService $gitlab, int $projectId, array $projectData, string $branch, ?string $token, bool $correctSolution): void
    {
        $files = [
            'README.md' => "# {$projectData['title']}\n\nWrite `42` into `solution.txt`.\n\nRun tests with:\n\n```bash\nbash tests/run.sh\n```\n",
            'tests/run.sh' => <<<'SH'
#!/usr/bin/env bash
set -euo pipefail

if [ ! -f solution.txt ]; then
  echo "FAIL: solution.txt is missing"
  exit 1
fi

actual="$(tr -d '[:space:]' < solution.txt)"
if [ "$actual" != "42" ]; then
  echo "FAIL: expected solution.txt to contain 42, got '$actual'"
  exit 1
fi

echo "PASS: solution.txt contains 42"
SH,
            'solution.txt' => $correctSolution ? "42\n" : "41\n",
        ];

        foreach ($files as $path => $content) {
            $gitlab->upsertFile(
                projectId: $projectId,
                filePath: $path,
                branch: $branch,
                content: $content,
                commitMessage: 'Seed real autotest workflow file ' . $path,
                token: $token,
            );
        }
    }

    private function awardXpOnce(XpService $xp, Submission $submission, string $description): void
    {
        $submission->loadMissing('project');
        $project = $submission->project;

        if (! $project || (int) $project->xp_reward <= 0) {
            return;
        }

        $alreadyAwarded = XpTransaction::where([
            'user_id' => $submission->user_id,
            'source_type' => 'submission',
            'source_id' => $submission->id,
        ])->exists();

        if (! $alreadyAwarded) {
            $xp->add(
                userId: $submission->user_id,
                amount: (int) $project->xp_reward,
                reason: 'project_completed',
                sourceType: 'submission',
                sourceId: $submission->id,
                description: $description,
            );
        }
    }

    private function activity(User $user, string $eventType, string $category, \Illuminate\Database\Eloquent\Model $subject, Carbon $at, array $metadata = []): void
    {
        UserActivity::updateOrCreate(
            [
                'user_id' => $user->id,
                'event_type' => $eventType,
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
                'created_at' => $at,
            ],
            [
                'event_category' => $category,
                'metadata' => $metadata,
                'ip_address' => '127.0.0.' . (($user->id % 200) + 1),
                'user_agent' => '21-LMS AutotestWorkflowSeeder',
            ]
        );
    }

    private function checklistData(Project $project): array
    {
        $project->loadMissing('checklists');

        return $project->checklists
            ->map(fn ($item) => [
                'item_key' => $item->item_key,
                'item_label' => $item->item_label,
                'is_required' => $item->is_required,
                'result' => 'passed',
            ])
            ->values()
            ->all();
    }
}
