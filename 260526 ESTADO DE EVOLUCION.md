# Wings Gestión — Estado de Evolución
> Fecha: 26/05/2026 | Rama: `main` | Commit: `2ba0e85`

---

## RESUMEN EJECUTIVO

| Área | Estado | Notas |
|------|--------|-------|
| Backend / API REST | ✅ Core completo | Servicios de pago, caja, liquidaciones operativos |
| Panel Web (rutas + controllers) | ✅ Completo | Todos los módulos tienen CRUD web |
| Design System | ✅ Implementado | Tokens, componentes, layout canónico |
| Vistas UI | 🟡 Mayoría completa | Algunas vistas sin migrar al DS |
| Seguridad | 🟡 Básica funcional | Faltan Policies de autorización granular |
| Tests | 🔴 Cobertura parcial | Solo pagos testeados; edge cases sin cubrir |
| Generación automática de deuda | 🔴 No implementado | Crítico para operación mensual |

---

## LO QUE ESTÁ IMPLEMENTADO

### Backend — Servicios core

| Servicio | Descripción |
|----------|-------------|
| `PagoCuotaService` | Cobro de cuotas FIFO fuerte, operativo y admin |
| `PagoService` | Pago regular de plan mensual con reglas de primer pago |
| `CajaService` | Apertura/cierre de caja operativa diaria |
| `LiquidacionService` | Liquidaciones a profesores por hora y por comisión |
| `CashflowService` | Movimientos de cashflow e integración al validar caja |
| `ClaseService` | Conteo de asistencias por semana, control de excesos |

### Backend — API REST (Sanctum)

- Autenticación con tokens
- ABM Alumnos, Deportes, Grupos, Profesores
- Pagos de cuotas y membresía
- Deudas (⚠️ rutas GET sin auth:sanctum — ver pendientes)
- Caja operativa: apertura, movimientos, cierre, validación admin
- Cashflow: saldos y movimientos
- Liquidaciones: generación, cierre, pago
- Cobranza: dashboard de estados, revisión de casos

### Panel Web — Módulos completos

| Módulo | Rutas | Roles |
|--------|-------|-------|
| Login / Logout | `GET/POST /login`, `POST /logout` | Todos |
| Dashboard admin | `GET /admin/dashboard` | Admin |
| Caja operativa | `GET /caja`, `GET+POST /caja/{id}/cobrar` | Operativo |
| Alumnos | CRUD completo + toggle activo + autocomplete | Admin/Operativo |
| Clases | CRUD + asistencias + cancelar + validar + profesores | Admin/Operativo |
| Grupos | CRUD + planes + toggle activo | Admin (escritura), todos (lectura) |
| Niveles | CRUD + check disponible | Admin |
| Deportes | CRUD + toggle activo | Admin |
| Profesores | CRUD + show + toggle activo | Admin |
| Rubros | CRUD | Admin |
| Subrubros | CRUD anidado bajo rubros | Admin |
| Tipos de Caja | CRUD | Admin |
| Liquidaciones | index + create + show + cerrar + pagar + recalcular | Admin |

### Vistas — Design System

| Vista | Estado |
|-------|--------|
| `layouts/ds-app.blade.php` | ✅ Layout canónico con sidebar oscuro |
| `layouts/app.blade.php` | ✅ Extiende ds-app, module-header automático |
| `components/ds/button` | ✅ Variantes: primary, secondary, danger, ghost |
| `components/ds/card` | ✅ Con rail por deporte |
| `components/ds/toggle` | ✅ Estado ON/OFF con disable |
| `components/ds/module-header` | ✅ Header gris oscuro por módulo |
| `components/ds/money-input` | ✅ Input de moneda con formato |
| `admin/dashboard` | ✅ KPIs + accesos rápidos |
| `alumnos/index` | ✅ Referencia canónica del DS |
| `alumnos/show` | ✅ |
| `alumnos/create` + `_form` | ✅ |
| `alumnos/edit` | ✅ |
| `clases/index` | ✅ Ventana hoy + filtros desde mañana |
| `clases/show` | ✅ Asistencias inline |
| `clases/create` | ✅ Único y recurrente |
| `clases/edit` | ✅ |
| `grupos/index` | ✅ |
| `grupos/show` | ✅ |
| `grupos/create` + `edit` + `_form` | ✅ |
| `niveles/index` + CRUD | ✅ |
| `deportes/index` + CRUD | ✅ |
| `profesores/index` + CRUD + show | ✅ |
| `rubros/index` + CRUD + subrubros | ✅ |
| `tipos-caja/index` + CRUD | ✅ |
| `liquidaciones/index` + create + show | ✅ |
| `operativo/caja` | ✅ Lista deudores del día |
| `operativo/cobrar` | ✅ Formulario cobro FIFO |
| `pdfs/recibo-cuota` | ✅ Blade (sin endpoint web activo) |
| `pdfs/recibo-liquidacion` | ✅ Blade (sin endpoint web activo) |

### Design System — CSS

- Tokens completos: colores, tipografía, radios, spacing
- `.ds-layout`, `.ds-sidebar`, `.ds-main`, `.ds-topbar`, `.ds-content`
- `.ds-module-header` — gris oscuro, siempre presente
- `.alumno-card` con rail por deporte
- `.filtros-card`, `.filtros-row`, `.filtros-control`, `.filtros-actions`
- `.stats-bar` — flex nowrap, botón primario siempre a la derecha
- `.ds-dot` — semáforo de estado
- `.ds-rail` — indicador por deporte
- `.ds-btn` — todos los tamaños y variantes
- `.ds-toggle` — switch activo/inactivo
- `.ds-grid-2`, `.ds-grid-3` — grids de layout
- `.ds-table`, `.ds-badge` — tablas y etiquetas de estado

