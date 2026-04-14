# PLAN MAESTRO — Wings Gestión
> Generado: 2026-03-07 | 4 agentes de auditoría (Datos, Lógica, UI, Edge Cases)

---

## ESTADO GENERAL

| Área | Estado | % |
|------|--------|---|
| Arquitectura de datos | Sólida, 1 crítico | 90% |
| Lógica / Backend | Core implementado | 75% |
| UI / Design System | DS listo, vistas sin migrar | 40% |
| Tests y estabilidad | Cobertura parcial | 35% |

---

## PARTE 1 — BACKEND: QUÉ FALTA IMPLEMENTAR

### 🔴 CRÍTICOS (bloquean funcionalidad real)

**B1 — Seeder `tipos_caja` vacío**
La tabla `tipos_caja` no tiene datos. Toda la caja operativa requiere un `tipo_caja_id` para funcionar.
Solución: `TiposCajaSeeder` con Efectivo, Banco, Mercado Pago.

**B2 — Motor mensual de generación de deuda**
No existe un comando ni scheduler que genere `deuda_cuotas` automáticamente cada mes para todos los alumnos activos. Hoy se generan on-demand al cobrar.
Solución: `php artisan deudas:generar-mensuales` + `schedule()->monthly()`.

**B3 — Rutas de deudas sin auth (seguridad)**
`GET /api/alumnos/{id}/deudas` y `GET /api/deudas/{id}` son **públicas**. Cualquier visitante puede consultar deudas de cualquier alumno.
Solución: mover dentro del middleware `auth:sanctum`.

### 🟡 IMPORTANTES (afectan calidad o corrompen datos)

**B4 — Race condition en apertura de caja**
`CajaService::abrirCajaSiNoExiste()` no usa transacción. Dos requests simultáneos pueden crear dos cajas ABIERTA del mismo usuario el mismo día.
Solución: `DB::transaction()` + `SELECT ... FOR UPDATE`.

**B5 — Race condition en validación FIFO**
`PagoCuotaService::validarFifo()` lee `saldo_pendiente` sin lock. Dos pagos paralelos del mismo alumno pueden pasar ambos la validación y sobreescribir el saldo.
Solución: `SELECT ... FOR UPDATE` en la lectura de deuda.

**B6 — AlumnoPlan: boot solo en `creating()`**
Si un plan se actualiza directamente a `activo=true` (vía `update()`), el boot que desactiva otros planes no se dispara. Pueden coexistir múltiples planes activos.
Solución: agregar `updating()` con la misma lógica.

**B7 — Montos como `float` en comparaciones**
`DeudaCuota::getSaldoPendienteAttribute()` retorna `float`. Comparaciones con `==` pueden fallar por precisión de punto flotante.
Solución: operar con `bcmath` o `number_format`.

**B8 — Policies de autorización inexistentes**
No hay `app/Policies/`. El middleware `EnsureAdmin` solo verifica `rol === 'ADMIN'` (string hardcodeado). Un operativo puede acceder a recursos de otros operativos.
Solución: implementar Policies para `CajaOperativa`, `DeudaCuota`, `Liquidacion`.

**B9 — FIFO bidireccional faltante**
Admin puede crear `DeudaCuota` con períodos salteados (ej: crear 2026-05 sin que exista 2026-03). El FIFO al cobrar puede romperse.
Solución: `StoreDeudaCuotaRequest` debe validar continuidad de períodos.

### 🟢 MENORES (mejoras de calidad)

**B10 — Estado cobranza calculado on-demand (sin persistencia)**
`CobranzaEstadoService` recalcula el estado de cada alumno en cada request. Con 500+ alumnos esto es lento.
Solución: columna `estado_cobranza` en `alumnos`, actualizar en post-commit worker.

**B11 — `fecha_pago` operativo no validada como "hoy"**
Un operativo puede antedatar un pago. `StorePagoCuotaOperativoRequest` debería forzar `fecha_pago = today()`.

**B12 — Respuestas de error inconsistentes**
Algunos controllers retornan `{error: {message: ...}}`, otros `{message: ..., error: ...}`.
Solución: trait `ApiResponse` centralizado.

**B13 — `fecha` nullable en `movimientos_operativos`**
Un movimiento sin fecha es inválido. Cambiar a `NOT NULL DEFAULT CURRENT_DATE`.

---

## PARTE 2 — CASOS DE USO FALTANTES

