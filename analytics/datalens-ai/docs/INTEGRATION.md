# DataLens + datalens-ai — интеграция

Сверено с кодом **2026-08-14**. Frontend Laravel по-прежнему не обязателен; контракт HTTP — `docs/API.md`.

---

## 1. Что есть что

### DataLens (платформа)

Готовый open-source BI (Яндекс), Docker Compose. Сам SQL и естественный язык не генерирует.

Платформу **не встраиваем** в `app/` Laravel. В `21-lms` она лежит как:

```text
analytics/datalens/
```

(сейчас там оригинальный README DataLens; compose/исходники UI пользователь кладёт сам, upstream не клонируем без команды).

### datalens-ai (наш сервис)

Python/FastAPI `:8100`. Читает схему PostgreSQL, зовёт локальную LLM, валидирует SQL, создаёт connection / QL-чарты / dashboard в DataLens, редактирует дашборд словами.

Путь:

```text
/home/smurod_8880/projects/21-lms/analytics/datalens-ai
```

Workspace: `/home/user/analytics/datalens-ai`.

---

## 2. Структура внутри 21-lms

```text
21-lms/
├── app/ ... routes/ resources/   # Laravel, не трогаем этим сервисом
└── analytics/
    ├── datalens/                 # платформа / README
    └── datalens-ai/
        ├── main.py
        ├── config.py
        ├── datalens_client.py
        ├── schema_analyzer.py
        ├── sql_pipeline.py
        ├── sql_validator.py
        ├── ai_editor.py
        ├── dashboard_service.py
        ├── ql_chart_builder.py
        ├── dashboard_builder.py
        ├── llm_client.py
        ├── start_llm.py
        ├── sql_generator.py      # legacy, не в HTTP-пайплайне
        ├── models/schema.py
        ├── prompts/*.md
        ├── samples/
        ├── tests/
        ├── docs/
        ├── requirements.txt
        ├── .env.example
        └── README.md             # устаревший обзор Дня 1
```

Старый план «класть `datalens/` и `datalens-ai/` в корень Laravel» **отменён**. Место — `analytics/`.

---

## 3. Требования к машине

- Docker + Compose (для DataLens).
- Python 3.11+ (в архиве `.pyc` — 3.13).
- GPU CUDA желателен (Qwen 3.6 35B Q4).
- RAM 32 GB как у целевого ноутбука.
- PostgreSQL, по которому строятся дашборды.
- Порты: `8085` UI, `8088` auth, `8100` datalens-ai, `8001` LLM.

MySQL в `schema_analyzer` / `sql_validator` **не реализован**, хотя `pymysql` есть в `requirements.txt`. Laravel на MySQL ≠ этот сервис умеет MySQL.

---

## 4. DataLens

```bash
cd analytics/datalens   # когда там будет compose
docker compose up -d
curl -I http://localhost:8085
```

Auth должен быть на хосте: `127.0.0.1:8088` (override compose).  
`datalens-ai` логинится так: `POST http://127.0.0.1:8088/signin`.

Workbook: сервис ищет `DATALENS_WORKBOOK_TITLE` (default `AI Generated`) и создаёт, если нет.

---

## 5. datalens-ai

```bash
cd /home/smurod_8880/projects/21-lms/analytics/datalens-ai
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
cp .env.example .env
# поправить пути LLM и пароли БД
```

Ключевые переменные — см. `.env.example` и `config.py`:

- `DATALENS_BASE_URL`, `DATALENS_AUTH_URL`, логин/пароль, `DATALENS_WORKBOOK_TITLE`;
- `DATALENS_DB_HOST/PORT/NAME/USER/PASSWORD` — для Docker;
- `DB_URL` — для Python на хосте;
- `OPENAI_API_KEY`, `OPENAI_BASE_URL`, `OPENAI_MODEL`, `OPENAI_TIMEOUT`, `OPENAI_MAX_OUTPUT_TOKENS`, `OPENAI_REASONING_EFFORT`.

`datalens-ai` использует официальный OpenAI API. Локальный `start_llm.py` сохранён как legacy-инструмент и не нужен для рабочего AI pipeline.

```bash
python start_llm.py
curl http://127.0.0.1:8001/health

uvicorn main:app --host 0.0.0.0 --port 8100
curl http://127.0.0.1:8100/health
```

---

## 6. Пайплайн (как в коде)

### Generate — `dashboard_service.create_ai_dashboard`

1. Login + `ensure_workbook` + `create_database_connection` с типом из `DATALENS_DB_TYPE` (`raw_sql_level=dashsql`).
2. `schema_analyzer.analyze` — PostgreSQL, COUNT(*), top-10 непустых таблиц.
3. `sql_pipeline.plan_and_validate_dashboard` — 3 графика, схема в промпте, EXPLAIN + LIMIT 5, retry.
4. `ql_chart_builder` + `POST /api/charts/v1/charts`.
5. `dashboard_builder` + `mix.createDashboardV1`.
6. Ответ: `dashboard_id`, `dashboard_url` (= URL workbook), `workbook_id`, `connection_id`, `charts`.

HTTP-поле `chart_count` передаётся в pipeline; при отсутствии LLM выбирает необходимое число charts по запросу и доступной схеме.

### Edit — `ai_editor.edit_dashboard`

Загружает дашборд и SQL чартов → `editor_plan.md` → actions `add|update|delete|reorder` → валидация новых SQL → пересборка layout → `updateDashboardV1`.

---

## 7. Laravel

Контракт: `docs/API.md`. Не ломать поля `dashboard_id`, `dashboard_url`, `charts`, `added`, `updated`, `deleted`, `sections`.

Минимальная схема:

1. пункт меню;
2. `POST /api/dashboards/generate` → сохранить id/url;
3. `POST /api/dashboards/{id}/edit`;
4. iframe на `dashboard_url`.

Интеграцию **не начинать**, пока не решена стратегия MySQL (A/B/C из handoff).

---

## 8. Типичные проблемы (подтверждены кодом)

| Симптом | Причина в коде / конфиге |
|---|---|
| DashSQL API is disallowed | нет `raw_sql_level=dashsql` |
| пустой график | 0 строк; пайплайн такие SQL отбрасывает |
| обрезанный JSON LLM | нужен `enable_thinking: false` + один график за запрос |
| объекты в корне, не в workbook | connection без `workbook_id` |
| Duplicated id | коллизия layout id (сейчас 8-символьный генератор) |
| DataLens не видит БД на 127.0.0.1 | нужен `DATALENS_DB_HOST=172.17.0.1` |
| `/api/validate-sql` 422 | `db_url` обязателен в теле |
| `/api/analyze` 422 | `db_url` — query-параметр, не JSON |

---

## 9. Чек-лист

- [ ] DataLens `:8085` и auth `:8088`
- [ ] `analytics/datalens-ai/.env` заполнен
- [ ] LLM `:8001/health`
- [ ] `uvicorn` `:8100/health` → `llm_server` и `datalens` true
- [ ] `python tests/smoke_datalens_client.py`
- [ ] `python tests/create_ai_dashboard.py` или `POST /api/dashboards/generate`

---

## 10. Что уже сделано vs старый текст

Раньше INTEGRATION писал «завернуть функции в HTTP». Это **уже сделано** в `main.py` 0.2.0.

Ещё не сделано:

- cleanup объектов DataLens при ошибке посередине generate;
- MySQL;
- использование `chart_count`;
- KPI/pie/фильтры;
- service-to-service auth Laravel → datalens-ai;
- Dockerfile/Compose для самого datalens-ai.
