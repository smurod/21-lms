<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DataLens AI integration
    |--------------------------------------------------------------------------
    |
    | Laravel only calls the FastAPI service. Database credentials and the
    | DataLens/LLM configuration belong to analytics/datalens-ai/.env and are
    | intentionally not copied into the Laravel environment.
    |
    */
    // Server-side base (Laravel → FastAPI). Inside a container network this
    // is the service name (http://datalens-ai:8100), on the host — 127.0.0.1.
    'base_url' => rtrim((string) env('DATALENS_AI_URL', 'http://127.0.0.1:8100'), '/'),
    // Browser-facing base (SSE streams opened from the admin panel). Falls
    // back to base_url. In docker-compose it is the published port, e.g.
    // http://localhost:8100, while base_url stays http://datalens-ai:8100.
    'public_url' => rtrim((string) env('DATALENS_AI_PUBLIC_URL', (string) env('DATALENS_AI_URL', 'http://127.0.0.1:8100')), '/'),
    'ui_url'   => rtrim((string) env('DATALENS_UI_URL', 'http://localhost:8085'), '/'),
    'timeout'  => (int) env('DATALENS_AI_TIMEOUT', 120),
    // Shared secret sent in X-API-Key header. Must match SERVICE_API_KEY in datalens-ai/.env.
    // Leave empty in local dev if datalens-ai SERVICE_API_KEY is also empty.
    'api_key'  => (string) env('DATALENS_AI_API_KEY', ''),
];
