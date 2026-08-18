<?php

use App\Http\Controllers\Public\ProjectController as PublicProjectController;
use App\Http\Controllers\Public\CourseController;
use App\Http\Controllers\Public\ProgressController;
use App\Http\Controllers\Public\SubmissionTestController;
use App\Http\Controllers\Public\GitlabController;
use App\Http\Controllers\Public\CalendarController;
use App\Http\Controllers\Public\ActivitiesController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GamificationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\OidcController;
use Illuminate\Support\Facades\Route;

// School21 OpenID Connect provider for GitLab SSO.
Route::get('/.well-known/openid-configuration', [OidcController::class, 'discovery'])->name('oidc.discovery');
Route::get('/oidc/jwks', [OidcController::class, 'jwks'])->name('oidc.jwks');
Route::get('/oidc/authorize', [OidcController::class, 'authorize'])->middleware('auth')->name('oidc.authorize');

// Auth pages — accessible without auth, redirect if already logged in
Route::view('/login', 'auth.login')->name('login')->middleware('guest');
Route::view('/register', 'auth.register')->name('register')->middleware('guest');
Route::view('/forgot-password', 'auth.forgot-password')->name('forgot-password')->middleware('guest');

// All other routes require authentication
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

    // /dashboard — точка входа после логина, редиректит по ролям
    Route::get('/dashboard', function () {
        if (auth()->check() && auth()->user()->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('public.home');
    })->name('dashboard');

    Route::get('/public', function () {
        return view('public.layouts.app');
    });
    Route::get('/map', function () {
        return view('public.projects.map');
    })->name('public.projects.map');

    // Admin routes with role check
    Route::prefix('admin')
        ->middleware(['role:admin'])
        ->name('admin.')
        ->group(function () {
            Route::get('/', fn () => redirect()->route('admin.dashboard'));
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            // AI Analytics — dashboards are personal to the administrator who created them.
            Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
            Route::get('/analytics/new', [AnalyticsController::class, 'create'])->name('analytics.create');
            Route::get('/analytics/dashboards', [AnalyticsController::class, 'dashboards'])->name('analytics.dashboards');
            Route::post('/analytics/generate', [AnalyticsController::class, 'generate'])->name('analytics.generate');
            Route::post('/analytics/agent', [AnalyticsController::class, 'agent'])->name('analytics.agent');
            Route::post('/analytics/{analyticsDashboard}/edit', [AnalyticsController::class, 'edit'])->name('analytics.edit');
            Route::post('/analytics/{analyticsDashboard}/chat', [AnalyticsController::class, 'chat'])->name('analytics.chat');
            Route::post('/analytics/live/generate', [AnalyticsController::class, 'startLiveGenerate'])->name('analytics.live.generate');
            Route::post('/analytics/{analyticsDashboard}/live/edit', [AnalyticsController::class, 'startLiveEdit'])->name('analytics.live.edit');

            // Users management
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::patch('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');

            // Projects CRUD
            Route::get('/projects', [AdminProjectController::class, 'index'])->name('projects.index');
            Route::get('/projects/create', [AdminProjectController::class, 'create'])->name('projects.create');
            Route::post('/projects', [AdminProjectController::class, 'store'])->name('projects.store');
            Route::get('/projects/{project:id}/edit', [AdminProjectController::class, 'edit'])->name('projects.edit');
            Route::put('/projects/{project:id}', [AdminProjectController::class, 'update'])->name('projects.update');
            Route::get('/projects/{project:id}/tests', [AdminProjectController::class, 'tests'])->name('projects.tests');
            Route::post('/projects/{project:id}/tests', [AdminProjectController::class, 'storeTest'])->name('projects.tests.store');
            Route::put('/projects/{project:id}/tests/{test:id}', [AdminProjectController::class, 'updateTest'])->name('projects.tests.update');
            Route::delete('/projects/{project:id}/tests/{test:id}', [AdminProjectController::class, 'destroyTest'])->name('projects.tests.destroy');
            Route::delete('/projects/{project:id}', [AdminProjectController::class, 'destroy'])->name('projects.destroy');

            // Courses use numeric IDs in the admin panel. The public Course model
            // is still resolved by slug (see Course::getRouteKeyName()), therefore
            // every admin binding explicitly uses the primary key.
            Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/create', [AdminCourseController::class, 'create'])->name('courses.create');
            Route::post('/courses', [AdminCourseController::class, 'store'])->name('courses.store');
            Route::get('/courses/{course:id}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
            Route::put('/courses/{course:id}', [AdminCourseController::class, 'update'])->name('courses.update');
            Route::delete('/courses/{course:id}', [AdminCourseController::class, 'destroy'])->name('courses.destroy');

            // Module CRUD (nested under a numeric admin course ID).
            Route::prefix('courses/{course:id}/modules')->name('courses.modules.')->group(function () {
                Route::get('/', [ModuleController::class, 'index'])->name('index');
                Route::get('/create', [ModuleController::class, 'create'])->name('create');
                Route::post('/', [ModuleController::class, 'store'])->name('store');
                Route::get('/{module:id}/edit', [ModuleController::class, 'edit'])->name('edit');
                Route::put('/{module:id}', [ModuleController::class, 'update'])->name('update');
                Route::delete('/{module:id}', [ModuleController::class, 'destroy'])->name('destroy');
            });

            // Lesson CRUD (nested under a numeric admin module ID).
            Route::prefix('modules/{module:id}/lessons')->name('modules.lessons.')->group(function () {
                Route::get('/', [LessonController::class, 'index'])->name('index');
                Route::get('/create', [LessonController::class, 'create'])->name('create');
                Route::post('/', [LessonController::class, 'store'])->name('store');
                Route::get('/{lesson:id}/edit', [LessonController::class, 'edit'])->name('edit');
                Route::put('/{lesson:id}', [LessonController::class, 'update'])->name('update');
                Route::delete('/{lesson:id}', [LessonController::class, 'destroy'])->name('destroy');
            });

            // Reviews
            Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
            Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
            Route::post('/reviews/{review}/submit', [ReviewController::class, 'submit'])->name('reviews.submit');
            Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
        });

    // Autotest logs for submission owners, assigned reviewers and admins.
    Route::get('/submissions/{submission}/tests', [SubmissionTestController::class, 'index'])->name('public.submissions.tests.index');
    Route::post('/submissions/{submission}/tests/rerun', [SubmissionTestController::class, 'rerun'])->name('public.submissions.tests.rerun');
    Route::get('/submissions/{submission}/tests/{testRun}', [SubmissionTestController::class, 'show'])->name('public.submissions.tests.show');

    // P2P Reviews — available to regular users assigned as reviewers.
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('/{review}', [ReviewController::class, 'show'])->name('show');
        Route::post('/{review}/submit', [ReviewController::class, 'submit'])->name('submit');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
    });

    // P2P Chat
    Route::prefix('chats')->name('chats.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/{chat}', [ChatController::class, 'show'])->name('show');
        Route::post('/{chat}/messages', [ChatController::class, 'store'])->name('messages.store');
        Route::get('/{chat}/messages', [ChatController::class, 'messages'])->name('messages');
    });

    // Gamification
    Route::prefix('gamification')->name('gamification.')->group(function () {
        Route::get('/achievements', [GamificationController::class, 'achievements'])->name('achievements');
        Route::get('/leaderboard', [GamificationController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/profile', [GamificationController::class, 'profile'])->name('profile');
    });

    // Project listing and detail pages
    Route::get('/projects', [PublicProjectController::class, 'index'])->name('public.projects.index');
    Route::get('/projects/{project}', [PublicProjectController::class, 'show'])->name('public.projects.show');

    // Course listing and detail pages
    Route::get('/courses', [App\Http\Controllers\Public\CourseController::class, 'index'])->name('public.courses.index');
    Route::get('/courses/{course}', [App\Http\Controllers\Public\CourseController::class, 'show'])->name('public.courses.show');
    Route::get('/courses/{course}/modules/{module}', [App\Http\Controllers\Public\CourseController::class, 'moduleShow'])->name('public.courses.module');
    Route::get('/courses/{course}/modules/{module}/lessons/{lesson}', [App\Http\Controllers\Public\CourseController::class, 'lessonShow'])->name('public.courses.lesson');

    // Progress page
    Route::get('/progress', ProgressController::class)->name('public.progress');

    /* ------------------------------------------------------------------
       Calendar — fully server-side (no AJAX endpoints).

       Pages: each tab is its own server-rendered route.
       Actions: native HTML forms, controller redirects back with flash.

       CHANGED vs old version:
         - removed POST /calendar/slots/{slot}/book (manual booking is gone:
           slots are booked ONLY by the system algorithm in BookingService
           when a user submits a project for review);
         - removed AJAX endpoints: GET /calendar/data, GET /calendar/grid-data,
           GET /calendar/queue-position (the UI-only app.js never calls them);
         - removed duplicate GET /calendar/schedule (same as /calendar).
       ------------------------------------------------------------------ */
    Route::get('/calendar', CalendarController::class)->name('public.calendar');
    Route::get('/calendar/my-events', [CalendarController::class, 'myEventsPage'])->name('public.calendar.my-events');

    // Events — standalone pages (School 21 style: team-up announcements).
    // The calendar "Events" tab links here; the list partial is shared.
    Route::get('/events', [CalendarController::class, 'eventsPage'])->name('public.events.index');
    Route::get('/events/{event}', [CalendarController::class, 'showEvent'])->name('public.events.show');
    Route::post('/events/{event}/register', [CalendarController::class, 'registerForEvent'])->name('public.events.register');
    Route::delete('/events/{event}/register', [CalendarController::class, 'unregisterFromEvent'])->name('public.events.unregister');
    Route::get('/calendar/events', fn () => redirect()->route('public.events.index')); // old URL keeps working

    Route::post('/calendar/slots', [CalendarController::class, 'createSlot'])->name('calendar.slots.create');
    Route::post('/calendar/slots/{slot}/book', [CalendarController::class, 'bookSlot'])->name('calendar.slots.book');
    Route::delete('/calendar/slots/{slot}', [CalendarController::class, 'destroySlot'])->name('calendar.slots.destroy');
    Route::post('/calendar/events', [CalendarController::class, 'createEvent'])->name('calendar.events.create');
    Route::delete('/calendar/events/{event}', [CalendarController::class, 'destroyEvent'])->name('calendar.events.destroy');
    Route::post('/calendar/submit-review', [CalendarController::class, 'submitProjectForReview'])->name('calendar.submit-review');

    Route::get('/activities', ActivitiesController::class)->name('public.activities');

    // School21 GitLab account. API access is managed automatically via admin API.
    Route::get('/gitlab', [GitlabController::class, 'show'])->name('public.gitlab');
    Route::post('/gitlab/sign-in', [GitlabController::class, 'signIn'])->name('public.gitlab.sign-in');

    // Tribes page (opened from the Activities overlay)
    Route::view('/tribes', 'public.tribes')->name('public.tribes');

    // Legal pages
    Route::get('/terms', fn () => view('legal.terms'))->name('terms');
    Route::get('/policy', fn () => view('legal.policy'))->name('policy');

    // Authenticated project actions
    Route::post('/projects/{project}/subscribe', SubscriptionController::class)
        ->middleware('rate_limit.subscribe')
        ->name('public.projects.subscribe');

    // Home and more pages
    Route::prefix('')->name('public.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Public\MainController::class, 'home'])->name('home');
        // Calendar uses CalendarController (see the Calendar block above) — not Route::view here
        // Route::view('/progress', 'public.profile')->name('progress'); // REMOVED
        Route::view('/activities', 'public.activities')->name('activities');
        Route::view('/more', 'public.more')->name('more');
        Route::view('/settings', 'public.tribes')->name('settings');
    });
});
