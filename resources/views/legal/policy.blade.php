@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'Политика конфиденциальности')

@section('content')
    @include('public.components.notifications-overlay')
    @include('public.components.user-menu-overlay')
    @include('public.components.activities-overlay')
    @include('public.components.projects-overlay')

    <main class="public-doc-page">
        <section class="public-doc-hero">
            <div>
                <p class="public-kicker">Privacy</p>
                <h1>Политика конфиденциальности</h1>
                <p>Как 21-LMS хранит, использует и защищает данные пользователей.</p>
            </div>
        </section>

        <article class="public-doc-card">
            <p>
                Мы серьёзно относимся к защите ваших персональных данных. Настоящая политика описывает, какие данные мы собираем и как их используем.
            </p>

            <h2>1. Собираемые данные</h2>
            <p>
                Мы собираем: имя пользователя, email, данные об обучении (проекты, оценки, XP), информацию о входах в систему.
            </p>

            <h2>2. Использование данных</h2>
            <p>
                Данные используются для предоставления образовательных услуг, отслеживания прогресса, начисления XP и улучшения платформы.
            </p>

            <h2>3. Интеграция с GitLab</h2>
            <p>
                Для работы платформы используется интеграция с GitLab. Данные репозиториев хранятся на серверах GitLab.
            </p>

            <h2>4. Защита данных</h2>
            <p>
                Мы используем стандартные методы защиты: шифрование паролей, двухфакторную аутентификацию, защищённые соединения.
            </p>

            <h2>5. Ваши права</h2>
            <p>
                Вы можете запросить доступ к своим данным, их исправление или удаление, обратившись к администрации платформы.
            </p>
        </article>
    </main>
@endsection
