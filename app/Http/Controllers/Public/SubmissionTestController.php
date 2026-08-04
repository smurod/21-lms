<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\RunSubmissionTestsJob;
use App\Models\Admin\Review;
use App\Models\Admin\Submission;
use App\Models\TestRun;
use App\Services\GitlabService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionTestController extends Controller
{
    public function index(Request $request, Submission $submission): View
    {
        $this->authorizeSubmissionAccess($request, $submission);

        $submission->load(['project', 'user']);
        $testRuns = $submission->testRuns()
            ->with('results.projectTest')
            ->latest('id')
            ->get();
        $canRerun = $this->canRerun($request, $submission);
        $rerunReason = $this->rerunBlockReason($request, $submission);

        return view('public.tests.index', compact('submission', 'testRuns', 'canRerun', 'rerunReason'));
    }

    public function show(Request $request, Submission $submission, TestRun $testRun): View
    {
        $this->authorizeSubmissionAccess($request, $submission);

        abort_unless($testRun->submission_id === $submission->id, 404);

        $submission->load(['project', 'user']);
        $testRun->load(['results.projectTest']);
        $canRerun = $this->canRerun($request, $submission);
        $rerunReason = $this->rerunBlockReason($request, $submission);

        return view('public.tests.show', compact('submission', 'testRun', 'canRerun', 'rerunReason'));
    }

    public function rerun(Request $request, Submission $submission, GitlabService $gitlab): RedirectResponse
    {
        $this->authorizeSubmissionAccess($request, $submission);
        $submission->load(['project', 'user']);

        if (! $this->canRerun($request, $submission)) {
            return back()->withErrors(['tests' => $this->rerunBlockReason($request, $submission)]);
        }

        $this->lockCommitIfMissing($submission, $gitlab);

        $submission->update([
            'status' => 'queued',
            'tests_passed' => 0,
            'tests_total' => 0,
            'test_score' => 0,
            'test_output' => trim(($submission->test_output ?? '') . "\n\nAutotest rerun queued at " . now()->toDateTimeString()),
            'tested_at' => null,
            'completed_at' => null,
        ]);

        RunSubmissionTestsJob::dispatch($submission->id);

        return redirect()->route('public.submissions.tests.index', $submission)
            ->with('success', 'Autotest rerun queued. Start the tests queue worker if it is not running.');
    }

    private function authorizeSubmissionAccess(Request $request, Submission $submission): void
    {
        $user = $request->user();

        $allowed = $user && (
                (int) $submission->user_id === (int) $user->id
                || $user->hasRole('admin')
                || Review::where('submission_id', $submission->id)
                    ->where('reviewer_id', $user->id)
                    ->exists()
            );

        abort_unless($allowed, 403);
    }

    private function canRerun(Request $request, Submission $submission): bool
    {
        return $this->rerunBlockReason($request, $submission) === null;
    }

    private function rerunBlockReason(Request $request, Submission $submission): ?string
    {
        $user = $request->user();
        $submission->loadMissing('project');
        $project = $submission->project;

        if (! $project?->has_automated_tests) {
            return 'Automated tests are not enabled for this project.';
        }

        if ($submission->testRuns()
            ->whereIn('status', ['queued', 'running'])
            ->exists()) {
            return 'An autotest run is already queued or running.';
        }

        if ($submission->status === 'testing' || $submission->status === 'queued') {
            return 'This submission is already waiting for autotests.';
        }

        if ($user?->hasRole('admin')) {
            return null;
        }

        if ((int) $submission->user_id !== (int) $user?->id) {
            return 'Only the submission owner or an admin can rerun autotests.';
        }

        $maxStudentReruns = max(0, (int) env('TEST_RUNNER_STUDENT_RERUN_LIMIT', 3));
        $runsCount = $submission->testRuns()->count();

        if ($runsCount >= $maxStudentReruns + 1) {
            return "Student rerun limit reached ({$maxStudentReruns}).";
        }

        if ($submission->status === 'passed') {
            return 'Passed submissions can be rerun only by an admin.';
        }

        if (! in_array($submission->status, ['failed', 'tested', 'in_review', 'reviewed'], true)) {
            return 'Autotests can be rerun after a failed/tested submission or by admin.';
        }

        return null;
    }

    private function lockCommitIfMissing(Submission $submission, GitlabService $gitlab): void
    {
        if (! empty($submission->git_commit_hash) || empty($submission->git_url)) {
            return;
        }

        $hash = $gitlab->latestCommitHash(
            repositoryUrl: $submission->git_url,
            branch: $submission->project?->default_branch ?: 'main',
            token: $submission->user?->connectedGitlabToken(),
        );

        if ($hash) {
            $submission->forceFill(['git_commit_hash' => $hash])->save();
        }
    }
}
