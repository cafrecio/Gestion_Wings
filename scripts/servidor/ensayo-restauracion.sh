#!/bin/bash
# Ensayo T82: levantar Wings desde lo guardado, aparte de produccion.
# No toca /home/wings, /home/wingstest, ni las bases wings / wingstest.
set -uo pipefail
BASE=/root/ensayo-t82
DB=wings_ensayo_t82
USR=ensayo_t82
PORT=8099
PHP=php82
RCLONE="/usr/local/bin/rclone --config /root/.config/rclone/rclone.conf"
rm -rf "$BASE"; mkdir -p "$BASE"; cd "$BASE"
T0=$(date +%s)
paso() { echo; echo "== [$(( $(date +%s) - T0 ))s] $*"; }
fin() { echo "   -> $1"; }

paso "1. Bajar de Drive el respaldo mas nuevo (no la copia local)"
ULT=$($RCLONE lsf 'drive:BackUp VPS' --include 'wings_*.tgz.enc' 2>/dev/null | sort | tail -1)
$RCLONE copy "drive:BackUp VPS/$ULT" "$BASE/" 2>/dev/null && fin "ok: $ULT ($(du -h "$BASE/$ULT" | cut -f1))" || { fin "FALLA: no se pudo bajar"; exit 1; }

paso "2. Descifrar (clave identica a la copia de Drive, comprobada por huella)"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in "$BASE/$ULT" -out paquete.tgz -pass file:/root/wings-backup/clave && tar -xzf paquete.tgz && fin "ok: $(ls wings.sql env.txt storage.tgz | tr '\n' ' ')" || { fin "FALLA"; exit 1; }

paso "3. Base aparte"
PASS=$(openssl rand -hex 16)
mysql -e "DROP DATABASE IF EXISTS $DB; CREATE DATABASE $DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; DROP USER IF EXISTS '$USR'@'localhost'; CREATE USER '$USR'@'localhost' IDENTIFIED BY '$PASS'; GRANT ALL ON $DB.* TO '$USR'@'localhost';"
mysql $DB < wings.sql && fin "ok: $(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB'") tablas"
for T in users alumnos pagos deuda_cuotas clases cajas_operativas cashflow_movimientos profesores migrations; do
  printf "   %-22s viva=%-6s copia=%s\n" $T "$(mysql -N -e "SELECT COUNT(*) FROM wings.$T" 2>&1)" "$(mysql -N -e "SELECT COUNT(*) FROM $DB.$T" 2>&1)"
done

paso "4. Codigo desde GitHub, sin credenciales (como en un servidor nuevo)"
COMMIT=$(git -C /home/wings/app rev-parse HEAD)
GIT_TERMINAL_PROMPT=0 git clone -q https://github.com/cafrecio/Gestion_Wings.git app 2>&1 | tail -2
if [ -d app/.git ]; then git -C app checkout -q "$COMMIT" && fin "ok: $(git -C app log --oneline -1)"; else fin "FALLA: no se pudo clonar sin credenciales"; exit 1; fi

paso "5. Configuracion y archivos subidos"
cp env.txt app/.env
sed -i -e "s/^DB_DATABASE=.*/DB_DATABASE=$DB/" -e "s/^DB_USERNAME=.*/DB_USERNAME=$USR/" -e "s/^DB_PASSWORD=.*/DB_PASSWORD=$PASS/" \
       -e "s#^APP_URL=.*#APP_URL=http://127.0.0.1:$PORT#" app/.env
grep -q '^APP_KEY=base64:' app/.env && fin "APP_KEY presente en el respaldo" || fin "FALLA: sin APP_KEY"
if [ -s storage.tgz ]; then tar -xzf storage.tgz -C app && fin "storage/app restaurado: $(find app/storage/app -type f | wc -l) archivos"; else fin "storage vacio en el respaldo"; fi

paso "6. Dependencias y compilacion"
cd app
COMPOSER_ALLOW_SUPERUSER=1 $PHP /usr/local/bin/composer install -q --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3; fin "composer: $?"
npm ci --silent >/dev/null 2>&1 && npm run build >/dev/null 2>&1 && fin "build ok: $(ls public/build/assets | wc -l) archivos" || fin "FALLA npm"
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache

paso "7. Arranque"
$PHP artisan config:clear -q
$PHP artisan migrate:status 2>&1 | grep -c -E 'Ran' | xargs -I{} echo "   migraciones aplicadas: {}"
$PHP artisan migrate:status 2>&1 | grep -c -E 'Pending' | xargs -I{} echo "   migraciones pendientes del codigo de produccion: {}"
$PHP artisan wings:preflight 2>&1 | tail -15
$PHP artisan schedule:list 2>&1 | tail -10
$PHP artisan serve --host=127.0.0.1 --port=$PORT >/dev/null 2>&1 &
SRV=$!; sleep 4
echo "   /login -> $(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$PORT/login)"
echo "   /      -> $(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$PORT/)"
kill $SRV 2>/dev/null

paso "8. Limpieza"
cd /; mysql -e "DROP DATABASE $DB; DROP USER '$USR'@'localhost';"; rm -rf "$BASE"
fin "base, usuario y carpeta del ensayo borrados"
echo; echo "== TOTAL: $(( $(date +%s) - T0 ))s"
