@extends('public.layouts.app')

@section('title', ($course->title ?? 'Course Detail') . ' — School 21')

@section('content')
    @php
        $course = $course ?? null;
        $title = $course->title ?? 'sc04. Знакомство с Git';
        $category = $course->category ?? 'Software development';
        $description = $course->description ?? 'Основные понятия системы контроля версий Git. Вы изучите базовые команды git, научитесь работать с репозиториями, ветками и коммитами.';
        $modules = $modules ?? collect([
            (object)['id'=>1,'title'=>'Введение в Git','order'=>1,'lessons'=>collect([
                (object)['id'=>1,'title'=>'Что такое Git?','order'=>1,'duration'=>'15 min'],
                (object)['id'=>2,'title'=>'Установка Git','order'=>2,'duration'=>'10 min'],
                (object)['id'=>3,'title'=>'Первый репозиторий','order'=>3,'duration'=>'20 min'],
            ])],
            (object)['id'=>2,'title'=>'Базовые команды','order'=>2,'lessons'=>collect([
                (object)['id'=>4,'title'=>'git init и git status','order'=>1,'duration'=>'15 min'],
                (object)['id'=>5,'title'=>'git add и git commit','order'=>2,'duration'=>'20 min'],
                (object)['id'=>6,'title'=>'git log и git diff','order'=>3,'duration'=>'15 min'],
            ])],
        ]);
    @endphp

    <main class="main-content course-detail-page">
        <div class="course-header-block">
            <span class="course-cat">{{ $category }}</span>
            <h1>{{ $title }}</h1>
            <p>{{ $description }}</p>
        </div>

        <div class="modules-list">
            @foreach($modules as $module)
                <div class="module-accordion">
                    <div class="module-header">
                        <div class="module-order">{{ $module->order }}</div>
                        <h3>{{ $module->title }}</h3>
                        <span class="module-count">{{ $module->lessons->count() }} lessons</span>
                    </div>
                    <div class="module-lessons">
                        @foreach($module->lessons as $lesson)
                            <a href="{{ route('public.courses.lesson', [$course->slug ?? 'sc04', $module->id, $lesson->id]) }}" class="lesson-row">
                                <span class="lesson-order">{{ $lesson->order }}</span>
                                <span class="lesson-title">{{ $lesson->title }}</span>
                                <span class="lesson-duration">{{ $lesson->duration }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </main>
@endsection
