#!/bin/bash
# Ensayo T82: levantar Wings desde lo guardado, aparte de produccion.
# No toca /home/wings, /home/wingstest, ni las bases wings / wingstest.
set -uo pipefail
BASE=/root/ensayo-t82
DB=wings_ensayo_t82
USR=ensayo_t82
PORT=8099
PHP=php82
REPO="${REPO:-https://github.com/cafrecio/Gestion_Wings.git}"
CONF_REAL=/root/.config/rclone/rclone.conf
FALLAS=0
SRV=
# H2 de Codex: cualquier salida limpia. Antes, un fallo a mitad de camino dejaba el respaldo descifrado, el .env, la base y el usuario.
limpiar() {
  [ -n "$SRV" ] && kill "$SRV" 2>/dev/null
  cd /
  mysql -e "DROP DATABASE IF EXISTS $DB; DROP USER IF EXISTS '$USR'@'localhost';"
  rm -rf "$BASE"
  echo "   limpieza: base, usuario y carpeta del ensayo borrados"
}
trap limpiar EXIT
trap 'exit 130' INT TERM
rm -rf "$BASE"; mkdir -p "$BASE"; chmod 700 "$BASE"; cd "$BASE"
# H4 de Codex: rclone reescribe su configuracion al renovar la credencial. Trabaja sobre una copia que se borra con el ensayo.
HUELLA_CONF=$(sha256sum "$CONF_REAL" | cut -d' ' -f1)
cp -p "$CONF_REAL" "$BASE/rclone.conf"
RCLONE="/usr/local/bin/rclone --config $BASE/rclone.conf"
T0=$(date +%s)
paso() { echo; echo "== [$(( $(date +%s) - T0 ))s] $*"; }
fin() { echo "   -> $1"; }
falla() { echo "   -> FALLA: $1"; FALLAS=$((FALLAS+1)); }

# FUENTE=wings (por defecto): respaldo nocturno bajado de Drive.
# FUENTE=wingstest: el sitio de prueba no tiene respaldo nocturno; se arma su paquete
# igual que respaldar.sh (solo lectura sobre wingstest) para ensayar con datos.
FUENTE="${FUENTE:-wings}"
SRC_DB="$FUENTE"; SRC_APP="/home/$FUENTE/app"
if [ "$FUENTE" = "wings" ]; then
  paso "1. Bajar de Drive el respaldo mas nuevo (no la copia local)"
  ULT=$($RCLONE lsf 'drive:BackUp VPS' --include 'wings_*.tgz.enc' 2>/dev/null | sort | tail -1)
  $RCLONE copy "drive:BackUp VPS/$ULT" "$BASE/" 2>/dev/null && fin "ok: $ULT ($(du -h "$BASE/$ULT" | cut -f1))" || { fin "FALLA: no se pudo bajar"; exit 1; }
else
  paso "1. Armar el paquete de $FUENTE como respaldar.sh"
  mkdir -p arma
  mysqldump --single-transaction --routines --triggers "$SRC_DB" > arma/wings.sql
  cp "$SRC_APP/.env" arma/env.txt
  if [ -d "$SRC_APP/storage/app" ]; then tar -czf arma/storage.tgz -C "$SRC_APP" storage/app; else : > arma/storage.tgz; fi
  tar -czf arma/paquete.tgz -C arma wings.sql env.txt storage.tgz
  ULT="${FUENTE}_ensayo.tgz.enc"
  openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -in arma/paquete.tgz -out "$ULT" -pass file:/root/wings-backup/clave
  rm -rf arma; fin "ok: $ULT ($(du -h "$ULT" | cut -f1))"
fi

paso "2. Descifrar (clave identica a la copia de Drive, comprobada por huella)"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in "$BASE/$ULT" -out paquete.tgz -pass file:/root/wings-backup/clave && tar -xzf paquete.tgz && fin "ok: $(ls wings.sql env.txt storage.tgz | tr '\n' ' ')" || { fin "FALLA"; exit 1; }

paso "3. Base aparte"
PASS=$(openssl rand -hex 16)
mysql -e "DROP DATABASE IF EXISTS $DB; CREATE DATABASE $DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; DROP USER IF EXISTS '$USR'@'localhost'; CREATE USER '$USR'@'localhost' IDENTIFIED BY '$PASS'; GRANT ALL ON $DB.* TO '$USR'@'localhost';"
mysql $DB < wings.sql && fin "ok: $(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB'") tablas"
DIF=0
for T in users alumnos pagos deuda_cuotas clases asistencias cajas_operativas movimientos_operativos cashflow_movimientos liquidaciones liquidacion_detalles profesores migrations; do
  V=$(mysql -N -e "SELECT COUNT(*) FROM $SRC_DB.$T" 2>&1); C=$(mysql -N -e "SELECT COUNT(*) FROM $DB.$T" 2>&1)
  [ "$V" = "$C" ] && [[ "$V" != *ERROR* ]] && M=ok || { M="NO COINCIDE"; DIF=$((DIF+1)); }
  printf "   %-24s viva=%-7s copia=%-7s %s\n" $T "$V" "$C" "$M"
