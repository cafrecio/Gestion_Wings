#!/bin/bash
# Restaura un respaldo de Wings.
#
#   Ensayo (no toca nada real):   ./restaurar.sh <archivo.enc> ensayo
#   De verdad (PISA la base):     ./restaurar.sh <archivo.enc> EN-SERIO
#
# El ensayo restaura en una base descartable y compara los conteos.
set -euo pipefail

ARCHIVO="${1:-}"
MODO="${2:-ensayo}"
CLAVE=/root/wings-backup/clave

if [ -z "${ARCHIVO}" ] || [ ! -f "${ARCHIVO}" ]; then
    echo "Falta el archivo de respaldo. Los disponibles:"
    ls -1t /var/backups/wings/*.tgz.enc 2>/dev/null || echo "  (ninguno)"
    exit 1
fi

WORKDIR=$(mktemp -d /tmp/restaurar-wings-XXXXXX)
limpiar() { rm -rf "${WORKDIR}"; }
trap limpiar EXIT

echo "== Descifrando =="
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 \
    -in "${ARCHIVO}" -out "${WORKDIR}/paquete.tgz" -pass file:"${CLAVE}"
tar -xzf "${WORKDIR}/paquete.tgz" -C "${WORKDIR}"
echo "   contenido: $(ls -1 "${WORKDIR}" | tr '\n' ' ')"

if [ "${MODO}" = "EN-SERIO" ]; then
    echo "== RESTAURANDO SOBRE LA BASE REAL =="
    mysql wings < "${WORKDIR}/wings.sql"
    echo "   listo. Revisar el .env por separado: ${WORKDIR}/env.txt"
    exit 0
fi

echo "== Ensayo en base descartable =="
mysql -e "DROP DATABASE IF EXISTS wings_ensayo; CREATE DATABASE wings_ensayo CHARACTER SET utf8mb4;"
mysql wings_ensayo < "${WORKDIR}/wings.sql"

echo "== Comparacion contra la base viva =="
DIFERENCIAS=0
for TABLA in users alumnos pagos deuda_cuotas clases cajas_operativas movimientos_operativos cashflow_movimientos profesores deportes niveles grupos rubros subrubros tipos_caja; do
    VIVA=$(mysql -N -B -e "SELECT COUNT(*) FROM wings.${TABLA};" 2>/dev/null || echo "?")
    COPIA=$(mysql -N -B -e "SELECT COUNT(*) FROM wings_ensayo.${TABLA};" 2>/dev/null || echo "?")
    if [ "${VIVA}" = "${COPIA}" ]; then
        printf "   %-24s %-6s = %-6s ok\n" "${TABLA}" "${VIVA}" "${COPIA}"
    else
        printf "   %-24s %-6s = %-6s NO COINCIDE\n" "${TABLA}" "${VIVA}" "${COPIA}"
        DIFERENCIAS=$((DIFERENCIAS + 1))
    fi
done

mysql -e "DROP DATABASE wings_ensayo;"

if [ "${DIFERENCIAS}" -eq 0 ]; then
    echo "== ENSAYO CORRECTO: el respaldo se restaura completo =="
    exit 0
fi
echo "== ENSAYO FALLIDO: ${DIFERENCIAS} tabla(s) no coinciden =="
exit 1
