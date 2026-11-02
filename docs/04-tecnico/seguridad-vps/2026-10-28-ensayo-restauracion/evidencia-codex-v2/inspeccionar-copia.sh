#!/bin/bash
# Se invoca antes del primer curl real a /login y del trap del ensayo original.
set -uo pipefail
C=/root/control-t82-codex-v2
B=/root/ensayo-t82
[[ ${FUENTE_CONTROL:-} = wings || ${FUENTE_CONTROL:-} = wingstest ]] || exit 90
S=$FUENTE_CONTROL
viva=$(git -c safe.directory="/home/$S/app" -C "/home/$S/app" rev-parse HEAD)
copia=$(git -C "$B/app" rev-parse HEAD)
if [ "$viva" = "$copia" ]; then echo CODIGO_IGUAL=si; else echo CODIGO_IGUAL=no; fi > "$C/$S-codigo.txt"
python3 "$C/auditar-datos.py" compare "$S" wings_ensayo_t82 > "$C/$S-datos.jsonl"
echo $? > "$C/$S-datos.codigo"
python3 "$C/auditar-datos.py" files "/home/$S/app/storage/app" "$B/app/storage/app" > "$C/$S-archivos.jsonl"
echo $? > "$C/$S-archivos.codigo"
FUENTE_CONTROL=$S php82 "$C/funcionamiento.php" > "$C/$S-funcional.jsonl"
echo $? > "$C/$S-funcional.codigo"
mapfile -t paquetes < <(find "$B" -maxdepth 1 -type f -name '*.tgz.enc')
[ "${#paquetes[@]}" = 1 ] || exit 91
[ "$(mysql -N -e "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='wings_ensayo'")" = 0 ] || exit 92
WINGS_DB=$S WINGS_APP_DIR="/home/$S/app" bash "$C/restaurar.sh" "${paquetes[0]}" ensayo > "$C/$S-canonico.txt" 2>&1
echo $? > "$C/$S-canonico.codigo"
echo AUDITORIA_TERMINADA > "$C/$S-auditoria-marca"
