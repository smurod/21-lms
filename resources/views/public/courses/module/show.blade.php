@extends('public.layouts.app')

@section('title', $module->title)

@section('content')
<div class="space-y-12">

    <div class="pt-16 pb-8">
        <a href="{{ route('public.courses.show', $module->course) }}" class="text-brand-600 dark:text-brand-400 hover:underline text-sm inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span class="hover:text-brand-700 dark:hover:text-brand-300">{{ $module->course->title }}</span>
        </a>
        <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-gray-100 mt-2">{{ $module->title }}</h1>
        @if($module->description)
        <p class="text-gray-600 dark:text-dark-300 mt-2">{{ $module->description }}</p>
        @endif
    </div>

    <!-- Access Check -->
    @if(!auth()->check())
    <div class="max-w-5xl mx-auto bg-brand-50 dark:bg-brand-900/10 border border-brand-200 dark:border-brand-800 rounded-xl p-6 text-center">
        <p class="text-brand-700 dark:text-brand-300 font-medium">Для доступа к модулю необходимо <a href="{{ route('login') }}" class="underline">войти</a></p>
    </div>
    @elseif(!$canAccess)
    <div class="max-w-5xl mx-auto bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-800 rounded-xl p-6">
        <p class="text-orange-700 dark:text-orange-400">Необходимо завершить:
            @foreach($missingCourses as $missing)
            <a href="{{ route('public.courses.show', $missing) }}" class="underline hover:text-orange-600">{{ $missing->title }}</a>
            @endforeach
        </p>
    </div>
    @endif

    <!-- Lessons -->
    <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Уроки</h2>
        <div class="space-y-3">
            @forelse($module->lessons as $lesson)
            <a href="{{ route('public.courses.lesson', [$module->course, $module, $lesson]) }}"
               class="block p-4 rounded-xl border border-gray-200 dark:border-dark-700 hover:bg-gray-50 dark:hover:bg-dark-850 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-brand-100 dark:bg-brand-900/20 flex items-center justify-center text-brand-600 dark:text-brand-400 font-bold">
                        {{ $loop->iteration }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $lesson->title }}</span>
                            @if($lesson->is_free)
                            <span class="px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300">Free</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-dark-400">
                            {{ ucfirst($lesson->content_type) }} · {{ $lesson->estimated_minutes }} мин.
                        </p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
            @empty
            <div class="text-center py-8 text-gray-500 dark:text-dark-400">
                <p>Уроки в этом модуле пока не добавлены</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Projects -->
    <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Pet-проекты</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($module->projects as $project)
            <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all">
                <div class="p-5">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100">{{ $project->title }}</h3>
                    <p class="text-sm text-gray-500 dark:text-dark-400 mt-1 line-clamp-2">{{ Str::limit($project->description, 80) }}</p>
                    <div class="flex items-center gap-3 mt-3 text-sm text-gray-500 dark:text-dark-400">
                        <span class="px-2 py-0.5 rounded text-xs bg-brand-100 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300">
                            {{ $project->difficulty }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-xs bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300">
                            {{ $project->xp_reward ?? 0 }} XP
                        </span>
                    </div>
                    <a href="{{ route('public.courses.show', $module->course) }}#project-{{ $project->id }}"
                       class="mt-4 w-full text-center block px-3 py-2 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 transition-colors">
                        Подробнее
                    </a>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-8 text-gray-500 dark:text-dark-400">
                <p>Проекты в этом модуле пока не добавлены</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
