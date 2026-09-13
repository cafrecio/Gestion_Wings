#!/usr/bin/env bash

# SEG-06 — la restauracion repone base, archivos y configuracion, y el ensayo
# comprueba las tres cosas.
#
# El respaldo nocturno guarda tres piezas: la base, los archivos subidos
# (storage.tgz, donde viven los recibos emitidos) y la configuracion (env.txt,
# con APP_KEY). Hasta el 13/09/2026 `restaurar.sh` reponia solo la base:
# storage.tgz se descomprimia en el directorio temporal y el `trap limpiar` lo
# borraba. Un servidor perdido se reponia sin sus recibos, y el ensayo anunciaba
# "el respaldo se restaura completo".
#
# El ensayo ademas comparaba una lista fija de 15 tablas de las 35 del esquema,
# sin `pago_deuda_cuota` — la tabla que dice que periodos cubre cada pago.
#
# Esta prueba arma paquetes cifrados reales y un `mysql` falso, y exige que cada
# uno de esos agujeros de por rojo.
#
# Uso: bash tests/Deployment/restaurar_repone_todo_test.sh

set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")/../.." && pwd -P)"
SCRIPT="$PROJECT_DIR/scripts/servidor/restaurar.sh"
TEST_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/wings-restaurar-test.XXXXXX")"
FAKE_BIN="$TEST_ROOT/bin"
APP_DIR="$TEST_ROOT/app"
CLAVE="$TEST_ROOT/clave"

cleanup() { rm -rf -- "$TEST_ROOT"; }
trap cleanup EXIT

mkdir -p "$FAKE_BIN" "$APP_DIR/storage/app/private/recibos"
printf 'clave-de-prueba\n' > "$CLAVE"

# En Git Bash el openssl es el de Windows y no entiende /tmp/...: las rutas que
# recibe se pasan en formato mixto (C:/...), que ambos lados aceptan.
ruta_nativa() {
    if command -v cygpath >/dev/null 2>&1; then cygpath -m "$1"; else printf '%s' "$1"; fi
}

fallos=0
verificar() {
    local descripcion="$1" esperado="$2" obtenido="$3"
    if [ "$esperado" = "$obtenido" ]; then
        printf '  ok    %s\n' "$descripcion"
    else
        printf '  FALLA %s (esperado %s, obtenido %s)\n' "$descripcion" "$esperado" "$obtenido"
        fallos=$((fallos + 1))
    fi
}

# ---------------------------------------------------------------- mysql falso
#
# Responde SHOW TABLES desde archivos de control y COUNT(*) desde un mapa, para
# poder montar el caso "esta viva pero no en la copia" sin una base real.
cat > "$FAKE_BIN/mysql" <<'EOF'
#!/usr/bin/env bash
consulta=""
base=""
for (( i=1; i<=$#; i++ )); do
    argumento="${!i}"
    case "$argumento" in
        -e) siguiente=$((i+1)); consulta="${!siguiente}"; i=$siguiente ;;
        -N|-B) ;;
        *) [ -z "$base" ] && base="$argumento" ;;
    esac
done

# Carga del .sql por redireccion: se consume y no se responde nada.
if [ -z "$consulta" ]; then cat > /dev/null; exit 0; fi