done
# Plata, al peso: el total de cada tabla de dinero tiene que ser identico.
for Q in "pagos:SUM(monto_final)" "deuda_cuotas:SUM(monto_original)" "deuda_cuotas:SUM(monto_pagado)" "cashflow_movimientos:SUM(monto)" "movimientos_operativos:SUM(monto)" "liquidaciones:SUM(total_calculado)" "liquidacion_detalles:SUM(monto)"; do
  T=${Q%%:*}; E=${Q#*:}
  V=$(mysql -N -e "SELECT COALESCE($E,0) FROM $SRC_DB.$T" 2>&1); C=$(mysql -N -e "SELECT COALESCE($E,0) FROM $DB.$T" 2>&1)
  [ "$V" = "$C" ] && [[ "$V" != *ERROR* ]] && M=ok || { M="NO COINCIDE"; DIF=$((DIF+1)); }
  printf "   %-24s viva=%-12s copia=%-12s %s\n" "$T.$E" "$V" "$C" "$M"
done
fin "diferencias: $DIF"
[ "$DIF" = 0 ] || FALLAS=$((FALLAS+1))

paso "4. Codigo desde GitHub, sin credenciales (como en un servidor nuevo)"
COMMIT=$(git -c safe.directory="$SRC_APP" -C "$SRC_APP" rev-parse HEAD)
GIT_TERMINAL_PROMPT=0 git clone -q "$REPO" app 2>&1 | tail -2
if [ -d app/.git ]; then git -C app checkout -q "$COMMIT" && fin "ok: $(git -C app log --oneline -1)"; else fin "FALLA: no se pudo clonar sin credenciales"; exit 1; fi

paso "5. Configuracion y archivos subidos"
cp env.txt app/.env
sed -i -e "s/^DB_DATABASE=.*/DB_DATABASE=$DB/" -e "s/^DB_USERNAME=.*/DB_USERNAME=$USR/" -e "s/^DB_PASSWORD=.*/DB_PASSWORD=$PASS/" \
       -e "s#^APP_URL=.*#APP_URL=http://127.0.0.1:$PORT#" app/.env
grep -q '^APP_KEY=base64:' app/.env && fin "APP_KEY presente en el respaldo" || falla "sin APP_KEY"
if [ -s storage.tgz ]; then tar -xzf storage.tgz -C app && fin "storage/app restaurado: $(find app/storage/app -type f | wc -l) archivos"; else fin "storage vacio en el respaldo"; fi

paso "6. Dependencias y compilacion"
cd app
COMPOSER_ALLOW_SUPERUSER=1 $PHP /usr/local/bin/composer install -q --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3; fin "composer: $?"
npm ci --silent >/dev/null 2>&1 && npm run build >/dev/null 2>&1 && fin "build ok: $(ls public/build/assets | wc -l) archivos" || falla "npm"
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache

paso "7. Arranque"
$PHP artisan config:clear -q
$PHP artisan migrate:status 2>&1 | grep -c -E 'Ran' | xargs -I{} echo "   migraciones aplicadas: {}"
$PHP artisan migrate:status 2>&1 | grep -c -E 'Pending' | xargs -I{} echo "   migraciones pendientes del codigo de produccion: {}"
$PHP artisan wings:preflight 2>&1 | tail -15
$PHP artisan schedule:list 2>&1 | tail -10
$PHP artisan serve --host=127.0.0.1 --port=$PORT >/dev/null 2>&1 &
SRV=$!; sleep 4
LOGIN=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$PORT/login)
RAIZ=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$PORT/)
echo "   /login -> $LOGIN"
echo "   /      -> $RAIZ"
[ "$LOGIN" = 200 ] || falla "/login no responde 200"
kill $SRV 2>/dev/null; SRV=

paso "8. Resultado"
[ "$(sha256sum "$CONF_REAL" | cut -d' ' -f1)" = "$HUELLA_CONF" ] && fin "rclone.conf real sin cambios" || falla "rclone.conf real cambio durante el ensayo"
echo; echo "== TOTAL: $(( $(date +%s) - T0 ))s · fallas: $FALLAS"
# H1 de Codex: el codigo de salida dice la verdad. Antes terminaba en 0 con diferencias.
[ "$FALLAS" = 0 ]
