<?php

namespace App\Services;

use App\Models\Admin\Submission;
use App\Models\Admin\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewAssignmentService
{
    protected int $reviewersCount = 2;

    public function setReviewersCount(int $count): self
    {
        $this->reviewersCount = $count;
        return $this;
    }

    /**
     * Assign reviewers for a given submission.
     */
    public function assign(Submission $submission): void
    {
        // Don't assign if submission is already passed
        if ($submission->status === 'passed') {
            return;
        }

        $projectId = $submission->project_id;

        // Find users who have passed the same project
        $qualifiedUsers = User::whereHas('submissions', function ($q) use ($projectId) {
            $q->where('project_id', $projectId)
                ->where('status', 'passed');
        })->where('id', '!=', $submission->user_id)
            ->inRandomOrder()
            ->limit($this->reviewersCount)
            ->get();

        // Remove users already assigned to this submission
        $alreadyAssigned = Review::where('submission_id', $submission->id)
            ->pluck('reviewer_id')
            ->toArray();

        $qualifiedUsers = $qualifiedUsers->filter(fn ($u) => !in_array($u->id, $alreadyAssigned));

        DB::transaction(function () use ($submission, $qualifiedUsers) {
            foreach ($qualifiedUsers as $reviewer) {
                Review::create([
                    'submission_id' => $submission->id,
                    'reviewer_id' => $reviewer->id,
                    'status' => 'pending',
                    'is_auto_assigned' => true,
                    'started_at' => now(),
                    'completed_at' => now()->addDays(3), // 3-day deadline
                ]);
            }
        });
    }
}
