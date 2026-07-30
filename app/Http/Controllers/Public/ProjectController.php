<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\Submission;
use App\Services\GitlabCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Submission statuses meaning "user is currently working on the project".
     */
    private const ACTIVE_STATUSES = ['pending', 'queued', 'testing', 'in_progress', 'resubmitted'];

    /**
     * Submission statuses meaning "project is waiting for / going through review".
     */
    private const REVIEW_STATUSES = ['tested', 'in_review', 'reviewed'];

    /**
     * Submission statuses meaning "project is finished".
     */
    private const DONE_STATUSES = ['passed'];

    /**
     * Projects list — fully server-side.
     *
     * Tabs (?tab=):
     *   open       — published projects the user has NO submission for (available to enroll)
     *   inprogress — user's submission in ACTIVE_STATUSES
     *   review     — user's submission in REVIEW_STATUSES
     *   done       — user's submission in DONE_STATUSES
     */
    public function index(Request $request, GitlabCacheService $gitlabCache)
    {
        $activeTab = $request->query('tab', 'open');
        if (!in_array($activeTab, ['open', 'inprogress', 'review', 'done'], true)) {
            $activeTab = 'open';
        }

        $userId = Auth::id();

        // --- User's submissions grouped by "tab meaning" (single query) ---
        $activeIds = [];
        $reviewIds = [];
        $doneIds   = [];
        $failedIds = [];

        if ($userId) {
            $latestStatuses = Submission::where('user_id', $userId)
                ->orderBy('id')
                ->get(['project_id', 'status'])
                // keep the LATEST attempt per project (later rows overwrite earlier)
                ->keyBy('project_id')
                ->map(fn ($s) => $s->status);

            foreach ($latestStatuses as $projectId => $status) {
                if (in_array($status, self::ACTIVE_STATUSES, true)) {
                    $activeIds[] = $projectId;
                } elseif (in_array($status, self::REVIEW_STATUSES, true)) {
                    $reviewIds[] = $projectId;
                } elseif (in_array($status, self::DONE_STATUSES, true)) {
                    $doneIds[] = $projectId;
                } elseif ($status === 'failed') {
                    $failedIds[] = $projectId;
                }
            }
        }

        $enrolledProjectIds  = array_merge($activeIds, $reviewIds);
        $completedProjectIds = $doneIds;
        $retryProjectIds     = $failedIds;

        // --- Base query: published, real (linked to GitLab) projects ---
        $query = Project::where('is_published', true)
            ->whereNotNull('gitlab_project_id')
            ->orderBy('order_position');

        $query = match ($activeTab) {
            // available to enroll: no submission of mine at all
            'open'       => $query->whereNotIn('id', array_merge($activeIds, $reviewIds, $doneIds)),
            'inprogress' => $query->whereIn('id', $activeIds),
            'review'     => $query->whereIn('id', $reviewIds),
            'done'       => $query->whereIn('id', $doneIds),
        };

        $projects = $query->get();

        $userLevel = Auth::check() ? (Auth::user()->level ?? 1) : 1;

        // Counters for tab badges (cheap: already have the id arrays)
        $tabCounts = [
            'inprogress' => count($activeIds),
            'review'     => count($reviewIds),
            'done'       => count($doneIds),
        ];

        // --- Enrich with GitLab + user's own submission status ---
        $gitlabProjects = collect($gitlabCache->getProjects());

        $enrichedProjects = $projects->map(function ($project) use ($gitlabProjects, $enrolledProjectIds, $completedProjectIds, $retryProjectIds, $userId) {
            $gitlabData  = $gitlabProjects->firstWhere('id', $project->gitlab_project_id) ?: [
                'id' => $project->gitlab_project_id,
                'web_url' => $project->repository_url,
                'http_url_to_repo' => $project->repository_url,
                'ssh_url_to_repo' => '',
                'path' => $project->slug,
                'path_with_namespace' => $project->slug,
                'default_branch' => $project->default_branch ?? 'main',
                'empty_repo' => false,
            ];
            $isEnrolled  = in_array($project->id, $enrolledProjectIds);
            $isCompleted = in_array($project->id, $completedProjectIds);
            $isRetry     = in_array($project->id, $retryProjectIds);

            $testStatus   = ['percent' => 0, 'result' => null, 'testsPassed' => 0, 'testsTotal' => 0];
            $reviewStatus = ['received' => 0, 'required' => $project->required_reviews_count ?? 0, 'percent' => 0];

            if ($userId && ($isEnrolled || $isCompleted || $isRetry)) {
                // ONLY the current user's submission (was: any user's — privacy bug)
                $latestSubmission = Submission::where('project_id', $project->id)
                    ->where('user_id', $userId)
                    ->latest('id')
                    ->first();

                if ($latestSubmission) {
                    $testStatus['testsPassed'] = $latestSubmission->tests_passed ?? 0;
                    $testStatus['testsTotal']  = $latestSubmission->tests_total ?? 0;
                    $testStatus['result']      = $latestSubmission->status;
                    if ($testStatus['testsTotal'] > 0) {
                        $testStatus['percent'] = round(($testStatus['testsPassed'] / $testStatus['testsTotal']) * 100);
                    }

                    $reviewStatus['received'] = $latestSubmission->reviews_received ?? 0;
                    if ($reviewStatus['required'] > 0) {
                        $reviewStatus['percent'] = min(100, round(($reviewStatus['received'] / $reviewStatus['required']) * 100));
                    }
                }
            }

            return [
                'project'      => $project,
                'is_enrolled'  => $isEnrolled,
                'is_completed' => $isCompleted,
                'is_retry'     => $isRetry,
                'test_status'  => $testStatus,
                'review_status'=> $reviewStatus,
                'gitlab'       => $gitlabData ? [
                    'id'                  => $gitlabData['id'],
                    'web_url'             => $gitlabData['web_url'],
                    'http_url_to_repo'    => $gitlabData['http_url_to_repo'] ?? '',
                    'ssh_url_to_repo'     => $gitlabData['ssh_url_to_repo'] ?? '',
                    'path'                => $gitlabData['path'],
                    'path_with_namespace' => $gitlabData['path_with_namespace'],
                    'default_branch'      => $gitlabData['default_branch'],
                    'empty_repo'          => $gitlabData['empty_repo'] ?? true,
                ] : null,
            ];
        })
            // Public pages show ONLY real projects that actually exist in GitLab
            ->filter(fn ($item) => $item['gitlab'] !== null)
            ->values();

        return view('public.projects.index', [
            'projects'            => $enrichedProjects,
            'enrolledProjectIds'  => $enrolledProjectIds,
            'completedProjectIds' => $completedProjectIds,
            'activeTab'           => $activeTab,
            'tabCounts'           => $tabCounts,
            'userLevel'           => $userLevel,
        ]);
    }

    public function show(Project $project, GitlabCacheService $gitlabCache)
    {
        // Only real, published GitLab-backed projects are visible publicly
        abort_unless($project->is_published && $project->gitlab_project_id, 404);

        // Level gate: instead of a bare 403 we render the page with a lock
        // notice (subscribe button hidden) — better UX than "Forbidden".
        $userLevel   = Auth::check() ? (Auth::user()->level ?? 1) : 1;
        $levelLocked = $userLevel < $project->min_level;

        $gitlabProject = null;

        if ($project->gitlab_project_id) {
            $gitlabProject = $gitlabCache->getProjectById($project->gitlab_project_id);
        }

        // If GitLab API is temporarily unavailable, keep the LMS page usable
        // with DB-stored repository metadata. Actual GitLab actions still use
        // GitLabService and will report errors when needed.
        $gitlabProject = $gitlabProject ?: [
            'id' => $project->gitlab_project_id,
            'web_url' => $project->repository_url,
            'http_url_to_repo' => $project->repository_url,
            'ssh_url_to_repo' => '',
            'path' => $project->slug,
            'path_with_namespace' => $project->slug,
            'default_branch' => $project->default_branch ?? 'main',
            'empty_repo' => false,
        ];

        // Get user's active submission
        $activeSubmission = null;
        $completedSubmission = null;
        $reviewStatus = ['received' => 0, 'required' => $project->required_reviews_count ?? 0, 'percent' => 0];
        $testStatus = ['percent' => 0, 'result' => null, 'testsPassed' => 0, 'testsTotal' => 0];

        if (Auth::check()) {
            $activeSub = $project->activeSubmissions()->where('user_id', Auth::id())->first();

            if ($activeSub) {
                $activeSubmission = $activeSub;
            }

            // Latest completed submission (passed/failed)
            $latestCompleted = Submission::where('project_id', $project->id)
                ->where('user_id', Auth::id())
                ->whereIn('status', ['passed', 'failed'])
                ->latest('id')
                ->first();

            if ($latestCompleted) {
                $completedSubmission = $latestCompleted;
            }

            // Use latest of any status for test/review display
            $latestSub = Submission::where('project_id', $project->id)
                ->where('user_id', Auth::id())
                ->latest('id')
                ->first();

            if ($latestSub) {
                $testStatus['testsPassed'] = $latestSub->tests_passed ?? 0;
                $testStatus['testsTotal'] = $latestSub->tests_total ?? 0;
                $testStatus['result'] = $latestSub->status;
                if ($testStatus['testsTotal'] > 0) {
                    $testStatus['percent'] = round(($testStatus['testsPassed'] / $testStatus['testsTotal']) * 100);
                }

                $reviewStatus['received'] = $latestSub->reviews_received ?? 0;
                if ($reviewStatus['required'] > 0) {
                    $reviewStatus['percent'] = min(100, round(($reviewStatus['received'] / $reviewStatus['required']) * 100));
                }
            }
        }

        // Public project statistics (real DB counts, like School 21 shows)
        $projectStats = [
            'registered' => Submission::where('project_id', $project->id)->distinct('user_id')->count('user_id'),
            'working'    => Submission::where('project_id', $project->id)
                ->whereIn('status', ['pending', 'queued', 'testing', 'in_progress', 'resubmitted'])->count(),
            'waiting'    => Submission::where('project_id', $project->id)
                ->whereIn('status', ['tested', 'in_review'])->count(),
            'passed'     => Submission::where('project_id', $project->id)->where('status', 'passed')->count(),
            'failed'     => Submission::where('project_id', $project->id)->where('status', 'failed')->count(),
        ];

        return view('public.projects.show', compact(
            'project',
            'gitlabProject',
            'activeSubmission',
            'completedSubmission',
            'reviewStatus',
            'testStatus',
            'levelLocked',
            'userLevel',
            'projectStats'
        ));
    }
}
