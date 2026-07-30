<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\Review;
use App\Models\Admin\Submission;
use App\Models\Course;
use App\Models\XpTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $levels = config('xp.levels', []);
        ksort($levels);

        $currentLevel = $user?->current_level ?? $user?->level ?? 1;
        $totalXp = (int) ($user?->total_xp ?? 0);
        $currentRequired = $levels[$currentLevel]['xp_required'] ?? 0;
        $nextLevel = null;
        foreach ($levels as $level => $data) {
            if ($totalXp < ($data['xp_required'] ?? 0)) {
                $nextLevel = $level;
                break;
            }
        }
        $nextRequired = $nextLevel ? ($levels[$nextLevel]['xp_required'] ?? ($currentRequired + 1000)) : max($totalXp, $currentRequired);
        $levelSpan = max(1, $nextRequired - $currentRequired);
        $levelProgress = $nextLevel ? min(100, max(0, round((($totalXp - $currentRequired) / $levelSpan) * 100))) : 100;

        $submissions = Submission::with('project')
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        $passedSubmissions = $submissions->where('status', 'passed');
        $failedSubmissions = $submissions->where('status', 'failed');
        $activeSubmissions = $submissions->whereIn('status', ['pending', 'queued', 'testing', 'in_progress', 'resubmitted']);
        $reviewSubmissions = $submissions->whereIn('status', ['tested', 'in_review', 'reviewed']);

        $reviewsGiven = Review::where('reviewer_id', $user->id)->where('status', 'completed')->count();
        $reviewsReceived = Review::whereHas('submission', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', 'completed')
            ->count();

        $stats = [
            'total_projects' => Project::count(),
            'published_projects' => Project::where('is_published', true)->count(),
            'enrolled_projects' => $activeSubmissions->count(),
            'in_review_projects' => $reviewSubmissions->count(),
            'completed_projects' => $passedSubmissions->count() + $failedSubmissions->count(),
            'passed_projects' => $passedSubmissions->count(),
            'failed_projects' => $failedSubmissions->count(),
            'total_courses' => Course::where('is_published', true)->count(),
            'reviews_given' => $reviewsGiven,
            'reviews_received' => $reviewsReceived,
        ];

        $skills = $this->buildSkills($submissions);
        $xpGraph = $this->buildXpGraph($user->id, $totalXp);
        $logtime = $this->buildLogtime($user->id);

        $recentAchievements = $user->achievements()
            ->with('achievement')
            ->whereNotNull('unlocked_at')
            ->latest('unlocked_at')
            ->limit(6)
            ->get();

        return view('public.progress', compact(
            'user',
            'stats',
            'skills',
            'xpGraph',
            'logtime',
            'recentAchievements',
            'levels',
            'currentLevel',
            'nextLevel',
            'nextRequired',
            'levelProgress',
            'totalXp',
            'submissions'
        ));
    }

    public function index(): View
    {
        return $this->__invoke();
    }

    private function buildSkills($submissions): array
    {
        $skills = [
            'C' => 0,
            'C++' => 0,
            'Algorithms' => 0,
            'Structured programming' => 0,
            'Linux' => 0,
            'Code review' => 0,
            'Team work' => 0,
            'Git' => 0,
        ];

        foreach ($submissions as $submission) {
            $project = $submission->project;
            if (! $project) {
                continue;
            }

            $multiplier = match ($submission->status) {
                'passed' => 1.0,
                'failed' => 0.35,
                'in_review', 'reviewed', 'tested' => 0.55,
                default => 0.2,
            };

            $xp = (int) round(($project->xp_reward ?? 0) * $multiplier);
            $language = strtolower((string) $project->language);

            if ($language === 'cpp') {
                $skills['C++'] += $xp;
            } else {
                $skills['C'] += $xp;
            }

            $skills['Algorithms'] += (int) round($xp * 0.45);
            $skills['Structured programming'] += (int) round($xp * 0.35);
            $skills['Linux'] += (int) round($xp * 0.20);
            $skills['Git'] += (int) round($xp * 0.25);
        }

        $skills['Code review'] = Review::where('reviewer_id', auth()->id())->where('status', 'completed')->count() * 60;
        $skills['Team work'] = Review::whereHas('submission', fn ($q) => $q->where('user_id', auth()->id()))
                ->where('status', 'completed')
                ->count() * 40;

        return $skills;
    }

    private function buildXpGraph(int $userId, int $totalXp): array
    {
        $transactions = XpTransaction::where('user_id', $userId)
            ->orderBy('created_at')
            ->get(['created_at', 'balance_after', 'amount']);

        if ($transactions->isEmpty()) {
            return [
                ['date' => now()->subDays(2)->format('d.m.Y'), 'xp' => 0, 'amount' => 0],
                ['date' => now()->subDay()->format('d.m.Y'), 'xp' => 0, 'amount' => 0],
                ['date' => now()->format('d.m.Y'), 'xp' => $totalXp, 'amount' => $totalXp],
            ];
        }

        $points = $transactions->map(fn ($x) => [
            'date' => $x->created_at->format('d.m.Y'),
            'xp' => (int) ($x->balance_after ?? 0),
            'amount' => (int) $x->amount,
        ])->values()->all();

        if (count($points) === 1) {
            array_unshift($points, ['date' => now()->subDay()->format('d.m.Y'), 'xp' => 0, 'amount' => 0]);
        }

        return $points;
    }

    private function buildLogtime(int $userId): array
    {
        $start = Carbon::now()->startOfWeek();
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            $submissionsCount = Submission::where('user_id', $userId)->whereDate('updated_at', $day)->count();
            $reviewsCount = Review::where('reviewer_id', $userId)->whereDate('updated_at', $day)->count();
            $activity = $submissionsCount + $reviewsCount;

            $days[] = [
                'day' => strtoupper($day->format('D')),
                'campus' => 0,
                'imac' => $activity > 0 ? max(1, min(9, $activity * 2 + 3)) : 0,
            ];
        }

        return [
            'week' => $start->format('j M') . ' – ' . $start->copy()->addDays(6)->format('j M Y'),
            'days' => $days,
            'average' => (int) round(collect($days)->avg('imac')),
        ];
    }
}
