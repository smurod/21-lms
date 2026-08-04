<?php

namespace Database\Seeders;

use App\Models\Admin\Project;
use App\Models\Admin\Review;
use App\Models\Admin\Submission;
use App\Models\CalendarSlot;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserStat;
use App\Models\XpTransaction;
use App\Services\GitlabService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class DemoLmsSimulationSeeder extends Seeder
{
    private const PASSWORD = 'school21';

    /** @var array<int, User> */
    private array $demoUsers = [];

    private ?GitlabService $gitlab = null;
    private bool $withGitlab = true;
    private int $gitlabRepositoryLimit = 25;
    private int $gitlabRepositoriesCreated = 0;

    public function run(GitlabService $gitlab): void
    {
        $this->gitlab = $gitlab;
        $this->withGitlab = filter_var(env('DEMO_USERS_GITLAB', true), FILTER_VALIDATE_BOOLEAN);
        $this->gitlabRepositoryLimit = max(0, (int) env('DEMO_GITLAB_REPOSITORY_LIMIT', 25));

        $count = max(1, (int) env('DEMO_USERS_COUNT', 120));
        $projects = Project::query()
            ->with('checklists')
            ->where('is_published', true)
            ->orderBy('order_position')
            ->orderBy('id')
            ->get();

        if ($projects->isEmpty()) {
            $this->command?->warn('DemoLmsSimulationSeeder skipped: no published projects found.');
            return;
        }

        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        for ($i = 1; $i <= $count; $i++) {
            $this->demoUsers[$i] = $this->seedUser($i, $userRole);
        }

        foreach ($this->demoUsers as $i => $user) {
            $this->seedUserLifecycle($i, $user, $projects);
        }

        $this->command?->info(
            "Seeded {$count} realistic demo users via factories: registration → enrollment → submission → tests → P2P reviews → XP/statistics. "
            . ($this->withGitlab
                ? "GitLab API provisioning enabled; real repositories created: {$this->gitlabRepositoriesCreated}/{$this->gitlabRepositoryLimit}."
                : 'GitLab API provisioning skipped by DEMO_USERS_GITLAB=false.')
        );
    }

    private function seedUser(int $index, Role $userRole): User
    {
        $username = 'cadet' . str_pad((string) $index, 3, '0', STR_PAD_LEFT);
        $email = $username . '@example.test';
        $registeredAt = now()
            ->subDays(90 - ($index % 45))
            ->setTime(9 + ($index % 8), ($index * 7) % 60, 0);

        $attributes = User::factory()->make([
            'name' => $this->demoName($index),
            'username' => $username,
            'email' => $email,
            'password' => bcrypt(self::PASSWORD),
            'level' => 1,
            'total_xp' => 0,
        ])->getAttributes();

        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        if ($user->exists && method_exists($user, 'trashed') && $user->trashed()) {
            $user->restore();
        }

        $user->forceFill($attributes)->save();
        $user->forceFill([
            'created_at' => $registeredAt,
            'updated_at' => $registeredAt->copy()->addMinutes(3),
        ])->save();
        $user->syncRoles([$userRole]);

        $this->activity($user, 'user_registered', 'auth', $user, $registeredAt, [
            'source' => 'demo_simulation',
            'password_policy' => 'school21',
        ]);

        if ($this->withGitlab && $this->gitlab) {
            try {
                $this->gitlab->ensureUserAccount($user, self::PASSWORD);
                $this->activity($user, 'gitlab_account_ready', 'gitlab', $user, $registeredAt->copy()->addMinutes(4), [
                    'username' => $user->username,
                    'token_scopes' => config('services.gitlab.user_token_scopes', ['api', 'write_repository']),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Demo user GitLab API provisioning failed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->username,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $user;
    }

    private function seedUserLifecycle(int $index, User $user, \Illuminate\Support\Collection $projects): void
    {
        $started = 0;
        $completed = 0;
        $failed = 0;
        $reviewsReceived = 0;
        $reviewScores = [];
        $totalXp = 0;
        $coinBalance = 20 + ($index % 9);
        $registrationBase = Carbon::parse($user->created_at)->addDays(1);
        $projectLimit = 1 + ($index % min(8, max(1, $projects->count())));

        foreach ($projects->take($projectLimit)->values() as $projectIndex => $project) {
            $state = ($index + $projectIndex) % 8;
            $attempt = $state === 2 ? 2 : 1;
            $baseScore = min(100, 55 + (($index * 7 + $projectIndex * 11) % 46));
            $testsTotal = 22;
            $testsPassed = max(8, min($testsTotal, (int) round($testsTotal * ($baseScore / 100))));
            $requiredReviews = max(1, (int) ($project->required_reviews_count ?? 2));

            $enrolledAt = $registrationBase->copy()
                ->addDays($projectIndex * 7 + ($index % 4))
                ->setTime(10 + (($index + $projectIndex) % 7), (($index + $projectIndex) * 11) % 60, 0);
            $submittedAt = $enrolledAt->copy()->addDays(2 + (($index + $projectIndex) % 5))->addHours(2);
            $testedAt = $submittedAt->copy()->addMinutes(35 + (($index + $projectIndex) % 70));
            $firstReviewAt = $testedAt->copy()->addHours(4 + (($index + $projectIndex) % 6))->minute(0)->second(0);

            $status = match ($state) {
                0, 1 => 'passed',
                2 => 'failed',
                3 => 'in_review',
                4 => 'reviewed',
                5 => 'tested',
                6 => 'testing',
                default => 'in_progress',
            };

            if ($status === 'testing') {
                $testedAt = null;
            }
            if ($status === 'in_progress') {
                $submittedAt = null;
                $testedAt = null;
            }

            $submission = $this->submission(
                user: $user,
                project: $project,
                attempt: $attempt,
                status: $status,
                gitUrl: $this->repositoryUrl($user, $project, $attempt),
                testsPassed: $testsPassed,
                testsTotal: $testsTotal,
                testScore: $baseScore,
                enrolledAt: $enrolledAt,
                submittedAt: $submittedAt,
                testedAt: $testedAt,
            );
            $started++;

            $this->activity($user, 'project_enrolled', 'project', $project, $enrolledAt, [
                'project' => $project->slug,
                'attempt' => $attempt,
            ]);

            if ($submittedAt) {
                $this->activity($user, 'project_submitted', 'project', $submission, $submittedAt, [
                    'project' => $project->slug,
                    'git_url' => $submission->git_url,
                    'attempt' => $attempt,
                ]);
            }

            if ($testedAt) {
                $this->activity($user, 'tests_completed', 'testing', $submission, $testedAt, [
                    'tests_passed' => $testsPassed,
                    'tests_total' => $testsTotal,
                    'test_score' => $baseScore,
                ]);
            }

            if (in_array($status, ['passed', 'failed', 'reviewed'], true)) {
                $reviewResult = $this->seedCompletedReviews($submission, $project, $index, $baseScore, $firstReviewAt, $requiredReviews);
                $reviewsReceived += $reviewResult['count'];
                $reviewScores = array_merge($reviewScores, $reviewResult['scores']);

                $finalScore = count($reviewResult['scores']) ? round(array_sum($reviewResult['scores']) / count($reviewResult['scores']), 2) : $baseScore;
                $completedAt = $firstReviewAt->copy()->addHours(($requiredReviews - 1) * 3 + 2);
                $finalStatus = $status === 'failed' || $finalScore < ($project->passing_score ?? 70) ? 'failed' : ($status === 'reviewed' ? 'reviewed' : 'passed');

                $submission->forceFill([
                    'status' => $finalStatus,
                    'review_score' => $finalScore,
                    'reviews_received' => $reviewResult['count'],
                    'final_score' => in_array($finalStatus, ['passed', 'failed'], true) ? $finalScore : null,
                    'reviewed_at' => $completedAt,
                    'completed_at' => in_array($finalStatus, ['passed', 'failed'], true) ? $completedAt : null,
                    'updated_at' => $completedAt,
                ])->save();

                $this->activity($user, 'peer_reviews_completed', 'review', $submission, $completedAt, [
                    'reviews_received' => $reviewResult['count'],
                    'review_score' => $finalScore,
                    'reviewer_points_spent' => $reviewResult['count'],
                    'coin_balance_before' => $coinBalance,
                    'coin_balance_after' => max(0, $coinBalance - $reviewResult['count']),
                ]);
                $coinBalance = max(0, $coinBalance - $reviewResult['count']);

                if ($finalStatus === 'passed') {
                    $completed++;
                    $totalXp += (int) $project->xp_reward;
                    $this->seedXp($user, $submission, (int) $project->xp_reward, $totalXp, $completedAt, "Project '{$project->title}' completed – {$finalScore}%");
                    $this->notification($user, 'project_passed', 'Project passed', "{$project->title} passed with {$finalScore}%", $completedAt);
                } elseif ($finalStatus === 'failed') {
                    $failed++;
                    $this->notification($user, 'project_failed', 'Project failed', "{$project->title} failed with {$finalScore}%. Resubmission is available.", $completedAt, 'high');
                }
            } elseif ($status === 'in_review') {
                $coinBefore = $coinBalance;
                $coinBalance = max(0, $coinBalance - 1);
                $this->seedPendingReview($submission, $project, $index, $projectIndex, $firstReviewAt);
                $this->activity($user, 'review_slot_booked', 'calendar', $submission, $firstReviewAt->copy()->subMinutes(20), [
                    'reviewer_points_spent' => 1,
                    'coin_balance_before' => $coinBefore,
                    'coin_balance_after' => $coinBalance,
                    'starts_at' => $firstReviewAt->toDateTimeString(),
                ]);
            }
        }

        $reviewsGiven = Review::where('reviewer_id', $user->id)->where('status', 'completed')->count();
        $averageReviewScore = count($reviewScores) ? round(array_sum($reviewScores) / count($reviewScores), 2) : 0;
        $completionRate = $started > 0 ? round(($completed / $started) * 100, 2) : 0;

        UserStat::updateOrCreate(
            ['user_id' => $user->id],
            [
                'projects_started' => $started,
                'projects_completed' => $completed,
                'projects_failed' => $failed,
                'completion_rate' => $completionRate,
                'reviews_given' => $reviewsGiven,
                'reviews_received' => $reviewsReceived,
                'average_review_score' => $averageReviewScore,
                'total_learning_hours' => $started * 7 + $completed * 5,
                'current_streak_days' => min(21, 2 + ($index % 10)),
                'longest_streak_days' => min(60, 8 + ($index % 35)),
                'discussions_created' => $index % 5,
                'helpful_replies' => ($index * 3) % 17,
            ]
        );

        $user->forceFill([
            'total_xp' => $totalXp,
            'level' => max(1, min(10, 1 + intdiv($totalXp, 600))),
        ])->save();
    }

    private function submission(
        User $user,
        Project $project,
        int $attempt,
        string $status,
        string $gitUrl,
        int $testsPassed,
        int $testsTotal,
        float $testScore,
        Carbon $enrolledAt,
        ?Carbon $submittedAt,
        ?Carbon $testedAt,
    ): Submission {
        $attributes = Submission::factory()->make([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'submission_type' => 'git',
            'git_url' => $gitUrl,
            'status' => $status,
            'tests_passed' => $testsPassed,
            'tests_total' => $testsTotal,
            'test_score' => $testScore,
            'test_output' => "Chronological demo submission for {$user->username} / {$project->slug}. {$testsPassed}/{$testsTotal} tests passed.",
            'test_details' => $this->testDetails($testsPassed, $testsTotal),
            'submitted_at' => $submittedAt ?? $enrolledAt,
            'tested_at' => $testedAt,
            'attempt_number' => $attempt,
        ])->getAttributes();

        $submission = Submission::updateOrCreate(
            [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'attempt_number' => $attempt,
            ],
            $attributes
        );

        $submission->forceFill([
            'created_at' => $enrolledAt,
            'updated_at' => $testedAt ?? $submittedAt ?? $enrolledAt,
        ])->save();

        return $submission;
    }

    private function seedCompletedReviews(Submission $submission, Project $project, int $index, float $baseScore, Carbon $firstReviewAt, int $required): array
    {
        $reviewers = $this->reviewersFor($submission->user_id, $index, $required);
        $scores = [];

        foreach ($reviewers as $offset => $reviewer) {
            $score = max(0, min(100, (int) round($baseScore + ($offset * 4) - 3)));
            $scores[] = $score;
            $startedAt = $firstReviewAt->copy()->addHours($offset * 3);
            $completedAt = $startedAt->copy()->addMinutes(45 + (($index + $offset) % 50));

            $attributes = Review::factory()
                ->completed($score)
                ->make([
                    'submission_id' => $submission->id,
                    'reviewer_id' => $reviewer->id,
                    'feedback' => 'Demo completed review: структура проекта проверена, основные сценарии разобраны.',
                    'private_notes' => 'Seeded chronological review for admin/UI load testing.',
                    'checklist_data' => $this->checklistData($project),
                    'time_spent_minutes' => $startedAt->diffInMinutes($completedAt),
                    'is_auto_assigned' => false,
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                ])->getAttributes();

            $review = Review::updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'reviewer_id' => $reviewer->id,
                ],
                $attributes
            );
            $review->forceFill(['created_at' => $startedAt, 'updated_at' => $completedAt])->save();

            $this->activity($reviewer, 'review_completed', 'review', $review, $completedAt, [
                'submission_id' => $submission->id,
                'student_username' => $submission->user->username,
                'project' => $project->slug,
                'score' => $score,
            ]);
        }

        return ['count' => count($scores), 'scores' => $scores];
    }

    private function seedPendingReview(Submission $submission, Project $project, int $index, int $projectIndex, Carbon $startsAt): void
    {
        $reviewer = $this->reviewersFor($submission->user_id, $index + $projectIndex, 1)->first();
        if (! $reviewer) {
            return;
        }

        if ($index % 3 !== 0) {
            $startsAt = now()->subMinutes(45)->second(0);
        }

        $slotAttributes = CalendarSlot::factory()
            ->booked($submission->user)
            ->make([
                'user_id' => $reviewer->id,
                'project_id' => $project->id,
                'date' => $startsAt->toDateString(),
                'start_time' => $startsAt->format('H:i'),
                'end_time' => $startsAt->copy()->addHour()->format('H:i'),
                'notes' => 'Demo chronological pending P2P review slot.',
            ])->getAttributes();

        $slot = CalendarSlot::updateOrCreate(
            [
                'user_id' => $reviewer->id,
                'project_id' => $project->id,
                'date' => $startsAt->toDateString(),
                'start_time' => $startsAt->format('H:i'),
            ],
            $slotAttributes
        );
        $slot->forceFill(['created_at' => $startsAt->copy()->subHours(2), 'updated_at' => $startsAt->copy()->subHours(2)])->save();

        $reviewAttributes = Review::factory()->make([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'is_auto_assigned' => false,
            'started_at' => $startsAt,
            'completed_at' => $startsAt->copy()->addDay(),
        ])->getAttributes();

        $review = Review::updateOrCreate(
            [
                'submission_id' => $submission->id,
                'reviewer_id' => $reviewer->id,
            ],
            $reviewAttributes
        );
        $review->forceFill(['created_at' => $startsAt->copy()->subHours(2), 'updated_at' => $startsAt->copy()->subHours(2)])->save();

        $this->notification($reviewer, 'review_assigned', 'New peer review', "Review {$project->title} for {$submission->user->username}", $startsAt->copy()->subHours(2));
    }

    private function reviewersFor(int $studentId, int $seed, int $count): \Illuminate\Support\Collection
    {
        return collect($this->demoUsers)
            ->filter(fn (User $candidate) => $candidate->id !== $studentId)
            ->sortBy(fn (User $candidate) => abs($candidate->id - $seed))
            ->take($count)
            ->values();
    }

    private function seedXp(User $user, Submission $submission, int $amount, int $balanceAfter, Carbon $at, string $description): void
    {
        $tx = XpTransaction::updateOrCreate(
            [
                'user_id' => $user->id,
                'source_type' => 'submission',
                'source_id' => $submission->id,
            ],
            [
                'amount' => $amount,
                'reason' => 'project_completed',
                'description' => $description,
                'balance_after' => $balanceAfter,
            ]
        );
        $tx->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

        $this->activity($user, 'xp_awarded', 'gamification', $tx, $at, [
            'amount' => $amount,
            'balance_after' => $balanceAfter,
        ]);
    }

    private function repositoryUrl(User $user, Project $project, int $attempt): string
    {
        if ($this->withGitlab && $this->gitlab && $this->gitlabRepositoriesCreated < $this->gitlabRepositoryLimit && $user->hasConnectedGitlab()) {
            try {
                $repository = $this->gitlab->createStudentProjectRepository(
                    token: $user->connectedGitlabToken(),
                    projectData: $project->toArray(),
                    ownerName: $user->username ?? $user->email,
                    attemptNumber: $attempt,
                );
                $this->gitlabRepositoriesCreated++;

                return $repository['http_url_to_repo'] ?? $repository['web_url'] ?? $this->fakeRepoUrl($user, $project, $attempt);
            } catch (\Throwable $e) {
                Log::warning('Demo GitLab repository creation failed, falling back to fake URL', [
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->fakeRepoUrl($user, $project, $attempt);
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
                'ip_address' => '127.0.' . (($user->id % 200) + 1) . '.' . (($subject->getKey() % 200) + 1),
                'user_agent' => '21-LMS Demo Seeder',
            ]
        );
    }

    private function notification(User $user, string $type, string $title, string $message, Carbon $at, string $priority = 'normal'): void
    {
        Notification::updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'created_at' => $at,
            ],
            [
                'message' => $message,
                'action_url' => null,
                'data' => ['seeded' => true],
                'is_read' => $at->lt(now()->subDays(2)),
                'read_at' => $at->lt(now()->subDays(2)) ? $at->copy()->addHours(6) : null,
                'priority' => $priority,
            ]
        );
    }

    private function demoName(int $index): string
    {
        $first = ['Alex', 'Mira', 'Timur', 'Sofia', 'Rustam', 'Nika', 'Aziz', 'Lola', 'Daniel', 'Malika'];
        $last = ['Vector', 'Kernel', 'Matrix', 'Pointer', 'Runtime', 'Binary', 'Stack', 'Queue', 'Lambda', 'Commit'];

        return $first[$index % count($first)] . ' ' . $last[intdiv($index, count($first)) % count($last)];
    }

    private function fakeRepoUrl(User $user, Project $project, int $attempt): string
    {
        $suffix = $attempt > 1 ? '-attempt-' . $attempt : '';

        return rtrim((string) config('services.gitlab.url'), '/') . '/' . $user->username . '/school21-' . $project->slug . $suffix . '.git';
    }

    private function testDetails(int $passed, int $total): array
    {
        return collect(range(1, $total))
            ->map(fn (int $number) => [
                'name' => 'demo_test_' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'status' => $number <= $passed ? 'passed' : 'failed',
            ])
            ->all();
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
