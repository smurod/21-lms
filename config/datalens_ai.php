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
    'base_url' => rtrim((string) env('DATALENS_AI_URL', 'http://127.0.0.1:8100'), '/'),
    'ui_url' => rtrim((string) env('DATALENS_UI_URL', 'http://localhost:8085'), '/'),
    'timeout' => (int) env('DATALENS_AI_TIMEOUT', 120),
];
