<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\OidcController;

Route::post('/oidc/token', [OidcController::class, 'token'])->name('oidc.token');
Route::get('/oidc/userinfo', [OidcController::class, 'userinfo'])->name('oidc.userinfo');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// AJAX Login endpoint
Route::post('/auth/login', function (Request $request) {
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'email' => 'required|string',
        'password' => 'required|string',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'error' => 'Please enter both email and password.',
        ], 422);
    }

    $credentials = $request->only('email', 'password');

    // Rate limiting
    $key = Str::lower($request->input('email')) . '|' . $request->ip();
    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        throw ValidationException::withMessages([
            'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
        ]);
    }

    if (!Auth::attempt($credentials, $request->filled('remember'))) {
        RateLimiter::hit($key, 60);
        return response()->json([
            'error' => 'Invalid login or password. Please try again.',
        ], 401);
    }

    RateLimiter::clear($key);
    session()->regenerate();

    $user = Auth::user();

    // Determine redirect based on role, but preserve auth middleware intended
    // URLs first. This is required for GitLab SSO: if GitLab sends a guest to
    // /oidc/authorize, login must continue the OIDC request instead of sending
    // the user to the dashboard.
    $intended = session()->pull('url.intended');
    if ($intended) {
        $redirect = $intended;
    } elseif ($user->hasRole('admin')) {
        $redirect = route('admin.dashboard');
    } else {
        $redirect = request()->query('redirect_to') ?: route('public.home');
    }

    return response()->json([
        'redirect' => $redirect,
    ]);
})->name('auth.login.api')->middleware('web');

// AJAX Register endpoint
Route::post('/auth/register', function (Request $request) {
    $rules = [
        'name' => 'required|string|max:255',
        'username' => [
            'required',
            'string',
            'max:50',
            'regex:/^[A-Za-z0-9_][A-Za-z0-9_.-]*$/',
            'unique:users,username',
            function (string $attribute, mixed $value, callable $fail): void {
                $reserved = ['admin', 'api', 'assets', 'dashboard', 'explore', 'groups', 'help', 'import', 'profile', 'projects', 'root', 'search', 'users'];

                if (in_array(strtolower((string) $value), $reserved, true)) {
                    $fail('This username is reserved by GitLab. Choose another username, for example lms-admin or your own login.');
                }
            },
        ],
        'email' => 'required|string|email|max:255|unique:users,email',
        'password' => [
            'required',
            'string',
            'min:8',
            'regex:/[A-Za-z]/',
            'regex:/[0-9]/',
            function (string $attribute, mixed $value, callable $fail): void {
                $blocked = [
                    '00000000',
                    '11111111',
                    '22222222',
                    '12121212',
                    '12345678',
                    '87654321',
                    '12341234',
                    'abcd1234',
                    'a1234567',
                    'gitlab21',
                    'a1b2c3d4',
                ];

                $password = strtolower((string) $value);

                if (in_array($password, $blocked, true)) {
                    $fail('Password is too common for GitLab. Use at least 8 characters with latin letters and numbers, for example school21.');
                    return;
                }

                $identityTokens = collect([
                    $request->input('username'),
                    str($request->input('email', ''))->before('@')->toString(),
                    ...preg_split('/[^A-Za-z0-9]+/', (string) $request->input('name'), -1, PREG_SPLIT_NO_EMPTY),
                ])->filter(fn ($token) => strlen((string) $token) >= 4)
                    ->map(fn ($token) => strtolower((string) $token))
                    ->unique();

                foreach ($identityTokens as $token) {
                    if (str_contains($password, $token)) {
                        $fail('Password must not contain your username, email login or name because GitLab rejects personal-info passwords. Example: school21.');
                        return;
                    }
                }
            },
            'confirmed',
        ],
    ];

    $messages = [
        'password.regex' => 'Password must contain at least one latin letter and one number. Example: school21.',
        'password.min' => 'Password must be at least 8 characters long.',
        'password.confirmed' => 'Passwords do not match.',
    ];

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
        return response()->json([
            'error' => $validator->errors()->first(),
        ], 422);
    }

    try {
        $gitlabResult = app(\App\Services\GitlabService::class)->provisionUserAccount(
            email: $request->email,
            username: $request->username,
            name: $request->name,
            password: $request->password,
        );
    } catch (\Throwable $e) {
        Log::error('GitLab user provisioning blocked API registration', [
            'email' => $request->email,
            'username' => $request->username,
            'error' => $e->getMessage(),
        ]);

        $error = str_contains(strtolower($e->getMessage()), 'password')
            ? 'Password is too common for GitLab. Use at least 8 characters with latin letters and numbers, for example school21.'
            : 'GitLab API is required for registration, but provisioning failed: ' . $e->getMessage();

        return response()->json([
            'error' => $error,
        ], 422);
    }

    $user = \App\Models\User::create([
        'name' => $request->name,
        'username' => $request->username,
        'email' => $request->email,
        'password' => bcrypt($request->password),
    ]);

    $userRole = \Spatie\Permission\Models\Role::firstOrCreate([
        'name' => 'user',
        'guard_name' => 'web',
    ]);
    $user->assignRole($userRole);

    $user->connectGitlabToken($gitlabResult['token']);

    Auth::login($user);
    session()->regenerate();

    return response()->json([
        'redirect' => route('public.home'),
        'gitlab_connected' => true,
    ]);
})->name('auth.register.api')->middleware('web');
