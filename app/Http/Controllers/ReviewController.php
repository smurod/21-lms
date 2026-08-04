<?php

namespace App\Http\Controllers;

use App\Models\Admin\Review;
use App\Services\ReviewAssignmentService;
use App\Services\XpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request, ReviewAssignmentService $reviewerService)
    {
        $user = auth()->user();

        $this->syncReviewStartTimesForReviewer($user->id);

        // Reviews assigned to this user as reviewer
        $reviews = Review::where('reviewer_id', $user->id)
            ->with(['submission.project', 'submission.user'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('completed_at')
            ->paginate(15);

        $view = $request->routeIs('admin.*') ? 'admin.reviews.index' : 'public.reviews.index';

        return view($view, compact('reviews'));
    }

    public function show(Review $review, ReviewAssignmentService $reviewerService)
    {
        // Ensure reviewer is authorized
        if ($review->reviewer_id !== auth()->id()) {
            abort(403, 'Unauthorized review access.');
        }

        $this->syncReviewStartTime($review);
        $review->refresh();

        if ($review->started_at && $review->started_at->isFuture()) {
            return redirect()->route('reviews.index')
                ->withErrors(['review' => 'Review ещё не начался. Дождитесь назначенного времени.']);
        }

        if ($review->status === 'pending') {
            $review->update([
                'status' => 'in_progress',
            ]);
        }

        $review->load(['submission.user', 'submission.project.checklists', 'submission.testResults', 'submission.reviews.reviewer']);

        $view = request()->routeIs('admin.*') ? 'admin.reviews.show' : 'public.reviews.show';

        return view($view, compact('review'));
    }

    public function submit(Review $review, Request $request, XpService $xp)
    {
        if ($review->reviewer_id !== auth()->id()) {
            abort(403, 'Unauthorized review access.');
        }

        $data = $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'feedback' => 'required|string',
            'private_notes' => 'nullable|string',
            'confidence_score' => 'nullable|numeric|min:0|max:100',
            'checklist' => 'nullable|array',
            'checklist.*' => 'required|in:passed,failed,not_applicable',
        ]);

        $review->loadMissing('submission.project.checklists');
        $requiredChecklistKeys = $review->submission->project->checklists
            ->where('is_required', true)
            ->pluck('item_key')
            ->all();
        $evaluatedChecklist = array_keys($data['checklist'] ?? []);
        $missingChecklist = array_diff($requiredChecklistKeys, $evaluatedChecklist);

        if (!empty($missingChecklist)) {
            return back()
                ->withInput()
                ->withErrors(['checklist' => 'Оцените все обязательные пункты чек-листа перед отправкой review.']);
        }

        $review->update([
            'score' => $data['score'],
            'feedback' => $data['feedback'],
            'private_notes' => $data['private_notes'] ?? null,
            'checklist_data' => $this->formatChecklistData($review, $data['checklist'] ?? []),
            'confidence_score' => isset($data['confidence_score']) ? round(((float) $data['confidence_score']) / 100, 2) : 1.0,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $submission = $review->submission()->with(['project', 'user'])->first();
        $project = $submission->project;

        // Recalculate review stats
        $completedReviews = $submission->reviews()->where('status', 'completed')->get();
        $completedCount = $completedReviews->count();
        $requiredCount = $project->required_reviews_count ?? 2;

        $averageScore = $completedReviews->avg('score') ?? 0;

        // Update intermediate review state
        $submission->update([
            'review_score' => $averageScore,
            'reviews_received' => $completedCount,
            'reviewed_at' => now(),
            'status' => $completedCount >= $requiredCount ? 'reviewed' : 'in_review',
        ]);

        // Finalize if enough reviews. If the project also has automated tests,
        // tests run AFTER the required P2P reviews are completed successfully.
        $passingScore = $project->passing_score ?? 70;
        if ($completedCount >= $requiredCount) {
            if ($averageScore < $passingScore) {
                $submission->update([
                    'status' => 'failed',
                    'final_score' => $averageScore,
                    'completed_at' => now(),
                ]);

                return redirect()->route('reviews.index')
                    ->with('error', "Review saved. Project FAILED on P2P review ({$averageScore}% < {$passingScore}%). User can resubmit.");
            }

            if ($project->has_automated_tests) {
                $submission->update([
                    'status' => 'queued',
                    'review_score' => $averageScore,
                    'reviews_received' => $completedCount,
                ]);

                \App\Jobs\RunSubmissionTestsJob::dispatch($submission->id);

                return redirect()->route('reviews.index')
                    ->with('success', "Review saved. P2P passed ({$averageScore}%). Autotests queued and will decide final passed/failed status.");
            }

            $submission->update([
                'status' => 'passed',
                'final_score' => $averageScore,
                'completed_at' => now(),
            ]);
            $this->awardSubmissionXp($submission, $xp);

            return redirect()->route('reviews.index')
                ->with('success', "Review saved. Project PASSED ({$averageScore}%). XP awarded: {$project->xp_reward}.");
        }

        // Still waiting for more reviews. No auto-assignment here:
        // the student must manually choose the next free slot in the calendar.
        return redirect()->route('reviews.index')
            ->with('success', "Review submitted ({$completedCount}/{$requiredCount}). Avg: ".round($averageScore,1)."%. Student must book the next free slot manually.");
    }

    private function awardSubmissionXp(\App\Models\Admin\Submission $submission, XpService $xp): void
    {
        $submission->loadMissing('project');
        $project = $submission->project;

        if (! $project || (int) $project->xp_reward <= 0) {
            return;
        }

        $alreadyAwarded = \App\Models\XpTransaction::where([
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

    private function formatChecklistData(Review $review, array $checklistResults): array
    {
        return $review->submission->project->checklists
            ->map(fn ($item) => [
                'item_key' => $item->item_key,
                'item_label' => $item->item_label,
                'is_required' => $item->is_required,
                'result' => $checklistResults[$item->item_key] ?? null,
            ])
            ->values()
            ->all();
    }

    private function syncReviewStartTimesForReviewer(int $reviewerId): void
    {
        Review::where('reviewer_id', $reviewerId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with('submission')
            ->get()
            ->each(fn (Review $review) => $this->syncReviewStartTime($review));
    }

    private function syncReviewStartTime(Review $review): void
    {
        $review->loadMissing('submission');
        $submission = $review->submission;

        if (! $submission) {
            return;
        }

        $slot = \App\Models\CalendarSlot::where('status', 'booked')
            ->where('user_id', $review->reviewer_id)
            ->where('booked_by_user_id', $submission->user_id)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->first();

        if (! $slot) {
            return;
        }

        $startsAt = \Illuminate\Support\Carbon::parse($slot->date->toDateString() . ' ' . $slot->start_time);
        $deadlineAt = $startsAt->copy()->addHours(24);

        if (! $review->started_at || ! $review->started_at->equalTo($startsAt)) {
            $review->forceFill([
                'started_at' => $startsAt,
                'completed_at' => $deadlineAt,
            ])->save();
        }
    }

    public function destroy(Review $review)
    {
        if ($review->reviewer_id !== auth()->id()) {
            abort(403, 'Unauthorized review access.');
        }

        $review->delete();

        return back()->with('success', 'Review declined.');
    }
}
