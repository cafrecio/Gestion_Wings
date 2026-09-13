#!/bin/bash
# Resumen de los avisos de la politica de seguridad de contenido (CSP).
#
# Por que un resumen y no un aviso por violacion: el respaldo avisa una vez por
# noche, pero la CSP genera un aviso por cada violacion, por cada carga de
# pagina y por cada usuario. Una pantalla con tres problemas abierta por diez
# personas son treinta mensajes en un rato: Telegram corta por limite de envios
# y el canal queda inservible. Lo que se manda es lo NUEVO, una vez por corrida.
#
# Usa el mismo robot y el mismo chat que las alertas de respaldo, con los
# secretos donde ya viven (/etc/wings-monitor/alertas.env). No duplica nada.
#
# Uso:  ./resumen-csp.sh            avisa solo lo que no se aviso antes
#       ./resumen-csp.sh --todo     lista todo lo acumulado, sin avisar
set -Eeuo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
# shellcheck source=monitoreo-common.sh
source "${SCRIPT_DIR}/monitoreo-common.sh"

cargar_config_monitoreo

APP="${WINGS_APP_DIR:-/home/wings/app}"
LOGS="${WINGS_CSP_LOGS:-${APP}/storage/logs}"
VISTAS="${WINGS_CSP_VISTAS:-/var/lib/wings/csp-avisadas.txt}"
MODO="${1:-}"

if ! ls "${LOGS}"/csp-*.log >/dev/null 2>&1; then
    echo "$(date '+%F %T') sin registros de CSP en ${LOGS}"
    exit 0
fi

WORKDIR=$(mktemp -d /tmp/resumen-csp-XXXXXX)
trap 'rm -rf "${WORKDIR}"' EXIT

# Cada linea del log trae el JSON al final. Se arma una firma por violacion
# distinta: misma directiva, mismo archivo y misma linea es el mismo problema,
# aunque lo hayan disparado quinientas visitas.
grep -ho '{"directiva".*}' "${LOGS}"/csp-*.log 2>/dev/null \
    | sed -E 's/.*"directiva":"([^"]*)".*"bloqueado":"([^"]*)".*"archivo":"([^"]*)".*"linea":([0-9]+).*/\1 | \2 | \3:\4/' \
    | sort | uniq -c | sort -rn > "${WORKDIR}/todas.txt" || true

if [ ! -s "${WORKDIR}/todas.txt" ]; then
    echo "$(date '+%F %T') sin violaciones registradas"
    exit 0
fi

if [ "${MODO}" = "--todo" ]; then
    echo "Violaciones de CSP acumuladas (veces | directiva | bloqueado | archivo:linea):"
    cat "${WORKDIR}/todas.txt"
    exit 0
fi

# Solo lo que no se aviso todavia. El archivo de vistas guarda la firma sin el
# contador: que una violacion conocida pase de 10 a 500 veces no es noticia
# nueva, el problema ya esta reportado.
mkdir -p "$(dirname "${VISTAS}")"
touch "${VISTAS}"
sed -E 's/^ *[0-9]+ //' "${WORKDIR}/todas.txt" | sort -u > "${WORKDIR}/firmas.txt"
comm -23 "${WORKDIR}/firmas.txt" <(sort -u "${VISTAS}") > "${WORKDIR}/nuevas.txt"

CANTIDAD=$(wc -l < "${WORKDIR}/nuevas.txt" | tr -d ' ')
if [ "${CANTIDAD}" -eq 0 ]; then
    echo "$(date '+%F %T') sin violaciones nuevas de CSP"
    exit 0
fi

TOTAL=$(wc -l < "${WORKDIR}/firmas.txt" | tr -d ' ')
MENSAJE="Wings: ${CANTIDAD} violacion(es) nueva(s) de la politica de seguridad (CSP), ${TOTAL} distintas en total."
MENSAJE="${MENSAJE}"$'\n\n'"$(head -n 10 "${WORKDIR}/nuevas.txt")"
if [ "${CANTIDAD}" -gt 10 ]; then
    MENSAJE="${MENSAJE}"$'\n'"... y $((CANTIDAD - 10)) mas."
fi
MENSAJE="${MENSAJE}"$'\n\n'"La politica sigue en modo aviso: nada esta bloqueado todavia."

if enviar_telegram "${MENSAJE}"; then
    # Se marcan como avisadas solo si el mensaje salio: si Telegram falla, la
    # proxima corrida vuelve a intentarlo en vez de perder el aviso.
    cat "${WORKDIR}/nuevas.txt" >> "${VISTAS}"
    sort -u -o "${VISTAS}" "${VISTAS}"
    echo "$(date '+%F %T') avisadas ${CANTIDAD} violaciones nuevas de CSP"
else
    echo "$(date '+%F %T') AVISO: no se pudo enviar el resumen de CSP por Telegram" >&2
    exit 1
fi
