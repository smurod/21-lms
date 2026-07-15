<?php

namespace App\Jobs;

use App\Services\GitlabService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateGitlabProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    protected string $name;
    protected string $path;
    protected ?string $description;
    protected string $defaultBranch;
    protected ?string $visibility;
    protected int $projectId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $name,
        string $path,
        ?string $description,
        string $defaultBranch,
        ?string $visibility,
        int $projectId
    ) {
        $this->name = $name;
        $this->path = $path;
        $this->description = $description;
        $this->defaultBranch = $defaultBranch;
        $this->visibility = $visibility;
        $this->projectId = $projectId;
    }

    /**
     * Execute the job.
     */
    public function handle(GitlabService $gitlab): void
    {
        try {
            $gitlabProject = $gitlab->createProjectWithConfig(
                name: $this->name,
                path: $this->path,
                description: $this->description,
                defaultBranch: $this->defaultBranch,
                visibility: $this->visibility,
            );

            // Update local project with GitLab data
            \DB::table('projects')
                ->where('id', $this->projectId)
                ->update([
                    'gitlab_project_id' => $gitlabProject['id'],
                    'repository_url' => $gitlabProject['web_url'],
                    'default_branch' => $gitlabProject['default_branch'] ?? $this->defaultBranch,
                    'gitlab_sync_status' => 'synced',
                    'updated_at' => now(),
                ]);
        } catch (\RuntimeException $e) {
            Log::error('GitLab project creation job failed', [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Mark the project as needing retry
            \DB::table('projects')
                ->where('id', $this->projectId)
                ->update([
                    'gitlab_sync_status' => 'failed',
                    'updated_at' => now(),
                ]);

            throw $e;
        }
    }
}
