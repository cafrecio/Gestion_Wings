# Wings-ER-Cuotas-Deudas-Pagos-V1.md

**Caso de uso (Index):** 3) Cuotas – Pagos + Imputación  
**Versión:** V1  
**Estado:** CANDIDATO A CIERRE  
**Fuente de verdad:** repo actualizado (migraciones + services)

---

## 3.2.a Entidades

### 3.2.a.1 DeudaCuota
**Tabla:** `deuda_cuotas`

- id (PK)
- alumno_id (FK → alumnos.id)
- periodo (string 7, formato `YYYY-MM`)
- monto_original (decimal 10,2)
- monto_pagado (decimal 10,2, default 0)
- estado (enum: `PENDIENTE` | `PAGADA` | `CONDONADA` | `AJUSTADA`, default `PENDIENTE`)
- created_at, updated_at

Índices / constraints:
- INDEX(alumno_id, periodo)
- UNIQUE(alumno_id, periodo)

Relaciones:
- Alumno 1 ── N DeudaCuota

---

### 3.2.a.2 Pago
**Tabla:** `pagos`

- id (PK)
- alumno_id (FK → alumnos.id)

**Campos del flujo “pago regular” (existen en el mismo registro):**
- plan_id (FK → grupo_planes.id, NULLABLE)  
- regla_primer_pago_id (FK → reglas_primer_pago.id, nullable)
- mes (tinyint)
- anio (smallint)
- monto_base (decimal 10,2)
- porcentaje_aplicado (decimal 5,2)
- monto_final (decimal 10,2)
- forma_pago_id (FK → formas_pago.id, NULLABLE)

**Estado (compartido por ambos flujos):**
- estado (ENUM: `pagado` | `parcial` | `adeuda` | `COMPLETADO`, default `pagado`)

**Campos de negocio (para ambos flujos):**
- fecha_pago (date)
- observaciones (text, nullable)
- created_at, updated_at

Relaciones:
- Alumno 1 ── N Pago
- Pago N ── N DeudaCuota (vía pivote)

Nota importante (para entender el modelo sin mezclar casos):
- Este caso de uso usa el registro `pagos` con `estado='COMPLETADO'` y típicamente `plan_id=NULL`, `forma_pago_id=NULL`.
- El “pago regular” (otro flujo) usa `estado` en (`pagado`,`parcial`,`adeuda`) y puede tener plan/forma.

---

### 3.2.a.3 PagoDeudaCuota (pivote de imputación)
**Tabla:** `pago_deuda_cuota`

- id (PK)
- pago_id (FK → pagos.id)
- deuda_cuota_id (FK → deuda_cuotas.id)
- monto_aplicado (decimal 12,2)
- created_at, updated_at

Constraints:
- UNIQUE(pago_id, deuda_cuota_id)

Relaciones:
- Pago 1 ── N PagoDeudaCuota
- DeudaCuota 1 ── N PagoDeudaCuota

---

## 3.2.b Entidades relacionadas (mínimas, por dependencia)

### 3.2.b.1 Alumno
**Tabla:** `alumnos`

- id (PK)
- deporte_id (FK → deportes.id)
- grupo_id (FK → grupos.id)
- dni, nombre, apellido, activo, timestamps (ver Caso 2)

Relaciones relevantes para Caso 3:
- Alumno 1 ── N DeudaCuota
- Alumno 1 ── N Pago
- Alumno 1 ── N AlumnoPlan

---

### 3.2.b.2 GrupoPlan
**Tabla:** `grupo_planes`

- id (PK)
- grupo_id (FK → grupos.id)
- clases_por_semana (int)
- precio_mensual (decimal 10,2)
- activo (bool)
- timestamps

---

### 3.2.b.3 AlumnoPlan
**Tabla:** `alumno_planes`

- id (PK)
- alumno_id (FK → alumnos.id)
- plan_id (FK → grupo_planes.id)
- fecha_desde (date)
- fecha_hasta (date, nullable)
- activo (bool)
- timestamps

Regla estructural:
- Un alumno debe tener como máximo un plan activo, y además la vigencia por fechas define qué plan aplica a cada período.

---

## 3.2.c Integración con Caja y Cashflow (referencias del modelo)

### 3.2.c.1 MovimientoOperativo
**Tabla:** `movimientos_operativos`

- id (PK)
- caja_operativa_id (FK → cajas_operativas.id)
- tipo_caja_id (FK → tipos_caja.id)
- subrubro_id (FK → subrubros.id)
- monto (decimal 12,2)
- fecha (date, nullable)
- observaciones (text, nullable)
- usuario_id (FK → users.id)
- timestamps

Nota:
- No existe FK directa desde `movimientos_operativos` a `pagos`.
- La relación con el pago queda trazada vía `observaciones` (texto) y el registro de negocio `pagos`.

---

### 3.2.c.2 CashflowMovimiento
**Tabla:** `cashflow_movimientos`

- id (PK)
- fecha (date)
- subrubro_id (FK → subrubros.id)
- tipo_caja_id (FK → tipos_caja.id)
- monto (decimal 12,2)
- observaciones (text, nullable)
- usuario_admin_id (FK → users.id)
- referencia_tipo (string, nullable)  *(ej: `PAGO_CUOTA`)*
- referencia_id (bigint, nullable)    *(ej: id del pago)*
- timestamps

Índice:
- INDEX(referencia_tipo, referencia_id)

---

## 3.2.d Reglas/Constraints de consistencia (ER-level)

- UNIQUE(alumno_id, periodo) en `deuda_cuotas` garantiza idempotencia de deuda por período.
- UNIQUE(pago_id, deuda_cuota_id) en `pago_deuda_cuota` evita duplicar imputación a la misma deuda.
- `pagos.estado` admite `COMPLETADO` para pagos de cuota.
- La regla FIFO fuerte es lógica de service (no constraint DB): el ER la documenta como comportamiento, no como constraint.

