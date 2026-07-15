# PROJECT_STRUCTURE.md - Обновленная структура проекта (2026)

## 📋 Общая информация

**Проект:** Learning Management System (LMS)
**Фреймворк:** Laravel 10.x
**Язык:** PHP, Blade templating engine
**База данных:** MySQL/MariaDB
**Frontend:** React + Tailwind CSS v4
**Backend API:** RESTful API
**Статус:** Активная разработка

---

## 🏗️ Структура проекта

```
21-lms/
├── .env.example
├── .gitignore
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── package-lock.json
├── phpunit.xml
├── README.md
├── webpack.mix.js
└── PROJECT_STRUCTURE.md
```

### 📦 Корневые файлы и конфигурации

- **.env.example** — пример файла переменных окружения
- **.gitignore** — игнорируемые файлы (Node modules, vendor, etc.)
- **artisan** — CLI-инструмент Laravel (php artisan)
- **composer.json** — зависимости PHP (Laravel, пакеты)
- **composer.lock** — зафиксированные версии зависимостей
- **package.json** — зависимости Node.js (React, Tailwind CSS v4)
- **package-lock.json** — зафиксированные версии Node.js
- **phpunit.xml** — конфигурация тестов PHPUnit
- **webpack.mix.js** — конфигурация сборки CSS/JS
- **README.md** — документация проекта

---

## 📁 Основные директории

### `app/` — Приложение Laravel

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── CourseController.php          # Управление курсами (админ)
│   │   │   ├── DashboardController.php       # Панель администратора
│   │   │   ├── LessonController.php          # Управление уроками
│   │   │   ├── ModuleController.php         # Управление модулями
│   │   │   ├── ProjectController.php          # Управление проектами
│   │   │   └── UserController.php         # Управление пользователями
│   │   ├── Public/
│   │   │   ├── ActivitiesController.php        # Активности пользователей
│   │   │   ├── CalendarController.php         # Календарь событий
│   │   │   ├── CourseController.php          # Просмотр курсов (публичный)
│   │   │   ├── MainController.php             # Главная страница
│   │   │   ├── ProgressController.php         # Прогресс обучения
│   │   │   └── ProjectController.php          # Просмотр проектов (публичный)
│   │   ├── ChatController.php                  # Чат
│   │   ├── Controller.php                     # Базовый контроллер
│   │   ├── GamificationController.php          # Геймификация
│   │   ├── ReviewController.php               # Отзывы
│   │   └── SubscriptionController.php          # Подписки
│   ├── Middleware/
│   ├── Requests/
│   ├── Resources/
│   └── Providers/
│       ├── AppServiceProvider.php
│       ├── EventServiceProvider.php
│       └── RouteServiceProvider.php
├── Models/
│   ├── Achievement.php
│   ├── AchievementProgress.php
│   ├── Activity.php
│   ├── ActivityLog.php
│   ├── Chat.php
│   ├── ChatMessage.php
│   ├── Comment.php
│   ├── Course.php
│   ├── CourseComment.php
│   ├── CourseUser.php
│   ├── Enrollment.php
│   ├── EnrollmentType.php
│   ├── Lesson.php
│   ├── LessonComment.php
│   ├── LessonMaterial.php
│   ├── LessonResource.php
│   ├── Module.php
│   ├── Project.php
│   ├── ProjectComment.php
│   ├── ProjectResource.php
│   ├── Review.php
│   ├── Role.php
│   ├── Subscription.php
│   ├── SubscriptionHistory.php
│   ├── User.php
│   └── UserSubscription.php
├── Services/
│   ├── AchievementService.php
│   ├── BookingService.php
│   ├── ChatService.php
│   ├── CourseService.php
│   ├── GitlabService.php
│   ├── LessonService.php
│   ├── ModuleService.php
│   ├── ProjectService.php
│   ├── ProgressService.php
│   ├── UserService.php
│   └── UserService.php
├── Jobs/
│   └── CreateGitlabProjectJob.php
├── Policies/
├── Providers/
└── Traits/
```

### `database/` — База данных

```
database/
├── migrations/
│   ├── 2024_01_01_000000_create_users_table.php
│   ├── 2024_01_01_000001_create_courses_table.php
│   ├── 2024_01_01_000002_create_modules_table.php
│   ├── 2024_01_01_000003_create_lessons_table.php
│   ├── 2024_01_01_000004_create_activities_table.php
│   ├── 2024_01_01_000005_create_enrollments_table.php
│   ├── 2024_01_01_000006_create_achievement_progress_table.php
│   ├── 2024_01_01_000007_create_chat_messages_table.php
│   ├── 2024_01_01_000008_create_reviews_table.php
│   ├── 2024_01_01_000009_create_subscriptions_table.php
│   ├── 2024_01_01_000010_create_user_subscriptions_table.php
│   ├── 2024_01_01_000011_create_project_resources_table.php
│   ├── 2024_01_01_000012_create_project_comments_table.php
│   ├── 2024_01_01_000013_create_project_materials_table.php
│   ├── 2024_01_01_000014_create_activity_logs_table.php
│   ├── 2024_01_01_000015_create_enrollment_types_table.php
│   ├── 2024_01_01_000016_create_course_comments_table.php
│   ├── 2024_01_01_000017_create_lesson_materials_table.php
│   ├── 2024_01_01_000018_create_lesson_comments_table.php
│   ├── 2024_01_01_000019_create_lesson_resources_table.php
│   ├── 2024_01_01_000020_create_roles_table.php
│   ├── 2024_01_01_000021_create_subscription_history_table.php
│   ├── 2024_01_01_000022_create_project_materials_table.php
│   ├── 2024_01_01_000023_create_project_resources_table.php
│   ├── 2024_01_01_000024_create_chat_table.php
│   ├── 2024_01_01_000025_create_chat_messages_table.php
│   ├── 2024_01_01_000026_create_achievement_progress_table.php
│   ├── 2024_01_01_000027_create_achievement_progress_table.php
│   ├── 2024_01_01_000028_create_achievement_progress_table.php
│   ├── 2024_01_01_000029_create_achievement_progress_table.php
│   ├── 2024_01_01_000030_create_achievement_progress_table.php
│   ├── 2024_01_01_000031_create_achievement_progress_table.php
│   ├── 2024_01_01_000032_create_achievement_progress_table.php
│   ├── 2024_01_01_000033_create_achievement_progress_table.php
│   ├── 2024_01_01_000034_create_achievement_progress_table.php
│   ├── 2024_01_01_000035_create_achievement_progress_table.php
│   ├── 2024_01_01_000036_create_achievement_progress_table.php
│   ├── 2024_01_01_000037_create_achievement_progress_table.php
│   ├── 2024_01_01_000038_create_achievement_progress_table.php
│   ├── 2024_01_01_000039_create_achievement_progress_table.php
│   ├── 2024_01_01_000040_create_achievement_progress_table.php
│   └── 2024_01_01_000041_create_achievement_progress_table.php
├── seeders/
│   ├── DatabaseSeeder.php
│   ├── AchievementSeeder.php
│   ├── ActivitySeeder.php
│   ├── CourseSeeder.php
│   ├── EnrollmentTypeSeeder.php
│   ├── LessonSeeder.php
│   ├── ModuleSeeder.php
│   ├── ProjectSeeder.php
│   ├── ReviewSeeder.php
│   ├── RoleSeeder.php
│   ├── SubscriptionSeeder.php
│   └── UserSeeder.php
└── factories/
```

### `routes/` — Маршруты

```
routes/
├── api.php          # API маршруты (REST)
├── web.php          # Web маршруты (Blade)
└── channels.php      # Laravel Channels (для чата в реальном времени)
```

### `resources/views/` — Blade шаблоны

```
resources/views/
├── components/               # Переиспользуемые компоненты (31 файл)
├── admin/                    # Панель администратора (81 файл)
├── public/                    # Публичные страницы (81 файл)
├── profile/                   # Страницы профиля пользователя (18 файл)
├── legal/                      # Юридические документы (10 файл)
├── auth/                      # Страницы авторизации (20 файл)
└── layouts/                    # Основные шаблоны layouts/
```

### `public/` — Статические файлы

```
public/
├── css/
│   └── app.css                # Сборка Tailwind CSS
├── images/
│   └── (изображения проекта)
├── js/
│   └── app.js                  # React приложение
└── vendor/
    ├── (Laravel vendor)
    └── (npm packages)
