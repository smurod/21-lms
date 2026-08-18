# PROJECT INFO — datalens-ai

Актуально **2026-08-14**. Сверено с исходниками в `analytics/datalens-ai/`.  
Это паспорт текущей версии **0.2.0**, не снимок Дня 1.

## Что это

Микросервис FastAPI на порту **8100**: AI-агент над локальным Yandex DataLens (`:8085`).  
Анализирует PostgreSQL, генерирует и чинит SQL через локальную LLM (llama.cpp `:8001`, alias `qwen3.6-coding`), создаёт QL-чарты и дашборд, редактирует дашборд естественным языком.

## Где лежит

| Среда | Путь |
|---|---|
| Ноутбук пользователя | `/home/smurod_8880/projects/21-lms/analytics/datalens-ai` |
| Workspace Arena | `/home/user/analytics/datalens-ai` |
| Платформа DataLens (README) | `.../21-lms/analytics/datalens/` |

Рядом, но **вне** этого сервиса: Laravel `app/`, `reference/`, `sql-mastery/`.

Железо (из ТЗ): Ubuntu 24.04, Legion Pro 7, RTX 5070 Ti 16GB, 32 GB RAM.

## Стек (из `requirements.txt` + кода)

- Python 3.11+/3.13
- fastapi 0.115.6, uvicorn 0.34.0, httpx 0.28.1
- pydantic 2.10.4, pydantic-settings 2.7.1, python-dotenv 1.0.1
- psycopg[binary] 3.2.3 — **единственный рабочий драйвер схемы/SQL**
- sqlalchemy 2.0.36 — в requirements, в пайплайне не используется
- pymysql 1.1.1 — в requirements, **вызовов в коде нет**

Целевая БД: PostgreSQL. MySQL/ClickHouse в enum `DatabaseType` есть, реализации нет.

## Версия и HTTP

`FastAPI(..., version="0.2.0")`.

Публичные (контракт `docs/API.md`):

```text
GET  /health
POST /api/dashboards/generate
GET  /api/dashboards/{id}
POST /api/dashboards/{id}/edit
GET  /api/charts/{id}
POST /api/validate-sql
```

Есть ещё: `GET /`, `POST /api/analyze` (query `db_url`).

Нет в `main.py`: `/api/generate-sql`, `/api/create-dashboard`, `/api/chat`.

## Что работает в коде

- `config.Settings` из `.env`
- `DataLensClient`: login cookies, workbook `AI Generated`, postgres connection `raw_sql_level=dashsql`, QL через `/api/charts/v1/charts`, dash через `mix.*DashboardV1`
- `schema_analyzer`: PostgreSQL information_schema, COUNT(*), skip системных/пустых таблиц, top-10 по score+log10(rows)
- `sql_pipeline`: промпты `.md`, один график за раз, схема в промпте (до 6 таблиц), EXPLAIN + LIMIT 5, 0 строк = fail, 2 retry
- `ql_chart_builder`: line/column/table из `samples/`
- `dashboard_builder`: сетка 36
- `ai_editor`: add/update/delete/reorder + пересборка layout
- `start_llm.py`: запуск llama-server
- тесты-скрипты в `tests/` (не pytest-набор)

## Чего в коде нет / расхождения

1. `chart_count` из HTTP передаётся в пайплайн; без него число charts определяет LLM.
2. `dashboard_url` = URL workbook, не карточки дашборда.
3. `title` в generate-ответе всегда `null`.
4. Нет cleanup connection/charts, если generate упал на середине.
5. Нет MySQL-анализатора, хотя 21-lms на MySQL.
6. Синхронный `psycopg` внутри `async def` (`schema_analyzer`, `sql_validator`).
7. CORS `allow_origins=["*"]` + `allow_credentials=True`.
8. `sql_generator.py` и `prompts/*.txt` — мёртвый контур Дня 1.
9. `models/schema.py`: ChartType всё ещё содержит `pie`/`indicator`; HealthResponse `version=0.1.0` не используется.
10. Конфликт `LLM_MODEL`: alias в `config.py` vs путь gguf в `start_llm.py`.
11. `/api/validate-sql` требует `db_url` в теле (не из `.env`).
12. Нет Dockerfile/Compose для datalens-ai.
13. Нет service-to-service auth.

## Команды проверки

```bash
cd /home/smurod_8880/projects/21-lms/analytics/datalens-ai
python3 -m py_compile main.py config.py datalens_client.py \
  dashboard_service.py sql_pipeline.py ai_editor.py \
  schema_analyzer.py sql_validator.py llm_client.py \
  ql_chart_builder.py dashboard_builder.py start_llm.py

uvicorn main:app --host 0.0.0.0 --port 8100
curl -s http://127.0.0.1:8100/health
curl -s http://127.0.0.1:8100/
```

## Журнал (сжатый)

- 2026-08-09. День 1: каркас FastAPI, LLM, схема, генерация SQL без DataLens-клиента. 4/5 SQL плана были невалидны — схема не попадала в промпт.
- 2026-08-10. Утверждены QL без dataset, workbook `AI Generated`, типы line/column/table. Написаны `config.py`, `datalens_client.py`.
- 2026-08-11. ТЗ обновлено. Пайплайн + editor.
- 2026-08-14. Зеркало в `21-lms/analytics/datalens-ai`. Документы приведены к фактам кода 0.2.0.
