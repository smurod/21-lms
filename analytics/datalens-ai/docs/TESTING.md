# Тестирование

Сверено с `tests/` и `main.py` **2026-08-14**.  
Это **скрипты**, не pytest-сьюит. Запускать из корня `datalens-ai` (чтобы импорты и `.env` нашлись):

```bash
cd /home/smurod_8880/projects/21-lms/analytics/datalens-ai
source venv/bin/activate
```

## 1. Инфраструктура

```bash
curl -I http://localhost:8085
curl -s http://localhost:8088
python start_llm.py
curl http://localhost:8001/health
uvicorn main:app --host 0.0.0.0 --port 8100
curl http://localhost:8100/health
```

Ожидание `/health`:

```json
{"status":"ok","llm_server":true,"datalens":true}
```

## 2. Скрипты в `tests/`

| Файл | Что делает | Удаляет объекты? |
|---|---|---|
| `smoke_datalens_client.py` | login, workbook, временный connection, delete | да |
| `smoke_ql_chart.py` | один line QL по `xp_transactions`, потом delete chart+connection | да |
| `create_ai_dashboard.py` | полный `create_ai_dashboard` | **нет** |
| `test_edit_dashboard.py` | create + 4 NL-правки (add, reorder, update, delete) | **нет** |
| `create_visible_dashboard.py` | видимый дашборд (ручной/демо) | смотреть скрипт |
| `create_visible_artifacts.py` | видимые артефакты | смотреть скрипт |
| `export_demo_dashboard.py` | выгрузка demo | нет |
| `dump_dashboards_from_result.py` | читает два `dashboard_id` из `result.txt` и валидирует SQL | нет |
| `inspect_ai_charts.py` | инспекция чартов | нет |

`tests/__init__.py` пустой.

## 3. Полный цикл editor

```bash
python tests/test_edit_dashboard.py
```

Факт кода (не «ждёт 30 секунд»):

1. `create_ai_dashboard` с сообщением про активность/отзывы;
2. пауза **5 секунд** между шагами;
3. запросы:
    - добавить таблицу топ-10 по XP (`users` + `xp_transactions`);
    - переместить её в первую секцию на первое место;
    - заменить первый график на submissions по статусу (column);
    - удалить график про средний балл отзывов/ревью.

Ожидание: печать `added` / `updated` / `deleted`, нет traceback, дашборд не пустой.

## 4. HTTP-смоук

```bash
curl -s -X POST http://127.0.0.1:8100/api/dashboards/generate \
  -H 'Content-Type: application/json' \
  -d '{"message":"Краткий дашборд по активности пользователей"}'

curl -s http://127.0.0.1:8100/api/dashboards/<id>
```

Validate SQL (нужен `db_url` в теле):

```bash
curl -s -X POST http://127.0.0.1:8100/api/validate-sql \
  -H 'Content-Type: application/json' \
  -d '{"db_url":"postgresql://...","sql":"SELECT 1 AS n"}'
```

Analyze (query, не JSON-body):

```bash
curl -s -X POST 'http://127.0.0.1:8100/api/analyze?db_url=postgresql://...'
```

## 5. Чек-лист качества дашборда

- [ ] Каждый график вернул ≥ 1 строку на этапе валидации.
- [ ] Нет пустых column (метрика на Y).
- [ ] `axisModeMap` использует реальные имена полей.
- [ ] После edit нет висячих id в layout.
- [ ] Connection в workbook, не в корне.
- [ ] `raw_sql_level=dashsql`.
- [ ] LLM вернула JSON без markdown-обёртки (пайплайн умеет вырезать, но лучше без неё).
- [ ] `kind` только `line` / `column` / `table`.
