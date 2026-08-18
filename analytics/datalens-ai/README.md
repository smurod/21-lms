# DataLens AI Service

AI-агент для автоматического создания дашбордов в Yandex DataLens.

## Возможности

- 🔍 **Анализ схемы БД** — автоматическое определение ключевых таблиц
- 🤖 **LLM-генерация SQL** — создание запросов через MOE 35B модель
- 📊 **Планирование дашбордов** — AI составляет план из 5-7 чартов
- ✅ **Валидация SQL** — проверка синтаксиса перед выполнением
- 💬 **Chat интерфейс** — общение с AI-ассистентом

## Архитектура

```
┌─────────────────────────────────────────┐
│  DataLens AI Service (FastAPI, :8100)   │
│  ├── /api/analyze     — анализ схемы    │
│  ├── /api/generate-sql — генерация SQL  │
│  ├── /api/create-dashboard — создание   │
│  ├── /api/validate-sql  — валидация     │
│  └── /api/chat        — чат с AI        │
└────────────┬────────────────────────────┘
             │
    ┌─────────────────┐
    ↓                  ↓
llama.cpp:8001    DataLens:8085
(MOE 35B LLM)    (REST API)
```

## Установка

```bash
cd /home/smurod_8880/exam2/datalens-ai
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

## Запуск

```bash
# Убедитесь, что llama.cpp запущен на порту 8001
# Затем:
uvicorn main:app --host 0.0.0.0 --port 8100 --reload
```

## API Endpoints

### GET /health
Проверка работоспособности сервиса.

### POST /api/analyze
Анализ схемы базы данных.

```bash
curl -X POST http://localhost:8100/api/analyze \
  -H "Content-Type: application/json" \
  -d '{"db_url": "postgresql://user:pass@host:5432/db"}'
```

### POST /api/generate-sql
Генерация SQL-запроса для визуализации.

```bash
curl -X POST http://localhost:8100/api/generate-sql \
  -H "Content-Type: application/json" \
  -d '{
    "db_url": "postgresql://user:pass@host:5432/db",
    "table": "users",
    "goal": "Показать динамику регистраций по дням",
    "chart_type": "line"
  }'
```

### POST /api/create-dashboard
Создание дашборда через AI (полный pipeline).

```bash
curl -X POST http://localhost:8100/api/create-dashboard \
  -H "Content-Type: application/json" \
  -d '{
    "db_url": "postgresql://user:pass@host:5432/db",
    "message": "Визуализируй эту БД"
  }'
```

### POST /api/validate-sql
Валидация SQL-запроса.

```bash
curl -X POST http://localhost:8100/api/validate-sql \
  -H "Content-Type: application/json" \
  -d '{
    "db_url": "postgresql://user:pass@host:5432/db",
    "sql": "SELECT * FROM users LIMIT 10"
  }'
```

### POST /api/chat
Чат с AI-ассистентом.

```bash
curl -X POST http://localhost:8100/api/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Какие таблицы самые важные в моей БД?",
    "db_url": "postgresql://user:pass@host:5432/db"
  }'
```

## Структура проекта

```
datalens-ai/
├── main.py                  # FastAPI приложение
├── llm_client.py            # Клиент для llama.cpp
├── schema_analyzer.py       # Анализ схемы БД
├── sql_generator.py         # Генерация SQL через LLM
├── sql_validator.py         # Валидация SQL
├── models/
│   └── schema.py            # Pydantic модели
├── prompts/
│   ├── schema_analysis.txt  # Промпт для анализа схемы
│   ├── sql_generation.txt   # Промпт для генерации SQL
│   └── dashboard_planning.txt # Промпт для планирования
├── examples/
│   ── sql_examples.json    # Few-shot примеры SQL
└── tests/                   # Тесты (будут добавлены)
```

## Требования

- Python 3.13+
- llama.cpp server (MOE 35B) на порту 8001
- PostgreSQL база данных для анализа

## Статус

**Версия:** 0.1.0 (День 1 из 7)

**Готово:**
- ✅ FastAPI приложение
- ✅ LLM клиент (llama.cpp)
- ✅ Schema Analyzer
- ✅ SQL Generator
- ✅ SQL Validator
- ✅ Pydantic модели
- ✅ Промпты для LLM

**Следующие шаги:**
- День 2: Тестирование на реальной БД
- День 3: DataLens API интеграция
- День 4-5: Chat UI widget
- День 6: Edit Dashboard agent
- День 7: Polish & documentation

