# Wings-ER-Caja-Cashflow-V4.md
**Caso de Uso:** Caja Operativa + Cashflow  
**Versión:** V4  
**Estado:** Candidato a Cierre (pendiente validación)  
**Fuente de verdad:** Index + Repo (migraciones/services)

---

## 1. Entidades

### 1.1 CajaOperativa
**Tabla:** `cajas_operativas`

- id (PK)
- fecha (date)
- tipo_caja_id (FK → TipoCaja)
- usuario_operativo_id (FK → User)
- estado (enum: ABIERTA | CERRADA | VALIDADA | RECHAZADA)
- apertura_at (datetime)
- cierre_at (datetime, nullable)

**Campos admin / auditoría**
- cerrada_por_admin (bool, default false)
- usuario_admin_cierre_id (FK → User, nullable)
- usuario_admin_validacion_id (FK → User, nullable)
- validada_at (datetime, nullable)

**Rechazo**
- motivo_rechazo (string/text, nullable) ✅ (agregado en V4)

**Timestamps**
- created_at
- updated_at

---

### 1.2 MovimientoOperativo
**Tabla:** `movimientos_operativos`

- id (PK)
- caja_operativa_id (FK → CajaOperativa)
- usuario_id (FK → User)
- subrubro_id (FK → Subrubro)
- tipo_caja_id (FK → TipoCaja)
- monto (decimal)
- descripcion (string, nullable)
- fecha (date, nullable) *(para reportes; la “verdad” operativa está en la CajaOperativa)*

**Timestamps**
- created_at
- updated_at

---

### 1.3 CashflowMovimiento
**Tabla:** `cashflow_movimientos`

- id (PK)
- fecha (date)
- tipo (enum: INGRESO | EGRESO)
- subrubro_id (FK → Subrubro)
- monto (decimal)
- descripcion (string, nullable)

**Origen (integración Caja → Cashflow)**
- caja_operativa_id (FK → CajaOperativa, nullable)
- movimiento_operativo_id (FK → MovimientoOperativo, nullable)

**Clasificación**
- tipo_caja_id (FK → TipoCaja) ✅ (agregado en V4)

**Timestamps**
- created_at
- updated_at

---

### 1.4 Subrubro
**Tabla:** `subrubros`

- id (PK)
- rubro_id (FK → Rubro)
- nombre (string)
- reservado (bool) *(si aplica por reglas operativas)*

---

### 1.5 Rubro
**Tabla:** `rubros`

- id (PK)
- nombre (string)

---

### 1.6 TipoCaja
**Tabla:** `tipos_caja`

- id (PK)
- nombre (string)

---

### 1.7 User
**Tabla:** `users`

- id (PK)
- name / email / etc
- rol (enum: ADMIN | OPERATIVO)

---

## 2. Relaciones (Cardinalidades)

- TipoCaja 1 ── N CajaOperativa
- User(OPERATIVO) 1 ── N CajaOperativa (por `usuario_operativo_id`)
- CajaOperativa 1 ── N MovimientoOperativo
- Subrubro 1 ── N MovimientoOperativo
- TipoCaja 1 ── N MovimientoOperativo

- Subrubro 1 ── N CashflowMovimiento
- TipoCaja 1 ── N CashflowMovimiento ✅ (V4)
- CajaOperativa 1 ── N CashflowMovimiento (cuando proviene de caja)
- MovimientoOperativo 1 ── 1 CashflowMovimiento (cuando se integra; idempotente)

---

## 3. Restricciones de negocio (ER-level)

- No puede existir una **CajaOperativa ABIERTA** con `fecha` distinta al día actual.
- Por usuario: no puede haber más de **una caja ABIERTA** simultánea.
- Rechazo: si estado = RECHAZADA ⇒ `motivo_rechazo` debe existir (nullable a nivel DB, obligatorio a nivel request).
- Integración Caja→Cashflow es idempotente (no duplica movimientos).
