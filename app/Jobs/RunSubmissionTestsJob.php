<?php

namespace App\Jobs;

use App\Models\Admin\Submission;
use App\Models\XpTransaction;
use App\Services\TestRunnerService;
use App\Services\XpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunSubmissionTestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public function __construct(public int $submissionId)
    {
        $this->onQueue('tests');
        $this->timeout = max(60, (int) env('TEST_RUNNER_JOB_TIMEOUT', 600));
    }

    public function handle(TestRunnerService $runner, XpService $xp): void
    {
        $submission = Submission::with(['project', 'user'])->findOrFail($this->submissionId);
        $project = $submission->project;

        if (! $project?->has_automated_tests) {
            throw new \RuntimeException("Submission {$submission->id} project has no automated tests enabled.");
        }

        $submission = $runner->run($submission);
        $submission->loadMissing('project');
        $project = $submission->project;

        if (! $runner->passes($submission)) {
            $submission->update([
                'status' => 'failed',
                'final_score' => $submission->test_score,
                'completed_at' => now(),
            ]);

            Log::info('Submission autotests failed', [
                'submission_id' => $submission->id,
                'project_id' => $project?->id,
                'test_score' => $submission->test_score,
            ]);

            return;
        }

        $reviewScore = $submission->review_score;
        $finalScore = $project->requires_peer_review && $reviewScore !== null
            ? round(((float) $reviewScore + (float) $submission->test_score) / 2, 2)
            : (float) $submission->test_score;

        $submission->update([
            'status' => 'passed',
            'final_score' => $finalScore,
            'completed_at' => now(),
        ]);

        $this->awardXpOnce($submission, $xp);

        Log::info('Submission autotests passed', [
            'submission_id' => $submission->id,
            'project_id' => $project?->id,
            'test_score' => $submission->test_score,
            'final_score' => $finalScore,
        ]);
    }

    private function awardXpOnce(Submission $submission, XpService $xp): void
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

        if ($alreadyAwarded) {
            return;
        }

        $xp->add(
            userId: $submission->user_id,
            amount: (int) $project->xp_reward,
            reason: 'project_completed',
            sourceType: 'submission',
            sourceId: $submission->id,
            description: "Project '{$project->title}' completed – {$submission->final_score}%"
        );
    }

    public function failed(\Throwable $e): void
    {
        $submission = Submission::find($this->submissionId);

        if ($submission) {
            $submission->update([
                'status' => 'failed',
                'test_output' => trim(($submission->test_output ?? '') . "\n\nTest job failed: " . $e->getMessage()),
                'completed_at' => now(),
            ]);
        }

        Log::error('RunSubmissionTestsJob failed', [
            'submission_id' => $this->submissionId,
            'error' => $e->getMessage(),
        ]);
    }
}