### Infraestructura

- Hook `pre-commit`: exporta dump automáticamente antes de cada commit
- `database/dump.sql`: BD versionada y sincronizada con el repo
- Scripts `db-export.sh` / `db-import.sh`
- Tests sobre SQLite `:memory:` (PHPUnit)

---

## LO QUE FALTA IMPLEMENTAR

### 🔴 CRÍTICO — Bloquea operación real

| # | Qué | Detalle |
|---|-----|---------|
| P1 | **Generación automática mensual de deuda** | No existe command ni scheduler. Hoy las deudas se generan on-demand. Con alumnos reales esto rompe el flujo. Requiere `php artisan deudas:generar-mensuales` + `schedule()->monthly()` |
| P2 | **Rutas API de deudas sin autenticación** | `GET /api/alumnos/{id}/deudas` y `GET /api/deudas/{id}` son públicas. Cualquier persona puede consultar deudas sin estar logueada |
| P3 | **Recibos PDF — endpoints web** | Las vistas blade existen (`pdfs/recibo-cuota`, `pdfs/recibo-liquidacion`) pero no hay rutas ni controller para generarlos y descargarlos |

### 🟡 IMPORTANTE — Afecta calidad o puede corromper datos

| # | Qué | Detalle |
|---|-----|---------|
| P4 | **Race condition en apertura de caja** | `CajaService::abrirCajaSiNoExiste()` sin transacción. Dos requests simultáneos pueden crear dos cajas abiertas del mismo día |
| P5 | **Race condition en FIFO de pagos** | `PagoCuotaService` lee `saldo_pendiente` sin lock. Dos pagos paralelos pueden corromper el saldo |
| P6 | **AlumnoPlan: boot solo en `creating()`** | Si un plan se activa via `update()` directamente, pueden coexistir dos planes activos para el mismo alumno |
| P7 | **Montos como `float`** | `saldo_pendiente` retorna float. Comparaciones con `==` pueden fallar por precisión. Migrar a `bcmath` |
| P8 | **Sin Policies de autorización** | El acceso se controla solo por `rol === 'ADMIN'` hardcodeado en middleware. Un operativo podría acceder a recursos de otros operativos si manipula el request |
| P9 | **`fecha_pago` no validada como "hoy"** | Un operativo puede antedatar un pago. Debería forzarse `fecha_pago = today()` en el request |

### 🟢 MENOR — Mejoras de calidad

| # | Qué | Detalle |
|---|-----|---------|
| P10 | **Estado cobranza calculado on-demand** | `CobranzaEstadoService` recalcula por alumno en cada request. Con 500+ alumnos se vuelve lento. Solución: columna `estado_cobranza` en alumnos con worker |
| P11 | **Responses API inconsistentes** | Algunos controllers retornan `{error: {message}}`, otros `{message, error}`. Falta trait `ApiResponse` centralizado |
| P12 | **`fecha` nullable en movimientos_operativos** | Un movimiento sin fecha es inválido por definición. Cambiar a `NOT NULL DEFAULT CURRENT_DATE` |
| P13 | **Validación de continuidad de períodos (FIFO)** | Admin puede crear `DeudaCuota` con períodos salteados, rompiendo el FIFO al cobrar |

### Vistas pendientes de crear o migrar

| Vista | Estado | Prioridad |
|-------|--------|-----------|
| `auth/login.blade.php` | Usa clases legacy (`glass-card`, `wings-btn`) | 🟢 Baja |
| `errors/403.blade.php` | Usa `wings-btn` en lugar de `x-ds.button` | 🟢 Baja |
| Historial de pagos por alumno | No existe | 🟢 Baja |
| Reporte cobranza exportable (CSV) | No existe | 🟢 Baja |

### Tests faltantes

| Test | Prioridad |
|------|-----------|
| Pago operativo (solo admin está cubierto) | 🔴 Alta |
| Condonación de deuda | 🟡 Media |
| Alumno sin plan activo al cobrar | 🟡 Media |
| Idempotencia: pagar dos veces el mismo período | 🟡 Media |
| Race condition: dos pagos simultáneos | 🟢 Baja |

---

## ORDEN DE TRABAJO SUGERIDO

### Próximos (desbloquear operación real)
1. **P1** — Comando + scheduler generación mensual de deuda
2. **P2** — Mover rutas de deudas detrás de `auth:sanctum`
3. **P3** — Endpoints PDF recibos (cuota + liquidación)

### Después (solidez y datos confiables)
4. **P4 + P5** — Transacciones y locks en caja y FIFO
5. **P6** — Fix boot `AlumnoPlan` en `updating()`
6. **P7** — Migrar montos a bcmath
7. Tests de pago operativo y edge cases

### Cuando haya usuarios reales
8. **P8** — Policies de autorización
9. **P10** — Columna `estado_cobranza` con worker
10. Reportes exportables

---

## ARCHIVOS CLAVE

| Propósito | Archivo |
|-----------|---------|
| Reglas de diseño UI | `docs/DESIGN-RULES.md` |
| Spec visual del DS | `docs/UI-SPEC.md` |
| Plan maestro original | `docs/PLAN-MAESTRO.md` |
| Contratos de negocio | `Contratos/` |
| Layout canónico | `resources/views/layouts/ds-app.blade.php` |
| Vista referencia DS | `resources/views/alumnos/index.blade.php` |
| CSS tokens y componentes | `resources/css/app.css` |
| Servicios core | `app/Services/` |
| Tests | `tests/Feature/` |
