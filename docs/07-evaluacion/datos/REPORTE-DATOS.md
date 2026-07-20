# Reporte de auditoría de arquitectura de datos — Wings

Auditoría de migraciones (59), modelos (27) y su uso en services/controllers, verificada contra la BD real MariaDB. **6 hallazgos** (1 crítico, 2 altos, 3 medios). En general la base está bien construida (dinero en `decimal`, FKs con constraint, buena cobertura de índices y uniques de negocio); el problema serio es un `UNIQUE` mal diseñado que convierte el cambio de plan en una bomba de tiempo.

### D1.0 — `alumno_planes`: el UNIQUE impide cambiar de plan más de una vez (bomba latente) — ✅ RESUELTO (2026-07-13)
**Severidad:** Crítica
**Resolución (SD1.1):** Migración `2026_07_19_140000_alumno_planes_activo_nullable` — `activo` pasa a nullable, los cerrados existentes (0) → NULL, y `AlumnoPlan::boot()` cierra con `activo=NULL`. Probado: 3 cambios de plan seguidos sobre el mismo alumno, todos OK, queda 1 activo y 3 cerrados en NULL.
**Dónde:** `database/migrations/2026_01_12_091959_create_alumno_planes_table.php:24` — `unique(['alumno_id','activo'], 'unique_alumno_plan_activo')`; columna `activo` = `tinyint(1) NOT NULL default 1`. Modelo: `app/Models/AlumnoPlan.php:33-40` (boot cierra el plan anterior con `activo=false`).
**Qué pasa:** El boot pone los planes viejos en `activo=0`. Como `activo` es NOT NULL y hay UNIQUE en `(alumno_id, activo)`, un alumno puede tener a lo sumo **una** fila con `activo=0`. Secuencia real: plan A→B funciona (queda A en 0, B en 1). Pero A→B→**C** falla: al cerrar B a `activo=0` ya existe A con `activo=0` → violación de UNIQUE → **error 500**. Contradice de lleno la regla de negocio que se acaba de implementar (cambio de plan hacia adelante) y la premisa de "el historial de AlumnoPlan se guarda, no se sobreescribe". Hoy no estalló solo porque ningún alumno cambió de plan dos veces (verificado en la BD: 0 alumnos con >1 plan inactivo). El primer alumno que lo haga rompe el flujo de cobro.
**Soluciones:**
- SD1.1 ⭐ Migración: hacer `activo` nullable y guardar `NULL` (no `0`) en los planes cerrados; el boot pasa a setear `activo=null`. MySQL permite múltiples `NULL` en un índice único, así que el UNIQUE sigue garantizando "un solo plan activo por alumno" sin limitar el histórico. Migrar los `0` existentes a `NULL`.
- SD1.2 Quitar el UNIQUE de la BD y enforcar "un plan activo" solo en aplicación (el boot ya lo hace). Pierde la garantía a nivel BD.
- SD1.3 Índice único parcial `WHERE activo=1` (MariaDB no lo soporta nativo como Postgres; se emula con columna generada). Más complejo, mismo resultado que SD1.1.

### D2.0 — `referencia_tipo` es string libre con valores inconsistentes para lo mismo — ✅ RESUELTO (2026-07-13)
**Severidad:** Alta
**Resolución (SD2.1):** Constantes `REF_CAJA`, `REF_PAGO_CUOTA`, `REF_LIQUIDACION` en el modelo `CashflowMovimiento`, usadas en todos los services/controllers y en el DemoSeeder. Migración `2026_07_20_053440_normalizar_referencia_tipo_cashflow` normalizó los 145 registros `MOVIMIENTO_OPERATIVO` → `CAJA_OPERATIVA`. Probado: historial sin duplicados.
**Dónde:** `cashflow_movimientos.referencia_tipo` (string nullable, `2026_02_01_100006:21`). Valores en uso: `CAJA_OPERATIVA` (`CashflowIntegracionCajaService:71`), `MOVIMIENTO_OPERATIVO` (DemoSeeder), `PAGO_CUOTA` (`PagoCuotaService:176`), `LIQUIDACION` (`LiquidacionPagoService:111`), `SEED`, y `NULL`.
**Qué pasa:** No hay enum ni constantes: cada quien escribe el string a mano. El servicio real marca el reflejo de caja como `CAJA_OPERATIVA`, pero el seeder usó `MOVIMIENTO_OPERATIVO` para exactamente lo mismo. Eso ya causó el bug de movimientos duplicados en el historial (el filtro no reconocía las copias del seeder). Un campo de tipado semántico sin dominio cerrado es una fuente permanente de incoherencia.
**Soluciones:**
- SD2.1 ⭐ Definir constantes en el modelo `CashflowMovimiento` (`REF_CAJA`, `REF_PAGO_CUOTA`, `REF_LIQUIDACION`) y usarlas en todos lados; normalizar los datos existentes (`MOVIMIENTO_OPERATIVO`→`CAJA_OPERATIVA`). Idealmente `enum` de MySQL o cast a enum de PHP 8.
- SD2.2 Al menos documentar el conjunto válido y corregir el seeder para no volver a divergir.

