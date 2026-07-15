<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Admin\Submission;
use App\Models\Course;
use App\Models\Notification;
use App\Models\UserActivity;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function home(Request $request)
    {
        $user = $request->user();

        // === Hero section data ===
        $levels = config('xp.levels');
        $currentLevel = $user->getCurrentLevelAttribute();
        $nextLevel = null;
        $xpProgress = 0;

// Find the next level the user hasn't reached yet
        ksort($levels);
        foreach ($levels as $level => $data) {
            if ($user->total_xp < $data['xp_required']) {
                $nextLevel = $level;
                break;
            }
        }

        // Calculate XP progress to next level
        if ($nextLevel !== null) {
            $prevXp = $levels[$currentLevel]['xp_required'];
            $nextXp = $levels[$nextLevel]['xp_required'];
            $xpProgress = min(100, round(($user->total_xp - $prevXp) / ($nextXp - $prevXp) * 100));
        } else {
            $xpProgress = 100;
        }

        // === Stats strip data ===
        $stat = $user->stat;
        $totalXp = $user->total_xp;

        // Projects started (submissions)
        $projectsStarted = Submission::where('user_id', $user->id)->whereIn('status', ['pending', 'in_progress', 'queued', 'testing', 'in_review'])->count();

        // Courses count
        $totalCourses = Course::where('is_published', true)->count();
        $completedCourses = $user->completedCourses()->count();

        // Peer review points (reviews given)
        $peerReviewPoints = $stat ? $stat->reviews_given : 0;

        // Code review points (test score from submissions)
        $codeReviewPoints = Submission::where('user_id', $user->id)
            ->whereNotNull('review_score')
            ->where('review_score', '>', 0)
            ->count();

        // === Notifications ===
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // === Events (no events table exists — placeholder) ===
        $events = [];

        // === Agenda (no calendar/agenda table exists — placeholder) ===
        $agenda = [];

        // === Unlocked achievements (badges) ===
        $unlockedAchievements = $user->unlockedAchievements()
            ->with('achievement')
            ->orderBy('unlocked_at', 'desc')
            ->limit(3)
            ->get();

        // Recent user activities
        $recentActivities = UserActivity::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('public.home', compact(
            'user',
            'currentLevel',
            'nextLevel',
            'xpProgress',
            'stat',
            'totalXp',
            'projectsStarted',
            'totalCourses',
            'completedCourses',
            'peerReviewPoints',
            'codeReviewPoints',
            'notifications',
            'events',
            'agenda',
            'unlockedAchievements',
            'recentActivities',
            'levels',
        ));
    }


}
