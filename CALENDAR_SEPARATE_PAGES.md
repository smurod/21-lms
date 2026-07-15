# Календарь — Отдельные страницы для вкладок

## Что было сделано:

### 1. Контроллер CalendarController.php
Добавлены три метода для отдельных страниц:

- **`schedulePage()`** — страница с сеткой календаря
- **`eventsPage()`** — страница со всеми событиями всех пользователей
- **`myEventsPage()`** — страница с событиями и слотами текущего пользователя

Каждый метод возвращает отдельный view с соответствующей активной вкладкой.

### 2. Маршруты web.php
Добавлены три маршрута для отдельных страниц:

```php
Route::get('/calendar', CalendarController::class)->name('public.calendar');
Route::get('/calendar/schedule', CalendarController::class)->name('public.calendar.schedule');
Route::get('/calendar/events', [CalendarController::class, 'eventsPage'])->name('public.calendar.events');
Route::get('/calendar/my-events', [CalendarController::class, 'myEventsPage'])->name('public.calendar.my-events');
```

### 3. Представление calendar.blade.php
Обновлены кнопки вкладок для навигации на отдельные страницы:

```html
<a href="{{ url('/calendar/schedule') }}" class="calendar-tab ...">Schedule</a>
<a href="{{ url('/calendar/events') }}" class="calendar-tab ...">Events</a>
<a href="{{ url('/calendar/my-events') }}" class="calendar-tab ...">My events</a>
```

## URLs после изменений:

| Вкладка | URL |
|------|-----|
| Schedule | `/calendar/schedule` |
| Events | `/calendar/events` |
| My events | `/calendar/my-events` |

## Как это работает:

1. **Schedule** → `/calendar/schedule` — сетка календаря с интерактивной таблицей
2. **Events** → `/calendar/events` — список всех событий всех пользователей
3. **My events** → `/calendar/my-events` — события и слоты текущего пользователя

## Преимущества:

✓ Чистые URL для SEO
✓ Отдельные страницы для каждой вкладки
✓ Можно добавлять meta теги и SEO оптимизацию
✓ Легче добавлять микроразметку
✓ Каждый раздел можно оптимизировать отдельно

## Можно ли использовать одну страницу с табами?

Да, можно вернуться к одному файлу `/calendar`, используя параметр `?tab=...`. Для этого нужно:

1. Удалить маршруты для отдельных страниц
2. Вернуть метод `__invoke()` с переключением вкладок
3. Вернуть кнопки вкладок с URL параметрами

