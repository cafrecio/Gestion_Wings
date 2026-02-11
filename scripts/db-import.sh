#!/bin/bash
# ---------------------------------------------------------------
# db-import.sh — Restaura la BD de Wings desde el dump versionado.
# Ejecutar desde la raíz del proyecto:
#   bash scripts/db-import.sh
#
# Requisitos: mysql disponible en PATH (viene con XAMPP).
# Lee credenciales desde .env automáticamente.
# Crea la base de datos si no existe.
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

if [ ! -f "$DUMP_FILE" ]; then
    echo "ERROR: No se encontró database/dump.sql"
    echo "Usá 'php artisan migrate --seed' para crear la BD desde cero."
    exit 1
fi

# Leer variables de .env
DB_HOST=$(grep -E '^DB_HOST=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r')
DB_PORT=$(grep -E '^DB_PORT=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r')
DB_DATABASE=$(grep -E '^DB_DATABASE=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r')
DB_USERNAME=$(grep -E '^DB_USERNAME=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r')
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r')

MYSQL_CMD="mysql --host=$DB_HOST --port=$DB_PORT --user=$DB_USERNAME"
if [ -n "$DB_PASSWORD" ]; then
    MYSQL_CMD="$MYSQL_CMD --password=$DB_PASSWORD"
fi

echo "Creando base de datos $DB_DATABASE si no existe..."
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "Importando dump en $DB_DATABASE..."
$MYSQL_CMD "$DB_DATABASE" < "$DUMP_FILE"

echo "Listo. BD restaurada desde database/dump.sql."