### Implementados ✅
| Caso | Descripción |
|------|-------------|
| UC-01 | Login / logout (web + API) |
| UC-02 | Listado, crear, editar, ver alumno |
| UC-03 | Pago membresía mensual con reglas de primer pago |
| UC-04 | Cambio de plan (sin retroactividad) |
| UC-05 | Cobro de cuota — FIFO fuerte, operativo |
| UC-06 | Cobro de cuota — admin con cashflow directo |
| UC-07 | Condonación y ajuste de deuda (admin) |
| UC-08 | Apertura / cierre de caja operativa |
| UC-09 | Registro de movimiento operativo en caja |
| UC-10 | Validación / rechazo de caja por admin |
| UC-11 | Integración caja → cashflow al validar |
| UC-12 | Generación de liquidación por HORA |
| UC-13 | Generación de liquidación por COMISIÓN |
| UC-14 | Cierre y pago de liquidación a profesor |
| UC-15 | Dashboard cobranza: estados AL_DIA / MOROSO / DEUDOR |
| UC-16 | Revisión y resolución de casos de cobranza |
| UC-17 | Cierre del día (resumen operativo + admin) |
| UC-18 | Consulta cashflow: saldos y movimientos admin |
| UC-19 | ABM Deportes, Grupos, Profesores, Rubros, Subrubros, Tipos Caja |

### No implementados ❌
| Caso | Descripción | Prioridad |
|------|-------------|-----------|
| UC-20 | Generación automática mensual de deuda_cuotas | 🔴 ALTA |
| UC-21 | Registro de clase (fecha, hora, grupo) | 🟡 MEDIA |
| UC-22 | Asignación de profesor a clase | 🟡 MEDIA |
| UC-23 | Toma de asistencia por clase (bulk) | 🟡 MEDIA |
| UC-24 | Registro de asistencia exceso (EXTRA / RECUPERA) | 🟡 MEDIA |
| UC-25 | Vista operativo caja — pantalla de cobro del día | 🔴 ALTA |
| UC-26 | Dashboard admin — KPIs, últimos movimientos, alertas | 🟡 MEDIA |
| UC-27 | Recibo PDF de pago de cuota (endpoint + descarga) | 🟡 MEDIA |
| UC-28 | Recibo PDF de liquidación a profesor | 🟢 BAJA |
| UC-29 | Activar / desactivar alumno | 🟡 MEDIA |
| UC-30 | Agregar / quitar alumno de grupo | 🟡 MEDIA |
| UC-31 | Historial de pagos por alumno | 🟢 BAJA |
| UC-32 | Reporte de cobranza exportable (CSV / Excel) | 🟢 BAJA |
| UC-33 | Configuración de tipos de caja (CRUD web) | 🟡 MEDIA |
| UC-34 | Configuración de reglas de primer pago (CRUD web) | 🟢 BAJA |
| UC-35 | Cancelación de clase y manejo de asistencias | 🟡 MEDIA |

---

## PARTE 3 — UI: QUÉ VISTAS FALTAN / MIGRAR

### Referencia visual: `alumnos/index.blade.php`
El patrón a replicar en todo el sistema:
- Module header rojo (`ds-module-header`)
- Filtros card (`filtros-card`, `filtros-row`, `filtros-control`)
- Stats bar (`stats-bar`)
- Cards con rail por categoría
- Acciones con `<x-ds.button>` y `<x-ds.toggle>`

### Vistas a crear (nuevas) 🆕
| Vista | Módulo | Patrón base | Prioridad |
|-------|--------|-------------|-----------|
| `operativo/caja.blade.php` | Caja del día | Cards de alumnos + acción cobrar | 🔴 ALTA |
| `admin/dashboard.blade.php` | Dashboard | KPIs + tabla resumen | 🔴 ALTA |
| `alumnos/show.blade.php` | Detalle alumno | Secciones con form-group DS | 🟡 MEDIA |
| `alumnos/create.blade.php` + `_form` | Nuevo alumno | Form DS | 🟡 MEDIA |
| `alumnos/edit.blade.php` + `_form` | Editar alumno | Form DS | 🟡 MEDIA |
| `profesores/index.blade.php` | Profesores | Cards igual a alumnos | 🟡 MEDIA |
| `liquidaciones/index.blade.php` | Liquidaciones | Lista + estado | 🟢 BAJA |
| `cajas/index.blade.php` (admin) | Cajas pendientes | Lista + acciones admin | 🟢 BAJA |

