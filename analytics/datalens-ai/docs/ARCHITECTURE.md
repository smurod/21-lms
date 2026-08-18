# Архитектура datalens-ai

Сверено с кодом **2026-08-14** (`main.py` 0.2.0).

## Размещение

В проекте `21-lms` сервис живёт отдельно от Laravel-кода:

```text
21-lms/
└── analytics/
    ├── datalens/          # README / compose платформы DataLens (не исходники UI)
    └── datalens-ai/       # этот FastAPI-сервис
```

Локально у пользователя:

```text
/home/smurod_8880/projects/21-lms/analytics/datalens-ai
```

В workspace:

```text
/home/user/analytics/datalens-ai
```

Код **не** кладётся в `app/`, `resources/`, `public/`, `app/Services/`.

## Поток данных

```text
Laravel  ──HTTP──>  datalens-ai (FastAPI :8100)
                          │
                          ├──> llama-server LLM (:8001)
                          │
                          ├──> PostgreSQL (схема + EXPLAIN + LIMIT 5)
                          │         адрес: Settings.db_url / DB_URL
                          │
                          └──> DataLens UI/API (:8085)
                                   ├── auth (:8088) — POST /signin
                                   ├── gateway POST /gateway/root/<svc>/<action>
                                   ├── charts-engine POST/DELETE /api/charts/v1/charts
                                   └── us / bi / mix
```

Два разных адреса одной БД:

- `DB_URL` — видит Python на хосте (`127.0.0.1`);
- `DATALENS_DB_*` — видит DataLens из Docker (обычно `172.17.0.1`).

## Модули (факт файлов)

| Файл | Роль в текущем пайплайне |
|---|---|
| `main.py` | FastAPI 0.2.0, публичные endpoint'ы |
| `config.py` | `pydantic-settings` из `.env` |
| `dashboard_service.py` | полный цикл generate |
| `sql_pipeline.py` | 1 график за запрос LLM + валидация |
| `ai_editor.py` | edit: add/update/delete/reorder |
| `datalens_client.py` | логин, workbook, connection, QL, dash |
| `ql_chart_builder.py` | JSON QL из `samples/chart_*.json` |
| `dashboard_builder.py` | JSON дашборда, сетка 36 |
| `schema_analyzer.py` | PostgreSQL `information_schema` + COUNT |
| `sql_validator.py` | `EXPLAIN` + `LIMIT 5` через `psycopg` |
| `llm_client.py` | llama.cpp `/v1/chat/completions` |
| `start_llm.py` | запуск `llama-server` |
| `sql_generator.py` | **не вызывается** текущим `main.py` / `dashboard_service.py` |
| `models/schema.py` | Pydantic-модели; часть — наследие Дня 1 |

## Как код ходит в DataLens

Из `datalens_client.py`:

| Операция | Реальный вызов |
|---|---|
| Логин | `POST {auth}/signin` |
| Gateway | `POST /gateway/root/{service}/{action}` |
| Найти/создать workbook | `us.getWorkbooksList` / `us.createWorkbook` |
| Connection | `bi.createConnection` (`workbook_id`, `raw_sql_level=dashsql`) |
| Создать QL-чарт | `POST /api/charts/v1/charts` (не `mix.__createQLChart__`) |
| Обновить QL-чарт | `POST /api/charts/v1/charts/{id}` |
| Удалить QL-чарт | `DELETE /api/charts/v1/charts/{id}` |
| Создать дашборд | `mix.createDashboardV1` (`entry` + `mode=publish`) |
| Прочитать дашборд | `mix.getDashboardV1` (`dashboardId`) |
| Обновить дашборд | `mix.updateDashboardV1` (`entry.entryId`) |
| Удалить entry | `us._deleteUSEntry` |

## Объекты в United Storage

- `workbook` — контейнер, заголовок по умолчанию `AI Generated`;
- `connection` / type `postgres`;
- `widget` — QL-чарт, SQL в `data.shared.queryValue`;
- `dash` — `tabs → items + layout`.

Dataset в первой версии **не создаётся**.

## QL-чарт (что кладёт `ql_chart_builder`)

Типы шаблонов: `line`, `column`, `table` (файлы `samples/chart_xp_dynamics_by_day.json`, `chart_submissions_by_status.json`, `chart_top10_users_xp.json`).

- `line`: колонка 0 → X, остальные → Y;
- `column`: последнее числовое поле → Y, остальные → X;
- `table`: все поля в `flat-table-columns`;
- неизвестный тип нормализуется в `table`.

## Дашборд (`dashboard_builder`)

- сетка 36 колонок;
- заголовок секции `w=12 h=2`, note справа `w=24`;
- `column` — `w=18 h=16` (две колонки);
- `line` / `table` — `w=36 h=20`;
- id виджетов: 2 случайных символа + 6, чтобы не было `Duplicated id`.

## Пайплайн generate (порядок в коде)

1. login + ensure workbook + create connection (**до** LLM);
2. `analyze_schema` (только PostgreSQL);
3. `plan_and_validate_dashboard` — до 3 валидных графиков, до 8 попыток, 2 retry SQL;
4. create QL charts + `build_dashboard_data` + `create_dashboard`.

`chart_count` из HTTP-запроса передаётся в pipeline; если он не указан, LLM выбирает масштаб dashboard из схемы и запроса.
