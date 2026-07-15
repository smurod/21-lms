<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    /**
     * Check and unlock achievements for a user after an event.
     */
    public function checkAndUnlock(int $userId, string $event, ?array $metadata = null): void
    {
        $achievements = Achievement::where('is_active', true)
            ->where('condition_type', 'event')
            ->where(function ($q) use ($event) {
                $q->where('condition_value', $event)
                  ->orWhere('condition_value', 'any');
            })
            ->get();

        if ($achievements->isEmpty()) {
            return;
        }

        $user = User::findOrFail($userId);

        DB::transaction(function () use ($user, $achievements, $event, $metadata) {
            foreach ($achievements as $achievement) {
                // Skip if already unlocked
                if ($user->achievements()->where('achievement_id', $achievement->id)->whereNotNull('unlocked_at')->exists()) {
                    continue;
                }

                // Check condition params
                $params = $achievement->condition_params ?? [];
                $required = $params['count'] ?? 1;

                // Verify condition based on event type
                $fulfilled = $this->checkCondition($user, $achievement, $event, $metadata);

                if ($fulfilled) {
                    $this->unlock($user, $achievement);
                }
            }
        });
    }

    protected function checkCondition(User $user, Achievement $achievement, string $event, ?array $metadata): bool
    {
        $params = $achievement->condition_params ?? [];
        $count = $params['count'] ?? 1;
        $eventParam = $params['event'] ?? $event;

        switch ($achievement->condition_value) {
            case 'projects_completed':
                return $user->submissions()
                    ->where('status', 'passed')
                    ->count() >= $count;

            case 'reviews_given':
                return $user->chatsAsReviewer() // using reviewer as proxy
                    ->where('reviews_count', '>=', $count)
                    ->exists();

            case 'xp_threshold':
                return $user->total_xp >= $count;

            case 'streak_days':
                return ($user->stat->longest_streak_days ?? 0) >= $count;

            case 'projects_failed':
                return $user->submissions()
                    ->where('status', 'failed')
                    ->count() >= $count;

            case 'any':
                return true;

            default:
                // Count specific event occurrences
                return UserActivity::where('user_id', $user->id)
                    ->where('event_type', $eventParam ?? $event)
                    ->count() >= $count;
        }
    }

    protected function unlock(User $user, Achievement $achievement): void
    {
        // Create UserAchievement record
        $userAchievement = $user->achievements()->create([
            'achievement_id' => $achievement->id,
            'progress_data' => [
                'unlocked_at' => now()->toISOString(),
                'progress' => 1,
            ],
            'unlocked_at' => now(),
        ]);

        // Award XP if applicable
        if ($achievement->xp_reward > 0) {
            $user->increment('total_xp', $achievement->xp_reward);

            // Record XP transaction
            \App\Models\XpTransaction::create([
                'user_id' => $user->id,
                'project_id' => $achievement->id,
                'xp_earned' => $achievement->xp_reward,
                'reason' => "Achievement unlocked: {$achievement->name}",
            ]);
        }
    }
}
