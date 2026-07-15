@extends('public.layouts.app')

@section('title', 'Courses — School 21')

@section('content')
    @php
        $courses = [
            ['cat'=>'Software development','code'=>'sc04','title'=>'Знакомство с Git','desc'=>'Основные понятия системы контроля версий Git'],
            ['cat'=>'System administration','code'=>'sc05','title'=>'Знакомство с ОС Linux','desc'=>'Краткое описание основных понятий ОС Linux'],
            ['cat'=>'System administration','code'=>'sc06','title'=>'Знакомство с bash','desc'=>'Основные команды bash'],
            ['cat'=>'Software development','code'=>'sc07','title'=>'Обзор GitLab','desc'=>'Системы управления репозиториями. Возможности GitLab'],
            ['cat'=>'General','code'=>'sc08','title'=>'Проверка проектов','desc'=>'Проверка проектов в Школе 21'],
            ['cat'=>'Software development','code'=>'sc09','title'=>'Введение в структурное программирование','desc'=>'Введение в структурное программирование'],
            ['cat'=>'C','code'=>'sc10','title'=>'Погружение в историю языка C','desc'=>'История развития языка программирования C'],
            ['cat'=>'C','code'=>'sc11','title'=>'Первая программа на языке C','desc'=>'Написание первой программы на C'],
            ['cat'=>'C','code'=>'sc12','title'=>'Ввод-вывод в программах на C','desc'=>'Работа с вводом и выводом данных в C'],
        ];
    @endphp

    <main class="main-content courses-page">
        <div class="courses-header">
            <h1>All courses</h1>
        </div>

        <div class="courses-grid">
            @foreach($courses as $course)
                <a href="{{ route('public.courses.show', $course['code']) }}" class="course-card">
                    <div class="course-visual">
                        <div class="course-pattern">
                            <div class="pattern-blocks"></div>
                        </div>
                        <div class="course-code">{{ $course['code'] }}</div>
                        <div class="course-title-overlay">{{ $course['title'] }}</div>
                    </div>
                    <div class="course-info">
                        <span class="course-cat">{{ $course['cat'] }}</span>
                        <h4 class="course-list-title">{{ $course['code'] }}. {{ $course['title'] }}</h4>
                        <p class="course-desc">{{ $course['desc'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </main>
@endsection
