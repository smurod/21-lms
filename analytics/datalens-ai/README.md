# datalens-ai

FastAPI-микросервис, который превращает текстовый запрос в готовый DataLens-dashboard.

**Версия:** 0.2.0  
**Стек:** Python 3.11+, FastAPI, psycopg (PostgreSQL), универсальный LLM-слой (OpenAI-совместимые / Anthropic / Google Gemini)  
**Порт:** 8100

---

## Что делает

1. Принимает текстовый запрос на русском («Покажи активность пользователей за месяц»)
2. Анализирует схему PostgreSQL-базы (таблицы, FK, реальные данные)
3. Генерирует SQL для каждого чарта через LLM (провайдер настраивается в `.env`)
4. Валидирует SQL на реальной БД (`EXPLAIN` + `LIMIT 5`)
5. Создаёт QL-чарты и dashboard в Yandex DataLens
6. Возвращает прямую ссылку на dashboard

Поддерживаемые типы чартов: `line`, `area`, `column`, `bar`, `pie`.

---

## Быстрый старт

### 1. Зависимости

```bash
cd analytics/datalens-ai
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### 2. Конфигурация

```bash
cp .env.example .env
```

Заполнить в `.env`:

| Переменная | Описание |
|---|---|
| `LLM_PROVIDER` | Семейство API: `openai` / `anthropic` / `google` |
| `LLM_API_KEY` | Ключ выбранного провайдера |
| `LLM_BASE_URL` | Свой endpoint (OpenRouter, DeepSeek, Ollama, vLLM…). Пусто = официальный API провайдера |
| `LLM_MODEL` | Модель. Пусто = дефолт провайдера |
| `DB_URL` | PostgreSQL URL, который видит сам сервис (`postgresql://user:pass@127.0.0.1:5432/db`) |
| `DATALENS_BASE_URL` | URL DataLens UI (по умолчанию `http://localhost:8085`) |
| `DATALENS_DB_HOST` | Хост БД, который видит DataLens из Docker (обычно `172.17.0.1`) |
| `SERVICE_API_KEY` | Общий секрет с Laravel (X-API-Key). Пусто = auth отключён |

### LLM-провайдеры

| Провайдер | `LLM_PROVIDER` | `LLM_BASE_URL` | Пример `LLM_MODEL` |
|---|---|---|---|
| OpenAI | `openai` | _(пусто)_ | `gpt-5.6-luna` |
| GLM (Zhipu, CN) | `openai` | `https://open.bigmodel.cn/api/paas/v4` | `glm-5.3-flash` |
| GLM (Z.ai, intl) | `openai` | `https://api.z.ai/api/paas/v4` | `glm-5.3-flash` |
| OpenRouter | `openai` | `https://openrouter.ai/api/v1` | `anthropic/claude-sonnet-4.6` |
| DeepSeek | `openai` | `https://api.deepseek.com/v1` | `deepseek-chat` |
| Ollama (локально) | `openai` | `http://127.0.0.1:11434/v1` | `qwen3:14b` (ключ не нужен) |
| vLLM / LM Studio | `openai` | `http://127.0.0.1:8000/v1` | имя модели на сервере |
| Anthropic Claude | `anthropic` | _(пусто)_ | `claude-sonnet-4-6` |
| Google Gemini | `google` | _(пусто)_ | `gemini-2.5-flash` |

Structured JSON для каждого семейства включается автоматически: `json_schema` (OpenAI-совместимые), `tool_choice` (Anthropic), `responseSchema` (Gemini). Если endpoint не поддерживает `response_format`, сервис автоматически повторяет запрос обычным текстом и извлекает JSON из ответа.

**Reasoning-модели** (GLM, DeepSeek-R1, Qwen3, o-series) тратят токены на внутренние «размышления» и могут вернуть пустой `content` при малом бюджете. Клиент автоматически делает один повтор с бюджетом ×4 — настройка не требуется. Чтобы ускорить ответы и выключить «мышление» нативно для провайдера, задайте `LLM_EXTRA_BODY`:

```env
# GLM (Zhipu / Z.ai)
LLM_EXTRA_BODY={"thinking":{"type":"disabled"}}
# Qwen3 (vLLM)
LLM_EXTRA_BODY={"enable_thinking":false}
```

### 3. Запуск

```bash
uvicorn main:app --host 0.0.0.0 --port 8100
```

Проверка:
```bash
curl http://127.0.0.1:8100/health
# {"status":"ok","llm_server":true,"datalens":true}
```

Диагностика всего стека:
```bash
python check.py          # статус сервисов + команды запуска
python check.py --stop   # команды остановки
```

