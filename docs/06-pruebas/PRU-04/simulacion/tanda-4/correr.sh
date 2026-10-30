#!/bin/bash
# correr.sh <dia> — corre un día de la tanda 4 sobre la copia local wings_testing_guion y guarda la foto fotos/d<dia>.sql.gz.
# Arranca desde la foto del día anterior; el 41 arranca de fotos/d40.sql.gz, que es la base del sitio de prueba
# al 18/11 (bajarla con: ssh vps "mysqldump --single-transaction wingstest | gzip" > fotos/d40.sql.gz).
# Las fotos no se suben al repositorio (.gitignore). Uso, desde Git Bash: bash correr.sh 41
H="$(cd "$(dirname "$0")" && pwd)"; R="$(cd "$H/../../../../.." && pwd)"; RUT="$H/../../rutina"; F="$H/fotos"; mkdir -p "$F"
M=${MYSQL:-/c/xampp/mysql/bin/mysql}; MD=${MYSQLDUMP:-/c/xampp/mysql/bin/mysqldump}
restaurar() { $M -uroot -e "DROP DATABASE IF EXISTS wings_testing_guion; CREATE DATABASE wings_testing_guion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  gunzip -c "$1" | sed '1{/enable the sandbox mode/d}' | $M -uroot wings_testing_guion; }
php_d() { (cd "$R" && DB_DATABASE=wings_testing_guion FECHA_SIMULADA="$1" php "$H/$2" > "$H/salida-${2%.php}.txt" 2>&1); echo "  $2: $(grep -c 'NO ESPERADO' "$H/salida-${2%.php}.txt") no esperados"; }
rut() { (cd "$R" && DB_DATABASE=wings_testing_guion FECHA_SIMULADA="$1" php scripts/pruebas/rutina/rutina.php "$RUT/$2" > "$H/salida-${2%.json}.txt" 2>&1); echo "  $2: $(grep -E 'falló|GUARDADO' "$H/salida-${2%.json}.txt" | head -1)"; }
recargos() { (cd "$R" && DB_DATABASE=wings_testing_guion FECHA_SIMULADA="$1" php artisan cobranza:aplicar-punitorios); }
D=$1; restaurar "$F/d$((D-1)).sql.gz"
case $D in
 41) rut "2026-11-19 09:00:00" dia-41-manana.json; php_d "2026-11-19 11:00:00" d41.php; rut "2026-11-19 21:00:00" dia-41-noche.json ;;
 42) rut "2026-11-20 09:00:00" dia-42-manana.json; php_d "2026-11-20 11:00:00" d42.php; rut "2026-11-20 21:00:00" dia-42-noche.json ;;
 43) php_d "2026-11-21 09:30:00" d43a.php; rut "2026-11-21 10:30:00" dia-43-manana.json; php_d "2026-11-21 13:30:00" d43b.php ;;
 44) php_d "2026-11-22 20:00:00" d44.php ;;
 45) rut "2026-11-23 09:00:00" dia-45-manana.json; php_d "2026-11-23 11:00:00" d45.php; rut "2026-11-23 21:00:00" dia-45-noche.json ;;
 46) recargos "2026-11-24 06:15:00"; php_d "2026-11-24 09:30:00" d46a.php; rut "2026-11-24 10:00:00" dia-46-manana.json; php_d "2026-11-24 11:00:00" d46b.php; php_d "2026-11-24 21:00:00" d46c.php; rut "2026-11-24 21:10:00" dia-46-noche.json ;;
 47) rut "2026-11-25 09:00:00" dia-47-manana.json; php_d "2026-11-25 11:00:00" d47.php; rut "2026-11-25 21:00:00" dia-47-noche.json ;;
esac
$MD -uroot --single-transaction wings_testing_guion | gzip > "$F/d$D.sql.gz" && echo "  foto fotos/d$D.sql.gz"
