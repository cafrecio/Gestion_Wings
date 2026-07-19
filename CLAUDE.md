# Wings - Guia para Claude Code

Este archivo orienta el trabajo dentro del repo. No reemplaza la documentacion viva: indica donde leer antes de tocar cada area.

## Regla principal

Antes de modificar funcionalidad, leer:

- `docs/00-estado/ESTADO-ACTUAL.md`
- `docs/07-evaluacion/index.html` — evaluacion integral vigente (seguridad, datos, backend, frontend, 3 perfiles de usuario, integral). Fuente de verdad de lo que falta. Leer SIEMPRE antes de tocar caja, cobros, alumnos o sidebar. Las listas viejas quedaron archivadas en `docs/99-archivo/pendientes-viejos/`.

Ese archivo es la fuente de verdad del estado actual del proyecto. Si otro documento contradice `ESTADO-ACTUAL.md`, no asumir: registrar la contradiccion y resolverla antes de implementar.

Mapa visual del proyecto:

- `docs/00-mapa-proyecto/index.html`

Indice documental:

- `docs/README.md`

No usar el `README.md` raiz como fuente de verdad del proyecto. Se conserva como README base de Laravel.

## Stack actual

- Laravel 12 + PHP 8.2 + MariaDB, corriendo en XAMPP local.
- Frontend web con Blade, Tailwind CSS/Vite y JavaScript vanilla.
- API REST con Sanctum para integraciones.
- Tests con PHPUnit. Atencion: al 2026-06-15 la suite no esta confiable porque falla por una migracion incompatible con SQLite.
- Timezone funcional esperada: `America/Argentina/Buenos_Aires`.

## Rutas documentales

| Necesidad | Ruta |
|---|---|
| Estado actual del proyecto | `docs/00-estado/ESTADO-ACTUAL.md` |
| Mapa HTML del repo | `docs/00-mapa-proyecto/index.html` |
| Indice documental | `docs/README.md` |
| Producto / vision general | `docs/01-producto/` |
| Contratos de negocio | `docs/02-contratos/` |
| Design system y reglas UI | `docs/03-diseno-ui/` |
| Setup tecnico | `docs/04-tecnico/` |
| Pendientes crudos | `docs/05-pendientes/` |
| Pruebas funcionales | `docs/06-pruebas/` |
| Historico / archivo | `docs/99-archivo/` |

## Antes de tocar vistas

Leer obligatoriamente:

- `docs/03-diseno-ui/wings-design/SKILL.md`
- `docs/03-diseno-ui/design-system/DESIGN-RULES.md`
- `resources/views/alumnos/index.blade.php`

Reglas criticas:

- Botones con un solo verbo corto: `Nuevo`, `Editar`, `Guardar`, `Cobrar`, `Registrar`, `Volver`, `Filtrar`, `Limpiar`, etc.
- No usar frases como `Cobrar cuota`, `Guardar cambios`, `Nueva clase`, `Registrar movimiento`.
- Mantener `ds-btn`, `x-ds.*`, cards tipo `alumno-card` y estructura visual existente.
- No introducir Alpine.js ni Livewire.

## Base de datos - mantener sincronizada

El archivo `database/dump.sql` contiene el dump de la BD (sin la tabla `users`, ver mas abajo) y se versiona con el repo.

**IMPORTANTE - seguridad:** la tabla `users` NUNCA se versiona. Contiene hashes de contraseñas reales; versionarla es exponer credenciales en el historial de git. Siempre exportar con `--ignore-table`, exactamente como en el comando de abajo. No usar `mysqldump` sin ese flag para este proyecto.

Exportar antes de commits que toquen BD, migraciones, seeders o datos relevantes:

```bash
"C:/xampp/mysql/bin/mysqldump.exe" -u root --ignore-table=gestion_wings.users gestion_wings > database/dump.sql
```

Importar al clonar o despues de un pull si cambio el dump:

```bash
"C:/xampp/mysql/bin/mysql.exe" -u root gestion_wings < database/dump.sql
php artisan db:seed --class=UserSeeder
```

