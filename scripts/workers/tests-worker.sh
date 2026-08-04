#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${PROJECT_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
PHP_BIN="${PHP_BIN:-php}"
QUEUE_NAMES="${QUEUE_NAMES:-tests,default}"
QUEUE_TRIES="${QUEUE_TRIES:-1}"
QUEUE_TIMEOUT="${QUEUE_TIMEOUT:-600}"
QUEUE_SLEEP="${QUEUE_SLEEP:-2}"
QUEUE_MEMORY="${QUEUE_MEMORY:-512}"

cd "$PROJECT_DIR"

exec "$PHP_BIN" artisan queue:work \
  --queue="$QUEUE_NAMES" \
  --tries="$QUEUE_TRIES" \
  --timeout="$QUEUE_TIMEOUT" \
  --sleep="$QUEUE_SLEEP" \
  --memory="$QUEUE_MEMORY"
