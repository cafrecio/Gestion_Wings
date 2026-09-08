#!/bin/bash
# Prueba aislada: no usa red, servidor, base ni credenciales reales.
set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
PRUEBA_DIR=$(mktemp -d "${TMPDIR:-/tmp}/wings-monitor-test-XXXXXX")
limpiar() { rm -rf "${PRUEBA_DIR}"; }
trap limpiar EXIT

mkdir -p "${PRUEBA_DIR}/app"

printf '%s\n' \
    '#!/bin/bash' \
    'printf "STDIN:" >> "${PRUEBA_LOG}"' \
    'cat >> "${PRUEBA_LOG}"' \
    'printf "\nARGS:%s\n" "$*" >> "${PRUEBA_LOG}"' \
    'exit 0' > "${PRUEBA_DIR}/curl-falso"

printf '%s\n' \
    '#!/bin/bash' \
    'exit "${PHP_RESULT:-0}"' > "${PRUEBA_DIR}/php-falso"

chmod 700 "${PRUEBA_DIR}/curl-falso" "${PRUEBA_DIR}/php-falso"

printf '%s\n' \
    'TELEGRAM_BOT_TOKEN="token-prueba"' \
    'TELEGRAM_CHAT_ID="chat-prueba"' \
    'SCHEDULER_HEARTBEAT_URL="https://heartbeat.invalid/scheduler"' \
    'BACKUP_HEARTBEAT_URL="https://heartbeat.invalid/backup"' \
    > "${PRUEBA_DIR}/alertas.env"

export PRUEBA_LOG="${PRUEBA_DIR}/peticiones.log"
export WINGS_MONITOR_CONFIG="${PRUEBA_DIR}/alertas.env"
export WINGS_APP_DIR="${PRUEBA_DIR}/app"
export PHP_BIN="${PRUEBA_DIR}/php-falso"
export CURL_BIN="${PRUEBA_DIR}/curl-falso"

PHP_RESULT=0 "${SCRIPT_DIR}/ejecutar-scheduler.sh"
grep -q 'https://heartbeat.invalid/scheduler' "${PRUEBA_LOG}"

: > "${PRUEBA_LOG}"
set +e
PHP_RESULT=17 "${SCRIPT_DIR}/ejecutar-scheduler.sh"
ESTADO=$?
set -e

[ "${ESTADO}" -eq 17 ]
grep -q 'https://heartbeat.invalid/scheduler/fail' "${PRUEBA_LOG}"
grep -q 'https://api.telegram.org/bottoken-prueba/sendMessage' "${PRUEBA_LOG}"
grep -q 'chat_id=chat-prueba' "${PRUEBA_LOG}"
grep -q 'ALERTA Wings: fallo el scheduler' "${PRUEBA_LOG}"

echo "Monitoreo del scheduler: prueba correcta"
