#!/bin/bash
# Solo wingstest: nunca cargar alertas.env ni publicar heartbeats de produccion.
set -Eeuo pipefail
export WINGS_APP_DIR=/home/wingstest/app
export WINGS_MONITOR_CONFIG=${WINGS_APP_DIR}/storage/app/monitor-test.env
export WINGS_CSP_LOGS=${WINGS_APP_DIR}/storage/logs
export WINGS_CSP_VISTAS=${WINGS_APP_DIR}/storage/app/csp-avisadas-test.txt
unset SCHEDULER_HEARTBEAT_URL BACKUP_HEARTBEAT_URL TELEGRAM_BOT_TOKEN TELEGRAM_CHAT_ID
[ "$(id -un)" = wingstest ] || { echo 'Solo puede ejecutarlo wingstest'; exit 1; }
cd "${WINGS_APP_DIR}"
printf '%s scheduler-test inicio usuario=%s\n' "$(date -Is)" "$(id -un)"
estado=0
/usr/bin/php82 artisan schedule:run || estado=$?
printf '%s scheduler-test fin estado=%s\n' "$(date -Is)" "${estado}"
exit "${estado}"
