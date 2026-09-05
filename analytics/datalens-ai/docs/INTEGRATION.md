# DataLens + datalens-ai — интеграция

Сверено с кодом **2026-08-22** (`main.py` 0.2.0).

---

## 1. Что есть что

### DataLens (платформа)

Готовый open-source BI (Яндекс), Docker Compose. SQL и естественный язык не генерирует сам.

```text
analytics/datalens/    # Docker-инфраструктура DataLens
```

### datalens-ai (наш сервис)

Python/FastAPI `:8100`. Читает схему PostgreSQL, генерирует SQL через OpenAI API,
валидирует SQL, создаёт QL-чарты и dashboard в DataLens, редактирует дашборд словами.

```text
analytics/datalens-ai/
```

---

## 2. Структура внутри 21-lms

```text
21-lms/
├── app/ ... routes/ resources/   # Laravel, не трогаем этим сервисом
└── analytics/
    ├── datalens/                 # платформа DataLens (docker-compose)
    └── datalens-ai/
        ├── main.py               # FastAPI, endpoints, X-API-Key, SSE, jobs
        ├── config.py             # pydantic-settings из .env
        ├── dashboard_service.py  # generate с saga-откатом
        ├── sql_pipeline.py       # 1 чарт за LLM-запрос, валидация
        ├── ai_editor.py          # add/update/delete/reorder
        ├── datalens_client.py    # HTTP-клиент DataLens
        ├── ql_chart_builder.py   # JSON QL-чарта
        ├── dashboard_builder.py  # JSON dashboard, сетка 36
        ├── schema_analyzer.py    # information_schema + sample rows
        ├── sql_validator.py      # EXPLAIN + LIMIT (asyncio.to_thread)
        ├── llm_client.py         # OpenAI API + retry 429
        ├── entity_resolver.py    # email/проект → реальные ID
        ├── utils.py              # _extract_json_from_response
        ├── check.py      # диагностика стека
        ├── models/schema.py      # Pydantic-модели
        ├── prompts/*.md          # LLM-промпты
        ├── samples/              # шаблоны QL-чартов
        ├── tests/                # smoke-скрипты + unit-тесты
        ├── Dockerfile
        ├── docker-compose.yml
        ├── requirements.txt
        └── .env.example
```

---

## 3. Требования к машине

- Docker + Compose (для DataLens)
- Python 3.11+
- PostgreSQL (анализ схемы поддерживает только PostgreSQL)
- OpenAI API ключ
- Порты: `8085` UI, `8088` auth, `8100` datalens-ai

---

## 4. DataLens

```bash
cd analytics/datalens
UI_PORT=8085 docker compose up -d
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8085
# ожидаем 200
```

Auth публикуется на хосте: `127.0.0.1:8088`.
`datalens-ai` логинится: `POST http://127.0.0.1:8088/signin`.

Workbook: сервис ищет `DATALENS_WORKBOOK_TITLE` (default `AI Generated`), создаёт если нет.

---

## 5. datalens-ai

```bash
cd analytics/datalens-ai
python3 -m venv venv && source venv/bin/activate
pip install -r requirements.txt
cp .env.example .env
# заполнить OPENAI_API_KEY, DB_URL, DATALENS_DB_HOST и др.
uvicorn main:app --host 0.0.0.0 --port 8100
```

Ключевые переменные (`.env.example` и `config.py`):

| Переменная | Описание |
|---|---|
| `OPENAI_API_KEY` | Ключ OpenAI API — обязателен |
| `OPENAI_MODEL` | Модель (default `gpt-5.6-luna`) |
| `DB_URL` | PostgreSQL URL для Python (`127.0.0.1`) |
| `DATALENS_BASE_URL` | URL DataLens UI (default `http://localhost:8085`) |
| `DATALENS_DB_HOST` | Хост БД для DataLens из Docker (обычно `172.17.0.1`) |
| `SERVICE_API_KEY` | Shared secret с Laravel (X-API-Key). Пусто = auth отключён |

Диагностика стека:
```bash
python check.py          # статус + команды запуска
python check.py --stop   # команды остановки
```

---

## 6. Авторизация Laravel → datalens-ai (X-API-Key)

Все endpoint'ы кроме `/health` и `/` требуют заголовок `X-API-Key`.

```bash
# Генерация ключа
python3 -c "import secrets; print(secrets.token_hex(32))"

# analytics/datalens-ai/.env
SERVICE_API_KEY=<generated_key>

# Laravel .env
DATALENS_AI_API_KEY=<same_generated_key>
```

`DataLensAiService.php` автоматически передаёт заголовок если `DATALENS_AI_API_KEY` заполнен.
Пустой ключ с обеих сторон = auth отключён (локальная разработка).

---

## 7. Jobs persistence

`DASHBOARD_JOBS` дампится в `jobs.json` при shutdown uvicorn и восстанавливается при startup.
Незавершённые jobs при рестарте помечаются `failed` — Laravel получит корректный статус
вместо 404.

`jobs.json` добавлен в `.gitignore`, не коммитится.

---

## 8. Пайплайн generate

1. Login + `ensure_workbook` + `create_database_connection` (`raw_sql_level=dashsql`)
2. `schema_analyzer.analyze` — PostgreSQL information_schema + COUNT + sample rows (async)
3. `resolve_entities_async` — email/проект → реальные ID (async)
4. `plan_and_validate_dashboard` — LLM выбирает число чартов, генерирует по одному, EXPLAIN + LIMIT
5. Создать QL-чарты + собрать dashboard
6. **При ошибке на любом шаге** — saga-откат: удалить connection, чарты, dashboard

### Edit — `ai_editor.edit_dashboard`

Загружает дашборд → `editor_plan.md` → actions `add|update|delete|reorder|update_section`
→ валидация SQL → пересборка layout → `updateDashboardV1`.

---

## 9. Laravel — контракт

Файл `docs/API.md`. Нельзя ломать поля:
`dashboard_id`, `dashboard_url`, `embed_url`, `charts`, `added`, `updated`, `deleted`, `sections`.

Минимальная схема интеграции:
1. `POST /api/dashboard-jobs` `{"operation":"generate","message":"..."}` → `job_id`
2. `GET /api/dashboard-jobs/{job_id}/events` → SSE прогресс
3. При `status=completed` → сохранить `result.dashboard_id`, `result.embed_url`
4. `<iframe src="{embed_url}">` в UI

---

## 10. Типичные проблемы

| Симптом | Причина |
|---|---|
| `DashSQL API is disallowed` | нет `raw_sql_level=dashsql` в connection |
| Пустой график | SQL вернул 0 строк; пайплайн отбрасывает |
| DataLens не видит БД | нужен `DATALENS_DB_HOST=172.17.0.1` (хост из Docker) |
| `ENTRY_IS_LOCKED` | DataLens UI держит lock; ai_editor retry 3× через 2–4–6s |
| 403 от datalens-ai | нет или неверный `X-API-Key` заголовок |
| `/health` → `llm_server: false` | OpenAI ключ не задан или недоступен |

---

## 11. Чек-лист запуска

- [ ] DataLens `:8085` и auth `:8088` запущены
- [ ] `analytics/datalens-ai/.env` заполнен (OPENAI_API_KEY, DB_URL)
- [ ] `uvicorn main:app --host 0.0.0.0 --port 8100`
- [ ] `curl http://127.0.0.1:8100/health` → `llm_server: true, datalens: true`
- [ ] `python tests/smoke_datalens_client.py` — OK
- [ ] `python tests/create_ai_dashboard.py` — DONE + ссылка на дашборд
