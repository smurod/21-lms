<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class GitlabCacheService
{
    protected GitlabService $gitlab;

    protected int $ttl;

    public function __construct(GitlabService $gitlab)
    {
        $this->gitlab = $gitlab;
        $this->ttl = (int) config('services.gitlab.cache_ttl', 300);
    }

    /**
     * Get all projects with caching.
     */
    public function getProjects(): array
    {
        return Cache::remember(
            'gitlab:projects',
            $this->ttl,
            fn () => $this->gitlab->getProjects()
        );
    }

    /**
     * Get a project by ID with caching.
     */
    public function getProjectById(int $projectId): ?array
    {
        return Cache::remember(
            "gitlab:project:{$projectId}",
            $this->ttl,
            fn () => $this->gitlab->getProjectById($projectId)
        );
    }

    /**
     * Invalidate project cache when a project changes.
     */
    public function invalidateProject(int $projectId): void
    {
        Cache::forget("gitlab:project:{$projectId}");

        // Also invalidate the full list since it may include this project
        Cache::forget('gitlab:projects');
    }

    /**
     * Invalidate all GitLab cache.
     *
     * NOTE: no Cache::tags() here — the database/file cache drivers do not
     * support tags and would throw BadMethodCallException.
     */
    public function invalidateAll(): void
    {
        Cache::forget('gitlab:projects');
        Cache::forget('gitlab:health');
    }
}
