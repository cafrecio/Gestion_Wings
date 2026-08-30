#!/usr/bin/env bash

set -Eeuo pipefail

APP_DIR="${WINGS_APP_DIR:-/home/wings/app}"
EXPECTED_USER="${WINGS_DEPLOY_USER:-wings}"
PHP_BIN="${WINGS_PHP_BIN:-/usr/bin/php82}"
GIT_BIN="${WINGS_GIT_BIN:-git}"
NPM_BIN="${WINGS_NPM_BIN:-npm}"
COMPOSER_COMMAND="${WINGS_COMPOSER_BIN:-composer}"
MIGRATE_USER="${WINGS_MIGRATE_USER:-wings_migrate}"

PREVIOUS_COMMIT=""
DEPLOY_TMP=""
MIGRATION_CONFIG_CACHE=""
BUILD_BACKUP=""
BUILD_WAS_PRESENT=0
DEPLOY_STARTED=0
DEPLOY_FINISHED=0

timestamp() {
    date '+%Y-%m-%d %H:%M:%S'
}

log() {
    printf '[%s] %s\n' "$(timestamp)" "$*"
}

fail_before_maintenance() {
    log "ERROR: $*"
    exit 1
}

resolve_executable() {
    local candidate="$1"

    if [[ "$candidate" == */* ]]; then
        [[ -x "$candidate" ]] || return 1
        printf '%s\n' "$candidate"
        return 0
    fi

    command -v "$candidate"
}

run_step() {
    local label="$1"
    shift

    log "INICIO: ${label}"
    if "$@"; then
        log "OK: ${label}"
        return 0
    else
        local status=$?
        log "ERROR (${status}): ${label}"
        return "$status"
    fi
}

apply_permissions() {
    find "$APP_DIR" -path "$APP_DIR/.git" -prune -o -type d -exec chmod 755 {} + || return $?
    find "$APP_DIR" -path "$APP_DIR/.git" -prune -o -type f -exec chmod 644 {} + || return $?

    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} + || return $?
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -exec chmod 664 {} + || return $?
    chmod 640 "$APP_DIR/.env" || return $?
}

run_migrations() {
    DB_URL='' \
    DB_USERNAME="$MIGRATE_USER" \
    DB_PASSWORD="$WINGS_MIGRATE_PASSWORD" \
    APP_CONFIG_CACHE="$MIGRATION_CONFIG_CACHE" \
        "$PHP_BIN" artisan migrate --force
}

restore_build_backup() {
    [[ "$BUILD_WAS_PRESENT" -eq 1 ]] || return 1
    [[ -d "$BUILD_BACKUP" ]] || return 1

    rm -rf -- "$APP_DIR/public/build" || return $?
    cp -a "$BUILD_BACKUP" "$APP_DIR/public/build" || return $?
}

best_effort() {
    local label="$1"
    shift

    if "$@"; then
        log "ROLLBACK OK: ${label}"
        return 0
    else
        local status=$?
        log "ROLLBACK ERROR (${status}): ${label}"
        return "$status"
    fi
}

rollback() {
    local original_status="$1"
    local rollback_failed=0

    trap - EXIT INT TERM
    set +e

    log "FALLÓ EL DESPLIEGUE. Volviendo a ${PREVIOUS_COMMIT}."

    best_effort "restaurar commit" "$GIT_BIN" reset --hard "$PREVIOUS_COMMIT" || rollback_failed=1
    best_effort "restaurar dependencias PHP" "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction || rollback_failed=1
    best_effort "restaurar dependencias JavaScript" "$NPM_BIN" ci --include=dev || rollback_failed=1

    if best_effort "recompilar diseño anterior" "$NPM_BIN" run build; then
        :
    elif restore_build_backup; then
        log "ROLLBACK OK: se recuperó la copia anterior de public/build"
    else
        log "ROLLBACK ERROR: no se pudo recompilar ni recuperar public/build"
        rollback_failed=1
    fi

    best_effort "limpiar cachés anteriores" "$PHP_BIN" artisan optimize:clear || rollback_failed=1
    best_effort "rehacer caché de configuración" "$PHP_BIN" artisan config:cache || rollback_failed=1
    best_effort "rehacer caché de rutas" "$PHP_BIN" artisan route:cache || rollback_failed=1
    best_effort "rehacer caché de vistas" "$PHP_BIN" artisan view:cache || rollback_failed=1
    best_effort "restaurar permisos" apply_permissions || rollback_failed=1

    if ! best_effort "salir de mantenimiento" "$PHP_BIN" artisan up; then
        rm -f -- "$APP_DIR/storage/framework/down"
        if [[ -e "$APP_DIR/storage/framework/down" ]]; then
            log "ROLLBACK ERROR: no se pudo retirar el modo mantenimiento"
            rollback_failed=1
        else
            log "ROLLBACK OK: modo mantenimiento retirado por la ruta de respaldo"
        fi
    fi

    [[ -z "$DEPLOY_TMP" ]] || rm -rf -- "$DEPLOY_TMP"

    if [[ "$rollback_failed" -eq 0 ]]; then
        log "Rollback completo. El sistema quedó en ${PREVIOUS_COMMIT}."
    else
        log "Rollback incompleto: revisar los errores anteriores antes de volver a desplegar."
    fi

    [[ "$original_status" -ne 0 ]] || original_status=1
    exit "$original_status"
}

on_exit() {
    local status=$?

    if [[ "$DEPLOY_STARTED" -eq 1 && "$DEPLOY_FINISHED" -eq 0 ]]; then
        rollback "$status"
    fi

    [[ -z "$DEPLOY_TMP" ]] || rm -rf -- "$DEPLOY_TMP"
    exit "$status"
}

[[ "$APP_DIR" != "" && "$APP_DIR" != "/" ]] || fail_before_maintenance "directorio de aplicación inválido"
[[ -d "$APP_DIR/.git" ]] || fail_before_maintenance "no existe un repositorio Git en $APP_DIR"
[[ -f "$APP_DIR/.env" ]] || fail_before_maintenance "falta $APP_DIR/.env"

CURRENT_USER="$(id -un)"
[[ "$CURRENT_USER" == "$EXPECTED_USER" ]] || fail_before_maintenance "debe ejecutarse como ${EXPECTED_USER}; usuario actual: ${CURRENT_USER}"

PHP_BIN="$(resolve_executable "$PHP_BIN")" || fail_before_maintenance "no se encontró PHP 8.2 en $PHP_BIN"
GIT_BIN="$(resolve_executable "$GIT_BIN")" || fail_before_maintenance "no se encontró git"
NPM_BIN="$(resolve_executable "$NPM_BIN")" || fail_before_maintenance "no se encontró npm"
COMPOSER_BIN="$(resolve_executable "$COMPOSER_COMMAND")" || fail_before_maintenance "no se encontró composer"

if [[ -z "${WINGS_MIGRATE_PASSWORD:-}" ]]; then
    if [[ -t 0 ]]; then
        read -r -s -p 'Contraseña de wings_migrate: ' WINGS_MIGRATE_PASSWORD
        printf '\n'
        export WINGS_MIGRATE_PASSWORD
    else
        fail_before_maintenance "falta WINGS_MIGRATE_PASSWORD y la ejecución no es interactiva"
    fi
fi

[[ -n "$WINGS_MIGRATE_PASSWORD" ]] || fail_before_maintenance "la contraseña de wings_migrate está vacía"
[[ "$MIGRATE_USER" == "wings_migrate" ]] || fail_before_maintenance "el usuario de migración debe ser wings_migrate"

cd "$APP_DIR"
APP_DIR="$(pwd -P)"
[[ "$APP_DIR" != "/" ]] || fail_before_maintenance "el repositorio no puede ser la raíz del sistema"

[[ "$("$GIT_BIN" branch --show-current)" == "main" ]] || fail_before_maintenance "el despliegue solo se ejecuta desde la rama main"
[[ -z "$("$GIT_BIN" status --porcelain --untracked-files=normal)" ]] || fail_before_maintenance "el repositorio tiene cambios locales"

PREVIOUS_COMMIT="$("$GIT_BIN" rev-parse --verify HEAD)"
DEPLOY_TMP="$(mktemp -d "${TMPDIR:-/tmp}/wings-deploy.XXXXXX")"
MIGRATION_CONFIG_CACHE="$DEPLOY_TMP/migration-config.php"
BUILD_BACKUP="$DEPLOY_TMP/public-build"

if [[ -d "$APP_DIR/public/build" ]]; then
    cp -a "$APP_DIR/public/build" "$BUILD_BACKUP" || fail_before_maintenance "no se pudo respaldar public/build"
    BUILD_WAS_PRESENT=1
fi

trap on_exit EXIT
trap 'exit 130' INT TERM
DEPLOY_STARTED=1

log "Commit anterior: ${PREVIOUS_COMMIT}"

run_step "activar modo mantenimiento" "$PHP_BIN" artisan down
run_step "actualizar código" "$GIT_BIN" pull --ff-only
run_step "instalar dependencias PHP con PHP 8.2" "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
run_step "instalar dependencias JavaScript" "$NPM_BIN" ci --include=dev
run_step "compilar diseño" "$NPM_BIN" run build
run_step "aplicar migraciones como wings_migrate" run_migrations
run_step "limpiar cachés" "$PHP_BIN" artisan optimize:clear
run_step "cachear configuración" "$PHP_BIN" artisan config:cache
run_step "cachear rutas" "$PHP_BIN" artisan route:cache
run_step "cachear vistas" "$PHP_BIN" artisan view:cache
run_step "fijar permisos" apply_permissions
run_step "salir de mantenimiento" "$PHP_BIN" artisan up
run_step "verificar producción" "$PHP_BIN" artisan wings:preflight

DEPLOY_FINISHED=1
trap - EXIT INT TERM
rm -rf -- "$DEPLOY_TMP"

log "Despliegue completo: $("$GIT_BIN" rev-parse --verify HEAD)"