### 4. Запуск через Docker

```bash
cp .env.example .env   # заполнить переменные
docker compose up -d --build
docker compose logs -f
```

---

## API

Базовый URL: `http://127.0.0.1:8100`

| Метод | Путь | Описание |
|---|---|---|
| `GET` | `/health` | Статус сервиса, OpenAI и DataLens |
| `POST` | `/api/dashboard-jobs` | Создать фоновый job (generate / edit) |
| `GET` | `/api/dashboard-jobs/{id}` | Статус job |
| `GET` | `/api/dashboard-jobs/{id}/events` | SSE-стрим прогресса |
| `POST` | `/api/agent/respond` | AI-роутер: chat / generate / edit |
| `POST` | `/api/dashboards/generate` | Синхронная генерация (старый контракт) |
| `GET` | `/api/dashboards/{id}` | Получить dashboard из DataLens |
| `POST` | `/api/dashboards/{id}/edit` | Синхронное редактирование (старый контракт) |
| `GET` | `/api/charts/{id}` | Получить QL-чарт |
| `POST` | `/api/validate-sql` | Проверить SQL на реальной БД |

Полная документация: `/docs` (Swagger UI).

### Пример: создать dashboard

```bash
curl -s -X POST http://127.0.0.1:8100/api/dashboard-jobs \
  -H "Content-Type: application/json" \
  -H "X-API-Key: <SERVICE_API_KEY>" \
  -d '{"operation":"generate","message":"Покажи активность и топ пользователей"}'
```

Ответ содержит `job_id`. Статус:
```bash
curl http://127.0.0.1:8100/api/dashboard-jobs/<job_id>
```

---

## Структура проекта

```
analytics/datalens-ai/
├── main.py               # FastAPI, endpoints, job-система, SSE, X-API-Key
├── config.py             # pydantic-settings из .env
├── dashboard_service.py  # Полный пайплайн generate (saga-откат при ошибке)
├── sql_pipeline.py       # Генерация чартов по одному, валидация SQL
├── ai_editor.py          # Редактор dashboard: add/update/delete/reorder
├── datalens_client.py    # HTTP-клиент DataLens (login, workbook, QL, dash)
├── ql_chart_builder.py   # JSON QL-чарта из шаблонов samples/
├── dashboard_builder.py  # JSON dashboard, сетка 36 колонок
├── schema_analyzer.py    # Анализ PostgreSQL information_schema + sample rows
├── sql_validator.py      # EXPLAIN + LIMIT 5 (non-blocking)
├── llm_client.py         # OpenAI Chat Completions API + retry 429
├── entity_resolver.py    # Резолв email/проект → реальные ID из БД
├── database_support.py   # Проверка типа БД (только PostgreSQL)
├── chart_contract.py     # Валидация полей чарта до DataLens
├── chart_dedup.py        # Дедупликация SQL и заголовков
├── chart_sanity.py       # Проверка формы чарта (тип vs поля)
├── utils.py              # Утилиты (extract_json_from_response)
├── check.py      # Диагностика стека
├── models/schema.py      # Pydantic-модели
├── prompts/              # LLM-промпты (Markdown)
│   ├── chart_one.md
│   ├── chart_fix.md
│   ├── dashboard_count.md
│   └── editor_plan.md
├── samples/              # Эталонные JSON QL-чартов (шаблоны)
├── tests/                # Smoke-скрипты
├── requirements.txt
├── Dockerfile
├── docker-compose.yml
└── .env.example
```

---

## Конфигурация X-API-Key

Для защиты API между Laravel и datalens-ai:

```bash
# Генерация ключа
python3 -c "import secrets; print(secrets.token_hex(32))"

# analytics/datalens-ai/.env
SERVICE_API_KEY=<generated_key>

# Laravel .env
DATALENS_AI_API_KEY=<same_generated_key>
```

Эндпоинты `/health` и `/` работают без ключа (для healthcheck).

---

## Два адреса одной БД

DataLens работает в Docker и видит хост-машину по другому адресу:

```
# analytics/datalens-ai/.env
DB_URL=postgresql://user:pass@127.0.0.1:5432/db      # Python видит БД так
DATALENS_DB_HOST=172.17.0.1                           # DataLens видит БД так
```

---

## Требования

- Python 3.11+
- PostgreSQL (анализ схемы и валидация SQL только для PostgreSQL)
- Yandex DataLens (open-source, Docker) — `analytics/datalens/`
- LLM API ключ любого поддерживаемого провайдера (`LLM_PROVIDER` + `LLM_API_KEY` в `.env`)