```

---

## 📊 Статистика проекта

### Controllers: 17 файлов

#### 🎛️ Admin/ (6 контроллеров)
1. **CourseController.php** — Управление курсами (CRUD, модули, уроки)
2. **DashboardController.php** — Панель администратора с статистикой
3. **LessonController.php** — Управление уроками (материалы, ресурсы)
4. **ModuleController.php** — Управление модулями
5. **ProjectController.php** — Управление проектными курсами
6. **UserController.php** — Управление пользователями

#### 🌐 Public/ (6 контроллеров)
1. **ActivitiesController.php** — Активности пользователей (участие)
2. **CalendarController.php** — Календарь событий и занятий
3. **CourseController.php** — Просмотр курсов (публичный доступ)
4. **MainController.php** — Главная страница
5. **ProgressController.php** — Прогресс обучения и достижения
6. **ProjectController.php** — Просмотр проектов (публичный доступ)

#### 🔄 Root (5 контроллеров)
1. **ChatController.php** — Чат приложения
2. **Controller.php** — Базовый контроллер
3. **GamificationController.php** — Геймификация и достижения
4. **ReviewController.php** — Отзывы и рейтинги
5. **SubscriptionController.php** — Управление подписками

### Models: 23 файла

#### 📚 Основные модели
1. **Achievement.php** — Достижения
2. **AchievementProgress.php** — Прогресс достижений
3. **Activity.php** — Активность пользователя
4. **ActivityLog.php** — Логи активности
5. **Chat.php** — Чат
6. **ChatMessage.php** — Сообщения чата
7. **Comment.php** — Базовый класс комментария
8. **Course.php** — Курс
9. **CourseComment.php** — Комментарии к курсу
10. **CourseUser.php** — Связь user-course
11. **Enrollment.php** — Запись на курс
12. **EnrollmentType.php** — Типы записей
13. **Lesson.php** — Урок
14. **LessonComment.php** — Комментарии к уроку
15. **LessonMaterial.php** — Материалы урока
16. **LessonResource.php** — Ресурсы урока
17. **Module.php** — Модуль курса
18. **Project.php** — Проект
19. **ProjectComment.php** — Комментарии к проекту
20. **ProjectResource.php** — Ресурсы проекта
21. **Review.php** — Отзывы
22. **Role.php** — Роли пользователей
23. **Subscription.php** — Подписки
24. **SubscriptionHistory.php** — История подписок
25. **User.php** — Пользователь
26. **UserSubscription.php** — Подписка пользователя

### Services: 10 файлов

1. **AchievementService.php** — Управление достижениями
2. **BookingService.php** — Управление записями
3. **ChatService.php** — Обработка чата
4. **CourseService.php** — Управление курсами
5. **GitlabService.php** — Интеграция с GitLab (проекты)
6. **LessonService.php** — Управление уроками
7. **ModuleService.php** — Управление модулями
8. **ProjectService.php** — Управление проектами
9. **ProgressService.php** — Расчет прогресса
10. **UserService.php** — Управление пользователями

### Jobs: 1 файл

1. **CreateGitlabProjectJob.php** — Фоновая задача создания проекта в GitLab

### Views: 92 blade файл

#### 🧩 components/ (31 файл)
- Переиспользуемые UI компоненты
- Кнопки, карточки, формы, модальные окна

#### 🎛️ admin/ (81 файл)
- Панель администратора
- Страницы управления курсами, модулями, уроками
- Статистика и отчеты

#### 🌐 public/ (81 файл)
- Публичные страницы курсов и проектов
- Главная страница, календарь, активности

#### 👤 profile/ (18 файл)
- Страницы профиля пользователя

#### ⚖️ legal/ (10 файл)
- Юридические документы и условия

#### 🔐 auth/ (20 файл)
- Страницы авторизации и регистрации

#### 📐 layouts/ (1 файл)
- Основные шаблоны layout

### Migrations: 46 миграций
### Seeders: 11 файлов
### Config: 13 файлов

---

## 🔄 Зависимости модели

```
User (основная модель)
 ├─ AchievementProgress
 ├─ Activity
 ├─ ActivityLog
 ├─ Chat
 ├─ ChatMessage
 ├─ CourseComment
 ├─ CourseUser
 ├─ Enrollment
 ├─ EnrollmentType
 ├─ LessonComment
 ├─ LessonMaterial
 ├─ LessonResource
 ├─ Module
 ├─ ProjectComment
 ├─ ProjectResource
 ├─ Review
 ├─ Role
 ├─ Subscription
 ├─ SubscriptionHistory
 └─ UserSubscription
