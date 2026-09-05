#!/bin/bash
# Respaldo cifrado de Wings. Corre por cron todas las noches.
# El codigo no se respalda: esta en GitHub. Aca va lo que no se puede recuperar
# de otro lado: la base, la configuracion y los archivos subidos.
set -euo pipefail

DESTINO=/var/backups/wings
CLAVE=/root/wings-backup/clave
APP=/home/wings/app
FECHA=$(date +%Y-%m-%d_%H%M)

WORKDIR=$(mktemp -d /tmp/respaldo-wings-XXXXXX)
limpiar() { rm -rf "${WORKDIR}"; }
trap limpiar EXIT

# 1. Base de datos, consistente aunque haya gente operando
mysqldump --single-transaction --routines --triggers wings > "${WORKDIR}/wings.sql"

# 2. Configuracion y archivos subidos
cp "${APP}/.env" "${WORKDIR}/env.txt"
if [ -d "${APP}/storage/app" ]; then
    tar -czf "${WORKDIR}/storage.tgz" -C "${APP}" storage/app
else
    : > "${WORKDIR}/storage.tgz"
fi

# 3. Un solo paquete, comprimido y cifrado
tar -czf "${WORKDIR}/paquete.tgz" -C "${WORKDIR}" wings.sql env.txt storage.tgz
openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt \
    -in "${WORKDIR}/paquete.tgz" \
    -out "${DESTINO}/wings_${FECHA}.tgz.enc" \
    -pass file:"${CLAVE}"
chmod 600 "${DESTINO}/wings_${FECHA}.tgz.enc"

# 4. Rotacion: se conservan los 7 mas nuevos, mas los del dia 1 de cada mes
# El "|| true" es necesario: si no hay nada viejo que borrar, el filtro devuelve
# "no encontre nada", y con set -e eso mataria el script en un respaldo exitoso.
cd "${DESTINO}"
{ ls -1t wings_*.tgz.enc 2>/dev/null | tail -n +8 | grep -v -- "-01_" || true; } | while read -r viejo; do
    [ -n "${viejo}" ] && rm -f "${viejo}"
done

TAMANO=$(du -h "${DESTINO}/wings_${FECHA}.tgz.enc" | cut -f1)
echo "$(date '+%F %T') respaldo ok: wings_${FECHA}.tgz.enc (${TAMANO})"

# 5. Copia al Drive. Si falla, el respaldo local ya esta hecho: se avisa y se
#    sigue. Un problema de red no debe marcar como fallido un respaldo correcto.
REMOTO="drive:BackUp VPS"
# Ruta completa a proposito: cron usa PATH=/sbin:/bin:/usr/sbin:/usr/bin y
# rclone vive en /usr/local/bin, asi que por nombre no lo encuentra.
# Fallo seis noches seguidas en silencio por esto.
RCLONE=/usr/local/bin/rclone
RCLONE_CONF=/root/.config/rclone/rclone.conf
if "${RCLONE}" --config "${RCLONE_CONF}" copy "${DESTINO}/wings_${FECHA}.tgz.enc" "${REMOTO}/" --retries 3 2>/dev/null; then
    echo "$(date '+%F %T') copia en Drive ok"
    # En Drive se conservan 30 dias: hay espacio de sobra y sirve para ir mas atras
    "${RCLONE}" --config "${RCLONE_CONF}" delete "${REMOTO}/" --min-age 30d --include "wings_*.tgz.enc" 2>/dev/null || true
else
    echo "$(date '+%F %T') AVISO: el respaldo local esta hecho pero NO se pudo subir a Drive"
fi
