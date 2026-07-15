@extends('public.layouts.app')

@section('title', $lesson->title)

@section('content')
<div class="space-y-12">

    <div class="pt-16 pb-8">
        <a href="{{ route('public.courses.module', [$lesson->module->course, $lesson->module]) }}" class="text-brand-600 dark:text-brand-400 hover:underline text-sm inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span class="hover:text-brand-700 dark:hover:text-brand-300">{{ $lesson->module->title }}</span>
        </a>
        <div class="flex items-center gap-2 mt-2">
            <span class="text-gray-400 dark:text-dark-500">/</span>
            <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-gray-100">{{ $lesson->title }}</h1>
            @if(!$lesson->is_published && auth()->guest())
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300">🔒</span>
            @endif
        </div>
        <p class="text-sm text-gray-500 dark:text-dark-400 mt-1">
            {{ $lesson->module->course->title }}
        </p>
    </div>

    <!-- Lesson Content -->
    <div class="max-w-4xl mx-auto">
        @if($lesson->is_free || $lesson->is_published)
        <div class="prose dark:prose-invert max-w-none bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-8">
            @if($lesson->content_type === 'video' && $lesson->video_url)
            <div class="mb-6">
                <iframe src="{{ $lesson->video_url }}" class="w-full rounded-xl aspect-video" frameborder="0" allowfullscreen></iframe>
            </div>
            @endif

            @if($lesson->content)
            <div class="text-gray-800 dark:text-dark-200">
                {{ nl2br(e($lesson->content)) }}
            </div>
            @else
            <p class="text-gray-500 dark:text-dark-400 italic">Контент урока загружается...</p>
            @endif
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('public.courses.module', [$lesson->module->course, $lesson->module]) }}"
               class="text-brand-600 dark:text-brand-400 hover:underline">
                ← К списку уроков
            </a>
            @if(auth()->check())
            <a href="#"
               class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                Пройти урок
            </a>
            @endif
        </div>
        @else
        <div class="bg-gray-50 dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-8 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-dark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <p class="text-gray-600 dark:text-dark-400 font-medium">Этот урок доступен только после подписки на курс</p>
            <a href="{{ route('public.courses.show', $lesson->module->course) }}"
               class="mt-4 inline-block px-4 py-2 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 transition-colors">
                Перейти к курсу
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
