#!/bin/bash
# Restaura un respaldo de Wings.
#
#   Ensayo (no toca nada real):   ./restaurar.sh <archivo.enc> ensayo
#   De verdad (PISA lo que hay):  ./restaurar.sh <archivo.enc> EN-SERIO
#
# El respaldo guarda tres cosas y la restauracion repone las tres:
#
#   wings.sql    la base
#   storage.tgz  los archivos subidos (recibos emitidos)
#   env.txt      la configuracion, con APP_KEY
#
# Hasta el 13/09/2026 esto restauraba SOLO la base: storage.tgz se respaldaba
# todas las noches, se descomprimia en el directorio temporal y el `trap
# limpiar` lo borraba sin que nadie lo mirara. Un servidor perdido se
# reponia sin sus recibos.
set -euo pipefail

ARCHIVO="${1:-}"
MODO="${2:-ensayo}"

CLAVE="${WINGS_BACKUP_KEY:-/root/wings-backup/clave}"
APP="${WINGS_APP_DIR:-/home/wings/app}"
DESTINO="${WINGS_BACKUP_DIR:-/var/backups/wings}"
BASE_VIVA="${WINGS_DB:-wings}"
BASE_ENSAYO="${WINGS_DB_ENSAYO:-wings_ensayo}"

if [ -z "${ARCHIVO}" ] || [ ! -f "${ARCHIVO}" ]; then
    echo "Falta el archivo de respaldo. Los disponibles:"
    ls -1t "${DESTINO}"/*.tgz.enc 2>/dev/null || echo "  (ninguno)"
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

# Un paquete al que le falta una de las tres partes no sirve para reponer el
# servidor, aunque la base restaure bien. Se corta antes de tocar nada.
FALTANTES=""
for PARTE in wings.sql env.txt storage.tgz; do
    [ -f "${WORKDIR}/${PARTE}" ] || FALTANTES="${FALTANTES} ${PARTE}"
done
if [ -n "${FALTANTES}" ]; then
    echo "== PAQUETE INCOMPLETO: falta${FALTANTES} =="
    exit 1
fi

# Sin APP_KEY, Laravel no arranca: no descifra las sesiones ni nada guardado
# cifrado. Un respaldo sin esa linea restaura una base que no se puede usar.
if ! grep -qE '^APP_KEY=base64:.+' "${WORKDIR}/env.txt"; then
    echo "== CONFIGURACION INUTILIZABLE: env.txt no trae un APP_KEY valido =="
    exit 1
fi

if [ "${MODO}" = "EN-SERIO" ]; then
    echo "== RESTAURANDO SOBRE LO REAL =="

    echo "-- base ${BASE_VIVA}"
    mysql "${BASE_VIVA}" < "${WORKDIR}/wings.sql"

    # Los archivos se reponen sobre la aplicacion. Lo que haya ahora se aparta
    # primero: si esta restauracion se lanzo por error, se puede volver.
    if [ -d "${APP}/storage/app" ]; then
        APARTADO="${APP}/storage/app.reemplazado-$(date +%Y%m%d%H%M%S)"
        mv "${APP}/storage/app" "${APARTADO}"
        echo "-- lo anterior quedo en ${APARTADO}"
    fi
    tar -xzf "${WORKDIR}/storage.tgz" -C "${APP}"
    echo "-- archivos repuestos en ${APP}/storage/app"

    # El .env NO se pisa solo: puede tener claves rotadas despues del respaldo,
    # y sobrescribirlo a ciegas deja al servidor con credenciales viejas.
    cp "${WORKDIR}/env.txt" "${APP}/.env.del-respaldo"
    echo "-- configuracion copiada a ${APP}/.env.del-respaldo (comparar a mano)"
    exit 0
fi

echo "== Ensayo en base descartable =="
mysql -e "DROP DATABASE IF EXISTS ${BASE_ENSAYO}; CREATE DATABASE ${BASE_ENSAYO} CHARACTER SET utf8mb4;"
mysql "${BASE_ENSAYO}" < "${WORKDIR}/wings.sql"

echo "== Comparacion contra la base viva =="

# Las tablas salen de las dos bases, no de una lista escrita a mano.
#
# La lista fija tenia 15 de las 35 tablas y no incluia `pago_deuda_cuota`, que
# es donde vive la relacion entre un pago y los periodos que cubre. El ensayo
# podia anunciar "se restaura completo" sin haber mirado las imputaciones.
mysql -N -B -e "SHOW TABLES;" "${BASE_VIVA}"   > "${WORKDIR}/tablas_vivas.txt" 2>/dev/null || : > "${WORKDIR}/tablas_vivas.txt"
mysql -N -B -e "SHOW TABLES;" "${BASE_ENSAYO}" > "${WORKDIR}/tablas_copia.txt" 2>/dev/null || : > "${WORKDIR}/tablas_copia.txt"
sort -u "${WORKDIR}/tablas_vivas.txt" "${WORKDIR}/tablas_copia.txt" > "${WORKDIR}/tablas.txt"

if [ ! -s "${WORKDIR}/tablas.txt" ]; then
    echo "   no se pudo enumerar ninguna tabla en ninguna de las dos bases"
    mysql -e "DROP DATABASE IF EXISTS ${BASE_ENSAYO};"
    echo "== ENSAYO FALLIDO: sin tablas que comparar =="
    exit 1
fi

DIFERENCIAS=0
COMPARADAS=0
while read -r TABLA; do
    [ -n "${TABLA}" ] || continue

    # Una tabla que falta de un lado es un fallo, no un empate. Antes, cuando no
    # existia en ninguna de las dos bases, ambos lados devolvian "?" y el script
    # las daba por iguales.
    EN_VIVA=$(grep -Fxc -- "${TABLA}" "${WORKDIR}/tablas_vivas.txt" || true)
    EN_COPIA=$(grep -Fxc -- "${TABLA}" "${WORKDIR}/tablas_copia.txt" || true)
    if [ "${EN_VIVA}" -eq 0 ] || [ "${EN_COPIA}" -eq 0 ]; then
        DONDE="solo en la copia"
        [ "${EN_COPIA}" -eq 0 ] && DONDE="solo en la base viva"
        printf "   %-28s %s\n" "${TABLA}" "FALTA (${DONDE})"
        DIFERENCIAS=$((DIFERENCIAS + 1))
        continue
    fi

    VIVA=$(mysql -N -B -e "SELECT COUNT(*) FROM \`${BASE_VIVA}\`.\`${TABLA}\`;")
    COPIA=$(mysql -N -B -e "SELECT COUNT(*) FROM \`${BASE_ENSAYO}\`.\`${TABLA}\`;")
    COMPARADAS=$((COMPARADAS + 1))
    if [ "${VIVA}" = "${COPIA}" ]; then
        printf "   %-28s %-8s = %-8s ok\n" "${TABLA}" "${VIVA}" "${COPIA}"
    else
        printf "   %-28s %-8s = %-8s NO COINCIDE\n" "${TABLA}" "${VIVA}" "${COPIA}"
        DIFERENCIAS=$((DIFERENCIAS + 1))
    fi
done < "${WORKDIR}/tablas.txt"

echo "== Archivos subidos =="
mkdir -p "${WORKDIR}/storage-copia"
tar -xzf "${WORKDIR}/storage.tgz" -C "${WORKDIR}/storage-copia" 2>/dev/null || true
ARCH_COPIA=$(find "${WORKDIR}/storage-copia" -type f 2>/dev/null | wc -l | tr -d ' ')
if [ -d "${APP}/storage/app" ]; then
    ARCH_VIVOS=$(find "${APP}/storage/app" -type f 2>/dev/null | wc -l | tr -d ' ')
else
    ARCH_VIVOS=0
fi
if [ "${ARCH_COPIA}" -ge "${ARCH_VIVOS}" ]; then
    printf "   %-28s %-8s >= %-8s ok\n" "archivos" "${ARCH_COPIA}" "${ARCH_VIVOS}"
else
    # Menos archivos que los vivos significa que el respaldo perdio algo: los
    # recibos emitidos despues del respaldo si pueden faltar, pero no al reves.
    printf "   %-28s %-8s <  %-8s FALTAN ARCHIVOS\n" "archivos" "${ARCH_COPIA}" "${ARCH_VIVOS}"
    DIFERENCIAS=$((DIFERENCIAS + 1))
fi

echo "== Configuracion =="
echo "   APP_KEY presente en env.txt"

mysql -e "DROP DATABASE IF EXISTS ${BASE_ENSAYO};"

if [ "${DIFERENCIAS}" -eq 0 ]; then
    echo "== ENSAYO CORRECTO: ${COMPARADAS} tablas, archivos y configuracion =="
    exit 0
fi
echo "== ENSAYO FALLIDO: ${DIFERENCIAS} comprobacion(es) no coinciden =="
exit 1