```

---

## 🔗 Зависимости сервисов

```
AchievementService
 ├─ Achievement
 ├─ AchievementProgress
 └─ User

BookingService
 ├─ Subscription
 ├─ SubscriptionHistory
 └─ User

ChatService
 ├─ Chat
 ├─ ChatMessage
 └─ User

CourseService
 ├─ Course
 ├─ Module
 ├─ Lesson
 ├─ CourseUser
 ├─ Enrollment
 └─ AchievementService

GitlabService
 ├─ Project
 └─ CreateGitlabProjectJob

LessonService
 ├─ Lesson
 ├─ LessonMaterial
 ├─ LessonResource
 └─ User

ModuleService
 ├─ Module
 ├─ Lesson
 └─ User

ProjectService
 ├─ Project
 ├─ ProjectComment
 ├─ ProjectResource
 └─ User

ProgressService
 ├─ Activity
 ├─ ActivityLog
 ├─ CourseUser
 └─ User

UserService
 ├─ AchievementProgress
 ├─ CourseUser
 ├─ Enrollment
 ├─ Subscription
 └─ User
```

---

## 📈 Технический стек

### Backend
- **Laravel 10.x** — PHP фреймворк
- **PHP 8.x** — Язык программирования
- **MySQL/MariaDB** — База данных
- **RESTful API** — API архитектура

### Frontend
- **React** — JavaScript библиотека для UI
- **Tailwind CSS v4** — CSS фреймворк
- **Alpine.js** — Легковесный JavaScript фреймворк
- **JavaScript ES2022+** — Современный JS синтаксис

### DevOps
- **Git** — Контроль версий
- **PHPUnit** — Тестирование
- **Webpack** — Сборка ресурсов
- **Composer** — PHP зависимости
- **npm** — Node.js зависимости

---

## 🎯 Основные функции системы

1. **Управление курсами** — Создание, редактирование, публикация курсов
2. **Модули и уроки** — Структурирование учебного материала
3. **Проектные курсы** — Интеграция с GitLab
4. **Активности и прогресс** — Отслеживание прогресса обучения
5. **Чат** — Общение между пользователями
6. **Достижения** — Геймификация и награды
7. **Подписки** — Платные и бесплатные подписки
8. **Отзывы и рейтинги** — Обратная связь
9. **Календарь** — Планирование занятий
10. **Административная панель** — Управление системой

---

## 📝 Примечания

- Все контроллеры разделены на Admin/, Public/, и root уровни
- 92 blade файл организованы по логическим директориям
- 46 миграций для создания всех таблиц базы данных
- 11 seeders для начальных данных
- 13 конфигурационных файлов
- Интеграция с GitLab для проектных курсов
- Геймификация через систему достижений

---

**Обновлено:** 2026-07-10
**Версия документа:** 2.0

## 🌳 ДЕРЕВО ПРОЕКТА

```
21-lms/
├── 📂 app/                          # Основная логика приложения (Laravel)
│   ├── 📁 Actions/
│   │   ├── 📁 Fortify/               # Auth Fortify actions
│   │   └── 📁 Jetstream/              # Jetstream actions
│   ├── 📁 Http/
│   │   ├── 📁 Controllers/            # Контроллеры API и web
│   │   │   ├── 📁 Admin/              # Админ-контроллеры
│   │   │   │   ├── 📄 CourseController.php
│   │   │   │   ├── 📄 DashboardController.php
│   │   │   │   ├── 📄 LessonController.php
│   │   │   │   ├── 📄 ModuleController.php
│   │   │   │   ├── 📄 ProjectController.php
│   │   │   │   └── 📄 UserController.php
│   │   │   ├── 📄 ChatController.php
│   │   │   ├── 📄 Controller.php
│   │   │   ├── 📄 GamificationController.php
│   │   │   ├── 📄 ReviewController.php
│   │   │   ├── 📄 SubscriptionController.php
│   │   │   └── 📁 Public/              # Публичные контроллеры
│   │   │       ├── 📄 ActivitiesController.php
│   │   │       ├── 📄 CalendarController.php
│   │   │       ├── 📄 CourseController.php
│   │   │       ├── 📄 MainController.php
│   │   │       ├── 📄 ProgressController.php
│   │   │       └── 📄 ProjectController.php
│   │   ├── 📁 Middleware/           # Middleware файлы
│   │   └── 📄 Controller.php
│   ├── 📁 Jobs/
│   │   └── 📄 CreateGitlabProjectJob.php  # Работа по созданию GitLab проекта
│   ├── 📁 Models/                    # Все модели данных
│   │   ├── 📄 Achievement.php
│   │   ├── 📁 Admin/                # Модели администратора
│   │   │   ├── 📄 Project.php
│   │   │   ├── 📄 Review.php
│   │   │   └── 📄 Submission.php
│   │   ├── 📄 CalendarEvent.php
│   │   ├── 📄 CalendarSlot.php
│   │   ├── 📄 ChatMessage.php
│   │   ├── 📄 Chat.php
│   │   ├── 📄 CourseDependency.php
│   │   ├── 📄 Course.php
│   │   ├── 📄 Leaderboard.php
│   │   ├── 📄 LearningPath.php
│   │   ├── 📄 Lesson.php
│   │   ├── 📄 Module.php
│   │   ├── 📄 Notification.php
│   │   ├── 📄 SubmissionFile.php
│   │   ├── 📄 SubmissionReviewQueue.php
│   │   ├── 📄 TestResult.php
│   │   ├── 📄 UserAchievement.php
│   │   ├── 📄 UserActivity.php
│   │   ├── 📄 User.php
│   │   ├── 📄 UserStat.php
│   │   └── 📄 XpTransaction.php
│   ├── 📁 Providers/
│   │   ├── 📄 AppServiceProvider.php
│   │   ├── 📄 FortifyServiceProvider.php
│   │   └── 📄 JetstreamServiceProvider.php
│   ├── 📁 Services/                  # Сервисы бизнес-логики
│   │   ├── 📄 AchievementService.php
│   │   ├── 📄 BookingService.php
│   │   ├── 📄 ChatService.php
│   │   ├── 📄 GitlabCacheService.php
│   │   ├── 📄 GitlabHealthService.php
│   │   ├── 📄 GitlabService.php
│   │   ├── 📄 LeaderboardService.php
│   │   ├── 📄 ReviewAssignmentService.php
│   │   └── 📄 XpService.php
│   └── 📁 View/
│       └── 📁 Components/            # Компоненты представлений
├── 📂 bootstrap/                    # Laravel bootstrap файлы
│   ├── 📁 cache/
│   │   ├── 📄 packages.php
│   │   └── 📄 services.php
│   ├── 📄 app.php
│   └── 📄 providers.php
├── 📂 config/                         # Конфигурационные файлы
│   ├── 📄 app.php
│   ├── 📄 auth.php
│   ├── 📄 cache.php
│   ├── 📄 database.php
│   ├── 📄 filesystems.php
│   ├── 📄 fortify.php
│   ├── 📄 jetstream.php
│   ├── 📄 logging.php
│   ├── 📄 mail.php
│   ├── 📄 permission.php
│   ├── 📄 queue.php
│   ├── 📄 sanctum.php
│   ├── 📄 services.php
│   ├── 📄 session.php
│   └── 📄 xp.php
├── 📂 database/                       # База данных
│   ├── 📁 factories/
│   │   └── 📄 UserFactory.php
│   ├── 📁 migrations/
│   │   ├── 📄 0001_01_01_000000_create_users_table.php
│   │   ├── 📄 0001_01_01_000001_create_cache_table.php
│   │   ├── 📄 0001_01_01_000002_create_jobs_table.php
│   │   ├── 📄 2026_05_05_074722_create_courses_table.php
│   │   ├── 📄 2026_05_05_074722_create_modules_table.php
│   │   ├── 📄 2026_05_05_074722_create_projects_table.php
│   │   ├── 📄 2026_05_05_074722_create_submissions_table.php
│   │   ├── 📄 2026_05_05_074723_create_lessons_table.php
│   │   ├── 📄 2026_05_05_074723_create_project_dependencies_table.php
│   │   ├── 📄 2026_05_05_074723_create_project_tests_table.php
│   │   ├── 📄 2026_05_05_074723_create_reviews_table.php
│   │   ├── 📄 2026_05_05_074724_create_review_checklists_table.php
│   │   ├── 📄 2026_05_05_074724_create_reviewer_ratings_table.php
│   │   ├── 📄 2026_05_05_074724_create_submission_files_table.php
│   │   ├── 📄 2026_05_05_074724_create_test_results_table.php
│   │   ├── 📄 2026_05_05_074735_create_achievements_table.php
│   │   ├── 📄 2026_05_05_074735_create_discussions_table.php
│   │   ├── 📄 2026_05_05_074735_create_teams_table.php
│   │   ├── 📄 2026_05_05_074735_create_user_achievements_table.php
│   │   ├── 📄 2026_05_05_074735_create_user_activities_table.php
│   │   ├── 📄 2026_05_05_074735_create_xp_transactions_table.php
│   │   ├── 📄 2026_05_05_074736_create_ai_conversations_table.php
│   │   ├── 📄 2026_05_05_074736_create_ai_hints_table.php
│   │   ├── 📄 2026_05_05_074736_create_code_similarity_table.php
│   │   ├── 📄 2026_05_05_074736_create_discussion_replies_table.php
│   │   ├── 📄 2026_05_05_074736_create_learning_analytics_table.php
│   │   ├── 📄 2026_05_05_074736_create_notifications_table.php
│   │   ├── 📄 2026_05_05_074736_create_plagiarism_checks_table.php
│   │   ├── 📄 2026_05_05_074736_create_team_user_table.php
│   │   ├── 📄 2026_05_05_074746_create_leaderboards_table.php
│   │   ├── 📄 2026_05_05_074746_create_reports_table.php
│   │   ├── 📄 2026_05_05_074746_create_user_bans_table.php
│   │   ├── 📄 2026_05_05_074746_create_user_stats_table.php
│   │   ├── 📄 2026_05_05_074747_create_learning_paths_table.php
│   │   ├── 📄 2026_05_05_074747_create_user_learning_paths_table.php
│   │   ├── 📄 2026_05_05_074747_create_user_preferences_table.php
│   │   ├── 📄 2026_05_05_134513_add_two_factor_columns_to_users_table.php
│   │   ├── 📄 2026_05_05_134514_create_passkeys_table.php
│   │   ├── 📄 2026_05_05_134536_create_personal_access_tokens_table.php
│   │   ├── 📄 2026_05_23_000001_add_level_to_users_table.php
│   │   ├── 📄 2026_06_11_170616_create_permission_tables.php
│   │   ├── 📄 2026_06_20_163408_create_chats_and_chat_messages_tables.php
│   │   ├── 📄 2026_06_20_163408_create_course_dependencies_table.php
│   │   ├── 📄 2026_06_20_203454_make_gitlab_project_id_nullable.php
│   │   ├── 📄 2026_06_22_add_gitlab_sync_status_to_projects.php
│   │   ├── 📄 2026_07_04_000001_create_calendar_slots_table.php
│   │   ├── 📄 2026_07_04_000002_create_calendar_events_table.php
│   │   ├── 📄 2026_07_04_180307_create_submission_review_queues_table.php
│   │   └── 📄 2026_07_06_132256_create_calendar_event_registrations_table.php
│   └── 📁 seeders/
│       ├── 📄 AchievementSeeder.php
│       ├── 📄 CalendarSeeder.php
│       ├── 📄 CourseSeeder.php
│       ├── 📄 DatabaseSeeder.php
│       ├── 📄 ProjectSeeder.php
│       └── 📄 RolesAndPermissionsSeeder.php
├── 📂 docs/                           # Документация
│   └── 📄 login.txt
├── 📂 public/                          # Публичная директория (webroot)
│   ├── 📁 assets/
│   │   ├── 📁 css/
│   │   │   └── 📄 style.css
│   │   └── 📁 js/
│   │       └── 📄 app.js
│   ├── 📁 lumina/
│   │   ├── 📁 css/
│   │   │   ├── 📄 animations.css
│   │   │   └── 📄 tailwind.css
│   │   ├── 📁 img/
│   │   └── 📁 js/
│   │       └── 📄 lumina.js
│   ├── 📄 favicon.ico
│   ├── 📄 index.php (Laravel entry point)
│   ├── 📄 robots.txt
│   └── 📄 storage -> symlink (псевдоним для storage/app/public)
├── 📂 resources/                       # Ресурсы приложения
│   ├── 📁 css/
│   │   └── 📄 app.css
│   ├── 📁 js/
│   │   └── 📄 calendar.js
│   ├── 📁 markdown/
│   │   ├── 📄 policy.md
│   │   └── 📄 terms.md
│   └── 📁 views/
│       ├── 📁 admin/                  # Админ-панель
│       ├── 📁 auth/                   # Авторизация
│       ├── 📁 components/              # Компоненты (Blade)
│       ├── 📁 legal/                  # Юридические документы
│       ├── 📁 profile/                # Профиль пользователя
│       └── 📁 public/                 # Публичные страницы
├── 📂 routes/                          # Маршруты (routes)
│   ├── 📄 api.php                     # API маршруты
│   ├── 📄 console.php                 # Artisan команды
│   └── 📄 web.php                     # Web маршруты
├── 📂 storage/                         # Хранилище
│   ├── 📁 app/
│   │   ├── 📁 private/               # Приватные файлы
│   │   └── 📁 public/                 # Публичные файлы
│   ├── 📁 framework/
│   │   ├── 📁 cache/
│   │   ├── 📁 sessions/
│   │   ├── 📁 testing/
│   │   └── 📁 views/                 # Compiled Blade views
│   └── 📁 logs/
│       └── 📄 laravel.log
├── 📂 tests/                          # Тесты
│   ├── 📁 Feature/
│   │   ├── 📄 ApiTokenPermissionsTest.php
│   │   ├── 📄 AuthenticationTest.php
│   │   ├── 📄 BrowserSessionsTest.php
│   │   ├── 📄 CreateApiTokenTest.php
│   │   ├── 📄 DeleteAccountTest.php
│   │   ├── 📄 DeleteApiTokenTest.php
│   │   ├── 📄 EmailVerificationTest.php
│   │   ├── 📄 ExampleTest.php
│   │   ├── 📄 PasswordConfirmationTest.php
│   │   ├── 📄 PasswordResetTest.php
│   │   ├── 📄 ProfileInformationTest.php
│   │   ├── 📄 RegistrationTest.php
│   │   ├── 📄 TwoFactorAuthenticationSettingsTest.php
│   │   └── 📄 UpdatePasswordTest.php
│   ├── 📁 Unit/
│   │   └── 📄 ExampleTest.php
│   └── 📄 TestCase.php
├── 📄 artisan                         # Artisan CLI
├── 📄 composer.json                   # Composer зависимости
├── 📄 composer.lock
├── 📄 package.json                    # Node.js зависимости
├── 📄 package-lock.json
├── 📄 phpunit.xml                    # PHPUnit конфигурация
├── 📄 postcss.config.js               # PostCSS конфигурация
├── 📄 tailwind.config.js              # Tailwind CSS конфигурация
├── 📄 vite.config.js                 # Vite конфигурация
├── 📄 README.md                      # Описание проекта
├── 📂 .claude/
│   └── 📄 settings.local.json
└── 📂 vendor/                        # Composer зависимости (Laravel)
    └── 📁 laravel/
        └── 📁 framework/
            └── 📁 sanctum/
