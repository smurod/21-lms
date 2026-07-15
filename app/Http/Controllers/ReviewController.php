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

        // Reviews assigned to this user as reviewer
        $reviews = Review::where('reviewer_id', $user->id)
            ->with(['submission.project', 'submission.user'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('completed_at')
            ->paginate(15);

        // Trigger auto-assignment for pending submissions without enough reviews
        $pendingSubmissions = \App\Models\Admin\Submission::where('status', 'in_progress')
            ->whereDoesntHave('reviews', function ($q) {
                $q->where('status', 'pending');
            })
            ->with('project')
            ->get();

        foreach ($pendingSubmissions as $submission) {
            $reviewerService->assign($submission);
        }

        return view('admin.reviews.index', compact('reviews'));
    }

    public function show(Review $review, ReviewAssignmentService $reviewerService)
    {
        // Ensure reviewer is authorized
        if ($review->reviewer_id !== auth()->id()) {
            abort(403, 'Unauthorized review access.');
        }

        // Auto-assign additional reviewers if needed
        $reviewerService->assign($review->submission);

        $review->load(['submission.user', 'submission.project', 'submission.testResults', 'submission.reviews']);

        return view('admin.reviews.show', compact('review'));
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
        ]);

        $review->update([
            'score' => $data['score'],
            'feedback' => $data['feedback'],
            'private_notes' => $data['private_notes'] ?? null,
            'confidence_score' => $data['confidence_score'] ?? 1.0,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $submission = $review->submission()->with('project')->first();
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

        // Finalize if enough reviews AND passing score
        $passingScore = $project->passing_score ?? 70;
        if ($completedCount >= $requiredCount) {
            if ($averageScore >= $passingScore) {
                // PASSED → close submission, award XP
                if ($submission->status !== 'passed') {
                    $submission->update([
                        'status' => 'passed',
                        'final_score' => $averageScore,
                        'completed_at' => now(),
                    ]);

                    // Award XP (idempotent guard: check if already awarded)
                    $alreadyAwarded = \App\Models\XpTransaction::where([
                        'user_id' => $submission->user_id,
                        'source_type' => 'submission',
                        'source_id' => $submission->id,
                    ])->exists();

                    if (! $alreadyAwarded && $project->xp_reward > 0) {
                        $xp->add(
                            userId: $submission->user_id,
                            amount: (int) $project->xp_reward,
                            reason: 'project_completed',
                            sourceType: 'submission',
                            sourceId: $submission->id,
                            description: "Project '{$project->title}' completed – {$averageScore}%"
                        );
                    }
                }

                return redirect()->route('admin.reviews.index')
                    ->with('success', "Review saved. Project PASSED ({$averageScore}%). XP awarded: {$project->xp_reward}.");
            } else {
                // FAILED → allow resubmission
                $submission->update([
                    'status' => 'failed',
                    'final_score' => $averageScore,
                    'completed_at' => now(),
                ]);

                return redirect()->route('admin.reviews.index')
                    ->with('error', "Review saved. Project FAILED ({$averageScore}% < {$passingScore}%). User can resubmit.");
            }
        }

        // Still waiting for more reviews
        return redirect()->route('admin.reviews.show', $review)
            ->with('success', "Review submitted ({$completedCount}/{$requiredCount}). Avg: ".round($averageScore,1)."%");
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
