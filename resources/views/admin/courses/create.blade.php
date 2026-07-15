@extends('admin.layouts.app')

@section('title', 'Create Course')

@section('content')
    <div class="max-w-4xl space-y-8 anim-up" style="animation-delay: 0.1s">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.courses.index') }}" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition-all duration-300">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-white">New Course</h1>
                <p class="text-zinc-400 mt-1 font-medium text-sm">Создать новый учебный курс</p>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data" class="bg-zinc-900 rounded-3xl border border-white/5 divide-y divide-white/5 shadow-xl shadow-black/20">

            <!-- Basic Info -->
            <div class="p-8 space-y-6">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-1 h-5 bg-cyan-400 rounded-full"></div>
                    <h2 class="text-lg font-semibold text-white tracking-wide">Basic Info</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Title</label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                               class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300"
                               placeholder="Название курса">
                        @error('title')<p class="text-xs text-rose-400 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Slug</label>
                        <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required
                               class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono"
                               placeholder="course-slug">
                        <p class="text-[11px] text-zinc-500 font-mono">url.example.com/courses/{slug}</p>
                        @error('slug')<p class="text-xs text-rose-400 font-medium">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Description</label>
                    <textarea name="description" rows="3" required
                              class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 resize-none"
                              placeholder="Описание курса">{{ old('description') }}</textarea>
                    @error('description')<p class="text-xs text-rose-400 font-medium">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Learning Objectives</label>
                    <textarea name="learning_objectives" rows="4"
                              class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 resize-none"
                              placeholder="Чему научат студенты (каждая цель с новой строки)">{{ old('learning_objectives') }}</textarea>
                </div>
            </div>

            <!-- Settings -->
            <div class="p-8 space-y-6">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-1 h-5 bg-violet-400 rounded-full"></div>
                    <h2 class="text-lg font-semibold text-white tracking-wide">Settings</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Difficulty</label>
                        <div class="relative">
                            <select name="difficulty" required class="appearance-none w-full bg-zinc-950 border border-white/10 focus:border-violet-500/50 focus:ring-1 focus:ring-violet-500/50 px-4 py-3 pr-10 rounded-xl text-sm text-white outline-none transition-all duration-300 cursor-pointer">
                                <option value="beginner" {{ old('difficulty') === 'beginner' ? 'selected' : '' }} class="bg-zinc-900">Beginner</option>
                                <option value="intermediate" {{ old('difficulty') === 'intermediate' ? 'selected' : '' }} class="bg-zinc-900">Intermediate</option>
                                <option value="advanced" {{ old('difficulty') === 'advanced' ? 'selected' : '' }} class="bg-zinc-900">Advanced</option>
                                <option value="expert" {{ old('difficulty') === 'expert' ? 'selected' : '' }} class="bg-zinc-900">Expert</option>
                            </select>
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-zinc-500">
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Estimated Hours</label>
                        <input type="number" name="estimated_hours" value="{{ old('estimated_hours') }}" min="1"
                               class="w-full bg-zinc-950 border border-white/10 focus:border-violet-500/50 focus:ring-1 focus:ring-violet-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Order Position</label>
                        <input type="number" name="order_position" value="{{ old('order_position', 0) }}" min="0"
                               class="w-full bg-zinc-950 border border-white/10 focus:border-violet-500/50 focus:ring-1 focus:ring-violet-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Cover Image</label>
                    <div class="relative">
                        <input type="file" name="cover_image" accept="image/*"
                               class="w-full text-sm text-zinc-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-white/5 file:text-white hover:file:bg-white/10 transition-all cursor-pointer bg-zinc-950 border border-white/10 rounded-xl px-3 py-3">
                    </div>
                </div>

                <!-- Toggles (Custom UI Switches) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4">
                    <label class="flex items-center gap-4 p-4 rounded-2xl border border-white/5 bg-zinc-950/50 hover:bg-white/5 transition-colors cursor-pointer group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }} class="peer sr-only">
                            <div class="w-10 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </div>
                        <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Published</span>
                    </label>

                    <label class="flex items-center gap-4 p-4 rounded-2xl border border-white/5 bg-zinc-950/50 hover:bg-white/5 transition-colors cursor-pointer group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="peer sr-only">
                            <div class="w-10 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                        </div>
                        <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Featured</span>
                    </label>

                    <label class="flex items-center gap-4 p-4 rounded-2xl border border-white/5 bg-zinc-950/50 hover:bg-white/5 transition-colors cursor-pointer group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="is_mandatory" value="1" {{ old('is_mandatory') ? 'checked' : '' }} class="peer sr-only">
                            <div class="w-10 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-500"></div>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Mandatory</span>
                            <span class="text-[10px] text-zinc-500">Обязательный курс</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Dependencies -->
            <div class="p-8 space-y-6">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-1 h-5 bg-amber-400 rounded-full"></div>
                    <h2 class="text-lg font-semibold text-white tracking-wide">Dependencies</h2>
                </div>
                <p class="text-sm text-zinc-400 font-medium">Выберите курсы, которые студент должен завершить до начала этого курса.</p>

                @if(!isset($allCourses) || $allCourses->isEmpty())
                    <div class="p-6 rounded-2xl border border-dashed border-white/10 text-center">
                        <p class="text-sm text-zinc-500">Нет доступных курсов для зависимостей.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($allCourses as $otherCourse)
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-white/5 bg-zinc-950/50 hover:bg-white/5 hover:border-white/10 transition-all cursor-pointer group">
                                <input type="checkbox" name="dependent_course_ids[]" value="{{ $otherCourse->id }}"
                                       {{ in_array($otherCourse->id, old('dependent_course_ids', [])) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-white/10 bg-zinc-900 text-cyan-500 focus:ring-cyan-500/50 focus:ring-offset-zinc-900">
                                <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">{{ $otherCourse->title }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="p-8 flex items-center gap-4 justify-end bg-zinc-950/30 rounded-b-3xl">
                <a href="{{ route('admin.courses.index') }}" class="px-6 py-3 text-sm font-semibold text-zinc-400 hover:text-white transition-colors">
                    Отмена
                </a>
                <button type="submit" class="flex items-center gap-2 px-8 py-3 text-sm font-semibold text-black bg-white rounded-2xl hover:bg-white/90 transition-all active:scale-95 shadow-lg shadow-white/10">
                    <i data-lucide="check" class="w-4 h-4"></i> Создать курс
                </button>
            </div>
        </form>
    </div>
@endsection
