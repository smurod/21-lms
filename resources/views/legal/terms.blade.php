@extends('public.layouts.app')

@section('title', 'Условия использования')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <h1 class="text-3xl font-bold mb-8">Условия использования</h1>

    <div class="prose max-w-none">
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            Добро пожаловать в 21 LMS. Используя платформу, вы соглашаетесь с настоящими условиями.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">1. Общие положения</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Платформа 21 LMS предназначена для обучения студентов School 21. Доступ к материалам предоставляется на основе пройденных курсов и достигнутого уровня.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">2. Использование платформы</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Студенты могут проходить проекты, подавать решения, проходить пир-ревью и накапливать XP для повышения уровня.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">3. Интеграция с GitLab</h3>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Для каждого проекта автоматически создаётся репозиторий в GitLab. Студенты работают в своих ветках и создают pull request'ы для ревью.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">4. Правила поведения</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Запрещено: плагиат, несанкционированный доступ к чужим решениям, обход системы проверки.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">5. Изменения условий</h2>
        <p class="text-gray-600 dark:text-gray-300">
            Администрация оставляет за собой право изменять условия использования. Продолжение использования платформы означает согласие с изменениями.
        </p>
    </div>
</div>
@endsection