### D3.0 — Acoplamiento por string exacto en liquidaciones (subrubro por nombre) — ✅ RESUELTO (2026-07-13)
**Severidad:** Alta
**Resolución (SD3.1):** Nueva FK `profesores.subrubro_id` (migración `2026_07_20_053556_add_subrubro_id_to_profesores`). El alta y la edición de profesor asignan el subrubro por FK; `resolverSubrubroProfesor()` usa `$profesor->subrubro` y **se eliminó el fallback peligroso** (que imputaba a "cualquier subrubro de Sueldos" — en la práctica todos los sueldos iban al cajón del primer profesor). Si un profesor no tiene subrubro, el pago frena con mensaje accionable en vez de imputar mal. La migración ató los profesores existentes por ambos formatos de nombre históricos. Probado: cada profesor resuelve su propio cajón; sin subrubro → null (no imputa a otro); profe nuevo queda vinculado.
**Dónde:** `LiquidacionPagoService` / `LiquidacionWebController:276` — el pago de liquidación busca un subrubro cuyo nombre es exactamente `"{Deporte}-{Nombre Apellido}"` del profesor.
**Qué pasa:** La relación profesor↔subrubro de liquidación se resuelve por coincidencia de string, no por FK. Si el nombre del profesor cambia, o el subrubro se creó con otro formato, el pago falla con error genérico y sin aviso previo. Es un dato que debería estar relacionado por ID, no reconstruido por concatenación.
**Soluciones:**
- SD3.1 ⭐ Agregar `subrubro_id` (FK nullable) al profesor (o una tabla puente profesor↔subrubro) y usar la relación en vez del nombre. Migrar los existentes matcheando una única vez.
- SD3.2 Mínimo: verificar la existencia del subrubro antes de mostrar el formulario de pago y avisar, en vez de fallar al confirmar.

### D4.0 — Columna muerta `pagos.forma_pago_id` (con su FK) — ✅ RESUELTO (2026-07-13)
**Severidad:** Media
**Resolución (SD4.1):** La forma de pago no vuelve. Migración `2026_07_20_060753_drop_forma_pago` elimina la columna `pagos.forma_pago_id`, su FK y la tabla `formas_pago` (verificado: 0 pagos la usaban). Se limpió todo el código: modelo `FormaPago` y `FormaPagoSeeder` eliminados; `Pago` (fillable + relación), `PagoCuotaService`, `PagoService`, `PagoController`, `StorePagoRequest`, `DatabaseSeeder` y `EjemploCompletoSeeder` sin referencias. Probado: cobro de cuota funciona sin la columna.
**Dónde:** `pagos.forma_pago_id` + índice `pagos_forma_pago_id_foreign` (verificado en la BD). La "forma de pago" se eliminó del flujo de cobro.
**Qué pasa:** Quedó la columna, su FK a `formas_pago` y la tabla `formas_pago` entera, sin uso en el flujo actual. Datos y relaciones que ya no significan nada pero siguen en el esquema, en los modelos y en las auditorías, sumando confusión.
**Soluciones:**
- SD4.1 ⭐ Decidir explícitamente: si la forma de pago no vuelve, migración que elimine `forma_pago_id`, su FK y la tabla `formas_pago` (y la pantalla de configuración asociada). Si se conserva por histórico, documentar por qué y dejar de mostrar `formas_pago` en configuración.
- SD4.2 Dejar la columna pero quitarla de `$fillable` y de las vistas para que no se use por accidente.

