<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Review;
use App\Models\Admin\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Review> */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $startedAt = now()->subDays($this->faker->numberBetween(1, 10));

        return [
            'submission_id' => Submission::factory(),
            'reviewer_id' => User::factory(),
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
            'started_at' => $startedAt,
            'completed_at' => $startedAt->copy()->addDay(),
        ];
    }

    public function completed(int $score = 80): static
    {
        return $this->state(function () use ($score) {
            $startedAt = now()->subDays(4);

            return [
                'score' => $score,
                'feedback' => 'Factory generated completed review feedback.',
                'private_notes' => 'Factory generated private notes.',
                'time_spent_minutes' => 30,
                'confidence_score' => 0.90,
                'status' => 'completed',
                'started_at' => $startedAt,
                'completed_at' => $startedAt->copy()->addHours(2),
            ];
        });
    }
}
