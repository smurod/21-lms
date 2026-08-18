# LLM и промпты

Сверено с `llm_client.py`, `start_llm.py`, `sql_pipeline.py`, `ai_editor.py` **2026-08-14**.

## Модель

Основная модель: **Qwen 3.6 35B A3B** (квантизация Q4), alias API `qwen3.6-coding`.

Запуск: `python start_llm.py` → бинарь `llama-server`.

Параметры по умолчанию в `start_llm.py`:

| Параметр | Default / env |
|---|---|
| путь модели | `LLM_MODEL` или `/home/smurod_8880/models/gguf/qwen3_6_q4.gguf` |
| chat template | `LLM_CHAT_TEMPLATE` или `/home/smurod_8880/patched_qwen_template.jinja` |
| host/port | `0.0.0.0:8001` |
| alias | `qwen3.6-coding` |
| `-c` | 16000 |
| `-ngl` | 999 |
| `--n-cpu-moe` | 26 |
| `--batch-size` / `--ubatch-size` | 8192 / 4096 |
| `-t` | 6 |
| KV cache | `q4_0` / `q4_0` |
| `--flash-attn` | on |

**Конфликт имён:** в `start_llm.py` переменная `LLM_MODEL` — это **путь к .gguf**.  
В `config.py` / `.env.example` `LLM_MODEL` — это **alias** `qwen3.6-coding`.  
В `.env.example` путь вынесен в `LLM_MODEL_PATH`, но `start_llm.py` читает именно `LLM_MODEL` (и ещё `LLM_CHAT_TEMPLATE`). На другой машине пути нужно поправить.

`llama-server` ищется в `PATH`, иначе `/home/smurod_8880/llama.cpp/build/bin/llama-server`.

## Клиент

`llm_client.LLMClient`:

- `POST {LLM_BASE_URL}/v1/chat/completions`;
- health: `GET {LLM_BASE_URL}/health`;
- из `Settings`: `llm_base_url`, `llm_model`, `llm_timeout` (180), `llm_max_tokens` (4096);
- temperature 0.2, top_p 0.95, top_k 40, repeat_penalty 1.05;
- в каждом chat-запросе: `"chat_template_kwargs": {"enable_thinking": false}`.

Без `enable_thinking: false` Qwen3 тратит лимит на `reasoning_content`, `content` пустой, JSON обрывается.

`chat_stream()` существует, но `enable_thinking` туда **не** передаётся. Публичный API стрим не использует.

## Промпты (рабочие)

Текущий пайплайн читает **только** Markdown:

| Файл | Кто вызывает | Задача |
|---|---|---|
| `prompts/chart_one.md` | `sql_pipeline._generate_one_chart` | один график |
| `prompts/chart_fix.md` | `sql_pipeline` и `ai_editor` | починить SQL |
| `prompts/editor_plan.md` | `ai_editor.edit_dashboard` | список actions |

Плейсхолдеры: `{schema}`, `{message}`, `{avoid}`, `{sql}`, `{error}`, `{charts}`.

Файлов `prompts/*.txt` в дереве **нет**. `sql_generator.py` всё ещё пытается грузить `sql_generation.txt` и `dashboard_planning.txt`, но **этот модуль не вызывается** из `main.py` / `dashboard_service.py`.

## Принцип «один график за запрос»

`sql_pipeline.plan_and_validate_dashboard`:

- в промпт идут до **6** ключевых таблиц, до **12** колонок, PK/FK и связи;
- за раз LLM возвращает один JSON-график;
- типы только `line` / `column` / `table`;
- SQL: `EXPLAIN` + `LIMIT 5`; 0 строк = ошибка;
- до 2 попыток починки;
- цель — 3 валидных графика, максимум 8 попыток генерации;
- анти-повтор по уже пробованным title.

## Известные факты

- Маленькая модель (`qwen3_final_merged.gguf` в `.env.example`) плохо держит многошаговый edit.
- Без реальной схемы модель выдумывает колонки — поэтому в `.md` промпты схема подставляется явно.
- `examples/sql_examples.json` используется только мёртвым `sql_generator.py`.
