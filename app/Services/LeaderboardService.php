<?php

namespace App\Services;

use App\Models\Leaderboard;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class LeaderboardService
{
    protected array $periods = ['daily', 'weekly', 'monthly', 'all_time'];

    /**
     * Recalculate the leaderboard for a given period.
     */
    public function recalculate(string $period = 'daily'): void
    {
        if (!in_array($period, $this->periods)) {
            throw new \InvalidArgumentException("Invalid period: {$period}");
        }

        $periodStart = match ($period) {
            'daily' => today()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            'all_time' => null,
        };

        $periodEnd = $periodStart?->copy()->addDays(
            match ($period) {
                'daily' => 1,
                'weekly' => 7,
                'monthly' => now()->daysInMonth,
                default => 0,
            }
        );

        $users = User::withCount([
            'submissions as xp_earned' => function ($q) use ($periodStart, $periodEnd) {
                if ($periodStart) {
                    $q->whereBetween('submitted_at', [$periodStart, $periodEnd]);
                }
                $q->where('status', 'passed');
            },
        ])
        ->withCount([
            'submissions as projects_completed' => function ($q) use ($periodStart, $periodEnd) {
                if ($periodStart) {
                    $q->whereBetween('submitted_at', [$periodStart, $periodEnd]);
                }
                $q->where('status', 'passed');
            },
        ])
        ->withCount([
            'submissions as projects_failed' => function ($q) use ($periodStart, $periodEnd) {
                if ($periodStart) {
                    $q->whereBetween('submitted_at', [$periodStart, $periodEnd]);
                }
                $q->where('status', 'failed');
            },
        ])
        ->get();

        $sorted = $period === 'all_time'
            ? $users->sortByDesc('total_xp')
            : $users->sortByDesc('projects_completed');

        Leaderboard::where('period', $period)
            ->where(function ($q) use ($periodStart, $periodEnd) {
                if ($periodStart) {
                    $q->where('period_start', $periodStart)
                      ->where('period_end', $periodEnd);
                }
            })
            ->delete();

        foreach ($sorted as $rank => $user) {
            Leaderboard::create([
                'user_id' => $user->id,
                'period' => $period,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'xp_earned' => $period === 'all_time' ? $user->total_xp : ($user->xp_earned ?? 0),
                'projects_completed' => $user->projects_completed ?? 0,
                'projects_failed' => $user->projects_failed ?? 0,
                'rank' => $rank + 1,
            ]);
        }
    }

    /**
     * Get the ranked leaderboard for a period.
     */
    public function getLeaderboard(string $period = 'daily'): Collection
    {
        return Leaderboard::where('period', $period)
            ->with('user:id,name,username')
            ->orderBy('rank')
            ->get();
    }

    /**
     * Get a user's rank in a given period.
     */
    public function getRank(int $userId, string $period = 'daily'): ?int
    {
        return Leaderboard::where('user_id', $userId)
            ->where('period', $period)
            ->value('rank');
    }
}
