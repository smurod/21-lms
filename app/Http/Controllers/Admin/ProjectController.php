<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CreateGitlabProjectJob;
use App\Models\Admin\Project;
use App\Services\GitlabCacheService;
use App\Services\GitlabService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()->latest('created_at');

        if ($search = $request->input('search')) {
            // 'like' (not Postgres-only 'ilike') — MySQL is case-insensitive by default collation
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            match ($status) {
                'published' => $query->where('is_published', true),
                'draft' => $query->where('is_published', false),
                default => null,
            };
        }

        $projects = $query->paginate(15);

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.create');
    }

    public function store(Request $request, GitlabService $gitlab, GitlabCacheService $gitlabCache)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:projects,slug',
            'description' => 'required|string',
            'instructions' => 'required|string',
            'hints' => 'nullable|string',
            'course_id' => 'nullable|integer',
            'module_id' => 'nullable|integer',
            'difficulty' => 'required|in:beginner,intermediate,advanced,expert',
            'estimated_hours' => 'nullable|integer|min:1',
            'order_position' => 'nullable|integer|min:0',
            'language' => 'required|string|max:50',
            'language_version' => 'nullable|string|max:20',
            'submission_type' => 'nullable|string|max:50',
            'allowed_file_extensions' => 'nullable|string',
            'max_file_size_mb' => 'nullable|integer|min:1',
            'has_automated_tests' => 'nullable|boolean',
            'test_file_path' => 'nullable|string',
            'test_timeout_seconds' => 'nullable|integer|min:1',
            'docker_config' => 'nullable|string',
            'xp_reward' => 'nullable|integer|min:0',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'requires_peer_review' => 'nullable|boolean',
            'required_reviews_count' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'is_mandatory' => 'nullable|boolean',
            'tags' => 'nullable|string',
            'learning_outcomes' => 'nullable|string',
        ]);

        // Fix checkboxes: unchecked = false (was missing from $data)
        $data['is_published'] = $request->boolean('is_published');
        $data['is_mandatory'] = $request->boolean('is_mandatory');
        $data['has_automated_tests'] = $request->boolean('has_automated_tests');
        $data['requires_peer_review'] = $request->boolean('requires_peer_review', true); // default true

        $defaultBranch = 'main';

        $project = Project::create([
            ...$data,
            'xp_reward' => $data['xp_reward'] ?? 100,
            'passing_score' => $data['passing_score'] ?? 70,
            'required_reviews_count' => $data['required_reviews_count'] ?? 2,
            'gitlab_project_id' => null,
            'repository_url' => null,
            'default_branch' => $defaultBranch,
            'gitlab_sync_status' => 'pending',
            'is_published' => $data['is_published'],
            'is_mandatory' => $data['is_mandatory'],
            'has_automated_tests' => $data['has_automated_tests'],
            'requires_peer_review' => $data['requires_peer_review'],
        ]);

        // Dispatch async job to create GitLab project
        dispatch(new CreateGitlabProjectJob(
            name: $data['title'],
            path: $data['slug'],
            description: $data['description'],
            defaultBranch: $defaultBranch,
            visibility: config('services.gitlab.visibility', 'public'),
            projectId: $project->id,
        ));

        return redirect()->route('admin.projects.index')
            ->with('success', "Project '{$project->title}' created — GitLab repo will be created in background.");
    }

    public function edit(Project $project)
    {
        return view('admin.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project, GitlabService $gitlab, GitlabCacheService $gitlabCache)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:projects,slug,' . $project->id,
            'description' => 'required|string',
            'instructions' => 'required|string',
            'hints' => 'nullable|string',
            'difficulty' => 'required|in:beginner,intermediate,advanced,expert',
            'estimated_hours' => 'nullable|integer|min:1',
            'order_position' => 'nullable|integer|min:0',
            'language' => 'required|string|max:50',
            'language_version' => 'nullable|string|max:20',
            'submission_type' => 'nullable|string|max:50',
            'xp_reward' => 'nullable|integer|min:0',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'requires_peer_review' => 'nullable|boolean',
            'required_reviews_count' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'is_mandatory' => 'nullable|boolean',
            'has_automated_tests' => 'nullable|boolean',
        ]);

        // Fix checkboxes: ensure false is saved when unchecked
        $data['is_published'] = $request->boolean('is_published');
        $data['is_mandatory'] = $request->boolean('is_mandatory');
        $data['has_automated_tests'] = $request->boolean('has_automated_tests');
        $data['requires_peer_review'] = $request->boolean('requires_peer_review');

        try {
            // Verify GitLab connectivity — non-blocking, just a warning
            if ($project->gitlab_project_id) {
                try {
                    $gitlab->getProjectById($project->gitlab_project_id);
                } catch (\RuntimeException $e) {
                    \Log::warning('GitLab project unreachable', [
                        'project_id' => $project->gitlab_project_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Sync GitLab repo name + visibility with local project
            if ($project->gitlab_project_id) {
                try {
                    $gitlab->updateProject(
                        id: $project->gitlab_project_id,
                        name: $data['title'],
                        visibility: 'private',
                    );
                    $gitlabCache->invalidateProject($project->gitlab_project_id);
                } catch (\RuntimeException $e) {
                    \Log::warning('Failed to sync GitLab project on update', [
                        'project_id' => $project->gitlab_project_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $project->update($data);

            return redirect()->route('admin.projects.index')
                ->with('success', "Project '{$project->title}' updated.");
        } catch (\RuntimeException $e) {
            return back()
                ->withInput()
                ->withErrors(['gitlab' => 'GitLab error: ' . $e->getMessage()]);
        }
    }

    public function destroy(Project $project, GitlabService $gitlab, GitlabCacheService $gitlabCache)
    {
        $name = $project->title;
        $gitlabProjectId = $project->gitlab_project_id;

        // Try to delete the GitLab repository first
        if ($gitlabProjectId) {
            try {
                $gitlab->deleteProject($gitlabProjectId);
                $gitlabCache->invalidateProject($gitlabProjectId);
            } catch (\RuntimeException $e) {
                // Log but don't block deletion
                \Log::warning('Failed to delete GitLab project', [
                    'project_id' => $gitlabProjectId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', "Project '{$name}' deleted.");
    }
}
