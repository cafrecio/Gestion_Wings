#!/usr/bin/env bash

# SEG-04 — un preflight en rojo nunca publica el release.
#
# El preflight controla el entorno de produccion: APP_DEBUG apagado, cookies seguras,
# sin cuentas de prueba. Corria DESPUES de `artisan up`, o sea que el sitio se abria al
# publico y recien entonces se revisaba si estaba bien: un release con APP_DEBUG=true
# quedaba en internet mostrando credenciales de base en cada error hasta que el control
# terminaba y abortaba.
#
# Esta prueba arma un repositorio y binarios falsos, hace fallar el preflight y exige
# que el sitio nunca se haya abierto con el codigo nuevo: el unico `artisan up` que
# puede existir es el del rollback, despues de volver a la version anterior.
#
# Uso: bash tests/Deployment/deploy_preflight_antes_de_publicar_test.sh

set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")/../.." && pwd -P)"
TEST_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/wings-preflight-test.XXXXXX")"
SOURCE_REPO="$TEST_ROOT/source"
REMOTE_REPO="$TEST_ROOT/remote.git"
DEPLOY_REPO="$TEST_ROOT/deploy"
FAKE_BIN="$TEST_ROOT/bin"
COMMAND_LOG="$TEST_ROOT/commands.log"
DEPLOY_OUTPUT="$TEST_ROOT/deploy.log"

cleanup() {
    rm -rf -- "$TEST_ROOT"
}
trap cleanup EXIT

mkdir -p "$SOURCE_REPO/scripts" "$REMOTE_REPO" "$FAKE_BIN"

git init --bare "$REMOTE_REPO" >/dev/null
git -C "$SOURCE_REPO" init -b main >/dev/null
git -C "$SOURCE_REPO" config user.name "Deploy Test"
git -C "$SOURCE_REPO" config user.email "deploy-test@localhost.invalid"

cp "$PROJECT_DIR/scripts/deploy.sh" "$SOURCE_REPO/scripts/deploy.sh"
printf 'version anterior\n' > "$SOURCE_REPO/version.txt"
printf 'APP_ENV=production\n' > "$SOURCE_REPO/.env"
mkdir -p "$SOURCE_REPO/storage/framework" "$SOURCE_REPO/bootstrap/cache" "$SOURCE_REPO/public"
printf '*\n!.gitignore\n' > "$SOURCE_REPO/storage/framework/.gitignore"
printf '*\n!.gitignore\n' > "$SOURCE_REPO/bootstrap/cache/.gitignore"

git -C "$SOURCE_REPO" add scripts/deploy.sh version.txt .env storage/framework/.gitignore bootstrap/cache/.gitignore
git -C "$SOURCE_REPO" commit -m "versión anterior" >/dev/null
git -C "$SOURCE_REPO" remote add origin "$REMOTE_REPO"
git -C "$SOURCE_REPO" push -u origin main >/dev/null

git clone --branch main "$REMOTE_REPO" "$DEPLOY_REPO" >/dev/null
PREVIOUS_COMMIT="$(git -C "$DEPLOY_REPO" rev-parse HEAD)"

printf 'version nueva\n' > "$SOURCE_REPO/version.txt"
git -C "$SOURCE_REPO" add version.txt
git -C "$SOURCE_REPO" commit -m "versión nueva" >/dev/null
git -C "$SOURCE_REPO" push >/dev/null

cat > "$FAKE_BIN/composer" <<'EOF'
#!/usr/bin/env bash
exit 0
EOF

cat > "$FAKE_BIN/npm" <<'EOF'
#!/usr/bin/env bash
printf 'npm %s\n' "$*" >> "$DEPLOY_TEST_COMMAND_LOG"
exit 0
EOF

