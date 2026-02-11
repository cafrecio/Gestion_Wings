# Wings – Guía para Claude Code

## Stack
- Laravel 11 + PHP 8.2 + MariaDB (XAMPP en Windows)
- API REST con Sanctum, sin frontend propio
- Tests: PHPUnit con SQLite :memory:

## Base de datos — SIEMPRE mantener sincronizada

**REGLA OBLIGATORIA PARA CLAUDE:** Cada vez que se haga un commit, SIEMPRE exportar primero el dump actualizado de la BD. Cada vez que se empiece una sesión y se sospeche que el dump cambió (ej: después de un pull), importar el dump.

El archivo `database/dump.sql` contiene el dump completo de la BD (estructura + datos).
Se versiona en el repo para que al clonar o hacer pull en cualquier máquina se tenga la misma BD.

### Comandos de sincronización

```bash
# EXPORTAR (antes de cada commit que toque la BD o migraciones):
"C:/xampp/mysql/bin/mysqldump.exe" -u root gestion_wings > database/dump.sql

# IMPORTAR (al clonar, o después de pull si el dump cambió):
"C:/xampp/mysql/bin/mysql.exe" -u root gestion_wings < database/dump.sql
```

También hay scripts bash disponibles: `bash scripts/db-export.sh` y `bash scripts/db-import.sh`.

### Al clonar el repo (setup inicial)

```bash
# 1. Configurar .env (ver CLAUDE_ENV_SETUP.md)
cp .env.example .env
php artisan key:generate

# 2. Crear la BD e importar el dump
"C:/xampp/mysql/bin/mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS gestion_wings"
"C:/xampp/mysql/bin/mysql.exe" -u root gestion_wings < database/dump.sql

# 3. Instalar dependencias
composer install
npm install && npm run build
```

### Flujo de trabajo con commits

1. Hacer cambios (migraciones, seeders, datos manuales, etc.)
2. **SIEMPRE** exportar el dump antes de commitear: `"C:/xampp/mysql/bin/mysqldump.exe" -u root gestion_wings > database/dump.sql`
3. Incluir `database/dump.sql` en el commit
4. Push

### Al hacer pull (si el dump cambió)

```bash
"C:/xampp/mysql/bin/mysql.exe" -u root gestion_wings < database/dump.sql
```

### Notas
- Los scripts en `scripts/` leen credenciales de `.env` automáticamente
- Detectan XAMPP en `C:\xampp\mysql\bin` y agregan al PATH si es necesario
- Si no hay dump disponible, se puede levantar desde cero: `php artisan migrate --seed`

## Tests

```bash
php artisan test
```

Los tests corren sobre SQLite :memory: (configurado en `phpunit.xml`), no tocan la BD real.

## Estructura de servicios clave

- `app/Services/PagoCuotaService.php` — Pago de cuotas (operativo y admin)
- `app/Services/PagoService.php` — Pago regular (plan mensual)
- `app/Services/CajaService.php` — Caja operativa diaria
- `app/Services/LiquidacionService.php` — Liquidaciones a profesores
- `app/Services/CashflowService.php` — Movimientos de cashflow

## Contratos

Los documentos de negocio están en `Contratos/`. Consultar antes de modificar reglas de negocio.
