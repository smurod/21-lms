<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function index(Module $module): View
    {
        $course = $module->course;
        $lessons = $module->lessons->load('module');
        return view('admin.lessons.index', compact('module', 'course', 'lessons'));
    }

    public function create(Module $module): View
    {
        return view('admin.lessons.create', compact('module'));
    }

    public function store(Request $request, Module $module): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => "required|string|max:255|unique:lessons,slug,{$module->id}_id",
            'content' => 'required|string',
            'content_type' => 'required|in:text,video,interactive',
            'video_url' => 'nullable|url|max:512',
            'estimated_minutes' => 'nullable|integer|min:1',
            'order_position' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'is_free' => 'nullable|boolean',
        ]);

        Lesson::create([
            ...$data,
            'module_id' => $module->id,
            'is_published' => $request->has('is_published'),
            'is_free' => $request->has('is_free'),
        ]);

        return redirect()->route('admin.modules.lessons.index', $module)
            ->with('success', "Lesson '{$data['title']}' created for module '{$module->title}'.");
    }

    public function edit(Module $module, Lesson $lesson): View
    {
        return view('admin.lessons.edit', compact('module', 'lesson'));
    }

    public function update(Request $request, Module $module, Lesson $lesson): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => "required|string|max:255|unique:lessons,slug,{$lesson->id},${module->id}_id",
            'content' => 'required|string',
            'content_type' => 'required|in:text,video,interactive',
            'video_url' => 'nullable|url|max:512',
            'estimated_minutes' => 'nullable|integer|min:1',
            'order_position' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'is_free' => 'nullable|boolean',
        ]);

        $lesson->update([
            ...$data,
            'is_published' => $request->has('is_published'),
            'is_free' => $request->has('is_free'),
        ]);

        return redirect()->route('admin.modules.lessons.index', $module)
            ->with('success', "Lesson '{$lesson->title}' updated.");
    }

    public function destroy(Module $module, Lesson $lesson): RedirectResponse
    {
        $name = $lesson->title;
        $lesson->delete();

        return redirect()->route('admin.modules.lessons.index', $module)
            ->with('success', "Lesson '{$name}' deleted.");
    }
}
