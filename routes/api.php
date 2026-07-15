<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Validation\ValidationException;

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

    // Determine redirect based on role
    if ($user->hasRole('admin')) {
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
        'email' => 'required|string|email|max:255|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
    ];

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
    if ($validator->fails()) {
        return response()->json([
            'error' => $validator->errors()->first(),
        ], 422);
    }

    $user = \App\Models\User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
    ]);

    Auth::login($user);
    session()->regenerate();

    return response()->json([
        'redirect' => route('public.home'),
    ]);
})->name('auth.register.api')->middleware('web');
