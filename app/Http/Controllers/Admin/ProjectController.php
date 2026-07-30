<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $data['requires_peer_review'] = $request->boolean('requires_peer_review', true);

        $data['xp_reward'] = $data['xp_reward'] ?? 100;
        $data['passing_score'] = $data['passing_score'] ?? 70;
        $data['required_reviews_count'] = $data['required_reviews_count'] ?? 2;
        $data['default_branch'] = 'main';
        $data['created_by'] = $request->user()?->id;

        try {
            // Admin create is synchronous: DB and GitLab must stay in sync.
            $gitlabProject = $gitlab->createProjectWithConfig(
                name: $data['title'],
                path: $data['slug'],
                description: $data['description'],
                defaultBranch: $data['default_branch'],
                visibility: config('services.gitlab.visibility', 'private'),
                initializeWithReadme: true,
            );

            $gitlab->syncProjectReadme(
                projectId: $gitlabProject['id'],
                projectData: $data,
                branch: $gitlabProject['default_branch'] ?? $data['default_branch']
            );

            $project = Project::create([
                ...$data,
                'gitlab_project_id' => $gitlabProject['id'],
                'repository_url' => $gitlabProject['web_url'],
                'default_branch' => $gitlabProject['default_branch'] ?? $data['default_branch'],
                'gitlab_sync_status' => 'synced',
            ]);

            $gitlabCache->invalidateProject($project->gitlab_project_id);

            return redirect()->route('admin.projects.index')
                ->with('success', "Project '{$project->title}' created and synced with GitLab.");
        } catch (\RuntimeException $e) {
            return back()
                ->withInput()
                ->withErrors(['gitlab' => 'GitLab error: ' . $e->getMessage()]);
        }
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
        $data['xp_reward'] = $data['xp_reward'] ?? 100;
        $data['passing_score'] = $data['passing_score'] ?? 70;
        $data['required_reviews_count'] = $data['required_reviews_count'] ?? 2;

        try {
            $defaultBranch = $project->default_branch ?: 'main';
            $gitlabProject = null;

            if ($project->gitlab_project_id) {
                $gitlabProject = $gitlab->updateProject(
                    id: $project->gitlab_project_id,
                    name: $data['title'],
                    path: $data['slug'],
                    description: $data['description'],
                    visibility: config('services.gitlab.visibility', 'private'),
                );
            } else {
                // Legacy local project: create missing GitLab repository during update.
                $gitlabProject = $gitlab->createProjectWithConfig(
                    name: $data['title'],
                    path: $data['slug'],
                    description: $data['description'],
                    defaultBranch: $defaultBranch,
                    visibility: config('services.gitlab.visibility', 'private'),
                    initializeWithReadme: true,
                );
            }

            $gitlab->syncProjectReadme(
                projectId: $gitlabProject['id'],
                projectData: [...$project->toArray(), ...$data],
                branch: $gitlabProject['default_branch'] ?? $defaultBranch
            );

            $project->update([
                ...$data,
                'gitlab_project_id' => $gitlabProject['id'],
                'repository_url' => $gitlabProject['web_url'] ?? $project->repository_url,
                'default_branch' => $gitlabProject['default_branch'] ?? $defaultBranch,
                'gitlab_sync_status' => 'synced',
            ]);

            $gitlabCache->invalidateProject($project->gitlab_project_id);

            return redirect()->route('admin.projects.index')
                ->with('success', "Project '{$project->title}' updated and synced with GitLab.");
        } catch (\RuntimeException $e) {
            $project->update(['gitlab_sync_status' => 'failed']);

            return back()
                ->withInput()
                ->withErrors(['gitlab' => 'GitLab error: ' . $e->getMessage()]);
        }
    }

    public function destroy(Project $project, GitlabService $gitlab, GitlabCacheService $gitlabCache)
    {
        $name = $project->title;
        $gitlabProjectId = $project->gitlab_project_id;

        try {
            // Delete in GitLab first. If GitLab deletion fails, keep the local
            // project so the admin can retry and DB/GitLab do not diverge.
            if ($gitlabProjectId && ! $gitlab->deleteProject($gitlabProjectId)) {
                throw new \RuntimeException('GitLab project deletion failed.');
            }

            $project->delete();

            if ($gitlabProjectId) {
                $gitlabCache->invalidateProject($gitlabProjectId);
            }

            return redirect()->route('admin.projects.index')
                ->with('success', "Project '{$name}' deleted in LMS and GitLab.");
        } catch (\RuntimeException $e) {
            return back()->withErrors(['gitlab' => 'GitLab error: ' . $e->getMessage()]);
        }
    }
}