```

## 📊 ГРАФ ЗАВИСИМОСТЕЙ МОДЕЛЕЙ

```
Admin (Администратор)
  └── HasOne: UserStat

User (Пользователь)
  ├── HasMany: Course
  ├── HasMany: Module
  ├── HasMany: Lesson
  ├── HasMany: Project
  ├── HasMany: Submission
  ├── HasMany: Review
  ├── HasMany: Achievement
  ├── HasMany: UserAchievement
  ├── HasMany: XpTransaction
  ├── HasMany: Chat
  ├── HasMany: ChatMessage
  ├── HasMany: Discussion
  ├── HasMany: DiscussionReply
  ├── HasMany: Team
  ├── HasMany: Leaderboard
  ├── HasMany: Notification
  ├── HasMany: LearningPath
  ├── HasMany: UserLearningPath
  ├── HasMany: CalendarEvent
  ├── HasMany: CalendarSlot
  └── HasOne: UserStat

Course (Курс)
  ├── HasMany: Module
  └── HasMany: CourseDependency

Module (Модуль)
  ├── BelongsTo: Course
  └── HasMany: Lesson

Lesson (Урок)
  ├── BelongsTo: Module
  └── HasMany: Project

Project (Проект)
  ├── BelongsTo: Lesson
  ├── HasMany: Submission
  ├── HasMany: Test
  └── HasMany: ProjectDependency

