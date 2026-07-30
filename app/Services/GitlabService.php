<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GitlabService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $url = config('services.gitlab.internal_url') ?: config('services.gitlab.url');
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

    private function client(?string $token = null): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($token ?: $this->token);
    }

    private function adminClient(): \Illuminate\Http\Client\PendingRequest
    {
        $adminToken = config('services.gitlab.admin_token') ?: config('services.gitlab.token');

        if (empty($adminToken)) {
            throw new \InvalidArgumentException('GitLab admin token is not configured. Set GITLAB_ADMIN_TOKEN in .env');
        }

        return Http::withToken($adminToken);
    }

    public function webUrl(): string
    {
        return rtrim((string) config('services.gitlab.url'), '/');
    }

    public function signInUrl(): string
    {
        return config('services.gitlab.sign_in_url') ?: $this->webUrl() . '/users/sign_in';
    }

    public function ssoUrl(): string
    {
        return config('services.gitlab.sso_url') ?: $this->webUrl() . '/users/sign_in';
    }

    public function loginUrl(): string
    {
        return config('services.gitlab.login_url') ?: $this->webUrl() . '/users/sign_in?auto_sign_in=false';
    }

    public function userWebUrl(User $user): string
    {
        $username = $this->normalizeUsername($user->username ?: Str::before($user->email, '@'));

        return $this->webUrl() . '/' . rawurlencode($username);
    }

    /**
     * Validate a user token and return GitLab account data.
     */
    public function getAuthenticatedUser(?string $token = null): array
    {
        $response = $this->client($token)->get($this->baseUrl . '/user');

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab auth error [{$response->status()}]: " . $response->body());
        }

        return $response->json() ?? [];
    }

    public function findUserByUsername(string $username): ?array
    {
        $username = $this->normalizeUsername($username);

        $response = $this->adminClient()->get($this->baseUrl . '/users', [
            'username' => $username,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab users lookup error [{$response->status()}]: " . $response->body());
        }

        return collect($response->json() ?? [])
            ->first(fn (array $user) => ($user['username'] ?? null) === $username);
    }

    public function findUserByEmail(string $email): ?array
    {
        $response = $this->adminClient()->get($this->baseUrl . '/users', [
            'search' => $email,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab users lookup error [{$response->status()}]: " . $response->body());
        }

        return collect($response->json() ?? [])
            ->first(fn (array $user) => strcasecmp((string) ($user['email'] ?? ''), $email) === 0);
    }

    public function createUser(string $email, string $username, string $name, string $password): array
    {
        $response = $this->adminClient()->post($this->baseUrl . '/users', [
            'email' => $email,
            'username' => $this->normalizeUsername($username),
            'name' => $name,
            'password' => $password,
            'skip_confirmation' => true,
            'reset_password' => false,
            'force_random_password' => false,
            'can_create_group' => false,
            'projects_limit' => 100,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab create user error [{$response->status()}]: " . $response->body());
        }

        return $response->json() ?? [];
    }

    public function updateUserPassword(int $userId, string $password): array
    {
        $response = $this->adminClient()->put($this->baseUrl . "/users/{$userId}", [
            'password' => $password,
            'reset_password' => false,
            'force_random_password' => false,
            'skip_reconfirmation' => true,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab update user password error [{$response->status()}]: " . $response->body());
        }

        return $response->json() ?? [];
    }

    public function createImpersonationToken(int $userId, string $name = '21-LMS token'): array
    {
        if ($userId === 1) {
            throw new \RuntimeException('21-LMS refuses to manage GitLab root impersonation tokens. Use a non-root GitLab user.');
        }

        $scopes = config('services.gitlab.user_token_scopes', ['api', 'write_repository']);
        if (!is_array($scopes) || empty($scopes)) {
            $scopes = ['api', 'write_repository'];
        }

        $response = $this->adminClient()->post($this->baseUrl . "/users/{$userId}/impersonation_tokens", [
            'name' => $name,
            'scopes' => array_values($scopes),
            'expires_at' => now()->addYear()->toDateString(),
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab create token error [{$response->status()}]: " . $response->body());
        }

        return $response->json() ?? [];
    }

    public function provisionUserAccount(string $email, string $username, string $name, string $password): array
    {
        $username = $this->normalizeUsername($username);
        $userByEmail = $this->findUserByEmail($email);
        $userByUsername = $this->findUserByUsername($username);

        if ($userByUsername && (!$userByEmail || (int) $userByUsername['id'] !== (int) $userByEmail['id'])) {
            throw new \RuntimeException('GitLab username is already taken by another account.');
        }

        if ($userByEmail && ($userByEmail['username'] ?? null) !== $username) {
            throw new \RuntimeException('GitLab email is already used by an account with another username.');
        }

        $gitlabUser = $userByEmail ?: $this->createUser($email, $username, $name, $password);

        if ($userByEmail) {
            $gitlabUser = $this->updateUserPassword((int) $gitlabUser['id'], $password);
        }

        $gitlabUserId = (int) $gitlabUser['id'];
        if ($gitlabUserId === 1 || ($gitlabUser['username'] ?? null) === 'root') {
            throw new \RuntimeException('21-LMS refuses to manage GitLab root impersonation tokens. Use a non-root GitLab user.');
        }

        $token = $this->createImpersonationToken(
            $gitlabUserId,
            '21-LMS token ' . now()->format('YmdHis')
        );

        if (empty($token['token'])) {
            throw new \RuntimeException('GitLab impersonation token was created without token value.');
        }

        return [
            'user' => $gitlabUser,
            'token' => $token['token'],
        ];
    }

    /**
     * Ensure that a local LMS user always has a working GitLab API token.
     *
     * Registration passes the plain password, so GitLab account is created with
     * the same username/email/password. Later calls cannot know the plain
     * password, therefore they can repair API access only for an existing
     * GitLab account by issuing a fresh impersonation token through admin API.
     */
    public function ensureUserAccount(User $user, ?string $plainPassword = null, bool $syncExistingPassword = false): array
    {
        if ($user->hasConnectedGitlab()) {
            try {
                $gitlabUser = $this->getAuthenticatedUser($user->connectedGitlabToken());

                return [
                    'user' => $gitlabUser,
                    'token' => $user->connectedGitlabToken(),
                    'created' => false,
                    'repaired' => false,
                ];
            } catch (\Throwable $e) {
                Log::warning('Stored GitLab token is invalid, trying to repair through admin API', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $username = $this->normalizeUsername($user->username ?: Str::before($user->email, '@'));
        $userByEmail = $this->findUserByEmail($user->email);
        $userByUsername = $this->findUserByUsername($username);

        if ($userByUsername && (!$userByEmail || (int) $userByUsername['id'] !== (int) $userByEmail['id'])) {
            throw new \RuntimeException('GitLab username is already taken by another account.');
        }

        if ($userByEmail && ($userByEmail['username'] ?? null) !== $username) {
            throw new \RuntimeException('GitLab email is already used by an account with another username.');
        }

        $gitlabUser = $userByEmail ?: $userByUsername;
        $created = false;

        if (!$gitlabUser) {
            if ($plainPassword === null) {
                throw new \RuntimeException('GitLab account is missing and LMS no longer has the plain registration password to recreate it. Re-register this user or create the GitLab account once with the same username/email.');
            }

            $gitlabUser = $this->createUser(
                email: $user->email,
                username: $username,
                name: $user->name,
                password: $plainPassword,
            );
            $created = true;
        } elseif ($plainPassword !== null && $syncExistingPassword) {
            $gitlabUser = $this->updateUserPassword((int) $gitlabUser['id'], $plainPassword);
        }

        $gitlabUserId = (int) $gitlabUser['id'];
        if ($gitlabUserId === 1 || ($gitlabUser['username'] ?? null) === 'root') {
            throw new \RuntimeException('21-LMS refuses to manage GitLab root impersonation tokens. Use a non-root GitLab user.');
        }

        $token = $this->createImpersonationToken(
            $gitlabUserId,
            '21-LMS token ' . now()->format('YmdHis')
        );

        if (empty($token['token'])) {
            throw new \RuntimeException('GitLab impersonation token was created without token value.');
        }

        $user->connectGitlabToken($token['token']);

        return [
            'user' => $gitlabUser,
            'token' => $token['token'],
            'created' => $created,
            'repaired' => true,
        ];
    }

    private function normalizeUsername(string $username): string
    {
        $normalized = Str::of($username)
            ->trim()
            ->replaceMatches('/[^A-Za-z0-9_.-]/', '-')
            ->replaceMatches('/^[^A-Za-z0-9_]+/', '')
            ->limit(50, '')
            ->toString();

        return $normalized !== '' ? $normalized : Str::random(8);
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
        ?string $visibility = null,
        bool $initializeWithReadme = true,
        ?string $token = null
    ): array {
        $data = [
            'name' => $name,
            'path' => $path,
            'visibility' => $visibility ?? config('services.gitlab.visibility', 'private'),
            'default_branch' => $defaultBranch,
            'initialize_with_readme' => $initializeWithReadme,
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

        $response = $this->client($token)
            ->post($this->baseUrl . '/projects', $data);

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab API error [{$response->status()}]: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Create a personal student repository in the connected GitLab account.
     * Prefer GitLab fork, because it copies the administrator/template project
     * with all files. If fork is unavailable, fall back to creating a repo and
     * syncing README.md.
     */
    public function createStudentProjectRepository(string $token, array $projectData, string $ownerName, int $attemptNumber = 1): array
    {
        $slug = $projectData['slug'] ?? str($projectData['title'] ?? 'project')->slug()->toString();
        $path = $this->makeStudentProjectPath($slug, $attemptNumber);
        $name = ($projectData['title'] ?? 'Project') . ($attemptNumber > 1 ? " — attempt {$attemptNumber}" : '');
        $existingProject = collect($this->getProjects($token))->firstWhere('path', $path);

        if ($existingProject) {
            $gitlabProject = $existingProject;
        } elseif (!empty($projectData['gitlab_project_id'])) {
            try {
                $gitlabProject = $this->forkProject(
                    token: $token,
                    sourceProjectId: (int) $projectData['gitlab_project_id'],
                    name: $name,
                    path: $path,
                    visibility: 'private'
                );
            } catch (\RuntimeException $e) {
                Log::warning('GitLab fork failed, falling back to repository creation', [
                    'source_project_id' => $projectData['gitlab_project_id'],
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);

                $gitlabProject = $this->createProjectWithConfig(
                    name: $name,
                    path: $path,
                    description: $projectData['description'] ?? null,
                    defaultBranch: 'main',
                    visibility: 'private',
                    initializeWithReadme: true,
                    token: $token,
                );
            }
        } else {
            $gitlabProject = $this->createProjectWithConfig(
                name: $name,
                path: $path,
                description: $projectData['description'] ?? null,
                defaultBranch: 'main',
                visibility: 'private',
                initializeWithReadme: true,
                token: $token,
            );
        }

        try {
            $this->syncProjectReadme(
                projectId: (int) $gitlabProject['id'],
                projectData: [
                    ...$projectData,
                    'student_owner' => $ownerName,
                    'attempt_number' => $attemptNumber,
                ],
                branch: $gitlabProject['default_branch'] ?? 'main',
                token: $token,
            );
        } catch (\RuntimeException $e) {
            // Fork import can be asynchronous in GitLab: the repository may be
            // visible before its default branch is ready for file writes. The
            // fork already contains the template README, so enrollment must not
            // fail only because README re-sync was too early.
            Log::warning('GitLab student README sync skipped', [
                'student_project_id' => $gitlabProject['id'] ?? null,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        return $gitlabProject;
    }

    public function forkProject(string $token, int $sourceProjectId, string $name, string $path, string $visibility = 'private'): array
    {
        $response = $this->client($token)->post($this->baseUrl . "/projects/{$sourceProjectId}/fork", [
            'name' => $name,
            'path' => $path,
            'visibility' => $visibility,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab fork project error [{$response->status()}]: " . $response->body());
        }

        return $response->json() ?? [];
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
            Log::warning('GitLab create branch failed', [
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
    public function getProjectById(int $projectId, ?string $token = null): ?array
    {
        try {
            $response = $this->client($token)->get($this->baseUrl . "/projects/{$projectId}");
        } catch (\Throwable $e) {
            Log::warning('GitLab get project connection failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Update GitLab project metadata. Used by admin CRUD.
     */
    public function updateProject(
        int $id,
        string $name,
        ?string $path = null,
        ?string $description = null,
        ?string $visibility = null
    ): array {
        $data = ['name' => $name];

        if ($path !== null) {
            $data['path'] = $path;
        }

        if ($description !== null) {
            $data['description'] = $description;
        }

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
     * Create or update README.md with LMS project instructions.
     */
    public function syncProjectReadme(int $projectId, array $projectData, string $branch = 'main', ?string $token = null): void
    {
        $content = $this->buildProjectReadme($projectData);
        $this->upsertFile(
            projectId: $projectId,
            filePath: 'README.md',
            branch: $branch,
            content: $content,
            commitMessage: 'Sync project instructions from 21-LMS',
            token: $token
        );
    }

    /**
     * Create or update a repository file.
     */
    public function upsertFile(
        int $projectId,
        string $filePath,
        string $branch,
        string $content,
        string $commitMessage,
        ?string $token = null
    ): array {
        $encodedPath = rawurlencode($filePath);
        $url = $this->baseUrl . "/projects/{$projectId}/repository/files/{$encodedPath}";
        $payload = [
            'branch' => $branch,
            'content' => $content,
            'commit_message' => $commitMessage,
        ];

        $exists = $this->repositoryFileExists($projectId, $filePath, $branch, $token);
        $response = $exists
            ? $this->client($token)->put($url, $payload)
            : $this->client($token)->post($url, $payload);

        if (!$response->successful()) {
            throw new \RuntimeException("GitLab file sync error [{$response->status()}]: " . $response->body());
        }

        return $response->json() ?? [];
    }

    public function repositoryFileExists(int $projectId, string $filePath, string $branch = 'main', ?string $token = null): bool
    {
        $encodedPath = rawurlencode($filePath);
        $response = $this->client($token)->get(
            $this->baseUrl . "/projects/{$projectId}/repository/files/{$encodedPath}",
            ['ref' => $branch]
        );

        return $response->successful();
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

        Log::warning('GitLab delete project failed', [
            'project_id' => $id,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    /**
     * Delete all projects visible to the configured GitLab token.
     * Intended for development seed reset only.
     */
    public function deleteAllProjects(): void
    {
        foreach ($this->getProjects() as $project) {
            if (!isset($project['id'])) {
                continue;
            }

            $this->deleteProject((int) $project['id']);
        }
    }

    /**
     * Get all projects visible to the authenticated token.
     */
    public function getProjects(?string $token = null): array
    {
        $projects = [];
        $page = 1;

        do {
            try {
                $response = $this->client($token)->get($this->baseUrl . '/projects', [
                    'per_page' => 100,
                    'page' => $page,
                    'membership' => $token ? true : null,
                ]);
            } catch (\Throwable $e) {
                Log::warning('GitLab get projects connection failed', [
                    'base_url' => $this->baseUrl,
                    'page' => $page,
                    'error' => $e->getMessage(),
                ]);

                return $projects;
            }

            if (!$response->successful()) {
                Log::warning('GitLab get projects failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $projects;
            }

            $batch = $response->json() ?? [];
            $projects = array_merge($projects, $batch);
            $page++;
        } while (count($batch) === 100);

        return $projects;
    }

    private function makeStudentProjectPath(string $slug, int $attemptNumber): string
    {
        $path = 'school21-' . $slug;

        if ($attemptNumber > 1) {
            $path .= '-attempt-' . $attemptNumber;
        }

        return str($path)->slug('-')->toString();
    }

    private function buildProjectReadme(array $projectData): string
    {
        $title = $projectData['title'] ?? 'Project';
        $description = $projectData['description'] ?? '';
        $instructions = $projectData['instructions'] ?? '';
        $difficulty = $projectData['difficulty'] ?? 'beginner';
        $language = $projectData['language'] ?? 'unknown';
        $xpReward = $projectData['xp_reward'] ?? 0;
        $passingScore = $projectData['passing_score'] ?? 0;
        $estimatedHours = $projectData['estimated_hours'] ?? 0;
        $requiresReview = !empty($projectData['requires_peer_review']) ? 'yes' : 'no';
        $requiredReviews = $projectData['required_reviews_count'] ?? 0;
        $studentOwner = $projectData['student_owner'] ?? null;
        $attemptNumber = $projectData['attempt_number'] ?? null;

        $studentBlock = '';
        if ($studentOwner !== null) {
            $studentBlock = "\n| Student repository owner | {$studentOwner} |";
        }
        if ($attemptNumber !== null) {
            $studentBlock .= "\n| Attempt | {$attemptNumber} |";
        }

        return <<<MD
# {$title}

{$description}

## Project metadata

| Field | Value |
| --- | --- |
| Language | {$language} |
| Difficulty | {$difficulty} |
| Estimated hours | {$estimatedHours} |
| XP reward | {$xpReward} |
| Passing score | {$passingScore}% |
| Peer review required | {$requiresReview} |
| Required reviews | {$requiredReviews} |{$studentBlock}

## Instructions

{$instructions}

---

This README is managed by 21-LMS. Changes made in the LMS admin panel are synced to the template GitLab project; student repositories are generated from LMS project instructions.
MD;
    }
}
