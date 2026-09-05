#!/usr/bin/env python3
"""
check.py — диагностика и подсказки для запуска стека datalens-ai.

Проверяет:
  1. DataLens UI (docker compose, порт 8085)
  2. datalens-ai FastAPI (uvicorn, порт 8100)
  3. OpenAI API (доступность ключа и модели)

Если сервис не запущен — выводит точные команды для запуска.
Не запускает ничего сам: только проверяет и подсказывает.

Запуск:
    python check.py             # проверить всё
    python check.py --stop      # показать команды для остановки сервисов
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
VENV_DIR = ROOT / "venv"                             # локальное окружение

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


def _has_venv() -> bool:
    """Локальный venv создан и uvicorn установлен?"""
    return (VENV_DIR / "bin" / "uvicorn").exists()


def _uvicorn_bin() -> str:
    return f"{VENV_DIR}/bin/uvicorn" if _has_venv() else "uvicorn"


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
            _status("LLM model",  bool(data.get("llm_server")),
                    data.get("llm_provider", "неизвестен") if data.get("llm_server") else "недоступен")
            _status("DataLens API",  bool(data.get("datalens")),
                    "подключён" if data.get("datalens") else "недоступен")
        except Exception:
            pass
    else:
        env_file = ROOT / ".env"
        print("\n  Для запуска выполните:")
        if not env_file.exists():
            _cmd(
                "Скопировать .env.example → .env и заполнить LLM_API_KEY",
                f"cp {ROOT}/.env.example {ROOT}/.env",
            )
        if _has_venv():
            _cmd(
                "Запустить сервис (через локальный venv)",
                f"cd {ROOT} && {_uvicorn_bin()} main:app --host 0.0.0.0 --port {AI_PORT}",
            )
            _cmd(
                "Или в фоне (с логом)",
                f"cd {ROOT} && nohup {_uvicorn_bin()} main:app --host 0.0.0.0 --port {AI_PORT} > logs/ai.log 2>&1 &",
            )
            _cmd(
                "Или активировать venv и работать внутри него",
                f"source {VENV_DIR}/bin/activate && uvicorn main:app --host 0.0.0.0 --port {AI_PORT}",
            )
        else:
            _cmd(
                "Создать venv и установить зависимости (системный pip заблокирован PEP 668)",
                f"cd {ROOT} && python3 -m venv venv && venv/bin/pip install -r requirements.txt",
            )
            _cmd(
                "Затем запустить сервис",
                f"cd {ROOT} && venv/bin/uvicorn main:app --host 0.0.0.0 --port {AI_PORT}",
            )

    return alive


def _read_env_value(name: str) -> str:
    """Читает переменную из .env напрямую (не через pydantic, чтобы не импортировать)."""
    value = os.getenv(name, "")
    if value:
        return value
    env_file = ROOT / ".env"
    if not env_file.exists():
        return ""
    for line in env_file.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if line.startswith(f"{name}="):
            return line.split("=", 1)[1].strip().strip('"').strip("'")
    return ""


def check_llm_config() -> bool:
    """Проверка универсального LLM-конфига в .env (LLM_* или legacy OPENAI_*)."""
    _section("3 / LLM provider")

    provider = _read_env_value("LLM_PROVIDER") or "openai"
    model = _read_env_value("LLM_MODEL") or _read_env_value("OPENAI_MODEL")
    # Self-hosted OpenAI-compatible endpoints (Ollama, vLLM) работают без ключа.
    custom_endpoint = bool(_read_env_value("LLM_BASE_URL"))
    key = _read_env_value("LLM_API_KEY") or _read_env_value("OPENAI_API_KEY")

    _status("LLM_PROVIDER", provider in ("openai", "anthropic", "google"), provider)
    if model:
        _status("Модель", True, model)
    needs_key = provider in ("anthropic", "google") or not custom_endpoint
    has_key = bool(key)
    _status("LLM_API_KEY задан", has_key or not needs_key,
            f"...{key[-4:]}" if has_key else ("не требуется (свой endpoint)" if not needs_key else "не найден"))

    if not has_key and needs_key:
        print("\n  Добавьте ключ в файл .env:")
        print(f"    LLM_PROVIDER={provider}")
        print("    LLM_API_KEY=<ключ провайдера>")
        print(f"    Файл: {ROOT / '.env'}")

    return has_key or not needs_key


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
    key_ok = check_llm_config()

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
        if not key_ok: issues.append("LLM_API_KEY не задан")
        print("  ⚠  Обнаружены проблемы:")
        for issue in issues:
            print(f"     · {issue}")
        print("\n  Следуйте командам выше для устранения.")

    print()
    return 0 if all_ok else 1


if __name__ == "__main__":
    sys.exit(main())
