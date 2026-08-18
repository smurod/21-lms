#!/usr/bin/env python3
"""
check_and_run.py — единая точка запуска, проверки и остановки окружения.

Запуск:
    python check_and_run.py           # поднять всё и прогнать smoke-тест
    python check_and_run.py --stop    # остановить datalens-ai и LLM,
                                     # DataLens (docker) НЕ останавливаем

Сервисы:
  1. DataLens  — docker compose (../datalens, UI на 8085);
  2. LLM       — llama-server в отдельном окне терминала;
  3. datalens-ai — uvicorn в отдельном окне терминала.
"""

from __future__ import annotations

import argparse
import json
import os
import shlex
import shutil
import signal
import subprocess
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent
DATALENS_DIR = ROOT.parent / "datalens"

MODEL = os.getenv(
    "LLM_MODEL",
    "/home/smurod_8880/models/gguf/qwen3_6_q4.gguf",
)
CHAT_TEMPLATE = os.getenv(
    "LLM_CHAT_TEMPLATE",
    "/home/smurod_8880/patched_qwen_template.jinja",
)
LLAMA_SERVER = os.getenv(
    "LLAMA_SERVER",
    shutil.which("llama-server") or str(Path.home() / "llama.cpp/build/bin/llama-server"),
)

DATALENS_UI_URL = os.getenv("DATALENS_UI_URL", "http://127.0.0.1:8085")
DATALENS_UI_PORT = os.getenv("DATALENS_UI_PORT", "8085")
LLM_URL = os.getenv("LLM_BASE_URL", "http://127.0.0.1:8001")
AI_URL = os.getenv("AI_URL", "http://127.0.0.1:8100")

_compose_proc: subprocess.Popen | None = None


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def _http_ok(url: str, timeout: float = 2.0) -> bool:
    try:
        with urllib.request.urlopen(url, timeout=timeout) as response:
            return 200 <= response.status < 500
    except (urllib.error.URLError, ConnectionError, OSError):
        return False


def _wait_for(url: str, name: str, timeout: int = 240) -> bool:
    print(f"   waiting for {name} at {url} ...")
    start = time.time()
    while time.time() - start < timeout:
        if _http_ok(url):
            print(f"   ✅ {name} is available")
            return True
        time.sleep(2)
    print(f"   ❌ {name} did not become ready in {timeout}s")
    return False


def _find_docker_compose() -> list[str] | None:
    if shutil.which("docker") is None:
        return None
    try:
        subprocess.run(
            ["docker", "compose", "version"],
            check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
        )
        return ["docker", "compose"]
    except (subprocess.CalledProcessError, FileNotFoundError):
        pass
    if shutil.which("docker-compose"):
        return ["docker-compose"]
    return None


def _find_terminal() -> list[str] | None:
    """Find an available terminal emulator that can run a command."""
    for candidate in ("gnome-terminal", "konsole", "xfce4-terminal", "xterm"):
        if shutil.which(candidate):
            if candidate == "gnome-terminal":
                return [candidate, "--"]
            if candidate == "konsole":
                return [candidate, "-e"]
            if candidate == "xfce4-terminal":
                return [candidate, "-x"]
            if candidate == "xterm":
                return [candidate, "-e"]
    return None


def _open_terminal(title: str, command: list[str], cwd: Path) -> bool:
    """Open `command` in a separate terminal window. Keeps window open on exit."""
    terminal = _find_terminal()
    if not terminal:
        print("   ⚠️  no terminal emulator found, cannot open separate window")
        return False

    inner = " ".join(shlex.quote(c) for c in command)
    shell_cmd = (
        f"cd {shlex.quote(str(cwd))}; "
        f"{inner}; "
        "echo; echo '[нажмите Enter для закрытия окна]'; read -r _"
    )
    cmd = [*terminal, "bash", "-lc", shell_cmd]
    print(f"   opening terminal: {title}")
    try:
        subprocess.Popen(cmd, cwd=cwd, start_new_session=True)
        return True
    except OSError as exc:
        print(f"   ⚠️  failed to open terminal: {exc}")
        return False


# ---------------------------------------------------------------------------
# Steps
# ---------------------------------------------------------------------------

def ensure_datalens() -> bool:
    print("\n[1/4] DataLens (docker compose)")
    if _http_ok(DATALENS_UI_URL + "/"):
        print("   already running")
        return True

    if not DATALENS_DIR.exists():
        print(f"   ❌ folder not found: {DATALENS_DIR}")
        return False

    compose = _find_docker_compose()
    if not compose:
        print("   ❌ docker / docker compose not found in PATH")
        return False

    print(f"   starting DataLens with UI_PORT={DATALENS_UI_PORT}...")
    env = os.environ.copy()
    env["UI_PORT"] = DATALENS_UI_PORT
    global _compose_proc
    _compose_proc = subprocess.Popen(
        compose + ["up", "-d"], cwd=DATALENS_DIR, env=env
    )
    return _wait_for(DATALENS_UI_URL + "/", "DataLens UI", timeout=300)


