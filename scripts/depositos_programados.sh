#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="/c/wamp64/bin/php/php8.2.29/php.exe"
DB_HOST="18.222.102.91"
DB_PORT="3306"
LOG_DIR="$PROJECT_DIR/writable/logs"
LOG_FILE="$LOG_DIR/depositos_programados.log"
LOG_ROTATED="$LOG_DIR/depositos_programados.log.1"
MAX_LOG_SIZE_BYTES=5242880

mkdir -p "$LOG_DIR"
cd "$PROJECT_DIR"

if [[ -f "$LOG_FILE" ]]; then
  current_size="$(wc -c < "$LOG_FILE" | tr -d '[:space:]')"
  if [[ -n "$current_size" ]] && (( current_size >= MAX_LOG_SIZE_BYTES )); then
    mv -f "$LOG_FILE" "$LOG_ROTATED"
  fi
fi

if ! powershell.exe -NoProfile -Command "if (Test-NetConnection -ComputerName '$DB_HOST' -Port $DB_PORT -WarningAction SilentlyContinue).TcpTestSucceeded { exit 0 } else { exit 1 }" >/dev/null 2>&1; then
  {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] BD no disponible en $DB_HOST:$DB_PORT. Se omite depositos:programar."
  } >> "$LOG_FILE"
  exit 1
fi

"$PHP_BIN" spark depositos:programar >> "$LOG_FILE" 2>&1
