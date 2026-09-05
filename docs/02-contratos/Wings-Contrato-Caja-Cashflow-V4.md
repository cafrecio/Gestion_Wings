# Wings-Contrato-Caja-Cashflow-V4.md
**Caso de Uso:** Caja Operativa + Cashflow  
**Versión:** V4  
**Estado:** Candidato a Cierre (pendiente validación)  
**Fuente de verdad:** Index + Repo (migraciones/services/controllers)

---

## 1. Propósito
Gestionar operación diaria de caja por usuario (operativo), registrar movimientos operativos y reflejarlos en cashflow de forma **idempotente**, con ciclo de cierre y validación/rechazo por admin.

---

## 2. Roles
- **OPERATIVO:** opera caja, registra movimientos, cierra su caja.
- **ADMIN:** puede cerrar cajas viejas, validar cierres o rechazar con motivo.

---

## 3. Reglas Operativas (núcleo)

### 3.1 Apertura automática
- Al primer movimiento operativo del día del usuario, si no existe caja ABIERTA para ese usuario, se crea una CajaOperativa ABIERTA (apertura automática).

### 3.2 Caja vieja (bloqueo)
- No puede quedar una caja ABIERTA de un día anterior.
- Si el usuario intenta operar y tiene una caja ABIERTA de fecha vieja, el sistema bloquea y fuerza intervención administrativa.

### 3.3 Una caja abierta por usuario
- Un usuario no puede tener más de una caja ABIERTA al mismo tiempo.

### 3.4 Cierre
- El OPERATIVO cierra su caja (pasa a CERRADA).
- El ADMIN puede cerrar una caja (y se marca como cerrada_por_admin).

### 3.5 Validación / Rechazo (ADMIN)
- El ADMIN **valida** una caja cerrada (pasa a VALIDADA).
- El ADMIN **rechaza** una caja cerrada (pasa a RECHAZADA) y **DEBE** registrar un **motivo**.

✅ **Regla agregada en V4 (obligatoria):**  
Al rechazar, el request debe incluir `motivo` y se persiste en `cajas_operativas.motivo_rechazo`.

---

## 4. Integración Caja → Cashflow (idempotente)

### 4.1 Qué se integra
- Cada MovimientoOperativo que corresponda genera un CashflowMovimiento asociado.
- La integración no debe duplicar registros: si ya existe el CashflowMovimiento para ese MovimientoOperativo, no se crea otro.

### 4.2 Campos clave en cashflow
Un CashflowMovimiento derivado de caja debe referenciar:
- `caja_operativa_id`
- `movimiento_operativo_id`

✅ **Aclaración agregada en V4:**  
El CashflowMovimiento también almacena `tipo_caja_id` (para saldos/agrupaciones por tipo de caja).

### 4.3 Saldo inicial por tipo de caja

- Cada `TipoCaja` tiene un `saldo_inicial` mayor o igual a cero, con valor predeterminado cero.
- El saldo inicial no es un ingreso ni genera un `CashflowMovimiento`.
- El saldo disponible se calcula como `saldo_inicial + SUM(cashflow_movimientos.monto)`.
- La validación de fondos para pagar una liquidación usa ese mismo saldo disponible.

#### El saldo inicial se carga UNA sola vez, al crear el tipo de caja

**No se puede editar después.** Definido por Carlos el 30/08/2026; quedó sin registrar
hasta el 06/09 y por eso se implementó editable.

**Por qué:** el saldo inicial es el punto de partida de todo el historial. Si se cambia
más adelante, **todos los saldos pasados cambian con él**, y un reporte de septiembre
pasa a dar distinto en diciembre sin que ningún movimiento lo explique. Es reescribir
historia sin dejar rastro.

**Cómo se corrige un saldo después de creado:** con un movimiento no económico —ver
4.4—, que sí queda registrado, con fecha, importe y motivo.

**Única excepción admitida:** mientras el tipo de caja **no tenga ningún movimiento**,
el saldo inicial puede corregirse. Cubre el error de tipeo del primer día sin permitir
reescribir un historial que ya existe.

### 4.4 Movimientos no económicos

Los subrubros marcados como que **no afectan caja** estaban mal nombrados: no es que no
muevan plata, es que **no generan un resultado**.

**Son movimientos que cambian el saldo pero no son ganancia ni pérdida.** Ejemplos:
aporte de capital, retiro de utilidades, préstamo del dueño, devolución de ese préstamo.

| Tipo de movimiento | Cambia el saldo | Entra en el resultado |
|---|---|---|
| Cobro de cuota, venta de indumentaria | Sí | **Sí** |
| Alquiler, sueldos, servicios | Sí | **Sí** |
| **Aporte de capital, retiro de utilidades** | **Sí** | **No** |

**Consecuencia obligatoria para los reportes:** el cálculo del resultado del mes
**tiene que excluir los movimientos no económicos**. Si no, un aporte del dueño para
cubrir un mal mes aparece como ganancia, y el reporte dice exactamente lo contrario de
lo que pasó.

---

## 5. Endpoints (contrato funcional)

> Nota: se listan por intención. Los nombres exactos de rutas/controladores se validan contra repo.

### 5.1 Operativo: movimientos
- Crear MovimientoOperativo (si no hay caja ABIERTA, abre automáticamente)
- Listar movimientos por caja / por fecha (según implementación)

### 5.2 Operativo: cierre
- Cerrar CajaOperativa propia (si está ABIERTA)

### 5.3 Admin: gestión de cajas
- Cerrar caja vieja (cierre administrativo)
- Validar caja cerrada
- Rechazar caja cerrada **con motivo obligatorio** ✅ (V4)

---

## 6. Estados permitidos (CajaOperativa)
- ABIERTA → CERRADA (operativo) o CERRADA (admin, con marca cerrada_por_admin)
- CERRADA → VALIDADA (admin)
- CERRADA → RECHAZADA (admin, requiere motivo_rechazo)

---

## 7. Datos que se deben guardar (mínimo audit)
- Quién abrió/operó: `usuario_operativo_id`
- Quién cerró si fue admin: `usuario_admin_cierre_id` + `cerrada_por_admin`
- Quién validó: `usuario_admin_validacion_id` + `validada_at`
- Rechazo: `motivo_rechazo` (obligatorio al rechazar)

---

## 8. Criterio de cierre del contrato
Este contrato se considera **cerrado** cuando:
- ER y migraciones coinciden (incluyendo `motivo_rechazo` y `tipo_caja_id` en cashflow).
- Los endpoints de rechazo validan y persisten `motivo`.
- La integración caja→cashflow es idempotente (verificado en services).
