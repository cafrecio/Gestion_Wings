#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")/../.." && pwd -P)"
TEST_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/wings-deploy-test.XXXXXX")"
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
    migrate)
        exit 42
        ;;
    up)
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

if [[ "$DEPLOY_STATUS" -eq 0 ]]; then
    printf 'ERROR: el despliegue debía fallar en la migración.\n'
    cat "$DEPLOY_OUTPUT"
    exit 1
fi

[[ "$DEPLOY_STATUS" -eq 42 ]] || {
    printf 'ERROR: se esperaba código 42 y se obtuvo %s.\n' "$DEPLOY_STATUS"
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

[[ ! -e "$DEPLOY_REPO/storage/framework/down" ]] || {
    printf 'ERROR: el sistema quedó en mantenimiento.\n'
    cat "$DEPLOY_OUTPUT"
    exit 1
}

[[ -z "$(git -C "$DEPLOY_REPO" status --porcelain --untracked-files=normal)" ]] || {
    printf 'ERROR: el repositorio quedó con cambios después del rollback.\n'
    git -C "$DEPLOY_REPO" status --short
    cat "$DEPLOY_OUTPUT"
    exit 1
}

grep -q 'ERROR (42): aplicar migraciones como wings_migrate' "$DEPLOY_OUTPUT"
grep -q 'Rollback completo' "$DEPLOY_OUTPUT"
grep -q 'php82 artisan config:cache' "$COMMAND_LOG"
grep -q 'php82 artisan route:cache' "$COMMAND_LOG"
grep -q 'php82 artisan view:cache' "$COMMAND_LOG"
grep -q 'php82 artisan up' "$COMMAND_LOG"

printf 'OK: fallo de migración, rollback automático y salida de mantenimiento verificados.\n'