El import deja `users` vacia (la migracion crea la tabla, el dump no trae sus filas). El segundo comando crea las cuentas de acceso; ver `database/seeders/UserSeeder.php` para el detalle de contraseñas.

Tambien existen:

```bash
bash scripts/db-export.sh
bash scripts/db-import.sh
```

Setup tecnico detallado:

- `docs/04-tecnico/CLAUDE_ENV_SETUP.md`

## Tests

Comando:

```bash
php artisan test
```

Estado actual:

- Los tests base pasan.
- `Tests\Feature\PagoCuotaServiceTest` falla antes de probar la logica porque una migracion usa sintaxis MySQL `MODIFY`, no soportada por SQLite.
- Antes de confiar en la suite hay que corregir migraciones para SQLite o definir una BD MariaDB de test.

## Pruebas funcionales y seeders

Antes de crear o modificar seeders orientados a prueba completa, leer:

- `docs/06-pruebas/PLAN-PRUEBAS-FUNCIONALES.md`
- `docs/06-pruebas/GUIA-PRUEBA-COLABORADOR.html`

La carga de datos debe permitir probar flujos reales: alumno, deuda, cobro, caja, validacion, cashflow, clase, asistencia, liquidacion y recibos. No crear seeders con datos decorativos que no permitan ejecutar esos flujos.

## Servicios clave

| Servicio | Uso |
|---|---|
| `app/Services/PagoCuotaService.php` | Pago de cuotas, FIFO, deuda, pago operativo/admin. |
| `app/Services/PagoService.php` | Pago regular de plan mensual. |
| `app/Services/CajaService.php` | Caja operativa diaria. |
| `app/Services/CashflowService.php` | Movimientos de cashflow. |
| `app/Services/CashflowIntegracionCajaService.php` | Reflejo de caja validada en cashflow. |
| `app/Services/LiquidacionService.php` | Liquidaciones a profesores. |
| `app/Services/LiquidacionPagoService.php` | Pago de liquidaciones. |
| `app/Services/CobranzaEstadoService.php` | Estado de cobranza. |
| `app/Services/ReciboService.php` | Recibos PDF. |

## Roles

Tres roles definidos en `App\Models\User`.

**ANTES de tocar cualquier control de acceso (middlewares, `abort(403)`, `if ($user->isX())`, filtros de listados por usuario) leer `docs/02-contratos/PERMISOS-ROLES.md`.** Es la fuente de verdad y existe porque el modelo de permisos se malinterpretó varias veces.

Regla que se equivoca una y otra vez: **el OPERATIVO NO ve "solo lo suyo".** El rol define el dominio de trabajo, no la propiedad de los registros. El operativo ve y opera sobre todo lo del negocio que le corresponde por rubro/funcion (alumnos, cobros, cobranza, caja, clases), incluido lo que hizo el admin. Y el ADMIN nunca tiene menos poder que el operativo.

| Rol | Acceso esperado |
|---|---|
| `ADMIN` | Acceso total. Dashboard admin, cashflow, configuracion, usuarios, validaciones y liquidaciones. |
| `OPERATIVO` | Toda la operacion diaria: alumnos, cobro de cuotas, caja, cobranza, clases y asistencias. Ve lo del admin en los rubros que comparten (`permitido_para=OPERATIVO` = ambos). |
| `PROFESOR` | Solo clases y asistencias, con sidebar minimo. No participa de plata ni alumnos. |

Middlewares relevantes:

- `ensure.admin.web`
- `ensure.profesor.web`
- `ensure.admin`
- `bloqueo.caja.vieja`

## Reglas de trabajo

1. No tocar logica funcional si el pedido es solo documental u organizativo.
2. No mover archivos de codigo para "ordenar" sin pedido explicito.
3. Si se mueve documentacion, actualizar `docs/README.md`, `docs/00-estado/ESTADO-ACTUAL.md` y este archivo.
4. Si se detecta una contradiccion entre documentos y codigo, registrar primero en `ESTADO-ACTUAL.md`.
5. Antes de cambios grandes, revisar rutas en `routes/web.php` y `routes/api.php`.
6. Si se modifica BD o migraciones, revisar `database/dump.sql` y la estrategia de tests.
