<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'gitlab' => [
        // Public URL used in browser links.
        'url' => env('GITLAB_URL'),
        // Internal URL used by Laravel server-to-server GitLab API calls.
        // For local Wi-Fi use: GITLAB_URL=http://192.168.0.13 and GITLAB_INTERNAL_URL=http://localhost
        'internal_url' => env('GITLAB_INTERNAL_URL', env('GITLAB_URL')),
        'token' => env('GITLAB_TOKEN'),
        'admin_token' => env('GITLAB_ADMIN_TOKEN'),
        'visibility' => env('GITLAB_VISIBILITY', 'public'),
        'cache_ttl' => env('GITLAB_CACHE_TTL', 300),
        'user_token_scopes' => array_values(array_filter(array_map('trim', explode(',', env('GITLAB_USER_TOKEN_SCOPES', 'api,write_repository'))))),
        'sign_in_url' => rtrim(env('GITLAB_URL', ''), '/') . '/users/sign_in',
        'login_url' => env('GITLAB_LOGIN_URL', rtrim(env('GITLAB_URL', ''), '/') . '/users/sign_in?auto_sign_in=false'),
        'sso_url' => env('GITLAB_SSO_URL', rtrim(env('GITLAB_URL', ''), '/') . '/users/sign_in'),
    ],

    'school21_oidc' => [
        'issuer' => env('SCHOOL21_OIDC_ISSUER', env('APP_URL')),
        'client_id' => env('SCHOOL21_OIDC_CLIENT_ID', 'gitlab'),
        'client_secret' => env('SCHOOL21_OIDC_CLIENT_SECRET', 'change-me-school21-gitlab-sso'),
        'gitlab_redirect_uri' => env('SCHOOL21_OIDC_GITLAB_REDIRECT_URI', rtrim(env('GITLAB_URL', ''), '/') . '/users/auth/openid_connect/callback'),
        'private_key_path' => env('SCHOOL21_OIDC_PRIVATE_KEY_PATH', storage_path('app/oidc/school21-private.key')),
    ],

];
