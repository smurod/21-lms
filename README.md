# 21 LMS — Learning Management System for School 21

[![PHP](https://img.shields.io/badge/PHP-8.3-blue)](https://php.net)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-red)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)

LMS для School 21 с геймификацией, пировым ревью, автотестами и интеграцией GitLab.

## Стек

| Слой | Технологии |
|------|-----------|
| Backend | PHP 8.3, Laravel 13, Spatie Laravel Permission |
| Auth | Laravel Jetstream + Fortify (2FA, API tokens) |
| Frontend | Blade templates, Tailwind CSS 3.x, Livewire 3.x |
| Build | Vite 8.x, PostCSS |
| Database | PostgreSQL |
| External | GitLab API (авто-репозитории для проектов) |
| Testing | PHPUnit 12.x |

## Функциональность

- **Проекты** — CRUD проектов с авто-созданием GitLab репозитория
- **Субмиссии** — подача решений, трекинг статусов (pending → tested → reviewed → passed/failed)
- **Пир-ревью** — система взаимной проверки с чек-листами и рейтингом
- **XP / Геймификация** — уровни (1–6), XP транзакции с аудитом, прогресс от beginner → grandmaster
- **Доступ по уровням** — проекты требуют минимальный уровень студента
- **Админ-панель** — управление проектами и пользователями, дашборд со статистикой
- **Аутентификация** — регистрация/логин, 2FA, social login (Jetstream), API tokens
- **Публичные страницы** — каталог проектов, карточка проекта со статусом

## Домены

```
projects     → курсы, модули, задачи
submissions  → подача решений, статусы, попытки
reviews      → пир-ревью, чек-листы, рейтинги проверяющих
xp           → XP транзакции, уровни, ачивки
gitlab       → интеграция с GitLab API (репозитории, мерджи)
analytics    → learning paths, leaderboard, user stats, plagiarism
```

## Установка

```bash
# 1. Клонировать
git clone <repo-url> && cd 21-lms

# 2. Зависимости
composer install
npm install

# 3. Конфигурация
cp .env.example .env
php artisan key:generate

# 4. База данных (в .env настройте PostgreSQL)
php artisan migrate --seed

# 5. Frontend
npm run build

# 6. Хранение (symlink)
php artisan storage:link
```

### Seeders

| Seeder | Назначение |
|--------|-----------|
| `RolesAndPermissionsSeeder` | Роли admin/user + права |
| `ProjectSeeder` | Демо-проекты |

## Запуск

```bash
# Dev-сервер + queue + logs + vite (concurrently)
composer dev

# Или по отдельности
php artisan serve        # http://localhost:8000
php artisan queue:work   # background jobs
php artisan pail         # logs
npm run dev              # HMR
```

## Маршруты

| Префикс | Middleware | Назначение |
|---------|-----------|-----------|
| `/` | — | Публичный ленд |
| `/projects` | — | Публичный каталог проектов |
| `/dashboard` | auth, verified | Редирект по ролям |
| `/admin/*` | auth, verified, role:admin | Админ-панель |
| `/api/*` | auth:sanctum | API |

## Конфигурация

```env
# GitLab интеграция
GITLAB_URL=https://gitlab.com
GITLAB_TOKEN=your-personal-access-token
GITLAB_ADMIN_TOKEN=your-admin-token
GITLAB_VISIBILITY=public
GITLAB_CACHE_TTL=300

# XP система (config/xp.php)

## Personal Access Token (PAT) для GitLab

### Создание PAT для admin-аккаунта

1. Войдите в GitLab как admin
2. Profile Settings → Access Tokens
3. Создайте токен с scopes:
   - `api` — полный доступ к API
   - `read_api` — чтение API
   - `read_repository` — чтение репозиториев
   - `write_repository` — запись в репозитории
4. Скопируйте токен и сохраните в `.env` как `GITLAB_TOKEN`

### Admin Token vs Regular Token

- `GITLAB_TOKEN` — для обычных операций (чтение/создание репозиториев)
- `GITLAB_ADMIN_TOKEN` — для админ-операций (управление пользователями, группами)
- На self-hosted GitLab admin может создавать PAT с любыми scopes
- На gitlab.com PAT admin-аккаунта работает как обычный PAT (не имеет повышенных прав)
- Для instance-level admin API нужен `instance_admin` scope

### Rotation

- Регулярно меняйте токены (каждые 90 дней)
- При компрометации — немедленно отзовите токен
- Используйте Project Access Token для проектов вместо Personal
# Уровни: beginner(0) → intermediate(500) → advanced(1500) → expert(3000) → master(5000) → grandmaster(8000)
```

## Структура

```
app/
├── Actions/Fortify/        # Fortify actions (2FA, password)
├── Actions/Jetstream/      # Jetstream actions (delete user)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Admin CRUD
│   │   ├── Public/         # Публичные страницы
│   │   └── SubscriptionController.php
│   └── Middleware/CheckRole.php
├── Models/
│   ├── Admin/
│   │   ├── Project.php
│   │   ├── Submission.php
│   │   └── Review.php
│   ├── User.php
│   └── XpTransaction.php
├── Providers/
│   ├── AppServiceProvider.php
│   ├── FortifyServiceProvider.php
│   └── JetstreamServiceProvider.php
└── Services/
    ├── GitlabService.php   # GitLab API client
    └── XpService.php       # XP начисление, расчёт уровней

database/
├── migrations/             # 41 миграция
└── seeders/

resources/
├── views/
│   ├── admin/              # Админ-панель
│   ├── public/             # Публичные страницы
│   ├── auth/               # Jetstream auth views
│   ├── profile/            # Профиль пользователя
│   └── components/         # Blade компоненты
├── css/app.css
└── js/app.js

routes/
├── web.php                 # Маршруты
├── api.php                 # API маршруты
└── console.php
```

## Тесты

```bash
php artisan test
vendor/bin/phpunit --coverage-text
```

> **Примечание:** в репозитории сейчас только Jetstream boilerplate-тесты. Доменные тесты предстоит добавить.

## Известные ограничения

- `User::$with = ['roles']` загружает роли всегда, даже когда не нужны
- `ilike` оператор зависит от PostgreSQL (не кросс-дб)
- `resources/js/app.js` пустой — JS-логика отсутствует
- README — стандартный шаблон Laravel, требует кастомизации

## Архитектурные TODO

- [ ] Вынести бизнес-логику из `PublicProjectController` в service layer
- [ ] Создать DTO / FormRequest для валидации
- [ ] Добавить статус-enum для submissions
- [ ] Вынести XP level логику из User в XpService
- [ ] Написать доменные тесты
