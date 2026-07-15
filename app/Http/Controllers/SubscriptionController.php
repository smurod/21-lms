<?php

namespace App\Http\Controllers;

use App\Models\Admin\Project;
use App\Models\Admin\Submission;
use App\Services\GitlabService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function __invoke(Project $project, GitlabService $gitlab): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('register')
                ->with('redirect_to', route('public.projects.show', $project));
        }

        if (!$project->is_published) {
            return redirect()->back()
                ->withErrors(['error' => 'Запись на этот проект закрыта.']);
        }

        // Check for active enrollment
        $active = Submission::where('project_id', $project->id)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'queued', 'testing', 'in_review', 'reviewed', 'in_progress', 'resubmitted'])
            ->first();

        if ($active) {
            return redirect()->route('public.projects.show', $project)
                ->with('info', 'Вы уже записаны на этот проект.');
        }

        return DB::transaction(function () use ($project, $gitlab) {
            $user = Auth::user();

            // Handle resubscription for failed/passed projects
            $latestSubmission = Submission::where('project_id', $project->id)
                ->where('user_id', Auth::id())
                ->whereIn('status', ['failed', 'passed'])
                ->latest()
                ->first();

            $attemptNumber = 1;
            if ($latestSubmission) {
                $attemptNumber = $latestSubmission->attempt_number + 1;
                $latestSubmission->update(['status' => 'resubmitted']);
            }

            $submission = Submission::create([
                'user_id' => Auth::id(),
                'project_id' => $project->id,
                'status' => 'in_progress',
                'attempt_number' => $attemptNumber,
            ]);

            // Create GitLab developer branch if user has username
            if ($user->username && $project->gitlab_project_id) {
                $branchName = 'developer-' . $user->username;
                $ref = $project->default_branch ?? 'main';

                try {
                    // Check if branch already exists
                    $existing = $gitlab->getBranch((int) $project->gitlab_project_id, $branchName);
                    if (!$existing) {
                        $gitlab->createBranch(
                            (int) $project->gitlab_project_id,
                            $branchName,
                            $ref
                        );
                    }
                    $submission->git_url = $existing ? $existing['web_url'] : null;
                } catch (\RuntimeException $e) {
                    \Log::warning('Failed to create GitLab developer branch', [
                        'submission_id' => $submission->id,
                        'project_id' => $project->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $submission->save();
            }

            return redirect()->route('public.projects.show', $project)
                ->with('success', 'Вы успешно записались на проект!');
        });
    }
}
