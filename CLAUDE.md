# Wings – Guía para Claude Code

## Stack
- Laravel 11 + PHP 8.2 + MariaDB (XAMPP en Windows)
- API REST con Sanctum, sin frontend propio
- Tests: PHPUnit con SQLite :memory:

## Base de datos — Mantener sincronizada

El archivo `database/dump.sql` contiene el dump completo de la BD (estructura + datos).
Se versiona en el repo para que todos trabajen con la misma BD.

### Al clonar el repo (setup inicial)

```bash
# 1. Configurar .env (ver CLAUDE_ENV_SETUP.md)
cp .env.example .env
php artisan key:generate

# 2. Restaurar la BD desde el dump
bash scripts/db-import.sh

# 3. Instalar dependencias
composer install
npm install && npm run build
```

### Después de cambios en la BD (nuevos datos, migraciones, seeders)

```bash
# Exportar el estado actual de la BD al dump
bash scripts/db-export.sh

# Commitear el dump actualizado
git add database/dump.sql
git commit -m "Update database dump"
```

### Al hacer pull (si el dump cambió)

```bash
# Restaurar la BD desde el dump actualizado
bash scripts/db-import.sh
```

### Notas
- Los scripts leen credenciales de `.env` automáticamente.
- Detectan XAMPP en `C:\xampp\mysql\bin` y agregan al PATH si es necesario.
- `db-import.sh` crea la base de datos si no existe.
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