Submission (Отправка)
  ├── BelongsTo: User
  ├── BelongsTo: Project
  └── HasMany: SubmissionFile

SubmissionFile (Файл отправки)
  ├── BelongsTo: Submission
  └── HasMany: ReviewChecklist

Review (Ревью)
  ├── BelongsTo: Submission
  └── HasMany: ReviewerRating

Achievement (Достижение)
  ├── HasMany: UserAchievement
  └── HasMany: XpTransaction

UserAchievement (Достижение пользователя)
  ├── BelongsTo: User
  └── BelongsTo: Achievement

XpTransaction (Транзакция XP)
  ├── BelongsTo: User
  └── BelongsTo: Achievement

Chat (Чат)
  ├── BelongsTo: User
  └── HasMany: ChatMessage

ChatMessage (Сообщение чата)
  ├── BelongsTo: Chat
  └── BelongsTo: User

Discussion (Обсуждение)
  ├── BelongsTo: User
  └── HasMany: DiscussionReply

DiscussionReply (Ответ обсуждения)
  ├── BelongsTo: Discussion
  └── BelongsTo: User

Team (Команда)
  ├── HasMany: User (через team_user)
  └── HasMany: Submission

TeamUser (Связь команды и пользователя)
  ├── BelongsTo: Team
  └── BelongsTo: User

