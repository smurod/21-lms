#!/usr/bin/env python3
"""
Launch local llama-server for qwen3.6-coding with safe defaults.

Why this script exists:
- Qwen3 has a "thinking" mode that can spend all tokens on hidden reasoning,
  leaving empty content.
- This launcher starts the server with enough context, GPU offload,
  and lower temperature.
- The API client additionally sends enable_thinking=false for JSON requests.

Run:
    python start_llm.py
"""

from __future__ import annotations

import os
import shutil
import signal
import subprocess
import sys
import time
from pathlib import Path

# You can override these via environment variables.
MODEL = os.getenv(
    "LLM_MODEL",
    "/home/smurod_8880/models/gguf/qwen3_6_q4.gguf",
)
CHAT_TEMPLATE = os.getenv(
    "LLM_CHAT_TEMPLATE",
    "/home/smurod_8880/patched_qwen_template.jinja",
)
HOST = os.getenv("LLM_HOST", "0.0.0.0")
PORT = int(os.getenv("LLM_PORT", "8001"))
ALIAS = os.getenv("LLM_ALIAS", "qwen3.6-coding")
CONTEXT = int(os.getenv("LLM_CONTEXT", "16000"))
THREADS = int(os.getenv("LLM_THREADS", "6"))
N_CPU_MOE = int(os.getenv("LLM_N_CPU_MOE", "26"))


def require_file(path: str) -> None:
    if not Path(path).exists():
        print(f"ERROR: file not found: {path}", file=sys.stderr)
        sys.exit(1)


def wait_until_ready(timeout: int = 120) -> None:
    import urllib.request

    url = f"http://127.0.0.1:{PORT}/health"
    start = time.time()
    while time.time() - start < timeout:
        try:
            with urllib.request.urlopen(url, timeout=2) as response:
                if response.status == 200:
                    print(f"LLM is ready: {url}")
                    return
        except Exception:
            pass
        time.sleep(1)
    print("WARNING: LLM did not become ready in time.", file=sys.stderr)


def main() -> None:
    llama_server = shutil.which("llama-server")
    if not llama_server:
        llama_server = "/home/smurod_8880/llama.cpp/build/bin/llama-server"

    require_file(MODEL)
    require_file(CHAT_TEMPLATE)
    require_file(llama_server)

    cmd = [
        llama_server,
        "-m", MODEL,
        "-c", str(CONTEXT),
        "-ngl", "999",
        "--n-cpu-moe", str(N_CPU_MOE),
        "--flash-attn", "on",
        "--cache-type-k", "q4_0",
        "--cache-type-v", "q4_0",
        "--batch-size", "8192",
        "--ubatch-size", "4096",
        "-t", str(THREADS),
        "--host", HOST,
        "--port", str(PORT),
        "--alias", ALIAS,
        "--jinja",
        "-np", "1",
        # Keep server defaults from the tested fast command.
        # The API client overrides temperature for JSON requests.
        "--temp", "0.85",
        "--top-p", "0.95",
        "--top-k", "40",
        "--min-p", "0.05",
        "--repeat-penalty", "1.05",
        "--presence-penalty", "0.65",
        "--frequency-penalty", "0.0",
        "--chat-template-file", CHAT_TEMPLATE,
    ]

    print("Starting LLM:")
    print(" ".join(cmd))
    print()

    with subprocess.Popen(cmd) as process:
        try:
            wait_until_ready()
            while process.poll() is None:
                time.sleep(1)
        except KeyboardInterrupt:
            print("\nStopping LLM...")
            process.send_signal(signal.SIGINT)
            try:
                process.wait(timeout=10)
            except subprocess.TimeoutExpired:
                process.kill()


if __name__ == "__main__":
    main()
