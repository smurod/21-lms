@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'Условия использования')

@section('content')
    @include('public.components.notifications-overlay')
    @include('public.components.user-menu-overlay')
    @include('public.components.activities-overlay')
    @include('public.components.projects-overlay')

    <main class="public-doc-page">
        <section class="public-doc-hero">
            <div>
                <p class="public-kicker">Legal</p>
                <h1>Условия использования</h1>
                <p>Правила работы с платформой 21-LMS, проектами, GitLab и peer-review.</p>
            </div>
        </section>

        <article class="public-doc-card">
            <p>
                Добро пожаловать в 21 LMS. Используя платформу, вы соглашаетесь с настоящими условиями.
            </p>

            <h2>1. Общие положения</h2>
            <p>
                Платформа 21 LMS предназначена для обучения студентов School 21. Доступ к материалам предоставляется на основе пройденных курсов и достигнутого уровня.
            </p>

            <h2>2. Использование платформы</h2>
            <p>
                Студенты могут проходить проекты, подавать решения, проходить пир-ревью и накапливать XP для повышения уровня.
            </p>

            <h2>3. Интеграция с GitLab</h2>
            <p>
                Для каждого проекта автоматически создаётся репозиторий в GitLab. Студенты работают в своих ветках и создают pull request'ы для ревью.
            </p>

            <h2>4. Правила поведения</h2>
            <p>
                Запрещено: плагиат, несанкционированный доступ к чужим решениям, обход системы проверки.
            </p>

            <h2>5. Изменения условий</h2>
            <p>
                Администрация оставляет за собой право изменять условия использования. Продолжение использования платформы означает согласие с изменениями.
            </p>
        </article>
    </main>
@endsection