case "$consulta" in
    *"SHOW TABLES"*)
        archivo="$CBM_TEST_ROOT/tablas_${base}.txt"
        [ -f "$archivo" ] && cat "$archivo"
        exit 0
        ;;
    *"SELECT"*)
        # A que base apunta la consulta: las de contenido llevan la base
        # embebida, no como argumento.
        if [[ "$consulta" == *"${CBM_BASE_ENSAYO}"* ]]; then lado="copia"; else lado="vivos"; fi

        # SEG-07: importes e invariantes se responden por patron, porque no son
        # "una tabla" sino consultas con SUM, JOIN y subconsultas.
        patrones="$CBM_TEST_ROOT/patrones_${lado}.txt"
        if [ -f "$patrones" ]; then
            while IFS='=' read -r patron valor; do
                [ -n "$patron" ] || continue
                if [[ "$consulta" == *"$patron"* ]]; then printf '%s\n' "$valor"; exit 0; fi
            done < "$patrones"
        fi

        # SEG-06: conteo simple de una tabla.
        if [[ "$consulta" == *"SELECT COUNT(*) FROM \`"* ]]; then
            tabla="$(printf '%s' "$consulta" | sed -E 's/.*`[^`]+`\.`([^`]+)`.*/\1/')"
            valor="$(grep -E "^${tabla}=" "$CBM_TEST_ROOT/conteos_${lado}.txt" 2>/dev/null | cut -d= -f2)"
            printf '%s\n' "${valor:-0}"
            exit 0
        fi

        printf '0\n'
        exit 0
        ;;
esac
exit 0
EOF
chmod +x "$FAKE_BIN/mysql"