def ensure_llm() -> bool:
    print("\n[2/4] LLM (llama-server)")
    if _http_ok(LLM_URL + "/health"):
        print("   already running")
        return True

    if not Path(MODEL).exists():
        print(f"   ❌ model not found: {MODEL}")
        return False
    if not Path(LLAMA_SERVER).exists():
        print(f"   ❌ llama-server not found: {LLAMA_SERVER}")
        return False

    cmd = [
        LLAMA_SERVER,
        "-m", MODEL,
        "-c", "16000",
        "-ngl", "999",
        "--n-cpu-moe", "26",
        "--flash-attn", "on",
        "--cache-type-k", "q4_0",
        "--cache-type-v", "q4_0",
        "--batch-size", "8192",
        "--ubatch-size", "4096",
        "-t", "6",
        "--port", "8001",
        "--host", "0.0.0.0",
        "--alias", "qwen3.6-coding",
        "--jinja",
        "-np", "1",
        "--temp", "0.85",
        "--top-p", "0.95",
        "--top-k", "40",
        "--min-p", "0.05",
        "--repeat-penalty", "1.05",
        "--presence-penalty", "0.65",
        "--frequency-penalty", "0.0",
        "--chat-template-file", CHAT_TEMPLATE,
    ]
    _open_terminal("datalens-ai — LLM (llama-server :8001)", cmd, ROOT)
    return _wait_for(LLM_URL + "/health", "LLM", timeout=300)


def ensure_ai_service() -> bool:
    print("\n[3/4] datalens-ai (FastAPI/uvicorn :8100)")
    if _http_ok(AI_URL + "/health"):
        print("   already running")
        return True

    venv_python = ROOT / "venv" / "bin" / "python"
    python_bin = str(venv_python) if venv_python.exists() else sys.executable
    cmd = [
        python_bin, "-m", "uvicorn", "main:app",
        "--host", "0.0.0.0", "--port", "8100",
    ]
    _open_terminal("datalens-ai — API (uvicorn :8100)", cmd, ROOT)

    if not _wait_for(AI_URL + "/health", "datalens-ai", timeout=120):
        log_path = ROOT / "logs" / "ai_service.log"
        if log_path.exists():
            print(f"   last lines of {log_path}:")
            for line in log_path.read_text(
                encoding="utf-8", errors="replace"
            ).splitlines()[-20:]:
                print("   " + line)
        return False
    return True


def run_smoke_test() -> bool:
    print("\n[4/4] Smoke test: create dashboard (no edits/deletes)")
    payload = json.dumps(
        {"message": "Smoke test: покажи активность, отзывы и пользователей"}
    ).encode("utf-8")
    request = urllib.request.Request(
        AI_URL + "/api/dashboards/generate",
        data=payload,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(request, timeout=600) as response:
            body = response.read().decode("utf-8", errors="replace")
            print("   ✅ dashboard created")
            print("   response (first 1200 chars):")
            print("   " + body[:1200].replace("\n", "\n   "))
            return True
    except urllib.error.HTTPError as exc:
        detail = exc.read().decode("utf-8", errors="replace")
        print(f"   ❌ HTTP {exc.code}: {detail}")
        return False
    except Exception as exc:  # noqa: BLE001
        print(f"   ❌ request failed: {exc}")
        return False


# ---------------------------------------------------------------------------
# Stop
# ---------------------------------------------------------------------------

def stop_services() -> None:
    """Stop uvicorn and llama-server. Docker DataLens is left running."""
    print("Stopping datalens-ai (uvicorn :8100)...")
    subprocess.run(["pkill", "-f", "uvicorn main:app"], check=False)
    print("Stopping LLM (llama-server :8001)...")
    subprocess.run(["pkill", "-f", "llama-server.*--port 8001"], check=False)
    print("Done. DataLens containers are left running.")


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(description="Run/check datalens-ai stack.")
    parser.add_argument(
        "--stop", action="store_true",
        help="Stop uvicorn and llama-server and exit.",
    )
    args = parser.parse_args()

    if args.stop:
        stop_services()
        return 0

    if not ensure_datalens():
        return 1
    if not ensure_llm():
        return 1
    if not ensure_ai_service():
        return 1

    return 0 if run_smoke_test() else 2


if __name__ == "__main__":
    sys.exit(main())
