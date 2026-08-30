# LLM и промпты

Сверено с кодом **2026-08-22**.

## Модель

**OpenAI API**, модель по умолчанию: `gpt-5.6-luna`.

Конфигурация в `config.py` / `.env`:

| Переменная | Default | Описание |
|---|---|---|
| `OPENAI_API_KEY` | — | Обязателен |
| `OPENAI_BASE_URL` | `https://api.openai.com/v1` | Endpoint |
| `OPENAI_MODEL` | `gpt-5.6-luna` | Модель |
| `OPENAI_TIMEOUT` | `180` | Таймаут запроса (сек) |
| `OPENAI_MAX_OUTPUT_TOKENS` | `4096` | Максимум токенов в ответе |

## Клиент (`llm_client.py`)

- Async httpx-клиент к `/chat/completions`
- `chat_json()` — structured JSON output (`response_format: json_schema`)
- `chat()` — свободный текст
- `_post_with_retry()` — retry с backoff 1s/2s/4s при HTTP 429
- `health_check()` — дешёвый `POST /chat/completions` с `max_completion_tokens=1`

**Важно:** `reasoning_effort` **не передаётся** — это параметр o-series моделей,
gpt-5.6-luna его не принимает.

## Промпты

Все промпты — Markdown-файлы в `prompts/`. Плейсхолдеры: `{schema}`, `{message}`,
`{avoid}`, `{sql}`, `{error}`, `{charts}`, `{entity_context}`.

| Файл | Кто вызывает | Задача |
|---|---|---|
| `prompts/chart_one.md` | `sql_pipeline._generate_one_chart` | Один чарт за запрос |
| `prompts/chart_fix.md` | `sql_pipeline._validate_and_fix`, `ai_editor` | Починить SQL |
| `prompts/dashboard_count.md` | `sql_pipeline.decide_chart_count` | Число чартов |
| `prompts/editor_plan.md` | `ai_editor.edit_dashboard` | Список actions |

## Принцип «один чарт за запрос»

`sql_pipeline.plan_and_validate_dashboard`:

1. LLM выбирает число чартов (`dashboard_count.md`)
2. Цикл: LLM генерирует 1 чарт → `EXPLAIN` + `LIMIT 5` → при ошибке фикс
3. Дедупликация по SQL-fingerprint и title-fingerprint
4. Density check: pie 2–7 строк, bar/column ≤ 20, line/area ≤ 60
5. Shape check: line начинается с `date`, bar/pie заканчивается числом

## Schema context

В промпт передаётся схема из `sql_pipeline._format_schema()`:
- До 8 ключевых таблиц (+ обязательные: `users`, `projects`, `submissions`, `reviews`, `user_activities`)
- До 12 колонок каждой таблицы с PK/FK-флагами
- 2 реальных строки из таблицы (sample rows, без sensitive-полей)
- FK-связи

Это позволяет gpt-5.6-luna писать точный SQL без выдумывания полей.

## Structured output

`chat_json()` использует `response_format: json_schema` с `strict: true`.
Ответ всегда валидный JSON соответствующей схемы — не нужен `_extract_json_from_response`
для structured-запросов. Для `chat()` (editor_plan.md) — используется `utils._extract_json_from_response`
как fallback.

## Agent router (`/api/agent/respond`)

Системный промпт определяет tool:

| Tool | Условие |
|---|---|
| `chat` | Приветствие, вопрос, объяснение |
| `inspect_dashboard` | Объяснить текущий dashboard без изменений |
| `generate_dashboard` | Dashboard нет, пользователь просит визуализацию |
| `edit_dashboard` | Dashboard есть, пользователь просит изменения |
| `clear_dashboard` | Явный запрос очистки |
| `replace_dashboard` | Очистить и создать новый в одном запросе |