# ------------------------------------------------------------ openssl envuelto
#
# El script de restauracion es el del servidor y usa rutas POSIX, como
# corresponde en Linux. Corriendo la prueba en Git Bash, el openssl que
# encuentra es el de Windows y no sabe leer /tmp/...: este envoltorio traduce
# las rutas y delega en el openssl real, para no ensuciar el script con
# parches de una plataforma donde no corre.
OPENSSL_REAL="$(command -v openssl)"
cat > "$FAKE_BIN/openssl" <<EOF
#!/usr/bin/env bash
convertir() {
    if command -v cygpath >/dev/null 2>&1; then cygpath -m "\$1"; else printf '%s' "\$1"; fi
}
argumentos=()
for argumento in "\$@"; do
    case "\$argumento" in
        /tmp/*|/c/*)       argumentos+=("\$(convertir "\$argumento")") ;;
        pass:*)            argumentos+=("\$argumento") ;;
        file:/tmp/*|file:/c/*)
                           argumentos+=("file:\$(convertir "\${argumento#file:}")") ;;
        *)                 argumentos+=("\$argumento") ;;
    esac
done
exec "$OPENSSL_REAL" "\${argumentos[@]}"
EOF
chmod +x "$FAKE_BIN/openssl"

export PATH="$FAKE_BIN:$PATH"
export CBM_TEST_ROOT="$TEST_ROOT"
export CBM_BASE_ENSAYO="wings_ensayo"
export WINGS_BACKUP_KEY="$CLAVE"
export WINGS_APP_DIR="$APP_DIR"
export WINGS_DB="wings"
export WINGS_DB_ENSAYO="wings_ensayo"

# ------------------------------------------------------- armado de un paquete
#
# $1 destino .enc | $2 "con-env"/"sin-env" | $3 "con-storage"/"sin-storage"
# $4 cantidad de recibos dentro de storage.tgz
armar_paquete() {
    local destino="$1" env_modo="$2" storage_modo="$3" recibos="${4:-2}"
    local dir="$TEST_ROOT/armado.$$.$RANDOM"
    mkdir -p "$dir/storage/app/private/recibos"

    printf -- '-- volcado de prueba\n' > "$dir/wings.sql"

    if [ "$env_modo" = "con-env" ]; then
        printf 'APP_ENV=production\nAPP_KEY=base64:TFhBY2xhdmVEZVBydWViYVBhcmFXaW5nc1Rlc3Q9\nDB_DATABASE=wings\n' > "$dir/env.txt"
    else
        printf 'APP_ENV=production\nAPP_KEY=\nDB_DATABASE=wings\n' > "$dir/env.txt"
    fi

    local i=1
    while [ "$i" -le "$recibos" ]; do
        printf 'recibo %s\n' "$i" > "$dir/storage/app/private/recibos/recibo-$i.pdf"
        i=$((i + 1))
    done
    tar -czf "$dir/storage.tgz" -C "$dir" storage/app

    local piezas=(wings.sql env.txt)
    [ "$storage_modo" = "con-storage" ] && piezas+=(storage.tgz)

    tar -czf "$dir/paquete.tgz" -C "$dir" "${piezas[@]}"
    openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt \
        -in "$(ruta_nativa "$dir/paquete.tgz")" \
        -out "$(ruta_nativa "$destino")" \
        -pass file:"$(ruta_nativa "$CLAVE")"
    rm -rf "$dir"
}

correr_ensayo() {
    bash "$SCRIPT" "$1" ensayo > "$TEST_ROOT/salida.log" 2>&1 && echo 0 || echo $?
}

echo "SEG-06 — restauracion integral"

# 1. Las tres tablas coinciden y estan las tres piezas: el ensayo pasa.
printf 'alumnos\npagos\npago_deuda_cuota\n' > "$TEST_ROOT/tablas_wings.txt"
printf 'alumnos\npagos\npago_deuda_cuota\n' > "$TEST_ROOT/tablas_wings_ensayo.txt"
printf 'alumnos=60\npagos=12\npago_deuda_cuota=20\n' > "$TEST_ROOT/conteos_vivos.txt"
printf 'alumnos=60\npagos=12\npago_deuda_cuota=20\n' > "$TEST_ROOT/conteos_copia.txt"
armar_paquete "$TEST_ROOT/completo.enc" con-env con-storage 2
printf 'recibo 1\n' > "$APP_DIR/storage/app/private/recibos/recibo-1.pdf"
printf 'recibo 2\n' > "$APP_DIR/storage/app/private/recibos/recibo-2.pdf"
verificar "un respaldo completo y coincidente pasa el ensayo" "0" "$(correr_ensayo "$TEST_ROOT/completo.enc")"

# 2. Las imputaciones no coinciden: tiene que dar rojo.
#    Con la lista vieja de 15 tablas, `pago_deuda_cuota` no se miraba y esto
#    pasaba como correcto.
printf 'alumnos=60\npagos=12\npago_deuda_cuota=20\n' > "$TEST_ROOT/conteos_vivos.txt"
printf 'alumnos=60\npagos=12\npago_deuda_cuota=3\n'  > "$TEST_ROOT/conteos_copia.txt"
verificar "un respaldo con imputaciones incompletas falla" "1" "$(correr_ensayo "$TEST_ROOT/completo.enc")"
grep -q "pago_deuda_cuota" "$TEST_ROOT/salida.log" \
    && printf '  ok    el informe nombra la tabla que no coincide\n' \
    || { printf '  FALLA el informe no nombra pago_deuda_cuota\n'; fallos=$((fallos + 1)); }

# 3. Una tabla que existe viva y falta en la copia: rojo.
#    Antes, una tabla ausente en las DOS bases devolvia "?" de los dos lados y
#    se contaba como coincidencia.
printf 'alumnos\npagos\npago_deuda_cuota\nliquidaciones\n' > "$TEST_ROOT/tablas_wings.txt"
printf 'alumnos\npagos\npago_deuda_cuota\n'                > "$TEST_ROOT/tablas_wings_ensayo.txt"
printf 'alumnos=60\npagos=12\npago_deuda_cuota=20\nliquidaciones=4\n' > "$TEST_ROOT/conteos_vivos.txt"
printf 'alumnos=60\npagos=12\npago_deuda_cuota=20\n'                  > "$TEST_ROOT/conteos_copia.txt"
verificar "una tabla que falta en la copia falla" "1" "$(correr_ensayo "$TEST_ROOT/completo.enc")"

# 4. Paquete sin los archivos subidos: rojo antes de tocar la base.
printf 'alumnos\npagos\npago_deuda_cuota\n' > "$TEST_ROOT/tablas_wings.txt"
printf 'alumnos=60\npagos=12\npago_deuda_cuota=20\n' > "$TEST_ROOT/conteos_vivos.txt"
printf 'alumnos=60\npagos=12\npago_deuda_cuota=20\n' > "$TEST_ROOT/conteos_copia.txt"
armar_paquete "$TEST_ROOT/sin-storage.enc" con-env sin-storage
verificar "un paquete sin storage.tgz falla" "1" "$(correr_ensayo "$TEST_ROOT/sin-storage.enc")"

# 5. Respaldo con menos archivos que los vivos: perdio recibos.
armar_paquete "$TEST_ROOT/pocos-archivos.enc" con-env con-storage 1
verificar "un respaldo con menos archivos que los vivos falla" "1" "$(correr_ensayo "$TEST_ROOT/pocos-archivos.enc")"

# 6. Sin APP_KEY, Laravel no arranca: restaurar eso no repone un sistema usable.
armar_paquete "$TEST_ROOT/sin-appkey.enc" sin-env con-storage 2
verificar "un paquete sin APP_KEY falla" "1" "$(correr_ensayo "$TEST_ROOT/sin-appkey.enc")"

# 7. EL DEFECTO PRINCIPAL: la restauracion real repone los archivos subidos.
rm -rf "$APP_DIR/storage/app"
mkdir -p "$APP_DIR/storage/app/private/recibos"
printf 'recibo viejo\n' > "$APP_DIR/storage/app/private/recibos/viejo.pdf"
# Sin `|| true` un EN-SERIO que sale con error corta la prueba entera por el
# `set -e`, y las comprobaciones de abajo —las que cubren el defecto principal—
# no llegan a informarse.
bash "$SCRIPT" "$TEST_ROOT/completo.enc" EN-SERIO > "$TEST_ROOT/enserio.log" 2>&1 || true

repuestos="$(find "$APP_DIR/storage/app" -name 'recibo-*.pdf' -type f 2>/dev/null | wc -l | tr -d ' ')"
verificar "EN-SERIO repone los recibos del respaldo" "2" "$repuestos"

apartados="$(find "$APP_DIR" -maxdepth 2 -type d -name 'app.reemplazado-*' 2>/dev/null | wc -l | tr -d ' ')"
verificar "EN-SERIO aparta lo anterior en vez de borrarlo" "1" "$apartados"

if [ -f "$APP_DIR/.env.del-respaldo" ]; then
    printf '  ok    EN-SERIO deja la configuracion al lado sin pisar el .env\n'
else
    printf '  FALLA EN-SERIO no dejo la configuracion del respaldo\n'
    fallos=$((fallos + 1))
fi

if [ -f "$APP_DIR/.env" ]; then
    printf '  FALLA EN-SERIO creo un .env: debe dejarlo al operador\n'
    fallos=$((fallos + 1))
else
    printf '  ok    EN-SERIO no pisa el .env por su cuenta\n'
fi

# ---------------------------------------------------------------- SEG-07
#
# Contar filas no alcanza. Un volcado puede restaurar la misma cantidad de filas
# con los importes truncados: el ensayo de SEG-06 lo daba por correcto.

echo
echo "SEG-07 — verificacion fuerte del contenido"

# Vuelve el escenario coincidente de tablas y conteos.
printf 'alumnos\npagos\npago_deuda_cuota\n' > "$TEST_ROOT/tablas_wings.txt"
printf 'alumnos\npagos\npago_deuda_cuota\n' > "$TEST_ROOT/tablas_wings_ensayo.txt"
printf 'alumnos=60\npagos=12\npago_deuda_cuota=20\n' > "$TEST_ROOT/conteos_vivos.txt"
printf 'alumnos=60\npagos=12\npago_deuda_cuota=20\n' > "$TEST_ROOT/conteos_copia.txt"
rm -rf "$APP_DIR/storage/app"
mkdir -p "$APP_DIR/storage/app/private/recibos"
printf 'recibo 1\n' > "$APP_DIR/storage/app/private/recibos/recibo-1.pdf"
printf 'recibo 2\n' > "$APP_DIR/storage/app/private/recibos/recibo-2.pdf"

# a. Mismo conteo, mismos importes: pasa.
printf "SUM(monto_final)=480000.00\nSUM(monto_aplicado)=480000.00\nSUM(saldo_pendiente)=120000.00\n" > "$TEST_ROOT/patrones_vivos.txt"
cp "$TEST_ROOT/patrones_vivos.txt" "$TEST_ROOT/patrones_copia.txt"
verificar "importes que coinciden pasan" "0" "$(correr_ensayo "$TEST_ROOT/completo.enc")"

# b. EL CASO DE SEG-07: misma cantidad de pagos, distinta plata.
#    Con la comprobacion de SEG-06 sola, esto pasaba en verde.
printf "SUM(monto_final)=48000.00\nSUM(monto_aplicado)=480000.00\nSUM(saldo_pendiente)=120000.00\n" > "$TEST_ROOT/patrones_copia.txt"
verificar "mismo conteo con importes truncados falla" "1" "$(correr_ensayo "$TEST_ROOT/completo.enc")"
grep -q "pagos completados" "$TEST_ROOT/salida.log" \
    && printf '  ok    el informe nombra el importe que no coincide\n' \
    || { printf '  FALLA el informe no nombra el importe\n'; fallos=$((fallos + 1)); }

# c. Las imputaciones no suman lo mismo: que meses cubre cada pago quedo mal.
printf "SUM(monto_final)=480000.00\nSUM(monto_aplicado)=310000.00\nSUM(saldo_pendiente)=120000.00\n" > "$TEST_ROOT/patrones_copia.txt"
verificar "imputaciones con otra suma fallan" "1" "$(correr_ensayo "$TEST_ROOT/completo.enc")"

# d. La restauracion INTRODUJO incoherencias: la viva esta sana, la copia no.
printf "SUM(monto_final)=480000.00\nSUM(monto_aplicado)=480000.00\nSUM(saldo_pendiente)=120000.00\nWHERE d.monto_pagado=0\n" > "$TEST_ROOT/patrones_vivos.txt"
printf "SUM(monto_final)=480000.00\nSUM(monto_aplicado)=480000.00\nSUM(saldo_pendiente)=120000.00\nWHERE d.monto_pagado=3\n" > "$TEST_ROOT/patrones_copia.txt"
verificar "incoherencias que aparecen solo en la copia fallan" "1" "$(correr_ensayo "$TEST_ROOT/completo.enc")"

# e. La incoherencia ya estaba viva y el respaldo la copio igual: el respaldo
#    hizo bien su trabajo. Tiene que pasar, avisando que los DATOS estan mal.
printf "SUM(monto_final)=480000.00\nSUM(monto_aplicado)=480000.00\nSUM(saldo_pendiente)=120000.00\nWHERE d.monto_pagado=3\n" > "$TEST_ROOT/patrones_vivos.txt"
cp "$TEST_ROOT/patrones_vivos.txt" "$TEST_ROOT/patrones_copia.txt"
verificar "una incoherencia presente en las dos bases no invalida el respaldo" "0" "$(correr_ensayo "$TEST_ROOT/completo.enc")"
grep -q "problema de datos, no del respaldo" "$TEST_ROOT/salida.log" \
    && printf '  ok    lo distingue de un respaldo roto y lo avisa\n' \
    || { printf '  FALLA no avisa que hay datos incoherentes\n'; fallos=$((fallos + 1)); }

echo
if [ "$fallos" -eq 0 ]; then
    echo "SEG-06 y SEG-07: todas las comprobaciones pasan."
    exit 0
fi
echo "SEG-06 y SEG-07: $fallos comprobacion(es) fallaron."
exit 1