### Vistas a migrar al DS 🔄
| Vista | Problema | Prioridad |
|-------|----------|-----------|
| `alumnos/show.blade.php` | Clases legacy (`glass-card`, `wings-btn`) | 🟡 MEDIA |
| `alumnos/create.blade.php` | Clases legacy | 🟡 MEDIA |
| `alumnos/_form.blade.php` | `wings-input`, sin `ds/form-group` | 🟡 MEDIA |
| `auth/login.blade.php` | `glass-card`, `wings-btn` → usar DS | 🟢 BAJA |
| `errors/403.blade.php` | `wings-btn` → `<x-ds.button>` | 🟢 BAJA |

### Componentes DS faltantes
| Componente | Uso | Prioridad |
|------------|-----|-----------|
| `ds/form-group.blade.php` | Label + input + error en forms | 🔴 ALTA |
| `ds/badge.blade.php` | Estados (AL_DIA, MOROSO, etc.) | 🟡 MEDIA |
| `ds/table.blade.php` | Dashboard, caja, listados admin | 🟡 MEDIA |
| `ds/modal.blade.php` | Confirmaciones de acciones | 🟢 BAJA |

---

## PARTE 4 — EDGE CASES CRÍTICOS

### 🔴 Pueden romper datos
| # | Escenario | Causa | Impacto |
|---|-----------|-------|---------|
| E1 | Dos pagos simultáneos del mismo alumno | FIFO sin lock | Overflow en monto_pagado |
| E2 | Dos operativos abren caja a la vez | No hay transacción en abrirCaja | Dos cajas ABIERTA para 1 usuario |
| E3 | Plan actualizado a activo=true via update() | Boot solo en creating() | Dos planes activos en paralelo |
| E4 | Caja validada refleja cashflow dos veces | CashflowIntegracion no totalmente idempotente | Duplicado en cashflow |

### 🟡 Comportamiento inesperado
| # | Escenario | Consecuencia |
|---|-----------|--------------|
| E5 | `estaPagada()` retorna true con estado=PENDIENTE | Deuda con estado inconsistente |
| E6 | Admin borra profesor con liquidaciones | FK RESTRICT — error 500 sin mensaje claro |
| E7 | Alumno sin plan activo intenta pagar | Error técnico sin mensaje de usuario amigable |
| E8 | Operativo opera con fecha_pago de ayer | Datos incorrectos en reportes |

### Tests faltantes (críticos)
- Condonación de deuda
- Pago operativo (solo admin está testeado)
- Alumno sin plan: comportamiento al cobrar
- Idempotencia: pagar dos veces el mismo período
- Race condition: dos pagos simultáneos (test de integración con threads)

---

## ORDEN DE EJECUCIÓN RECOMENDADO

### Sprint 1 — Desbloquear el sistema (esta semana)
1. **B1** Seeder `TipoCaja` (Efectivo, Banco, Mercado Pago) — 30 min
2. **B3** Mover rutas de deudas detrás de `auth:sanctum` — 15 min
3. **UC-25** Vista `operativo/caja.blade.php` — UI del día del operativo — 1 día
4. **UC-26** Vista `admin/dashboard.blade.php` — KPIs básicos — 1 día
5. **Comp DS** `ds/form-group.blade.php` — 2h

### Sprint 2 — Datos y lógica sólida
6. **B2** Comando + scheduler generación mensual de deuda
7. **B4 + B5** Transacciones y locks en caja + FIFO
8. **B6** Fix boot `AlumnoPlan` en updating()
9. **B7** Migrar montos a bcmath
10. **UC-21 + UC-22 + UC-23** Clases + asistencias (flujo operativo completo)

### Sprint 3 — UI consistente
11. Migrar `alumnos/show`, `create`, `edit`, `_form` al DS
12. `ds/badge.blade.php` + `ds/table.blade.php`
13. UC-29, UC-30 (activar alumno, cambiar grupo)

### Sprint 4 — Calidad y producción
14. **B8** Policies de autorización
15. **B12** Estandarizar responses
16. Tests de edge cases E1-E4
17. UC-27 Recibos PDF
18. UC-32 Reportes exportables

---

## ARCHIVOS CLAVE DE REFERENCIA

| Propósito | Archivo |
|-----------|---------|
| Contratos negocio | `Contratos/` |
| Layout canónico | `resources/views/layouts/ds-app.blade.php` |
| Vista referencia DS | `resources/views/alumnos/index.blade.php` |
| CSS tokens y componentes | `resources/css/app.css` |
| Servicio core pagos | `app/Services/PagoCuotaService.php` |
| Servicio caja | `app/Services/CajaService.php` |
| Tests principales | `tests/Feature/PagoCuotaServiceTest.php` |
| Este plan | `docs/PLAN-MAESTRO.md` |