### D5.0 — Índice redundante en `deuda_cuotas` — ✅ RESUELTO (2026-07-13)
**Severidad:** Media
**Resolución (SD5.1):** Migración `2026_07_20_055939_drop_indice_redundante_deuda_cuotas` elimina `deuda_cuotas_alumno_id_periodo_index`; el UNIQUE cubre las consultas.
**Dónde:** `2026_02_01_000002_create_deuda_cuotas_table.php:27,30` — coexisten `index(['alumno_id','periodo'])` y `unique(['alumno_id','periodo'])`. Verificado en la BD: `deuda_cuotas_alumno_id_periodo_index` + `deuda_cuotas_alumno_periodo_unique` sobre las mismas columnas.
**Qué pasa:** El UNIQUE ya crea un índice sobre `(alumno_id, periodo)`; el `index()` explícito sobre las mismas columnas es redundante, ocupa espacio y ralentiza escrituras sin aportar a las lecturas.
**Soluciones:**
- SD5.1 ⭐ Migración que elimine el índice redundante `deuda_cuotas_alumno_id_periodo_index`; el UNIQUE cubre las consultas por `(alumno_id, periodo)`.
- SD5.2 Dejarlo (costo bajo, pero es ruido de esquema).

### D6.0 — Estados de negocio: dominio cerrado y constantes en modelos — ✅ RESUELTO (2026-07-13)
**Severidad:** Media
**Corrección del hallazgo:** verificado contra la BD real, las columnas de estado **ya eran ENUM** (dominio cerrado a nivel BD), no strings libres como asumió la auditoría inicial. Lo que faltaba resolver era: (1) `pagos.estado` arrastraba valores legacy del flujo viejo (`'pagado','parcial','adeuda'`) con default `'pagado'`; y (2) faltaban las constantes de estado en varios modelos.
**Resolución (SD6.1):** Migración `2026_07_20_060052_limpiar_enum_pagos_estado` deja `pagos.estado` en `ENUM('COMPLETADO','ANULADO')` default `'COMPLETADO'` (los 145 pagos ya eran COMPLETADO). Se agregaron las constantes de estado a `CajaOperativa`, `MovimientoOperativo` y `Pago` (DeudaCuota ya las tenía), y `PagoCuotaService`/`PagoService` ahora usan `Pago::ESTADO_COMPLETADO`.
**Dónde:** `deuda_cuotas.estado`, `cajas_operativas.estado`, `pagos.estado`, `movimientos_operativos.estado` — ENUM en BD; constantes en los modelos.
**Qué pasaba:** `pagos.estado` aceptaba valores viejos que el código ya no usa, y a varios modelos les faltaba la fuente de verdad en constantes.
**Soluciones:**
- SD6.1 ⭐ Migrar las columnas de estado a `enum` de MySQL (o cast a Enum de PHP 8.2 en el modelo) para cerrar el dominio a nivel BD/app. Empezar por las de plata: `deuda_cuotas.estado`, `cajas_operativas.estado`, `pagos.estado`.
- SD6.2 Mínimo: tests que verifiquen que solo se escriben estados válidos, y una `CHECK constraint` donde MariaDB lo permita.

## Fortalezas verificadas (no tocar)

- **Dinero:** todas las columnas de monto son `decimal(10,2)`/`(12,2)` con cast `decimal:` en los modelos. Nunca `float`. Correcto.
- **FKs e integridad:** 26 migraciones con constraints reales, 39 `onDelete` definidos. Uniques de negocio bien puestos: `(alumno_id,periodo)` en deudas, `(clase_id,alumno_id)` en asistencias, `(grupo_id,clases_por_semana,activo)` en planes, `(profesor_id,mes,anio)` en liquidaciones, `(dni,deporte_id)` en alumnos.
- **Índices:** buena cobertura en columnas calientes (`clases(fecha,horario)`, `liquidaciones(estado)`, `movimientos_operativos(estado)`, `cashflow(referencia_tipo,referencia_id)`).

## Tabla resumen

| ID | Severidad | Título | Recomendación |
|----|-----------|--------|---------------|
| D1 | Crítica | UNIQUE de alumno_planes rompe el 2º cambio de plan | ✅ Resuelto — activo nullable + NULL en cerrados (SD1.1) |
| D2 | Alta | referencia_tipo string libre e inconsistente | ✅ Resuelto — constantes + normalización (SD2.1) |
| D3 | Alta | Liquidación acopla profesor↔subrubro por nombre | ✅ Resuelto — FK subrubro_id en profesor (SD3.1) |
| D4 | Media | Columna muerta pagos.forma_pago_id | ✅ Resuelto — columna, FK, tabla y código eliminados (SD4.1) |
| D5 | Media | Índice redundante en deuda_cuotas | ✅ Resuelto — índice duplicado eliminado (SD5.1) |
| D6 | Media | Estados: enum ya existía; faltaba limpiar pagos.estado + constantes | ✅ Resuelto — enum limpio + constantes en modelos (SD6.1) |
