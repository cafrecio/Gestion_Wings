#!/bin/bash
# ---------------------------------------------------------------
# db-export.sh — Exporta la BD de Wings a un dump SQL versionable.
# Ejecutar desde la raíz del proyecto:
#   bash scripts/db-export.sh
#
# Requisitos: mysqldump disponible en PATH (viene con XAMPP).
# Lee credenciales desde .env automáticamente.
# ---------------------------------------------------------------

set -e

# Agregar XAMPP mysql/bin al PATH si existe (Windows)
[ -d "/c/xampp/mysql/bin" ] && export PATH="/c/xampp/mysql/bin:$PATH"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_DIR/.env"
DUMP_FILE="$PROJECT_DIR/database/dump.sql"

if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: No se encontró .env en $PROJECT_DIR"
    exit 1
fi

# Leer variables de .env
DB_HOST=$(grep -E '^DB_HOST=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r')
DB_PORT=$(grep -E '^DB_PORT=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r')
DB_DATABASE=$(grep -E '^DB_DATABASE=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r')
DB_USERNAME=$(grep -E '^DB_USERNAME=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r')
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r')

echo "Exportando $DB_DATABASE desde $DB_HOST:$DB_PORT ..."

MYSQLDUMP_CMD="mysqldump --host=$DB_HOST --port=$DB_PORT --user=$DB_USERNAME"
if [ -n "$DB_PASSWORD" ]; then
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --password=$DB_PASSWORD"
fi

# --routines --triggers: incluir procedimientos y triggers si existen
# --skip-comments: dump más limpio
# --complete-insert: INSERT con nombres de columna (más robusto)
$MYSQLDUMP_CMD --routines --triggers --skip-comments --complete-insert "$DB_DATABASE" > "$DUMP_FILE"

echo "Dump guardado en: database/dump.sql ($(wc -c < "$DUMP_FILE" | tr -d ' ') bytes)"
echo "Listo. Commiteá database/dump.sql para versionar la BD."
