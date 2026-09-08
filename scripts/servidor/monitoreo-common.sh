#!/bin/bash

# Funciones compartidas por los controles operativos del servidor.
# Los secretos viven fuera del repositorio, por defecto en:
# /etc/wings-monitor/alertas.env

MONITOR_CONFIG="${WINGS_MONITOR_CONFIG:-/etc/wings-monitor/alertas.env}"
CURL_BIN="${CURL_BIN:-/usr/bin/curl}"

cargar_config_monitoreo() {
    if [ -r "${MONITOR_CONFIG}" ]; then
        # shellcheck disable=SC1090
        if ! source "${MONITOR_CONFIG}"; then
            echo "$(date '+%F %T') AVISO: ${MONITOR_CONFIG} es invalido; monitoreo desactivado" >&2
        fi
    else
        echo "$(date '+%F %T') AVISO: no se puede leer ${MONITOR_CONFIG}; monitoreo desactivado" >&2
    fi

    # Una falla de configuracion del canal nunca debe impedir la tarea principal.
    return 0
}

enviar_heartbeat() {
    local url="${1:-}"

    [ -n "${url}" ] || return 1

    # La URL del heartbeat funciona como secreto. Se entrega por stdin para que
    # no aparezca en la linea de comandos ni en el listado de procesos.
    printf 'url = "%s"\n' "${url}" | "${CURL_BIN}" \
        --config - \
        --silent \
        --show-error \
        --fail \
        --max-time 15 \
        --output /dev/null
}

enviar_telegram() {
    local mensaje="${1:-}"

    [ -n "${TELEGRAM_BOT_TOKEN:-}" ] || return 1
    [ -n "${TELEGRAM_CHAT_ID:-}" ] || return 1
    [ -n "${mensaje}" ] || return 1

    # El token tampoco queda expuesto en la linea de comandos.
    printf 'url = "https://api.telegram.org/bot%s/sendMessage"\n' "${TELEGRAM_BOT_TOKEN}" | \
        "${CURL_BIN}" \
            --config - \
            --silent \
            --show-error \
            --fail \
            --max-time 15 \
            --request POST \
            --data-urlencode "chat_id=${TELEGRAM_CHAT_ID}" \
            --data-urlencode "text=${mensaje}" \
            --output /dev/null
}

avisar_fallo() {
    local heartbeat_url="${1:-}"
    local mensaje="${2:-Fallo operativo en Wings}"

    if [ -n "${heartbeat_url}" ]; then
        enviar_heartbeat "${heartbeat_url%/}/fail" || \
            echo "$(date '+%F %T') AVISO: no se pudo informar el fallo a Better Stack" >&2
    else
        echo "$(date '+%F %T') AVISO: falta la URL de Better Stack" >&2
    fi
    enviar_telegram "${mensaje}" || \
        echo "$(date '+%F %T') AVISO: no se pudo enviar la alerta por Telegram" >&2
}
