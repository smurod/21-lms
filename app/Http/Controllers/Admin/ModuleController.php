<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(Course $course): View
    {
        $modules = $course->modules()->orderBy('order_position')->get();
        return view('admin.modules.index', compact('course', 'modules'));
    }

    public function create(Course $course): View
    {
        return view('admin.modules.create', compact('course'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => "required|string|max:255|unique:modules,slug,0,course_id,{$course->id}",
            'description' => 'nullable|string',
            'order_position' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ]);

        Module::create([
            ...$data,
            'course_id' => $course->id,
            'is_published' => $request->has('is_published'),
        ]);

        return redirect()->route('admin.courses.modules.index', $course)
            ->with('success', "Module '{$data['title']}' created for course '{$course->title}'.");
    }

    public function edit(Course $course, Module $module): View
    {
        return view('admin.modules.edit', compact('course', 'module'));
    }

    public function update(Request $request, Course $course, Module $module): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => "required|string|max:255|unique:modules,slug,{$module->id},${course->id}_id",
            'description' => 'nullable|string',
            'order_position' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ]);

        $module->update([
            ...$data,
            'is_published' => $request->has('is_published'),
        ]);

        return redirect()->route('admin.courses.modules.index', $course)
            ->with('success', "Module '{$module->title}' updated.");
    }

    public function destroy(Course $course, Module $module): RedirectResponse
    {
        $name = $module->title;
        $module->delete();

        return redirect()->route('admin.courses.modules.index', $course)
            ->with('success', "Module '{$name}' deleted.");
    }
}
