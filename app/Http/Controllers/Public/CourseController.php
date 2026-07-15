<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Admin\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    /**
     * Public catalog of courses.
     */
    public function index(): View
    {
        $courses = Course::where('is_published', true)
            ->withCount(['modules', 'projects'])
            ->orderBy('order_position')
            ->get();

        return view('public.courses.index', compact('courses'));
    }

    /**
     * Public course detail page.
     */
    public function show(Course $course): View
    {
        $course->load(['modules.lessons', 'requiredBy' => function ($q) {
            $q->with('course');
        }]);

        // Check if user has completed all dependencies
        $user = auth()->user();
        $canAccess = true;
        $missingCourses = [];

        if ($user) {
            $missingCourses = $course->dependencies->filter(function ($dep) use ($user) {
                return !$user->isCourseCompleted($dep->depends_on_course_id);
            })->map(fn ($d) => $d->requiredCourse)->toArray();

            if (!empty($missingCourses)) {
                $canAccess = false;
            }
        }

        return view('public.courses.show', compact('course', 'canAccess', 'missingCourses'));
    }

    /**
     * Module detail page (lessons + projects).
     */
    public function moduleShow($module): View
    {
        // Resolve by slug
        if (is_string($module)) {
            $module = Module::where('slug', $module)->firstOrFail();
        }
        $module->load(['course', 'lessons', 'projects']);

        $user = auth()->user();
        $canAccess = true;
        $missingCourses = [];

        if ($user) {
            // Check if user has completed all dependencies of the course this module belongs to
            $course = $module->course;
            $missingCourses = $course->dependencies->filter(function ($dep) use ($user) {
                return !$user->isCourseCompleted($dep->depends_on_course_id);
            })->map(fn ($d) => $d->requiredCourse)->toArray();

            if (!empty($missingCourses)) {
                $canAccess = false;
            }
        }

        return view('public.courses.module.show', compact('module', 'canAccess', 'missingCourses'));
    }

    /**
     * Lesson detail page.
     */
    public function lessonShow($lesson): View
    {
        // Resolve by slug
        if (is_string($lesson)) {
            $lesson = Lesson::where('slug', $lesson)->firstOrFail();
        }
        $lesson->load(['module.course']);

        return view('public.courses.module.lesson.show', compact('lesson'));
    }

    /**
     * Subscribe to a project (pet-project) in a module.
     */
    public function subscribe(Module $module, int $projectId): RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $project = \App\Models\Admin\Project::where('id', $projectId)
            ->where('module_id', $module->id)
            ->firstOrFail();

        return app(\App\Http\Controllers\SubscriptionController::class, ['project' => $project]);
    }
}
