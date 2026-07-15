<?php

namespace App\Providers;

use App\Http\Middleware\RateLimitSubscribe;
use App\Services\GitlabCacheService;
use App\Services\GitlabHealthService;
use App\Services\GitlabService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register GitLab services
        $this->app->singleton(GitlabService::class, fn () => new GitlabService());
        $this->app->singleton(GitlabCacheService::class);
        $this->app->singleton(GitlabHealthService::class);

        // Run GitLab health check on boot
        $health = app(GitlabHealthService::class);
        $health->getHealthStatus();

        // Register middleware aliases
        Route::aliasMiddleware('rate_limit.subscribe', RateLimitSubscribe::class);
    }
}
