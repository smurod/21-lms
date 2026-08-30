#!/usr/bin/env python3
"""
check_and_run.py — диагностика и подсказки для запуска стека datalens-ai.

Проверяет:
  1. DataLens UI (docker compose, порт 8085)
  2. datalens-ai FastAPI (uvicorn, порт 8100)
  3. OpenAI API (доступность ключа и модели)

Если сервис не запущен — выводит точные команды для запуска.
Не запускает ничего сам: только проверяет и подсказывает.

Запуск:
    python check_and_run.py             # проверить всё
    python check_and_run.py --stop      # показать команды для остановки сервисов
"""

from __future__ import annotations

import argparse
import os
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent               # analytics/datalens-ai/
DATALENS_DIR = ROOT.parent / "datalens"              # analytics/datalens/
PROJECT_ROOT = ROOT.parent.parent                    # 21-lms/

DATALENS_UI_URL  = os.getenv("DATALENS_UI_URL",  "http://127.0.0.1:8085")
DATALENS_UI_PORT = os.getenv("DATALENS_UI_PORT", "8085")
AI_URL           = os.getenv("AI_URL",           "http://127.0.0.1:8100")
AI_PORT          = os.getenv("AI_PORT",          "8100")


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def _ok(url: str, timeout: float = 3.0) -> bool:
    try:
        with urllib.request.urlopen(url, timeout=timeout) as r:
            return 200 <= r.status < 500
    except Exception:
        return False


def _section(title: str) -> None:
    width = 56
    print()
    print("─" * width)
    print(f"  {title}")
    print("─" * width)


def _status(label: str, ok: bool, detail: str = "") -> None:
    icon = "✅" if ok else "❌"
    line = f"  {icon}  {label}"
    if detail:
        line += f"  ({detail})"
    print(line)


def _cmd(description: str, command: str) -> None:
    print(f"\n  › {description}")
    print(f"    {command}")


# ---------------------------------------------------------------------------
# Checks
# ---------------------------------------------------------------------------

def check_datalens() -> bool:
    _section("1 / DataLens UI  :{}".format(DATALENS_UI_PORT))
    alive = _ok(DATALENS_UI_URL + "/")
    _status("DataLens UI", alive, DATALENS_UI_URL)

    if not alive:
        compose_file = DATALENS_DIR / "docker-compose.yaml"
        if compose_file.exists():
            print("\n  Для запуска выполните:")
            _cmd(
                "Поднять DataLens через docker compose",
                f"cd {DATALENS_DIR} && UI_PORT={DATALENS_UI_PORT} docker compose up -d",
            )
            _cmd(
                "Проверить логи",
                f"cd {DATALENS_DIR} && docker compose logs --tail=30",
            )
        else:
            print(f"\n  ⚠  docker-compose.yaml не найден в {DATALENS_DIR}")
            print(f"     Склонируйте DataLens: https://github.com/datalens-tech/datalens")

    return alive


def check_ai_service() -> bool:
    _section("2 / datalens-ai FastAPI  :{}".format(AI_PORT))
    alive = _ok(AI_URL + "/health")
    _status("datalens-ai (uvicorn)", alive, AI_URL)

    if alive:
        # Дополнительно показать статус компонентов из /health
        try:
            with urllib.request.urlopen(AI_URL + "/health", timeout=5) as r:
                import json
                data = json.loads(r.read())
            _status("OpenAI model",  bool(data.get("llm_server")),
                    "gpt-5.6-luna" if data.get("llm_server") else "недоступен")
            _status("DataLens API",  bool(data.get("datalens")),
                    "подключён" if data.get("datalens") else "недоступен")
        except Exception:
            pass
    else:
        env_file = ROOT / ".env"
        print("\n  Для запуска выполните:")
        if not env_file.exists():
            _cmd(
                "Скопировать .env.example → .env и заполнить OPENAI_API_KEY",
                f"cp {ROOT}/.env.example {ROOT}/.env",
            )
        _cmd(
            "Установить зависимости (если не установлены)",
            f"cd {ROOT} && pip install -r requirements.txt",
        )
        _cmd(
            "Запустить сервис",
            f"cd {ROOT} && uvicorn main:app --host 0.0.0.0 --port {AI_PORT}",
        )
        _cmd(
            "Или в фоне (с логом)",
            f"cd {ROOT} && nohup uvicorn main:app --host 0.0.0.0 --port {AI_PORT} > logs/ai.log 2>&1 &",
        )

    return alive


def check_openai_key() -> bool:
    """Проверка наличия OPENAI_API_KEY в .env или окружении."""
    _section("3 / OpenAI API key")

    # Пробуем прочитать из .env напрямую (не через pydantic, чтобы не импортировать)
    key = os.getenv("OPENAI_API_KEY", "")
    env_file = ROOT / ".env"
    if not key and env_file.exists():
        for line in env_file.read_text(encoding="utf-8").splitlines():
            line = line.strip()
            if line.startswith("OPENAI_API_KEY="):
                key = line.split("=", 1)[1].strip().strip('"').strip("'")
                break

    has_key = bool(key and key != "")
    _status("OPENAI_API_KEY задан", has_key,
            f"sk-...{key[-4:]}" if has_key else "не найден")

    if not has_key:
        print("\n  Добавьте ключ в файл .env:")
        print(f"    OPENAI_API_KEY=sk-...")
        print(f"    Файл: {ROOT / '.env'}")

    return has_key


# ---------------------------------------------------------------------------
# Stop hints
# ---------------------------------------------------------------------------

def show_stop_commands() -> None:
    _section("Команды для остановки сервисов")

    _cmd(
        "Остановить datalens-ai (uvicorn)",
        "pkill -f 'uvicorn main:app'",
    )
    _cmd(
        "Остановить DataLens (docker compose)",
        f"cd {DATALENS_DIR} && docker compose down",
    )
    _cmd(
        "Остановить DataLens (сохранить данные)",
        f"cd {DATALENS_DIR} && docker compose stop",
    )


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(
        description="Проверить статус стека datalens-ai и вывести команды для запуска."
    )
    parser.add_argument(
        "--stop", action="store_true",
        help="Показать команды для остановки сервисов и выйти.",
    )
    args = parser.parse_args()

    print("\n🔍  Диагностика стека datalens-ai")
    print(f"    Время: {time.strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"    Директория: {ROOT}")

    if args.stop:
        show_stop_commands()
        print()
        return 0

    dl_ok  = check_datalens()
    ai_ok  = check_ai_service()
    key_ok = check_openai_key()

    _section("Итог")
    all_ok = dl_ok and ai_ok and key_ok
    if all_ok:
        print("  ✅  Все сервисы работают. Стек готов к работе.")
        print(f"\n  Откройте: {DATALENS_UI_URL}")
        print(f"  API:      {AI_URL}/docs")
    else:
        issues = []
        if not dl_ok:  issues.append("DataLens UI не запущен")
        if not ai_ok:  issues.append("datalens-ai не запущен")
        if not key_ok: issues.append("OPENAI_API_KEY не задан")
        print("  ⚠  Обнаружены проблемы:")
        for issue in issues:
            print(f"     · {issue}")
        print("\n  Следуйте командам выше для устранения.")

    print()
    return 0 if all_ok else 1


if __name__ == "__main__":
    sys.exit(main())
