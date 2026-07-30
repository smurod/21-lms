<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OidcController extends Controller
{
    public function discovery(): JsonResponse
    {
        return response()->json([
            'issuer' => $this->issuer(),
            'authorization_endpoint' => route('oidc.authorize'),
            'token_endpoint' => route('oidc.token'),
            'userinfo_endpoint' => route('oidc.userinfo'),
            'jwks_uri' => route('oidc.jwks'),
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'scopes_supported' => ['openid', 'profile', 'email'],
            'claims_supported' => [
                'sub',
                'iss',
                'aud',
                'exp',
                'iat',
                'auth_time',
                'nonce',
                'email',
                'email_verified',
                'name',
                'preferred_username',
                'nickname',
            ],
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic'],
        ]);
    }

    public function jwks(): JsonResponse
    {
        $details = openssl_pkey_get_details($this->privateKeyResource());
        $rsa = $details['rsa'] ?? null;

        abort_unless($rsa && isset($rsa['n'], $rsa['e']), Response::HTTP_INTERNAL_SERVER_ERROR, 'OIDC RSA key is not available.');

        return response()->json([
            'keys' => [[
                'kty' => 'RSA',
                'use' => 'sig',
                'kid' => $this->keyId(),
                'alg' => 'RS256',
                'n' => $this->base64UrlEncode($rsa['n']),
                'e' => $this->base64UrlEncode($rsa['e']),
            ]],
        ]);
    }

    public function authorize(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
            'response_type' => ['required', 'in:code'],
            'scope' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'nonce' => ['nullable', 'string'],
        ]);

        if ($validated['client_id'] !== $this->clientId()) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid OIDC client_id.');
        }

        if ($validated['redirect_uri'] !== $this->redirectUri()) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid OIDC redirect_uri.');
        }

        $user = $request->user();
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        $code = Str::random(80);
        Cache::put($this->codeCacheKey($code), [
            'user_id' => $user->id,
            'client_id' => $validated['client_id'],
            'redirect_uri' => $validated['redirect_uri'],
            'nonce' => $validated['nonce'] ?? null,
            'auth_time' => time(),
        ], now()->addMinutes(5));

        $target = $validated['redirect_uri'] . '?' . http_build_query(array_filter([
                'code' => $code,
                'state' => $validated['state'] ?? null,
            ], fn ($value) => $value !== null));

        return redirect()->away($target);
    }

    public function token(Request $request): JsonResponse
    {
        $client = $this->clientCredentials($request);

        if (($client['id'] ?? null) !== $this->clientId() || ($client['secret'] ?? null) !== $this->clientSecret()) {
            return response()->json(['error' => 'invalid_client'], Response::HTTP_UNAUTHORIZED);
        }

        if ($request->input('grant_type') !== 'authorization_code') {
            return response()->json(['error' => 'unsupported_grant_type'], Response::HTTP_BAD_REQUEST);
        }

        $code = (string) $request->input('code');
        $payload = Cache::pull($this->codeCacheKey($code));

        if (!$payload || ($payload['redirect_uri'] ?? null) !== $request->input('redirect_uri')) {
            return response()->json(['error' => 'invalid_grant'], Response::HTTP_BAD_REQUEST);
        }

        $user = User::find($payload['user_id'] ?? null);
        if (!$user) {
            return response()->json(['error' => 'invalid_grant'], Response::HTTP_BAD_REQUEST);
        }

        $accessToken = Str::random(80);
        Cache::put($this->accessTokenCacheKey($accessToken), $user->id, now()->addHour());

        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'id_token' => $this->idToken($user, $payload),
        ]);
    }

    public function userinfo(Request $request): JsonResponse
    {
        $header = (string) $request->header('Authorization', '');
        $token = Str::startsWith($header, 'Bearer ') ? Str::after($header, 'Bearer ') : '';
        $userId = $token ? Cache::get($this->accessTokenCacheKey($token)) : null;

        if (!$userId || !($user = User::find($userId))) {
            return response()->json(['error' => 'invalid_token'], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json($this->claims($user));
    }

    private function idToken(User $user, array $authPayload): string
    {
        $now = time();
        $claims = [
            ...$this->claims($user),
            'iss' => $this->issuer(),
            'aud' => $this->clientId(),
            'iat' => $now,
            'exp' => $now + 300,
            'auth_time' => $authPayload['auth_time'] ?? $now,
        ];

        if (!empty($authPayload['nonce'])) {
            $claims['nonce'] = $authPayload['nonce'];
        }

        return $this->signJwt($claims);
    }

    private function claims(User $user): array
    {
        return [
            'sub' => (string) $user->id,
            'email' => $user->email,
            'email_verified' => true,
            'name' => $user->name,
            'preferred_username' => $user->username,
            'nickname' => $user->username,
        ];
    }

    private function signJwt(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'RS256',
            'kid' => $this->keyId(),
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $signingInput = implode('.', $segments);

        if (!openssl_sign($signingInput, $signature, $this->privateKeyResource(), OPENSSL_ALGO_SHA256)) {
            Log::error('OIDC JWT signing failed');
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'OIDC JWT signing failed.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function privateKeyResource(): \OpenSSLAsymmetricKey
    {
        $privateKey = $this->privateKeyPem();
        $resource = openssl_pkey_get_private($privateKey);

        abort_unless($resource, Response::HTTP_INTERNAL_SERVER_ERROR, 'OIDC private key is invalid.');

        return $resource;
    }

    private function privateKeyPem(): string
    {
        $path = config('services.school21_oidc.private_key_path');

        if (!is_string($path) || $path === '') {
            $path = storage_path('app/oidc/school21-private.key');
        }

        if (!file_exists($path)) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0700, true);
            }

            $key = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);

            abort_unless($key, Response::HTTP_INTERNAL_SERVER_ERROR, 'OIDC private key generation failed.');
            openssl_pkey_export($key, $pem);
            file_put_contents($path, $pem);
            chmod($path, 0600);
        }

        return (string) file_get_contents($path);
    }

    private function keyId(): string
    {
        $details = openssl_pkey_get_details($this->privateKeyResource());

        return substr(sha1($details['key'] ?? $this->issuer()), 0, 16);
    }

    private function clientCredentials(Request $request): array
    {
        $basic = (string) $request->header('Authorization', '');

        if (Str::startsWith($basic, 'Basic ')) {
            $decoded = base64_decode(Str::after($basic, 'Basic '), true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$id, $secret] = explode(':', $decoded, 2);

                return ['id' => $id, 'secret' => $secret];
            }
        }

        return [
            'id' => (string) $request->input('client_id'),
            'secret' => (string) $request->input('client_secret'),
        ];
    }

    private function issuer(): string
    {
        return rtrim((string) config('services.school21_oidc.issuer'), '/');
    }

    private function redirectUri(): string
    {
        return (string) config('services.school21_oidc.gitlab_redirect_uri');
    }

    private function clientId(): string
    {
        return (string) config('services.school21_oidc.client_id');
    }

    private function clientSecret(): string
    {
        return (string) config('services.school21_oidc.client_secret');
    }

    private function codeCacheKey(string $code): string
    {
        return 'oidc:code:' . hash('sha256', $code);
    }

    private function accessTokenCacheKey(string $token): string
    {
        return 'oidc:access:' . hash('sha256', $token);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
