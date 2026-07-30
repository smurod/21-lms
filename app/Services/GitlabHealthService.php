<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GitlabHealthService
{
    protected string $baseUrl;
    protected ?string $token;

    public function __construct()
    {
        $url = config('services.gitlab.internal_url') ?: config('services.gitlab.url');

        $this->baseUrl = rtrim((string) $url, '/') . '/api/v4';
        $this->token = config('services.gitlab.admin_token') ?: config('services.gitlab.token');
    }

    /**
     * Check if GitLab API is reachable.
     *
     * All cache access is wrapped in rescue(): when the cache store is the
     * database and its table does not exist yet (e.g. during migrate:fresh),
     * the health check must not crash artisan commands.
     */
    public function isHealthy(): bool
    {
        $cacheKey = 'gitlab:health';
        $cached = rescue(fn () => Cache::get($cacheKey), null, false);

        if ($cached !== null) {
            return (bool) $cached;
        }

        if (!$this->token || $this->baseUrl === '/api/v4') {
            rescue(fn () => Cache::put($cacheKey, false, 300), null, false);
            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->get("{$this->baseUrl}/version");

            $healthy = $response->successful();

            // Cache result for 5 minutes (ignore cache failures)
            rescue(fn () => Cache::put($cacheKey, $healthy, 300), null, false);

            return $healthy;
        } catch (\Throwable $e) {
            rescue(fn () => Cache::put($cacheKey, false, 300), null, false);
            return false;
        }
    }

    /**
     * Get the last health check result with timestamp.
     */
    public function getHealthStatus(): array
    {
        $cacheKey = 'gitlab:health';
        $healthy = rescue(fn () => Cache::get($cacheKey), null, false);

        if ($healthy === null) {
            // First run — check immediately
            $healthy = $this->isHealthy();
        }

        return [
            'healthy' => (bool) $healthy,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
