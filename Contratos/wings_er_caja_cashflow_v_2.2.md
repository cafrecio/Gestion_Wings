# Wings – Entidad Relación Caja–Cashflow V2.2

## 1. Objetivo

Definir de forma **estructural y sin ambigüedades** las entidades y relaciones involucradas en la operatoria de **Caja Operativa** y **Cashflow**, alineadas al contrato *Wings–Contrato–Caja–Cashflow V2*.

Este documento **no define lógica**, solo **modelo de datos y vínculos**.

---

## 2. Entidades Principales

### Usuario
- id
- nombre
- rol (OPERATIVO | ADMIN)

Relaciones:
- Un usuario OPERATIVO genera cierres de caja
- Un usuario ADMIN valida cierres y registra movimientos de cashflow

---

### Rubro
- id
- nombre
- tipo (INGRESO | EGRESO)
- observacion
- timestamps

Relaciones:
- Un rubro tiene muchos subrubros

Restricciones:
- El tipo es inmutable para sus subrubros

---

### Subrubro
- id
- rubro_id (FK)
- nombre
- permitido_para (OPERATIVO | ADMIN)
- afecta_caja (bool)
- timestamps

Relaciones:
- Pertenece a un rubro
- Es utilizado por movimientos

Reglas:
- No puede contradecir el tipo del rubro

---

### TipoCaja
- id
- nombre (Efectivo | Banco | MercadoPago | etc.)

Relaciones:
- Es referenciado por movimientos

Nota:
- No genera cierres independientes

---

### CajaOperativa
- id
- usuario_operativo_id (FK Usuario)
- apertura_datetime
- cierre_datetime (nullable)
- estado (ABIERTA | CERRADA | VALIDADA | CERRADA_POR_ADMIN)
- timestamps

Relaciones:
- Pertenece a un usuario operativo
- Tiene muchos movimientos

Restricciones:
- Un usuario solo puede tener **una caja ABIERTA** a la vez
- Puede haber múltiples cajas por día (distintos turnos o usuarios)
- No puede quedar una caja abierta al cambiar de día (bloqueo lógico)


---

### MovimientoOperativo
> (antes figuraba como MovimientoCaja)

- id
- caja_operativa_id (FK CajaOperativa)
- fecha (date) *(opcional; para reportes rápidos. La fuente de verdad del turno es apertura_datetime/cierre_datetime de CajaOperativa)*
- tipo_caja_id (FK TipoCaja)
- subrubro_id (FK Subrubro)
- monto
- observaciones (nullable)
- usuario_id (FK Usuario) *(quién lo registró; normalmente OPERATIVO)*
- timestamps

Relaciones:
- MovimientoOperativo belongsTo CajaOperativa
- MovimientoOperativo belongsTo TipoCaja
- MovimientoOperativo belongsTo Subrubro → (Subrubro belongsTo Rubro)

Notas:
- **Rubro no se guarda** en el movimiento porque queda **implícito** vía `subrubro_id → rubro_id`.
- El tipo (INGRESO/EGRESO) también queda implícito por el rubro. Guardarlo como columna es opcional (denormalización).

Restricciones:
- Si usuario.rol = OPERATIVO → caja_operativa_id obligatorio
- Subrubro.permitido_para debe permitir OPERATIVO

---

### CashflowMovimiento
> Movimientos económicos reales, registrados por ADMIN.

- id
- fecha (date)
- subrubro_id (FK Subrubro)
- monto
- observaciones (nullable)
- usuario_admin_id (FK Usuario)
- referencia_tipo (nullable) *(ej: 'LIQUIDACION', 'AJUSTE', 'APORTE', 'RETIRO', 'SERVICIO', etc.)*
- referencia_id (nullable)
- timestamps

Relaciones:
- CashflowMovimiento belongsTo Subrubro → (Subrubro belongsTo Rubro)
- CashflowMovimiento belongsTo Usuario (ADMIN)

Notas:
- **Rubro no se guarda** en el movimiento porque queda **implícito** vía `subrubro_id → rubro_id`.

Restricciones:
- Subrubro.permitido_para debe permitir ADMIN
- Estos movimientos **no pertenecen** a CajaOperativa

Incluye:
- Sueldos
- Alquileres
- Intereses
- Retiros de utilidades
- Aportes de capital
- Ajustes

---

## 3. Relación Caja ↔ Cashflow

- Las cajas operativas pertenecen a usuarios, no al día
- Los movimientos OPERATIVOS viven dentro de una caja
- Las cajas cerradas **no impactan cashflow automáticamente**
- El ADMIN puede:
  - Cerrar cajas de otros usuarios (cierre delegado)
  - Validar cajas
- Los movimientos ADMIN impactan cashflow directamente

La caja sirve para **orden y control operativo**
El cashflow sirve para **verdad económica**

---

## 4. Diagrama Conceptual (Texto)

Usuario (OPERATIVO)
  └── CajaOperativa
        └── MovimientoOperativo ── Subrubro ── Rubro
                              │
                              └── TipoCaja

Usuario (ADMIN)
  ├── CashflowMovimiento ── Subrubro ── Rubro
  └── Validación de CierreCaja

---

## 5. Estado del Documento

- Documento: Wings–ER–Caja–Cashflow–V2.2
- Reemplaza: Wings–ER–Caja–Cashflow–V2
- Estado: Congelado
- Cambios futuros → nueva versión o anexo

---

**Wings – Sistema de Gestión**
