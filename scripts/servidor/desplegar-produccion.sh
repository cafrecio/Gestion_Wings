#!/usr/bin/env bash
# Despliegue de Wings en producción (/home/wings/app). Se corre como root en el servidor.
#
#   bash /tmp/desplegar-produccion.sh            # lleva producción a origin/main
#   bash /tmp/desplegar-produccion.sh 82ae1b58   # o a un commit dado
#
# Hace, en orden: respaldo de la base y del .env, controles del entorno, modo mantenimiento,
# código, dependencias, migraciones y catálogos, cachés, preflight, arriba y comprobaciones.
# Se frena ante el primer error. No toca el sitio de prueba. Escrito por Claude CAB el 30/10/2026
# (decisión de Carlos: producción después de la verificación de Codex).
set -euo pipefail

APP=/home/wings/app
USUARIO=wings
PHP="sudo -u $USUARIO /usr/bin/php82"
DESTINO=${1:-origin/main}
FECHA=$(date +%Y-%m-%d-%H%M)
RESPALDOS=/root/respaldos

paso() { echo; echo "== $*"; }
fallo() { echo "ERROR: $*" >&2; exit 1; }

cd "$APP"

paso "1. Respaldo de la base y del .env"
mysqldump --single-transaction --routines wings | gzip > "$RESPALDOS/wings-antes-despliegue-$FECHA.sql.gz"
gzip -t "$RESPALDOS/wings-antes-despliegue-$FECHA.sql.gz"
[ "$(stat -c %s "$RESPALDOS/wings-antes-despliegue-$FECHA.sql.gz")" -gt 2000 ] || fallo "el respaldo salió vacío"
cp .env "$RESPALDOS/wings-env-$FECHA"
chmod 600 "$RESPALDOS/wings-env-$FECHA"
ls -la "$RESPALDOS/wings-antes-despliegue-$FECHA.sql.gz"

paso "2. Controles del entorno antes de tocar nada"
grep -q '^APP_ENV=production' .env || fallo "APP_ENV no es production"
grep -q '^APP_DEBUG=false' .env || fallo "APP_DEBUG no es false"
grep -q '^FECHA_SIMULADA=' .env && fallo "producción tiene FECHA_SIMULADA: no corresponde"
grep -q '^APP_URL=https://wings.gestionar-te.com.ar' .env || fallo "APP_URL no es el de producción"
sudo -u $USUARIO git status --short | grep -q . && fallo "hay cambios locales en $APP; revisar antes"
sudo -u $USUARIO git fetch -q origin
echo "Producción hoy: $(sudo -u $USUARIO git log --oneline -1)"
echo "Destino:        $(sudo -u $USUARIO git log --oneline -1 "$DESTINO")"
echo "Migraciones pendientes antes: $($PHP artisan migrate:status 2>/dev/null | grep -c Pending || true)"

paso "2b. Correo: producción toma las líneas MAIL_ del sitio de prueba si no las tiene (SERVIDOR.md §correo)"
if ! grep -q '^MAIL_MAILER=' .env; then
    grep -E '^MAIL_' /home/wingstest/app/.env >> .env || fallo "el sitio de prueba no tiene las líneas MAIL_ para copiar"
    chown $USUARIO:$USUARIO .env
    echo "líneas MAIL_ agregadas: $(grep -cE '^MAIL_' .env)"
else
    echo "ya tenía MAIL_MAILER"
fi

paso "3. Mantenimiento"
$PHP artisan down

paso "4. Código"
sudo -u $USUARIO git merge --ff-only "$DESTINO"

paso "5. Dependencias y assets"
if ! sudo -u $USUARIO git diff --quiet HEAD@{1} HEAD -- composer.lock 2>/dev/null; then
    sudo -u $USUARIO composer install --no-dev --no-interaction --optimize-autoloader 2>&1 | tail -2
fi
sudo -u $USUARIO npm ci --no-audit --no-fund --silent
sudo -u $USUARIO npm run build 2>&1 | grep -i "built in\|error"

paso "6. Base: migraciones y catálogos (no toca usuarios ni datos cargados)"
$PHP artisan migrate --force
$PHP artisan db:seed --class=CatalogosSeeder --force
echo "Migraciones pendientes después: $($PHP artisan migrate:status | grep -c Pending || true)"

paso "7. Cachés"
$PHP artisan config:cache >/dev/null
$PHP artisan route:cache >/dev/null
$PHP artisan view:cache >/dev/null
$PHP artisan event:cache >/dev/null

paso "8. Preflight"
$PHP artisan wings:preflight

paso "9. Arriba y comprobaciones"
$PHP artisan up
echo "login: $(curl -s -o /dev/null -w '%{http_code}' https://wings.gestionar-te.com.ar/login)"
echo "ahora en producción: $(sudo -u $USUARIO git log --oneline -1)"
echo "programador (cron):"; crontab -l 2>/dev/null | grep -i "ejecutar-scheduler.sh" | grep -v test || echo "  !! sin cron del programador de producción"
echo "últimas líneas del registro:"; tail -3 storage/logs/laravel.log 2>/dev/null | cut -c1-160 || true
echo
echo "Listo. Si algo falla después, el respaldo es $RESPALDOS/wings-antes-despliegue-$FECHA.sql.gz"
