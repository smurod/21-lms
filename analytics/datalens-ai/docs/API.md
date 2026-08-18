# HTTP API контракта datalens-ai

> **Этот документ — обязательный протокол для всех изменений.** Endpoint'ы и поля ниже — публичный API для Laravel. Их можно расширять, но нельзя ломать: нельзя менять URL, методы, имена и семантику существующих полей ответа.

Сверено с `main.py` **2026-08-14**. Версия сервиса: **0.2.0**.

Базовый URL:

```text
http://<host>:8100
```

## Существующие endpoint'ы

| Метод | URL | Назначение |
|---|---|---|
| GET | `/` | Служебная информация о сервисе |
| GET | `/health` | Статус FastAPI, LLM и DataLens |
| POST | `/api/dashboards/generate` | Синхронное создание dashboard, старый контракт |
| GET | `/api/dashboards/{dashboard_id}` | Получить DataLens dashboard JSON |
| POST | `/api/dashboards/{dashboard_id}/edit` | Синхронное AI-редактирование, старый контракт |
| GET | `/api/charts/{chart_id}` | Получить QL chart |
| POST | `/api/validate-sql` | Проверить PostgreSQL SQL |
| POST | `/api/analyze` | Legacy-анализ схемы, `db_url` query-параметр |

## Health

### `GET /health`

```json
{
    "status": "ok",
    "llm_server": true,
    "datalens": true
}
```

Все три поля обязательны.

## Синхронная генерация — прежний контракт

### `POST /api/dashboards/generate`

Принимает необязательные `message`, `db_url`, `chart_count`, `workbook_id`. `chart_count` допускает 1–8; если он не передан, LLM сама определяет нужное число визуализаций по запросу и схеме.

Возвращает без изменения контракта:

```json
{
  "dashboard_id": "...",
  "dashboard_url": "http://localhost:8085/workbooks/<workbook_id>",
  "embed_url": "http://localhost:8085/<dashboard_entry_key>",
  "workbook_id": "...", 
  "connection_id": "...",
  "title": "...",
  "charts": []
}
```

`dashboard_url` исторически указывает на workbook, а не на конкретный dashboard.

`embed_url` — динамический прямой URL созданного dashboard. Его нужно использовать в Laravel iframe. URL строится datalens-ai по `dashboard_id` и фактическому `entry.name`. Внутренний DataLens `key` не используется: он может содержать collection ID и `/`, поэтому не является browser URL.

## Асинхронные jobs для Laravel admin

Эти endpoint'ы добавлены без изменения старых URL. Они позволяют серверной Laravel-странице показывать реальные этапы длительной работы без JavaScript/AJAX.

### `POST /api/dashboard-jobs`

Создаёт фоновую задачу в текущем процессе FastAPI.

Создание dashboard:

```json
{
  "operation": "generate",
  "message": "Покажи активность пользователей"
}
```

Редактирование dashboard:

```json
{
  "operation": "edit",
  "dashboard_id": "<datalens_dashboard_id>",
  "connection_id": "<connection_id>",
  "message": "Добавь таблицу топ-10 пользователей по XP"
}
```

`db_url` и `workbook_id` необязательны. Если `db_url` не передан, используется `DB_URL` из `.env` datalens-ai.

Ответ:

```json
{
  "job_id": "...",
  "operation": "generate",
  "status": "queued",
  "stage": "queued",
  "message": "Задача поставлена в очередь…",
  "step": 0,
  "error": null,
  "result": null
}
```

### `GET /api/dashboard-jobs/{job_id}`

Возвращает серверный статус задачи.

Статусы:

```text
queued
running
completed
failed
```

Этапы generate:

```text
1 login
2 workbook
3 connection
4 schema
5 sql
6 charts
7 layout
8 dashboard
9 completed
```

У завершённой задачи `result` содержит тот же набор данных, что синхронный generate endpoint. У ошибочной задачи поле `error` содержит безопасное текстовое описание ошибки.

> Jobs хранятся в памяти текущего процесса FastAPI. Если datalens-ai перезапустить, незавершённые job ID исчезнут. Для первого упрощённого Laravel-интерфейса это допустимо; постоянная очередь будет отдельной задачей.

## Редактирование — прежний контракт

### `POST /api/dashboards/{dashboard_id}/edit`

Принимает обязательное `message`, опциональные `db_url`, `connection_id`.

Возвращает:

```json
{
    "dashboard_id": "...",
    "added": [],
    "updated": [],
    "deleted": [],
    "sections": []
}
```

## Безопасность и наблюдаемость

- `/health` остаётся доступным для локального healthcheck.
- CORS ограничивается `CORS_ORIGINS` из `.env`; локальное значение по умолчанию разрешает только Laravel на `127.0.0.1:8000` и `localhost:8000`.
- Каждый ответ содержит `X-Request-ID`. Его нужно прикладывать к логу FastAPI при диагностике.
- Ошибки внешних сервисов не возвращают клиенту сырые детали, пароли, cookies или traceback. Ошибка блокировки dashboard возвращается как `409` и `ENTRY_IS_LOCKED`.
- `DATALENS_TIMEOUT` задаёт лимит ожидания вызовов DataLens (по умолчанию 60 секунд).
- `DATALENS_DB_TYPE` передаёт DataLens тип создаваемого connection. На текущем этапе AI-анализ и SQL-валидация поддерживают только `postgres`; при другом типе сервис завершит задачу понятной ошибкой до создания объектов DataLens.

## Совместимость

1. Не удалять и не переименовывать существующие endpoint'ы и поля.
2. Добавлять новые необязательные поля и endpoint'ы разрешено.
3. Несовместимые изменения требуют `/api/v2/...`.
4. `kind` чартов: `line`, `area`, `column`, `bar`, `pie`, `table`. Новые типы добавлены как расширение и не меняют семантику прежних значений.
5. Текущая реализация schema analyzer и SQL validator использует PostgreSQL (`psycopg`). MySQL в datalens-ai пока не реализован.
