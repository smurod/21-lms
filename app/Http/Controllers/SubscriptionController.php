<?php

namespace App\Http\Controllers;

use App\Models\Admin\Project;
use App\Models\Admin\Submission;
use App\Services\GitlabService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $user = Auth::user();

        try {
            $gitlabAccount = $gitlab->ensureUserAccount($user);
            $gitlabToken = $gitlabAccount['token'];
        } catch (\Throwable $e) {
            Log::error('GitLab account auto-provision failed before project subscription', [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('public.projects.show', $project)
                ->withErrors(['gitlab' => 'GitLab API недоступен для вашей учётной записи: ' . $e->getMessage()]);
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

        try {
            return DB::transaction(function () use ($project, $gitlab, $user, $gitlabToken) {
                // Retry policy:
                // - failed: create a new submission attempt, but reuse the same student GitLab repo;
                // - passed: do not create another attempt from the public flow;
                // - first enrollment: fork/create the student GitLab repo.
                $latestSubmission = Submission::where('project_id', $project->id)
                    ->where('user_id', Auth::id())
                    ->whereIn('status', ['failed', 'passed'])
                    ->latest('id')
                    ->first();

                if ($latestSubmission?->status === 'passed') {
                    return redirect()->route('public.projects.show', $project)
                        ->with('info', 'Проект уже пройден. Повторная сдача для повышения результата пока отключена.');
                }

                $attemptNumber = $latestSubmission ? $latestSubmission->attempt_number + 1 : 1;
                $gitUrl = $latestSubmission?->status === 'failed' ? $latestSubmission->git_url : null;
                $message = 'Вы успешно записались на проект! Репозиторий создан в вашей учётной записи School21 GitLab.';

                if (!$gitUrl) {
                    $studentRepository = $gitlab->createStudentProjectRepository(
                        token: $gitlabToken,
                        projectData: $project->toArray(),
                        ownerName: $user->username ?? $user->email,
                        attemptNumber: $attemptNumber,
                    );

                    $gitUrl = $studentRepository['http_url_to_repo']
                        ?? $studentRepository['web_url']
                        ?? null;
                } else {
                    $message = 'Проект возвращён в работу. Исправьте ошибки в том же GitLab-репозитории и отправьте на review повторно.';
                }

                Submission::create([
                    'user_id' => Auth::id(),
                    'project_id' => $project->id,
                    'submission_type' => 'git',
                    'git_url' => $gitUrl,
                    'status' => 'in_progress',
                    'attempt_number' => $attemptNumber,
                ]);

                return redirect()->route('public.projects.show', $project)
                    ->with('success', $message);
            });
        } catch (\Throwable $e) {
            Log::error('Project subscription failed', [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            $message = $e->getMessage();
            if (str_contains($message, 'GitLab auth error') || str_contains($message, '[401]') || str_contains($message, '[403]')) {
                return redirect()->route('public.gitlab')
                    ->withErrors(['gitlab' => 'GitLab token недействителен или недостаточно прав. 21-LMS попробует автоматически выпустить новый token через GitLab admin API.']);
            }

            return redirect()->route('public.projects.show', $project)
                ->withErrors(['gitlab' => 'Не удалось создать репозиторий в вашей учётной записи GitLab: ' . $message]);
        }
    }
}
