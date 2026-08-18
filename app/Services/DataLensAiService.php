<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DataLensAiService
{
    private string $baseUrl;

    private string $uiUrl;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('datalens_ai.base_url'), '/');
        $this->uiUrl = rtrim((string) config('datalens_ai.ui_url'), '/');
        $this->timeout = max(1, (int) config('datalens_ai.timeout', 120));
    }

    /**
     * Returns the public health contract exposed by datalens-ai.
     *
     * @return array{status?: string, llm_server?: bool, datalens?: bool, unavailable?: bool, detail?: string}
     */
    public function health(): array
    {
        try {
            $response = $this->client()->get('/health');

            if (! $response->successful()) {
                return [
                    'unavailable' => true,
                    'detail' => 'AI service returned HTTP ' . $response->status() . '.',
                ];
            }

            return $response->json() ?? [];
        } catch (ConnectionException) {
            return [
                'unavailable' => true,
                'detail' => 'AI service is unavailable.',
            ];
        }
    }

    /**
     * @return array{tool?: string, reply?: string}
     */
    public function agentRespond(string $message, ?string $dashboardContext = null, bool $hasDashboard = false): array
    {
        return $this->request('POST', '/api/agent/respond', [
            'message' => $message,
            'dashboard_context' => $dashboardContext,
            'has_dashboard' => $hasDashboard,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function startGeneration(string $message): array
    {
        return $this->request('POST', '/api/dashboard-jobs', [
            'operation' => 'generate',
            'message' => $message,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function startEdit(string $dashboardId, string $message, ?string $connectionId = null): array
    {
        $payload = [
            'operation' => 'edit',
            'dashboard_id' => $dashboardId,
            'message' => $message,
        ];

        if ($connectionId) {
            $payload['connection_id'] = $connectionId;
        }

        return $this->request('POST', '/api/dashboard-jobs', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function jobStatus(string $jobId): array
    {
        return $this->request('GET', '/api/dashboard-jobs/' . rawurlencode($jobId), []);
    }

    public function dashboardEmbedUrl(string $dashboardId, string $title): string
    {
        $slug = Str::slug($title) ?: 'dashboard';

        return $this->uiUrl
            . '/' . rawurlencode($dashboardId)
            . '-' . rawurlencode($slug);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout);
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload): array
    {
        try {
            $options = strtoupper($method) === 'GET' ? [] : ['json' => $payload];
            $response = $this->client()->send($method, $path, $options);
        } catch (ConnectionException) {
            throw new RuntimeException('AI service is unavailable. Start datalens-ai and try again.');
        }

        if (! $response->successful()) {
            $detail = $response->json('detail') ?: 'HTTP ' . $response->status();
            throw new RuntimeException((string) $detail);
        }

        return $response->json() ?? [];
    }
}
