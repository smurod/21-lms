# 21 LMS — Learning Management System for School 21

[![PHP](https://img.shields.io/badge/PHP-8.3-blue)](https://php.net)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-red)](https://laravel.com)
[![Docker](https://img.shields.io/badge/docker-compose%20v2-2496ED)](https://docs.docker.com/compose/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)

LMS для School 21: геймификация (XP/уровни), пир-ревью, автотесты, интеграция
GitLab и **AI-аналитика** — текстовый запрос превращается в готовый dashboard
в Yandex DataLens.

## Архитектура

```
                       ┌─────────────────────────── host ───────────────────────────┐
                       │                                                            │
 браузер ──:80────────▶ nginx ──fastcgi──▶ app (php-fpm) ──▶ db  (PostgreSQL 16)   │
    │                      │                    │    └──▶ redis                    │
    │                      │                    ├──▶ queue   (queue:work)          │
    │                      │                    └──▶ scheduler (schedule:work)    │
    │                      │                                                      │
    ├──:8100─────────────▶ datalens-ai (FastAPI) ──▶ db / LLM API                  │
    │                          │                                                   │
    └──:8085─────────────▶ DataLens OSS (ui, auth, us, control-api,                │
                            data-api, meta-manager, temporal, postgres)            │
                       └────────────────────────────────────────────────────────────┘
```

| Компонент | Порт | Что это |
|---|---|---|
| **nginx** | `:80` (APP_PORT) | Laravel UI + admin-панель |
| **app** | — | php-fpm 8.3, Laravel 13, Jetstream/Fortify |
| **db** | `127.0.0.1:5432` | PostgreSQL 16 — данные LMS |
| **redis** | `127.0.0.1:6379` | готов к cache/queue (сейчас всё в БД) |
| **queue** | — | `php artisan queue:work` |
| **scheduler** | — | `php artisan schedule:work` |
| **datalens-ai** | `:8100` | FastAPI: текст → SQL (LLM) → QL-чарты → dashboard |
| **DataLens UI** | `:8085` (UI_PORT) | Yandex DataLens OSS — витрина дашбордов |

---

## Быстрый старт (Docker — одна команда)

Требования: **Docker 24+** и **Docker Compose v2.24+** (`docker compose version`).

```bash
git clone https://github.com/smurod/21-lms.git && cd 21-lms
make init
```

`make init` сам: создаёт `.env` и `analytics/datalens-ai/.env` из примеров,
генерирует `APP_KEY`, собирает образы, поднимает **весь стек** (включая
DataLens), выполняет миграции и сиды.

Готово:

| URL | Что там |
|---|---|
| `http://localhost` | LMS |
| `http://localhost:8085` | DataLens UI |
| `http://localhost:8100/health` | datalens-ai health |

**Логин:** `admin@gmail.com` / `school21` (смените в продакшене —
`SEED_PASSWORD` в `.env`).

### Что настроить после установки

1. **Пароли и БД** — в `.env`: `DB_PASSWORD`, `SEED_PASSWORD` (и удалить
   сид-аккаунты, если прод).
2. **LLM** — в `analytics/datalens-ai/.env`: `LLM_PROVIDER` + `LLM_API_KEY`
   (OpenAI / Anthropic / Gemini / GLM / OpenRouter / Ollama — см. таблицу
   в [analytics/datalens-ai/README.md](analytics/datalens-ai/README.md)).
3. **Общий секрет** Laravel ↔ datalens-ai:
   ```bash
   python3 -c "import secrets; print(secrets.token_hex(32))"
   ```
   Записать в root `.env` как `DATALENS_AI_API_KEY` и
   `make restart`. (datalens-ai подхватит его автоматически из root `.env`.)
4. `make restart`.

Проверка: `make check` — статус nginx, datalens-ai, DataLens UI, контейнеров.

---

## Команды

```
make help      — все команды
make init      — первая установка (.env + key + build + migrate + seed)
make up        — поднять весь стек
make down      — остановить
make restart   — пересобрать и перезапустить
make logs      — логи всего стека (make logs-ai — только datalens-ai)
make check     — health-check всех компонент
make migrate / seed / fresh / tinker / shell / test
make update    — git pull + rebuild + migrate + queue:restart
make nuke      — DANGER: снести вместе с томами (все данные)
```

---

## Конфигурация

Один файл — root `.env` (шаблон: `.env.example`). Он конфигурирует **весь**
стек: Laravel, PostgreSQL, Redis, datalens-ai и DataLens OSS.

### Ключевые переменные

| Переменная | По умолчанию | Описание |
|---|---|---|
| `APP_PORT` / `DB_PORT` / `REDIS_PORT` / `AI_PORT` / `UI_PORT` | 80 / 5432 / 6379 / 8100 / 8085 | Хост-порты (менять при коллизиях) |
| `APP_ENV` / `APP_DEBUG` | production / false | Окружение Laravel |
| `DB_*` | pgsql, 21_lms | PostgreSQL LMS |
| `SEED_PASSWORD` | school21 | Пароль сид-аккаунтов |
| `MIGRATE_ON_BOOT` / `CACHE_ON_BOOT` | true | Автомиграции и config/route/view cache при старте |
| `GITLAB_TOKEN` | — | PAT GitLab (авто-репозитории проектов) |
| `DATALENS_AI_URL` | http://127.0.0.1:8100 | Laravel → datalens-ai (сервер-сайд) |
| `DATALENS_AI_PUBLIC_URL` | — | Браузерный адрес SSE (в Docker = `http://localhost:8100`) |
| `DATALENS_AI_API_KEY` | — | X-API-Key секрет, общий с datalens-ai |
| `POSTGRES_*`, `AUTH_ENABLED`, `UI_PORT` | — | Переменные включённого DataLens-стека |

Внутри docker-compose адреса автоматически переопределяются на имена
сервисов (`db`, `redis`, `datalens-ai`, `ui`, `auth`) — см. блоки
`environment:` в [docker-compose.yml](docker-compose.yml).

---

## AI-аналитика: как это работает

1. Админ пишет запрос в admin-панели («Покажи активность пользователей за месяц»)
2. Laravel (`DataLensAiService`) отправляет его в `datalens-ai` (`X-API-Key`)
3. Сервис анализирует схему PostgreSQL → LLM генерирует SQL для каждого чарта →
   валидация (`EXPLAIN` + `LIMIT 5`) → создание QL-чартов и dashboard в DataLens
4. Прогресс стримится в браузер по SSE (`/api/dashboard-jobs/{id}/events`)
5. Пользователь получает прямую ссылку на dashboard в DataLens UI

Типы чартов: `line`, `area`, `column`, `bar`, `pie`. Детали:
[analytics/datalens-ai/README.md](analytics/datalens-ai/README.md),
API — [analytics/datalens-ai/docs/API.md](analytics/datalens-ai/docs/API.md).

---

## Разработка без Docker (bare-metal)

Текущий dev-режим: Laravel и datalens-ai на хосте, DataLens — в Docker.

```bash
# 1. Зависимости
composer install && npm install

# 2. Конфигурация
cp .env.example .env && php artisan key:generate
# в .env: DB_* на ваш локальный PostgreSQL, APP_ENV=local, APP_DEBUG=true

# 3. БД + фронт
php artisan migrate --seed && npm run dev

# 4. Запуск (сервер + queue + logs + vite)
composer dev

# 5. datalens-ai (отдельный терминал)
cd analytics/datalens-ai
python3 -m venv venv && source venv/bin/activate
pip install -r requirements.txt
cp .env.example .env          # заполнить LLM_API_KEY, DB_URL…
uvicorn main:app --host 0.0.0.0 --port 8100

# 6. DataLens OSS (отдельный терминал)
cd analytics/datalens
docker compose up -d

# Диагностика всего стека
python ../datalens-ai/check.py
```

Адресация в dev-режиме (`.env` datalens-ai):
`DB_URL` → `127.0.0.1:5432`, `DATALENS_BASE_URL` → `localhost:8085`,
`DATALENS_AUTH_URL` → `127.0.0.1:8088`, `DATALENS_US_URL` → `localhost:3030`
(порты auth/us публикуются через `analytics/datalens/docker-compose.override.yml`).

---

## Production-чеклист

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Сменить `DB_PASSWORD`, `SEED_PASSWORD`, `POSTGRES_PASSWORD` (DataLens)
- [ ] Удалить/пересоздать сид-аккаунты (`admin@gmail.com`)
- [ ] `DATALENS_AI_API_KEY` заполнен (одинаковый с обеих сторон)
- [ ] HTTPS: поставить reverse-proxy (Traefik/Caddy/nginx + certbot) перед
      `nginx:APP_PORT`, обновить `APP_URL` и `CORS_ORIGINS`
- [ ] Бэкапы: `docker compose exec db pg_dump -U $DB_USERNAME $DB_DATABASE > backup.sql`
      + том `lms-db` (и `db-postgres` DataLens)
- [ ] Логи: `docker compose logs`, `storage/logs` (LOG_STACK=daily)
- [ ] Обновление: `make update`
- [ ] Bare-metal (если не Docker): `supervisor` для `queue:work` — образец в
      [supervisor/](supervisor/), `php artisan config:cache route:cache view:cache`,
      `php artisan storage:link`

---

## Функциональность

- **Проекты** — CRUD проектов с авто-созданием GitLab-репозитория
- **Субмиссии** — решения, статусы (pending → tested → reviewed → passed/failed)
- **Пир-ревью** — взаимная проверка, чек-листы, рейтинг проверяющих
- **Автотесты** — queue-воркеры прогоняют тесты (см. [docs/QUEUE_WORKERS.md](docs/QUEUE_WORKERS.md))
- **XP / Геймификация** — уровни 1–6 (beginner → grandmaster), XP-транзакции с аудитом
- **Доступ по уровням** — проекты требуют минимальный уровень студента
- **Календарь** — дедлайны и события (server-side обновление)
- **AI-аналитика** — text-to-dashboard на DataLens (см. выше)
- **Админ-панель** — проекты, пользователи, дашборд, AI-чаты
- **Аутентификация** — Jetstream/Fortify, 2FA, API-токены (Sanctum)

## Стек

| Слой | Технологии |
|---|---|
| Backend | PHP 8.3, Laravel 13, Spatie Laravel Permission |
| Auth | Laravel Jetstream + Fortify (2FA, API tokens) |
| Frontend | Blade, Tailwind CSS 3.x, Livewire 3.x, Vite 8 |
| Database | PostgreSQL 16, Redis 7 |
| AI-сервис | Python 3.11, FastAPI, psycopg 3, OpenAI/Anthropic/Gemini |
| BI | Yandex DataLens OSS (Docker) |
| Testing | PHPUnit 12 |

## Структура

```
21-lms/
├── app/                        # Laravel: controllers, models, services
│   └── Services/
│       ├── GitlabService.php   # GitLab API client
│       ├── XpService.php       # XP/уровни
│       └── DataLensAiService.php # клиент datalens-ai (X-API-Key)
├── analytics/
│   ├── datalens/               # Yandex DataLens OSS (docker-compose)
│   └── datalens-ai/            # FastAPI text-to-dashboard сервис
├── docker/                     # entrypoint.sh, php.ini, nginx conf
├── docker-compose.yml          # весь стек: make up
├── Dockerfile                  # php-fpm (target: app) + nginx (target: web)
├── Makefile                    # init/up/down/logs/...
├── supervisor/                 # образцы конфигов queue-воркеров
├── routes/ · database/ · resources/ · tests/
└── docs/                       # QUEUE_WORKERS и др.
```

## Тесты

```bash
make test                    # в Docker
php artisan test             # bare-metal
```

> В репозитории пока только Jetstream boilerplate-тесты; доменные тесты в процессе.
