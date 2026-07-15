@extends('public.layouts.app')

@section('title', 'Политика конфиденциальности')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <h1 class="text-3xl font-bold mb-8">Политика конфиденциальности</h1>

    <div class="prose max-w-none">
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            Мы серьёзно относимся к защите ваших персональных данных. Настоящая политика описывает, какие данные мы собираем и как их используем.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">1. Собираемые данные</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Мы собираем: имя пользователя, email, данные об обучении (проекты, оценки, XP), информацию о входах в систему.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">2. Использование данных</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Данные используются для предоставления образовательных услуг, отслеживания прогресса, начисления XP и улучшения платформы.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">3. Интеграция с GitLab</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Для работы платформы используется интеграция с GitLab. Данные репозиториев хранятся на серверах GitLab.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">4. Защита данных</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Мы используем стандартные методы защиты: шифрование паролей, двухфакторную аутентификацию, защищённые соединения.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">5. Ваши права</h2>
        <p class="text-gray-600 dark:text-gray-300">
            Вы можете запросить доступ к своим данным, их исправление или удаление, обратившись к администрации платформы.
        </p>
    </div>
</div>
@endsection
