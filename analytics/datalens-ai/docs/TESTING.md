# Тестирование

Сверено с кодом **2026-08-22**.

Запускать из корня `datalens-ai` с активированным venv:

```bash
cd analytics/datalens-ai
source venv/bin/activate
```

---

## 1. Unit-тесты (pytest)

Не требуют БД, DataLens или OpenAI ключа. Запускаются автономно.

```bash
pip install pytest
python -m pytest tests/unit/ -v
```

Ожидание: **85 passed** (или больше по мере добавления тестов).

| Файл | Что тестирует |
|---|---|
| `test_utils.py` | `_extract_json_from_response` — markdown, plain, пустая строка |
| `test_chart_dedup.py` | SQL и title fingerprint — нормализация, дедупликация |
| `test_chart_sanity.py` | `chart_shape_error` — все типы, граничные случаи |
| `test_chart_contract.py` | `ql_contract_error` — поля, дубли, table disabled |
| `test_sql_validator.py` | `read_only_sql_error` — SELECT/WITH ok, DDL/DML reject |
| `test_dashboard_builder.py` | layout, нет dup ID, `hideDashTitle`, half-width pairs |
| `test_ql_chart_builder.py` | `build_ql_chart` для line/area/column/bar/pie + normalize |

---

## 2. Диагностика стека

```bash
python check_and_run.py
```

Проверяет DataLens UI, datalens-ai FastAPI, OpenAI API key.
Если сервис не запущен — выводит точные команды для запуска.

Ожидание:
```
✅  DataLens UI
✅  datalens-ai (uvicorn)
✅  OpenAI model  (gpt-5.6-luna)
✅  DataLens API  (подключён)
✅  OPENAI_API_KEY задан
```

---

## 3. Проверка /health

```bash
curl -s http://127.0.0.1:8100/health | python3 -m json.tool
```

Ожидание:
```json
{"status": "ok", "llm_server": true, "datalens": true}
```

---

## 4. Smoke-скрипты (требуют живой стек)

| Файл | Что делает | Удаляет объекты? |
|---|---|---|
| `smoke_datalens_client.py` | login, workbook, connection, delete | ✅ да |
| `smoke_ql_chart.py` | один line QL чарт, потом delete | ✅ да |
| `create_ai_dashboard.py` | полный generate — 5–6 чартов | ❌ остаются |
| `test_edit_dashboard.py` | generate + 4 NL-правки (add/reorder/update/delete) | ❌ остаются |

```bash
# Smoke DataLens client (безопасно — удаляет временные объекты)
python tests/smoke_datalens_client.py

# Полный generate
python tests/create_ai_dashboard.py
# → выведет dashboard_id и ссылку на workbook

# Полный edit-цикл
python tests/test_edit_dashboard.py
```

---

## 5. HTTP smoke (curl)

```bash
# Generate через jobs API
curl -s -X POST http://127.0.0.1:8100/api/dashboard-jobs \
  -H 'Content-Type: application/json' \
  -d '{"operation":"generate","message":"Покажи активность пользователей"}'
# → {"job_id":"...","status":"queued",...}

# Статус job
curl -s http://127.0.0.1:8100/api/dashboard-jobs/<job_id> | python3 -m json.tool

# Validate SQL
curl -s -X POST http://127.0.0.1:8100/api/validate-sql \
  -H 'Content-Type: application/json' \
  -d '{"db_url":"postgresql://user:pass@127.0.0.1:5432/db","sql":"SELECT 1 AS n"}'

# Analyze schema
curl -s -X POST 'http://127.0.0.1:8100/api/analyze?db_url=postgresql://user:pass@127.0.0.1:5432/db'
```

Если `SERVICE_API_KEY` задан — добавить `-H 'X-API-Key: <key>'`.

---

## 6. Чек-лист качества дашборда

- [ ] Каждый чарт вернул ≥ 1 строку при валидации SQL
- [ ] `line`/`area` — первая колонка `date`, есть числовая метрика
- [ ] `column`/`bar` — последнее поле числовое, категории в X
- [ ] `pie` — 2–7 секторов, `dimensions` и `measures` заполнены
- [ ] Нет дублирующихся SQL или заголовков
- [ ] Connection создан в workbook, не в корне
- [ ] `raw_sql_level=dashsql` в connection
- [ ] После edit нет висячих ID в layout

---

## 7. Известные ограничения DataLens QL

| Проблема | Статус |
|---|---|
| Pie-чарт без цветов секторов | Ограничение DataLens QL — `mountedColors` не применяется рендерером. Данные передаются корректно. |
| `flatTable` renderer 500 | QL table-чарт падает с renderer 500. Заменяется на `bar` автоматически. |