# Todo anda salvo el preflight, que falla con 77 como lo haria un APP_DEBUG=true.
# Se registra cada `artisan up` con la version que tenia el repositorio en ese momento,
# para poder distinguir el que publica el codigo nuevo del que hace el rollback.
cat > "$FAKE_BIN/php82" <<'EOF'
#!/usr/bin/env bash
printf 'php82 %s\n' "$*" >> "$DEPLOY_TEST_COMMAND_LOG"

if [[ "${1:-}" == "$DEPLOY_TEST_COMPOSER" ]]; then
    exit 0
fi

[[ "${1:-}" == "artisan" ]] || exit 90

case "${2:-}" in
    down)
        mkdir -p storage/framework
        : > storage/framework/down
        ;;
    wings:preflight)
        exit 77
        ;;
    up)
        printf 'UP con: %s\n' "$(cat version.txt 2>/dev/null || echo desconocida)" \
            >> "$DEPLOY_TEST_COMMAND_LOG"
        rm -f storage/framework/down
        ;;
esac

exit 0
EOF

chmod 755 "$FAKE_BIN/composer" "$FAKE_BIN/npm" "$FAKE_BIN/php82"

set +e
WINGS_APP_DIR="$DEPLOY_REPO" \
WINGS_DEPLOY_USER="$(id -un)" \
WINGS_PHP_BIN="$FAKE_BIN/php82" \
WINGS_COMPOSER_BIN="$FAKE_BIN/composer" \
WINGS_NPM_BIN="$FAKE_BIN/npm" \
WINGS_GIT_BIN="$(command -v git)" \
WINGS_MIGRATE_PASSWORD="contraseña-solo-prueba" \
DEPLOY_TEST_COMMAND_LOG="$COMMAND_LOG" \
DEPLOY_TEST_COMPOSER="$FAKE_BIN/composer" \
    "/usr/bin/env" bash "$DEPLOY_REPO/scripts/deploy.sh" > "$DEPLOY_OUTPUT" 2>&1
DEPLOY_STATUS=$?
set -e

[[ "$DEPLOY_STATUS" -ne 0 ]] || {
    printf 'ERROR: el despliegue debía fallar por el preflight.\n'
    cat "$DEPLOY_OUTPUT"
    exit 1
}

# Lo que esta prueba existe para impedir: que el sitio se abra con el codigo nuevo.
if grep -qx 'UP con: version nueva' "$COMMAND_LOG"; then
    printf 'ERROR: el sitio se publicó con la versión nueva pese al preflight en rojo.\n'
    printf '       El preflight volvió a quedar después de `artisan up`.\n'
    cat "$COMMAND_LOG"
    exit 1
fi

grep -q 'ERROR (77): verificar producción' "$DEPLOY_OUTPUT" || {
    printf 'ERROR: no se registró el fallo del preflight.\n'
    cat "$DEPLOY_OUTPUT"
    exit 1
}

[[ "$(git -C "$DEPLOY_REPO" rev-parse HEAD)" == "$PREVIOUS_COMMIT" ]] || {
    printf 'ERROR: HEAD no volvió al commit anterior.\n'
    cat "$DEPLOY_OUTPUT"
    exit 1
}

grep -qx 'version anterior' "$DEPLOY_REPO/version.txt" || {
    printf 'ERROR: el contenido no volvió a la versión anterior.\n'
    cat "$DEPLOY_OUTPUT"
    exit 1
}

# El rollback si tiene que dejar el sitio abierto, con la version vieja.
grep -qx 'UP con: version anterior' "$COMMAND_LOG" || {
    printf 'ERROR: el rollback no devolvió el sitio al aire con la versión anterior.\n'
    cat "$COMMAND_LOG"
    exit 1
}

[[ ! -e "$DEPLOY_REPO/storage/framework/down" ]] || {
    printf 'ERROR: el sistema quedó en mantenimiento.\n'
    cat "$DEPLOY_OUTPUT"
    exit 1
}

printf 'OK: preflight en rojo, sitio nunca publicado con la versión nueva y rollback al aire.\n'
