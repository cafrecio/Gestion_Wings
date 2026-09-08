#!/bin/bash
# Ejecuta el scheduler de Laravel y publica su estado operativo.
set -Euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
# shellcheck source=monitoreo-common.sh
source "${SCRIPT_DIR}/monitoreo-common.sh"

cargar_config_monitoreo

APP="${WINGS_APP_DIR:-/home/wings/app}"
PHP_BIN="${PHP_BIN:-php82}"

if ! cd "${APP}"; then
    avisar_fallo "${SCHEDULER_HEARTBEAT_URL:-}" \
        "ALERTA Wings: no se encontro la aplicacion para ejecutar el scheduler en $(hostname) ($(date '+%F %T'))."
    exit 1
fi

if "${PHP_BIN}" artisan schedule:run; then
    enviar_heartbeat "${SCHEDULER_HEARTBEAT_URL:-}" || \
        echo "$(date '+%F %T') AVISO: scheduler correcto, pero no se pudo informar a Better Stack" >&2
    exit 0
else
    ESTADO=$?
fi

avisar_fallo "${SCHEDULER_HEARTBEAT_URL:-}" \
    "ALERTA Wings: fallo el scheduler de Laravel en $(hostname) ($(date '+%F %T'))."
exit "${ESTADO}"
