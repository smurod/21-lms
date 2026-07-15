# 📊 ПРОЕКТ LMS - ОБЗОР СИСТЕМЫ

## 🎯 Описание проекта

Это современная **LMS (Learning Management System)** - система обучения, разработанная на Laravel. Система включает в себя:

- 💯 **Система XP и уровней** - Поинты опыта за активность
- 🏆 **Система достижений** - Награды за выполнение задач
- 📚 **Курсы** - Структурированное обучение с модулями и уроками
- 💬 **Чаты и обсуждения** - Общение между пользователями
- 🏢 **Командная работа** - Работа в командах над проектами
- 📅 **Календарь событий** - Планирование и бронируемые слоты
- 🏆 **Рейтинг лидерборд** - Топ пользователей по XP
- 🔗 **GitLab интеграция** - Тестирование проектов в GitLab
- 📝 **Ревью проектов** - Проверка кода другими пользователями
- 📊 **Аналитика обучения** - Статистика прогресса
- 🔒 **Безопасность** - Двухфакторная авторизация, passkeys

---

## 📈 Статистика проекта

| Категория | Количество |
|---------|-----------|
| Директорий | 60+ |
| Файлов | 150+ |
| Migrations | 35+ |
| Моделей | 15+ |
| Сервисов | 9+ |
| Тестов | 14+ |

---

## 🏗️ Архитектура приложения

### 🔷 Frontend (Vue.js + Tailwind)
- **resources/js/calendar.js** - Календарь
- **resources/css/app.css** - Основной CSS
- **public/app.js** - JavaScript приложения
- **vite.config.js** - Vite конфигурация
- **tailwind.config.js** - Tailwind CSS

### 🔷 Backend (Laravel)
- **app/Http/Controllers/** - Контроллеры API и web
- **app/Services/** - Бизнес-логика
- **app/Models/** - 15+ Eloquent моделей
- **routes/** - Маршруты API, web, console

### 🔷 Database
- **database/migrations/** - 35+ миграций
- **database/seeders/** - Данные для заполнения БД

### 🔷 Testing
- **tests/Feature/** - Функциональные тесты
- **tests/Unit/** - Unit тесты

---

## 🎨 Основные компоненты

### 💎 Модели данных (15+)

1. **User** - Пользователь
   - XP, уровни, достижения
   - Двухфакторная авторизация
   - Passkeys

2. **Course** - Курс
   - Модули и уроки
   - Зависимости курсов

3. **Module** - Модуль
   - Уроки и проекты

4. **Lesson** - Урок
   - Проекты для разработки
   - Тесты

5. **Project** - Проект
   - GitLab проекты
   - Зависимости

6. **Submission** - Отправка
   - Файлы отправки
   - Ревью

7. **Achievement** - Достижение
   - XP за достижения
   - Призы

8. **Chat** - Чат
   - Общение пользователей
   - Сообщения

9. **Discussion** - Обсуждение
   - Обсуждения по темам

10. **Team** - Команда
    - Коллективная работа
    - Участники команды

11. **CalendarEvent** - Событие
    - Планирование
    - Бронируемые слоты

12. **Leaderboard** - Рейтинг
    - Топ по XP
    - Ежедневный рейтинг

13. **Notification** - Уведомление
    - Уведомления пользователям
    - Email и push

14. **UserStat** - Статистика
    - Аналитика пользователя
    - Прогресс обучения

15. **XpTransaction** - XP транзакции
    - Лог всех XP начислений
    - История достижений

---

### 🛠️ Сервисы бизнес-логики (9)

1. **XpService** - Система XP
   - Начисление XP
   - Проверка уровней
   - Достижения

2. **AchievementService** - Достижения
   - Проверка условий
   - Награждение пользователей
   - Хронология

3. **ChatService** - Чаты
   - Создание чатов
   - Отправка сообщений
   - Фильтрация

4. **GitlabService** - GitLab
   - Создание проектов
   - Тестирование
   - Синхронизация

5. **GitlabCacheService** - Кэш GitLab
   - Кэширование API ответов
   - Оптимизация запросов

6. **GitlabHealthService** - Мониторинг
   - Проверка здоровья GitLab
   - Уведомления о проблемах

7. **BookingService** - Брони
   - Создание слотов
   - Проверка доступности
   - Отмена бронирования

8. **LeaderboardService** - Рейтинг
   - Обновление рейтинга
   - Топ пользователей
   - Ежедневные обновления

9. **ReviewAssignmentService** - Ревью
   - Назначение ревью
   - Проверка списков
   - Оценки

---

### 📋 Migrations (35+)

| Таблица | Описание |
|---------|---------|
| users | Пользователи и авторизация |
| courses | Курсы |
| modules | Модули |
| lessons | Уроки |
| projects | Проекты |
| submissions | Отправки |
| achievements | Достижения |
| chats | Чаты |
| discussions | Обсуждения |
| teams | Команды |
| calendar_events | События календаря |
| leaderboards | Рейтинг |
| notifications | Уведомления |
| xp_transactions | Транзакции XP |

---

### 🧪 Тесты (14+)

- **Feature/**:
  - ApiTokenPermissionsTest
  - AuthenticationTest
  - BrowserSessionsTest
  - CreateApiTokenTest
  - DeleteAccountTest
  - DeleteApiTokenTest
  - EmailVerificationTest
  - ExampleTest
  - PasswordConfirmationTest
  - PasswordResetTest
  - ProfileInformationTest
  - RegistrationTest
  - TwoFactorAuthenticationSettingsTest
  - UpdatePasswordTest

- **Unit/**:
  - ExampleTest

---

## 🔗 Граф зависимостей

```
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
```

---

## 📁 Полная структура проекта

См. файл **PROJECT_STRUCTURE.md** для детального дерева проекта.

---

## 🚀 Технологический стек

- **Backend**: Laravel 10+ (PHP)
- **Frontend**: Vue.js + Tailwind CSS
- **Database**: MySQL/MariaDB
- **GitLab**: Интеграция для тестирования
- **Authentication**: Sanctum, Fortify, Jetstream
- **Testing**: PHPUnit
- **Build Tool**: Vite

---

**Дата создания**: 2026-07-06  
**Версия**: 1.0  
**Статус**: Активная разработка

