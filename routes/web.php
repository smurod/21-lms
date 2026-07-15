<?php

use App\Http\Controllers\Public\ProjectController as PublicProjectController;
use App\Http\Controllers\Public\CourseController;
use App\Http\Controllers\Public\ProgressController;
use App\Http\Controllers\Public\CalendarController;
use App\Http\Controllers\Public\ActivitiesController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GamificationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

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
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Users management
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');

            // Projects CRUD
            Route::get('/projects', [AdminProjectController::class, 'index'])->name('projects.index');
            Route::get('/projects/create', [AdminProjectController::class, 'create'])->name('projects.create');
            Route::post('/projects', [AdminProjectController::class, 'store'])->name('projects.store');
            Route::get('/projects/{project}/edit', [AdminProjectController::class, 'edit'])->name('projects.edit');
            Route::put('/projects/{project}', [AdminProjectController::class, 'update'])->name('projects.update');
            Route::delete('/projects/{project}', [AdminProjectController::class, 'destroy'])->name('projects.destroy');

            // Courses CRUD
            Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/create', [AdminCourseController::class, 'create'])->name('courses.create');
            Route::post('/courses', [AdminCourseController::class, 'store'])->name('courses.store');
            Route::get('/courses/{course}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
            Route::put('/courses/{course}', [AdminCourseController::class, 'update'])->name('courses.update');
            Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy'])->name('courses.destroy');

            // Module CRUD (nested under course)
            Route::prefix('courses/{course}/modules')->name('courses.modules.')->group(function () {
                Route::get('/', [ModuleController::class, 'index'])->name('index');
                Route::get('/create', [ModuleController::class, 'create'])->name('create');
                Route::post('/', [ModuleController::class, 'store'])->name('store');
                Route::get('/{module}/edit', [ModuleController::class, 'edit'])->name('edit');
                Route::put('/{module}', [ModuleController::class, 'update'])->name('update');
                Route::delete('/{module}', [ModuleController::class, 'destroy'])->name('destroy');
            });

            // Lesson CRUD (nested under module)
            Route::prefix('modules/{module}/lessons')->name('modules.lessons.')->group(function () {
                Route::get('/', [LessonController::class, 'index'])->name('index');
                Route::get('/create', [LessonController::class, 'create'])->name('create');
                Route::post('/', [LessonController::class, 'store'])->name('store');
                Route::get('/{lesson}/edit', [LessonController::class, 'edit'])->name('edit');
                Route::put('/{lesson}', [LessonController::class, 'update'])->name('update');
                Route::delete('/{lesson}', [LessonController::class, 'destroy'])->name('destroy');
            });

            // Reviews
            Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
            Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
            Route::post('/reviews/{review}/submit', [ReviewController::class, 'submit'])->name('reviews.submit');
            Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
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

    // Progress page — REMOVED: profile page deleted
    // Route::get('/progress', ProgressController::class)->name('public.progress');
    Route::get('/progress', fn() => redirect()->route('public.home'))->name('public.progress');

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
    Route::delete('/calendar/slots/{slot}', [CalendarController::class, 'destroySlot'])->name('calendar.slots.destroy');
    Route::post('/calendar/events', [CalendarController::class, 'createEvent'])->name('calendar.events.create');
    Route::delete('/calendar/events/{event}', [CalendarController::class, 'destroyEvent'])->name('calendar.events.destroy');
    Route::post('/calendar/submit-review', [CalendarController::class, 'submitProjectForReview'])->name('calendar.submit-review');

    Route::get('/activities', ActivitiesController::class)->name('public.activities');

    // GitLab sign-in page (static for now, opened from the Projects overlay)
    Route::view('/gitlab', 'public.gitlab')->name('public.gitlab');

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
