#!/bin/bash
# correr.sh <dia> — corre un día de la tanda 5 (48 a 57) sobre la copia local wings_testing_guion y guarda la foto fotos/d<dia>.sql.gz.
# Arranca de la foto del día anterior; el 48 arranca de fotos/d47m.sql.gz: la base del sitio al 25/11 con las migraciones de main.
# Las fotos no se suben al repositorio (.gitignore). Uso, desde Git Bash: bash correr.sh 48
H="$(cd "$(dirname "$0")" && pwd)"; R="$(cd "$H/../../../../.." && pwd)"; RUT="$H/../../rutina"; F="$H/fotos"; mkdir -p "$F"
M=${MYSQL:-/c/xampp/mysql/bin/mysql}; MD=${MYSQLDUMP:-/c/xampp/mysql/bin/mysqldump}
restaurar() { $M -uroot -e "DROP DATABASE IF EXISTS wings_testing_guion; CREATE DATABASE wings_testing_guion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  gunzip -c "$1" | sed '1{/enable the sandbox mode/d}' | $M -uroot wings_testing_guion; }
php_d() { (cd "$R" && DB_DATABASE=wings_testing_guion FECHA_SIMULADA="$1" php "$H/$2" > "$H/salida-${2%.php}.txt" 2>&1); echo "  $2: $(grep -c 'NO ESPERADO' "$H/salida-${2%.php}.txt") no esperados"; grep -E 'NO ESPERADO|Exception|Error:' "$H/salida-${2%.php}.txt" | head -12 | cut -c1-260; }
rut() { (cd "$R" && DB_DATABASE=wings_testing_guion FECHA_SIMULADA="$1" php scripts/pruebas/rutina/rutina.php "$RUT/$2" > "$H/salida-${2%.json}.txt" 2>&1); echo "  $2: $(grep -E 'falló|GUARDADO|inválido' "$H/salida-${2%.json}.txt" | head -1 | cut -c1-260)"; }
art() { (cd "$R" && DB_DATABASE=wings_testing_guion FECHA_SIMULADA="$1" php artisan $2 2>&1 | tail -3 | cut -c1-200); }
D=$1; if [ "$D" = 48 ]; then restaurar "$F/d47m.sql.gz"; else restaurar "$F/d$((D-1)).sql.gz"; fi
case $D in
 48) rut "2026-11-26 09:00:00" dia-48-manana.json; php_d "2026-11-26 11:00:00" d48.php; rut "2026-11-26 21:00:00" dia-48-noche.json; FIN="2026-11-26 22:00:00" ;;
 49) rut "2026-11-27 09:00:00" dia-49-manana.json; php_d "2026-11-27 11:00:00" d49.php; rut "2026-11-27 21:30:00" dia-49-noche.json; FIN="2026-11-27 22:00:00" ;;
 50) php_d "2026-11-28 09:30:00" d50a.php; rut "2026-11-28 10:00:00" dia-50-manana.json; php_d "2026-11-28 12:30:00" d50b.php; rut "2026-11-28 14:00:00" dia-50-noche.json; FIN="2026-11-28 22:00:00" ;;
 51) php_d "2026-11-29 20:00:00" d51.php; FIN="2026-11-29 22:00:00" ;;
 52) rut "2026-11-30 09:00:00" dia-52-manana.json; php_d "2026-11-30 11:00:00" d52.php; rut "2026-11-30 21:00:00" dia-52-noche.json; FIN="2026-11-30 22:00:00" ;;
 53) art "2026-12-01 06:00:00" "cobranza:generar-deudas --si-falta"; art "2026-12-01 06:15:00" "cobranza:aplicar-punitorios"; php_d "2026-12-01 08:30:00" d53a.php; rut "2026-12-01 09:00:00" dia-53-manana.json; php_d "2026-12-01 11:00:00" d53b.php; rut "2026-12-01 21:00:00" dia-53-noche.json; FIN="2026-12-01 22:00:00" ;;
 54) rut "2026-12-02 09:00:00" dia-54-manana.json; php_d "2026-12-02 11:00:00" d54.php; php_d "2026-12-02 19:00:00" d54b.php; rut "2026-12-02 21:00:00" dia-54-noche.json; FIN="2026-12-02 22:00:00" ;;
 55) art "2026-12-03 06:00:00" "cobranza:generar-deudas --si-falta"; art "2026-12-03 06:15:00" "cobranza:aplicar-punitorios"; rut "2026-12-03 09:00:00" dia-55-manana.json; php_d "2026-12-03 11:00:00" d55.php; php_d "2026-12-03 16:30:00" d55b.php; rut "2026-12-03 21:00:00" dia-55-noche.json; php_d "2026-12-03 21:30:00" d55c.php; FIN="2026-12-03 22:00:00" ;;
 56) php_d "2026-12-04 09:00:00" d56a.php; rut "2026-12-04 09:30:00" dia-56-manana.json; rut "2026-12-04 13:00:00" dia-56-mediodia.json; php_d "2026-12-04 14:00:00" d56b.php; rut "2026-12-04 15:00:00" dia-56-tarde.json; php_d "2026-12-04 17:00:00" d56c.php; rut "2026-12-04 21:00:00" dia-56-noche.json; FIN="2026-12-04 22:00:00" ;;
 57) rut "2026-12-05 09:00:00" dia-57-manana.json; php_d "2026-12-05 13:30:00" d57.php; rut "2026-12-05 21:00:00" dia-57-noche.json; php_d "2026-12-05 21:30:00" d57b.php; FIN="2026-12-05 22:00:00" ;;
esac
(cd "$R" && DB_DATABASE=wings_testing_guion FECHA_SIMULADA="$FIN" php "$H/cierre.php") | tee -a "$H/cierres-48-57.txt"
$MD -uroot --single-transaction wings_testing_guion | gzip > "$F/d$D.sql.gz" && echo "  foto fotos/d$D.sql.gz"
