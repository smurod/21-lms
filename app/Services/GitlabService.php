<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GitlabService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $url = config('services.gitlab.url');
        $token = config('services.gitlab.token');

        if (empty($url)) {
            throw new \InvalidArgumentException('GitLab URL is not configured. Set GITLAB_URL in .env');
        }

        if (empty($token)) {
            throw new \InvalidArgumentException('GitLab token is not configured. Set GITLAB_TOKEN in .env');
        }

        $this->baseUrl = rtrim($url, '/') . '/api/v4';
        $this->token = $token;
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->token);
    }

    /**
     * Create a project in GitLab and return its data.
     */
    public function createProjectWithConfig(
        string $name,
        string $path,
        ?string $description = null,
        string $defaultBranch = 'main',
        ?string $initialBranch = null,
        ?int $namespaceId = null,
        ?string $visibility = null
    ): array {
        $data = [
            'name' => $name,
            'path' => $path,
            'visibility' => $visibility ?? config('services.gitlab.visibility', 'private'),
            'default_branch' => $defaultBranch,
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        if ($initialBranch !== null) {
            $data['initial_branch'] = $initialBranch;
        }

        if ($namespaceId !== null) {
            $data['namespace_id'] = $namespaceId;
        }

        $response = $this->client()
            ->post($this->baseUrl . '/projects', $data);

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab API error [{$response->status()}]: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Create a new branch in a GitLab project.
     * Falls back to regular token if admin_token is not configured.
     */
    public function createBranch(int $projectId, string $branchName, string $ref = 'main'): array
    {
        $adminToken = config('services.gitlab.admin_token');
        $client = $adminToken ? Http::withToken($adminToken) : $this->client();

        $response = $client->post($this->baseUrl . "/projects/{$projectId}/repository/branches", [
            'branch' => $branchName,
            'ref' => $ref,
        ]);

        if (!$response->successful()) {
            \Log::warning('GitLab create branch failed', [
                'project_id' => $projectId,
                'branch' => $branchName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("GitLab create branch error [{$response->status()}]: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Check if a branch exists in a GitLab project.
     */
    public function getBranch(int $projectId, string $branchName): ?array
    {
        $response = $this->client()->get($this->baseUrl . "/projects/{$projectId}/repository/branches/{$branchName}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Get the default branch of a GitLab project.
     */
    public function getRepoDefaultBranch(int $projectId): string
    {
        $response = $this->client()->get($this->baseUrl . "/projects/{$projectId}");

        if (!$response->successful()) {
            return 'main';
        }

        return $response->json()['default_branch'] ?? 'main';
    }

    /**
     * Get a GitLab project by its ID.
     */
    public function getProjectById(int $projectId): ?array
    {
        $response = $this->client()->get($this->baseUrl . "/projects/{$projectId}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Update a GitLab project's name and/or visibility.
     */
    public function updateProject(int $id, string $name, ?string $visibility = null): array
    {
        $data = ['name' => $name];

        if ($visibility !== null) {
            $data['visibility'] = $visibility;
        }

        $response = $this->client()->put($this->baseUrl . "/projects/{$id}", $data);

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab update project error [{$response->status()}]: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Delete a GitLab project by its ID.
     */
    public function deleteProject(int $id): bool
    {
        $response = $this->client()->delete($this->baseUrl . "/projects/{$id}");

        // 204 No Content or 200 OK are both success
        if ($response->successful() || $response->status() === 204) {
            return true;
        }

        \Log::warning('GitLab delete project failed', [
            'project_id' => $id,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    /**
     * Get all projects visible to the authenticated user.
     */
    public function getProjects(): array
    {
        $response = $this->client()->get($this->baseUrl . '/projects', ['per_page' => 100]);

        if (!$response->successful()) {
            \Log::warning('GitLab get projects failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        return $response->json() ?? [];
    }
}
