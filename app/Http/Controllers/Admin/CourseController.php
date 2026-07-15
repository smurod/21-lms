<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseDependency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Course::query()->withCount(['modules', 'projects'])
            ->orderBy('order_position');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(slug) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        if ($status = $request->input('status')) {
            match ($status) {
                'published' => $query->where('is_published', true),
                'draft' => $query->where('is_published', false),
                default => null,
            };
        }

        $courses = $query->paginate(15);

        return view('admin.courses.index', compact('courses'));
    }

    public function create(): View
    {
        $allCourses = Course::orderBy('order_position')->get();
        return view('admin.courses.create', compact('allCourses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:courses,slug',
            'description' => 'required|string',
            'learning_objectives' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'difficulty' => 'required|in:beginner,intermediate,advanced,expert',
            'estimated_hours' => 'nullable|integer|min:1',
            'order_position' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_mandatory' => 'nullable|boolean',
            'dependent_course_ids' => 'nullable|array',
            'dependent_course_ids.*' => 'exists:courses,id',
        ]);

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('courses', 'public');
            $data['cover_image'] = $path;
        }

        $course = Course::create([
            ...$data,
            'is_published' => $request->has('is_published'),
            'is_featured' => $request->has('is_featured'),
            'is_mandatory' => $request->has('is_mandatory'),
            'created_by' => auth()->id(),
        ]);

        // Save dependencies
        if (!empty($data['dependent_course_ids'])) {
            foreach ($data['dependent_course_ids'] as $index => $depId) {
                CourseDependency::create([
                    'course_id' => $course->id,
                    'depends_on_course_id' => $depId,
                    'order_position' => $index,
                ]);
            }
        }

        return redirect()->route('admin.courses.index')
            ->with('success', "Course '{$course->title}' created.");
    }

    public function edit(Course $course): View
    {
        $allCourses = Course::where('id', '!=', $course->id)
            ->orderBy('order_position')
            ->get();
        $currentDependencies = $course->dependencies()->pluck('depends_on_course_id')->toArray();
        return view('admin.courses.edit', compact('course', 'allCourses', 'currentDependencies'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:courses,slug,' . $course->id,
            'description' => 'required|string',
            'learning_objectives' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'difficulty' => 'required|in:beginner,intermediate,advanced,expert',
            'estimated_hours' => 'nullable|integer|min:1',
            'order_position' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_mandatory' => 'nullable|boolean',
            'dependent_course_ids' => 'nullable|array',
            'dependent_course_ids.*' => 'exists:courses,id',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($course->cover_image) {
                storage_disk('public')->delete($course->cover_image);
            }
            $path = $request->file('cover_image')->store('courses', 'public');
            $data['cover_image'] = $path;
        }

        $course->update([
            ...$data,
            'is_published' => $request->has('is_published'),
            'is_featured' => $request->has('is_featured'),
            'is_mandatory' => $request->has('is_mandatory'),
        ]);

        // Re-sync dependencies
        CourseDependency::where('course_id', $course->id)->delete();
        if (!empty($data['dependent_course_ids'])) {
            foreach ($data['dependent_course_ids'] as $index => $depId) {
                CourseDependency::create([
                    'course_id' => $course->id,
                    'depends_on_course_id' => $depId,
                    'order_position' => $index,
                ]);
            }
        }

        return redirect()->route('admin.courses.index')
            ->with('success', "Course '{$course->title}' updated.");
    }

    public function destroy(Course $course): RedirectResponse
    {
        $name = $course->title;
        $course->delete();
        return redirect()->route('admin.courses.index')
            ->with('success', "Course '{$name}' deleted.");
    }
}