LearningPath (Путь обучения)
  ├── HasMany: Course
  └── HasMany: UserLearningPath

UserLearningPath (Путь обучения пользователя)
  ├── BelongsTo: User
  └── BelongsTo: LearningPath

CalendarEvent (Событие календаря)
  ├── HasMany: CalendarSlot
  └── BelongsTo: User

CalendarSlot (Слот календаря)
  ├── BelongsTo: CalendarEvent
  └── BelongsTo: User

Leaderboard (Рейтинг)
  ├── BelongsTo: User
  └── HasMany: XpTransaction

Notification (Уведомление)
  ├── BelongsTo: User
  └── HasMany: Notification

UserStat (Статистика пользователя)
  └── BelongsTo: User

User (Пользователь) - повторное упоминание
  └── HasOne: UserStat

GitlabProject (GitLab проект)
  └── BelongsTo: Project
```

## 🔗 ГРАФ СЕРВИСОВ

```
XpService
  ├── AchievementService
  │   └── Achievement
  └── UserAchievement

AchievementService
  ├── XpService
  └── Achievement

BookingService
  ├── CalendarEvent
  └── CalendarSlot

ChatService
  ├── Chat
  ├── ChatMessage
  └── Discussion

GitlabService
  ├── GitlabCacheService
  ├── GitlabHealthService
  └── GitlabProjectJob

