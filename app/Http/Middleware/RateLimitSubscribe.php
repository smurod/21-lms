<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitSubscribe
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'subscribe:' . ($request->user()?->id ?: $request->ip());

        $throttle = RateLimiter::attempt(
            $key,
            maxAttempts: 5,
            callback: fn () => $next($request),
            decaySeconds: 60,
        );

        if (! $throttle) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Слишком много попыток подписки. Попробуйте позже.',
                ], 429);
            }
            return back()->withErrors(['error' => 'Слишком много попыток подписки. Попробуйте позже.']);
        }

        return $throttle;
    }
}
