<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Services\LeaderboardService;
use Illuminate\View\View;

class GamificationController extends Controller
{
    public function achievements()
    {
        $user = auth()->user();

        $unlocked = $user->achievements()
            ->with('achievement')
            ->whereNotNull('unlocked_at')
            ->get();

        $locked = Achievement::where('is_active', true)
            ->whereDoesntHave('users', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereNotNull('unlocked_at');
            })
            ->where('is_hidden', false)
            ->get();

        $hidden = Achievement::where('is_active', true)
            ->whereDoesntHave('users', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereNotNull('unlocked_at');
            })
            ->where('is_hidden', true)
            ->get();

        return view('public.gamification.achievements', compact('unlocked', 'locked', 'hidden'));
    }

    public function leaderboard(LeaderboardService $service)
    {
        $period = request('period', 'weekly');
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'all_time'])) {
            $period = 'weekly';
        }

        // Ensure leaderboard is calculated
        $service->recalculate($period);

        $leaderboard = $service->getLeaderboard($period);
        $userRank = $service->getRank(auth()->id(), $period);

        return view('public.gamification.leaderboard', compact('leaderboard', 'userRank', 'period'));
    }

    public function profile()
    {
        $user = auth()->user();

        $unlocked = $user->achievements()
            ->with('achievement')
            ->whereNotNull('unlocked_at')
            ->orderByDesc('unlocked_at')
            ->limit(10)
            ->get();

        $stats = $user->stat;

        $xpLevels = config('xp.levels', []);

        return view('public.gamification.profile', compact('unlocked', 'stats', 'xpLevels'));
    }
}