ReviewAssignmentService
  ├── Submission
  ├── SubmissionFile
  └── ReviewChecklist

LeaderboardService
  ├── Leaderboard
  ├── XpTransaction
  └── Achievement
```

## 📋 РЕСУРСЫ И ФАЙЛЫ

### Конфигурации
- **config/app.php** - Основная конфигурация приложения
- **config/auth.php** - Авторизация
- **config/cache.php** - Кэш
- **config/database.php** - Настройки БД
- **config/filesystems.php** - Файловая система
- **config/logging.php** - Логирование
- **config/mail.php** - Настройки почты
- **config/permission.php** - Права доступа
- **config/queue.php** - Очереди задач
- **config/sanctum.php** - Sanctum API токены
- **config/session.php** - Сессии
- **config/xp.php** - Настройки XP системы
- **config/jetstream.php** - Jetstream конфигурация
- **config/fortify.php** - Fortify авторизация

### Файловая система и зависимости
- **composer.lock** - Блокировка версий Composer зависимостей
- **package-lock.json** - Блокировка версий Node.js зависимостей
- **llama.log** - Лог-файл приложения

### Frontend
- **resources/css/app.css** - Основной CSS
- **resources/js/calendar.js** - JavaScript для календаря
- **public/assets/css/style.css** - CSS для стиля (170600 байт)
- **public/assets/js/app.js** - JavaScript для приложения (120694 байт)
- **public/lumina/css/animations.css** - CSS анимации
- **public/lumina/css/tailwind.css** - Tailwind CSS (32103 байт)
- **public/lumina/js/lumina.js** - JavaScript для Lumina (4855 байт)
- **tailwind.config.js** - Tailwind CSS конфигурация
- **postcss.config.js** - PostCSS конфигурация
- **vite.config.js** - Vite конфигурация
- **package.json** - Node.js зависимости
- **phpunit.xml** - PHPUnit конфигурация

### Маршруты
- **routes/api.php** - API маршруты
- **routes/web.php** - Web маршруты
- **routes/console.php** - Artisan команды

### Документация
- **README.md** - Описание проекта
- **PROJECT_OVERVIEW.md** - Обзор проекта
- **PROJECT_GRAPH.html** - Граф проекта (визуализация зависимостей)
- **CALENDAR_SEPARATE_PAGES.md** - Документация по календарю (отдельные страницы)
- **CALENDAR_SERVER_SIDE_UPDATE.md** - Документация по серверной стороне обновления
- **docs/login.txt** - Информация для входа
- **resources/markdown/policy.md** - Политика
- **resources/markdown/terms.md** - Условия использования
- **.claude/settings.local.json** - Настройки Claude

## 🎯 Ключевые компоненты системы

### 💎 Модели (22+ модели)
1. **User** - Пользователь с XP, уровнями, достижениями
2. **Admin** - Администраторы системы (Project, Review, Submission)
3. **Course** - Курсы с модулями и уроками
4. **Module** - Модули курса
5. **Lesson** - Уроки с проектами
6. **Project** - Проекты для разработки
7. **Submission** - Отправки проектов
8. **SubmissionFile** - Файлы отправки
9. **Review** - Ревью проектов (Admin)
10. **Achievement** - Достижения
11. **UserAchievement** - Достижения пользователя
12. **Chat** - Чаты с пользователями
13. **ChatMessage** - Сообщения чата
14. **Discussion** - Обсуждения
15. **DiscussionReply** - Ответы обсуждений
16. **Team** - Команды
17. **CalendarEvent** - События календаря
18. **CalendarSlot** - Слоты календаря
19. **Leaderboard** - Рейтинг
20. **Notification** - Уведомления
21. **UserStat** - Статистика пользователя
22. **XpTransaction** - Транзакции XP
23. **LearningPath** - Путь обучения
24. **UserLearningPath** - Путь обучения пользователя
25. **CourseDependency** - Зависимости курсов
26. **TestResult** - Результаты тестов
27. **SubmissionReviewQueue** - Очередь ревью
28. **Test** - Тесты проектов
29. **ProjectDependency** - Зависимости проектов
30. **ReviewChecklist** - Чек-листы ревью
31. **ReviewerRating** - Оценки ревьюеров
32. **CodeSimilarity** - Кодовая схожесть
33. **PlagiarismCheck** - Плагиат чек
34. **LearningAnalytics** - Аналитика обучения
35. **UserPreferences** - Настройки пользователя
36. **PersonalAccessToken** - API токены
37. **TwoFactorAuthentication** - Двухфакторная авторизация
38. **Passkey** - Passkeys аутентификация

### 🛠️ Сервисы (10 сервисов)
1. **AchievementService** - Достижения
2. **BookingService** - Бронирование
3. **ChatService** - Чаты
4. **GitlabService** - GitLab интеграция
5. **GitlabCacheService** - Кэш GitLab
6. **GitlabHealthService** - Мониторинг GitLab
7. **LeaderboardService** - Рейтинг
8. **ReviewAssignmentService** - Назначение ревью
9. **XpService** - Система XP

### 📋 Migrations (46 миграций)
- Пользователи, авторизация, уровни, passkeys
- Курсы, модули, уроки, проекты
- Отправки, файлы, ревью
- Достижения, XP, чаты
- Обсуждения, команды, календарь
- Рейтинг, уведомления, аналитика
- **2026_07_06_132256_create_calendar_event_registrations_table.php** - Регистрации на события календаря

### 🧪 Тесты (13+ тестов)
- Авторизация, профиля
- API токены, двухфакторная авторизация
- Email верификация, сброс пароля
- Тесты функциональные и unit

### 🗺️ Маршруты
- **API**: REST API для данных
- **Web**: Страницы приложения
- **Console**: Artisan команды

### 📱 Контроллеры (17 контроллеров)
- **Admin**: CourseController, DashboardController, LessonController, ModuleController, ProjectController, UserController
- **Public**: ActivitiesController, CalendarController, CourseController, MainController, ProgressController, ProjectController
- **API**: ChatController, GamificationController, ReviewController, SubscriptionController, Controller

### 📁 Views (6 папок)
- **admin/** - Админ-панель
- **auth/** - Авторизация
- **components/** - Компоненты (Blade)
- **legal/** - Юридические документы
- **profile/** - Профиль пользователя
- **public/** - Публичные страницы

---

**Всего в проекте:**
- **72 директории**
- **300 файлов** (PHP, Blade, миграции, контроллеры, модели, сервисы, тесты)
- **46 миграций**
- **23 модели**
- **10 сервисов**
- **17 контроллеров**
- **16 тестов**
- **92 Blade файла** (views)

Это современная LMS (Learning Management System) с:
- 💯 Система XP и уровней
- 🏆 Система достижений
- 📚 Курсы, модули, уроки
- 💬 Чаты и обсуждения
- 🏢 Командная работа
- 📅 Календарь событий
- 🏆 Рейтинг лидерборд
- 🔗 GitLab интеграция
- 📊 Аналитика обучения
- 📝 Ревью проектов
- 🔒 Безопасность (двухфакторная, passkeys)
- 🎨 Визуализация проекта через PROJECT_GRAPH.html
- 📋 Подробная документация по календарю и компонентам
