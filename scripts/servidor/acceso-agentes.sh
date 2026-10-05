#!/usr/bin/env bash
# Mantiene el acceso de las computadoras de Carlos al servidor, sin que nadie lo repare a mano.
#
#   Instalar (una vez, como root):   ./acceso-agentes.sh --instalar
#   Sincronizar ahora:               ./acceso-agentes.sh --sincronizar
#   Verificar que sigue vivo:        ./acceso-agentes.sh --verificar
#
# POR QUE EXISTE
# El acceso vivia en un solo archivo del servidor, `/root/.ssh/authorized_keys`, escrito a
# mano desde la maquina que ya entraba. El 05/10/2026 CyE dejo de entrar aunque su clave
# seguia publicada en GitHub: algo repuso una version vieja de ese archivo y nadie se
# enteró hasta que un agente necesito el servidor. Ya habia pasado antes y se habia
# arreglado a mano, que es justamente lo que hace que vuelva a pasar.
#
# COMO LO RESUELVE
# La lista de claves autorizadas se vuelve a armar sola cada 15 minutos desde la fuente de
# verdad —las claves publicas de la cuenta de GitHub de Carlos— mas las claves propias del
# servidor que no vienen de ahi (despliegue, respaldo). Si manana alguien pisa el archivo,
# en 15 minutos vuelve. Si GitHub no responde, no se toca nada: nunca se deja al servidor
# sin acceso por una caida ajena.
#
# Una maquina nueva entra cargando su clave publica en GitHub. No hay paso en el servidor,
# ninguna clave privada viaja y el repositorio no guarda secretos.
set -Eeuo pipefail

FUENTE="${WINGS_CLAVES_URL:-https://github.com/cafrecio.keys}"
DESTINO="${WINGS_AUTHORIZED_KEYS:-/root/.ssh/authorized_keys}"
PROPIAS="${WINGS_CLAVES_PROPIAS:-/root/.ssh/claves-del-servidor}"
CRON="${WINGS_CRON_ACCESO:-/etc/cron.d/wings-acceso-agentes}"
SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/$(basename "${BASH_SOURCE[0]}")"

log() { printf '[%s] %s\n' "$(date '+%F %T')" "$*"; }

sincronizar() {
    local tmp
    tmp="$(mktemp)"
    trap 'rm -f "${tmp}"' RETURN

    if ! curl -fsS --max-time 20 "${FUENTE}" -o "${tmp}"; then
        log "GitHub no respondio: no se toca ${DESTINO}."
        return 0
    fi

    # Un archivo vacio o sin claves validas se descarta: dejaria el servidor sin acceso.
    if ! grep -qE '^(ssh-(ed25519|rsa)|ecdsa-sha2-)' "${tmp}"; then
        log "La respuesta no trae claves validas: no se toca ${DESTINO}."
        return 1
    fi

    mkdir -p "$(dirname "${DESTINO}")"
    chmod 700 "$(dirname "${DESTINO}")"
    touch "${PROPIAS}"
    chmod 600 "${PROPIAS}"

    local nuevo
    nuevo="$(mktemp)"
    {
        echo "# Lo arma ${CRON} desde ${FUENTE}. No editar a mano:"
        echo "# lo que se agregue aca se pierde en la proxima sincronizacion."
        echo "# Una clave que no venga de GitHub va en ${PROPIAS}."
        grep -E '^(ssh-(ed25519|rsa)|ecdsa-sha2-)' "${tmp}"
        grep -E '^(ssh-(ed25519|rsa)|ecdsa-sha2-)' "${PROPIAS}" 2>/dev/null || true
    } | awk '!vistas[$1" "$2]++' > "${nuevo}"

    install -m 600 "${nuevo}" "${DESTINO}"
    rm -f "${nuevo}"
    log "Autorizadas $(grep -cE '^(ssh-|ecdsa-)' "${DESTINO}") clave(s) en ${DESTINO}."
}

verificar() {
    local problemas=0

    [ -f "${CRON}" ] || { echo "FALTA la sincronizacion automatica (${CRON})."; problemas=1; }

    if [ ! -s "${DESTINO}" ]; then
        echo "FALTA ${DESTINO} o esta vacio."
        return 1
    fi

    local tmp
    tmp="$(mktemp)"
    trap 'rm -f "${tmp}"' RETURN
    if ! curl -fsS --max-time 20 "${FUENTE}" -o "${tmp}"; then
        echo "No se pudo leer ${FUENTE}; no se puede comparar."
        return "${problemas}"
    fi

    while read -r tipo clave _; do
        [ -n "${clave:-}" ] || continue
        if ! grep -qF "${clave}" "${DESTINO}"; then
            echo "La clave ${tipo} ${clave:0:20}... de GitHub NO esta autorizada."
            problemas=1
        fi
    done < <(grep -E '^(ssh-(ed25519|rsa)|ecdsa-sha2-)' "${tmp}")

    [ "${problemas}" -eq 0 ] && echo "Acceso OK: $(grep -cE '^(ssh-|ecdsa-)' "${DESTINO}") clave(s) autorizadas."
    return "${problemas}"
}

instalar() {
    # Las claves que ya estaban y no vienen de GitHub se conservan: son del despliegue o
    # del respaldo y sacarlas rompe tareas que no tienen nada que ver con los agentes.
    if [ -s "${DESTINO}" ] && [ ! -s "${PROPIAS}" ]; then
        grep -E '^(ssh-(ed25519|rsa)|ecdsa-sha2-)' "${DESTINO}" > "${PROPIAS}" || true
        chmod 600 "${PROPIAS}"
        log "Guardadas en ${PROPIAS} las claves que ya tenia el servidor."
    fi

    cat > "${CRON}" <<EOF
# Wings — el acceso de los agentes se repara solo. Ver ${SCRIPT}
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
*/15 * * * * root ${SCRIPT} --sincronizar >> /var/log/wings-acceso-agentes.log 2>&1
# Si la sincronizacion quedara rota, se avisa en vez de descubrirlo cuando hace falta.
30 7 * * * root ${SCRIPT} --avisar-si-falla >> /var/log/wings-acceso-agentes.log 2>&1
EOF
    chmod 644 "${CRON}"
    log "Instalada la sincronizacion en ${CRON}."

    sincronizar
    verificar
}

avisar_si_falla() {
    local salida
    if salida="$(verificar 2>&1)"; then
        log "${salida}"
        return 0
    fi

    log "PROBLEMA DE ACCESO: ${salida}"

    # Se reusa el robot de monitoreo que ya existe; si no esta, queda el log.
    local comunes="$(dirname "${SCRIPT}")/monitoreo-common.sh"
    if [ -r "${comunes}" ]; then
        # shellcheck disable=SC1090
        . "${comunes}"
        cargar_config_monitoreo 2>/dev/null || true
        if declare -F enviar_telegram >/dev/null; then
            enviar_telegram "Wings: el acceso de los agentes al servidor esta roto.
${salida}"
        fi
    fi
    return 1
}

case "${1:-}" in
    --instalar)        instalar ;;
    --sincronizar)     sincronizar ;;
    --verificar)       verificar ;;
    --avisar-si-falla) avisar_si_falla ;;
    *) echo "Uso: $0 --instalar | --sincronizar | --verificar | --avisar-si-falla"; exit 2 ;;
esac
