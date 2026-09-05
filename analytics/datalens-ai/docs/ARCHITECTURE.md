# Архитектура datalens-ai

Сверено с кодом **2026-08-22** (`main.py` 0.2.0).

## Размещение

```
21-lms/
└── analytics/
    ├── datalens/          # Docker-инфраструктура Yandex DataLens
    └── datalens-ai/       # этот FastAPI-сервис (:8100)
```

Код **не** кладётся в `app/`, `resources/`, `public/`.

## Поток данных

```
Laravel (PHP :8000)
    │  X-API-Key
    ▼
datalens-ai (FastAPI :8100)
    │
    ├──► OpenAI API (gpt-5.6-luna)
    │       structured JSON output, retry на 429
    │
    ├──► PostgreSQL (asyncio.to_thread + psycopg)
    │       схема, EXPLAIN, LIMIT 5, sample rows
    │       адрес: DB_URL (127.0.0.1 на хосте)
    │
    └──► DataLens UI/API (:8085)
             ├── auth (:8088)  POST /signin
             ├── gateway       POST /gateway/root/<svc>/<action>
             ├── charts-engine POST/DELETE /api/charts/v1/charts
             └── us / bi / mix
```

Два адреса одной БД:
- `DB_URL` — Python видит `127.0.0.1`
- `DATALENS_DB_HOST` — DataLens из Docker видит `172.17.0.1`

## Модули

| Файл | Роль |
|---|---|
| `main.py` | FastAPI, endpoints, job-система, SSE, X-API-Key middleware |
| `config.py` | pydantic-settings из `.env` |
| `dashboard_service.py` | Полный пайплайн generate; saga-откат при ошибке |
| `sql_pipeline.py` | 1 чарт за LLM-запрос; валидация SQL; sample rows в схеме |
| `ai_editor.py` | edit: add / update / delete / reorder / replace / clear |
| `datalens_client.py` | login, workbook, connection, QL-чарты, dashboard, locks |
| `ql_chart_builder.py` | JSON QL-чарта из шаблонов `samples/` |
| `dashboard_builder.py` | JSON dashboard, сетка 36 колонок |
| `schema_analyzer.py` | PostgreSQL information_schema + COUNT + sample rows |
| `sql_validator.py` | EXPLAIN + LIMIT 5 (asyncio.to_thread, non-blocking) |
| `llm_client.py` | OpenAI Chat Completions API + retry 429 |
| `entity_resolver.py` | Резолв email/проект → реальные ID из БД (async) |
| `database_support.py` | Проверка: только PostgreSQL |
| `chart_contract.py` | Валидация полей чарта до DataLens |
| `chart_dedup.py` | Дедупликация SQL и заголовков |
| `chart_sanity.py` | Проверка формы чарта (тип vs поля) |
| `utils.py` | `_extract_json_from_response` |
| `check.py` | Диагностика стека + команды запуска |
| `models/schema.py` | Pydantic-модели (ColumnInfo, TableInfo, SchemaAnalysis, ChartType…) |

## Надёжность

**Saga-откат:** при ошибке на любом шаге generate созданные DataLens-объекты
(connection, charts, dashboard) удаляются автоматически. `cleanup_on_error=False`
отключает откат для отладки.

**Jobs persistence:** `DASHBOARD_JOBS` дампится в `jobs.json` при shutdown uvicorn
и восстанавливается при startup. Незавершённые jobs помечаются `failed`.

**Async psycopg:** все вызовы `psycopg.connect()` обёрнуты в `asyncio.to_thread()`
— event loop uvicorn не блокируется.

## Пайплайн generate (порядок)

```
1. login → ensure workbook → create connection   (DataLens)
2. analyze_schema + sample_rows                  (PostgreSQL, async)
3. resolve_entities (email/проект → ID)          (PostgreSQL, async)
4. decide_chart_count                            (OpenAI)
5. loop: _generate_one_chart → validate → fix    (OpenAI + PostgreSQL)
6. create QL charts                              (DataLens)
7. build_dashboard_data                          (local)
8. create_dashboard                              (DataLens)
9. return result
```

При ошибке на шагах 1–9: `_rollback()` удаляет всё созданное.

## Как код ходит в DataLens

| Операция | Вызов |
|---|---|
| Логин | `POST {auth}/signin` |
| Gateway | `POST /gateway/root/{service}/{action}` |
| Workbook | `us.getWorkbooksList` / `us.createWorkbook` |
| Connection | `bi.createConnection` (`workbook_id`, `raw_sql_level=dashsql`) |
| Создать QL-чарт | `POST /api/charts/v1/charts` |
| Обновить QL-чарт | `POST /api/charts/v1/charts/{id}` |
| Удалить QL-чарт | `DELETE /api/charts/v1/charts/{id}` |
| Создать dashboard | `mix.createDashboardV1` |
| Читать dashboard | `mix.getDashboardV1` |
| Обновить dashboard | `mix.updateDashboardV1` |
| Lock / unlock | `POST/DELETE {us_url}/v1/locks/{entry_id}` |
| Удалить entry | `us._deleteUSEntry` |

## QL-чарт (ql_chart_builder)

Поддерживаемые типы: `line`, `area`, `column`, `bar`, `pie`.

- `line` / `area`: колонка 0 = X (date), остальные = Y
- `column` / `bar`: последнее числовое поле = Y, остальные = X
- `pie`: dimensions + measures, colorsConfig с mountedColors
- `table`: **отключён** (DataLens flatTable → renderer 500)

## Dashboard (dashboard_builder)

Сетка 36 колонок:
- `line` / `area` / full-width: `w=34 h=20`
- `column` / `bar` / `pie` парами: `w=15 h=16`
- Заголовок секции: `w=12 h=2`, note справа: `w=22 h=2`
- `hideDashTitle: true` — Laravel рендерит заголовок сам
