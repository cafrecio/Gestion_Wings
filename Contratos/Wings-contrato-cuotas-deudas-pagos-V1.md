# Wings-Contrato-Cuotas-Deudas-Pagos-V1.md

**Caso de uso (Index):** 3) Cuotas – Pagos + Imputación  
**Versión:** V1  
**Estado:** CERRADO (TO-BE implementado y verificado)  
**Incluye:** Registro de pago, auto-creación de deuda futura, imputación FIFO fuerte, integración con caja y cashflow.  
**No incluye:** Motor mensual de generación de deuda, estados dinámicos (Al día / Moroso / Deudor), reglas de asistencia, recibos PDF (ver Caso 7).

---

## 3.a Registro de pago de cuota

El pago de cuota puede realizarse desde:
- Flujo OPERATIVO (impacta en CajaOperativa)
- Flujo ADMIN (impacta directo en Cashflow)

Formato conceptual del request:
- alumno_id
- tipo_caja_id (operativo)
- items[] → { periodo (YYYY-MM), monto }

Un pago puede cubrir uno o múltiples períodos.

---

## 3.b Auto-creación de DeudaCuota (período vigente o futuro)

Si al registrar un pago no existe DeudaCuota para el período solicitado:

- Si el período es vigente o futuro → se crea automáticamente.
- Si el período es pasado y no existe deuda → error.

Reglas:
- Se respeta UNIQUE(alumno_id, periodo) (idempotencia).
- El motor mensual no puede duplicar una deuda ya existente.

---

## 3.c Cálculo de monto_original

El monto_original de la deuda se obtiene del plan aplicable al período.

Regla:
- Se determina el rango del período (YYYY-MM-01 a último día del mes).
- Se busca el AlumnoPlan cuya vigencia cubra ese período.
- Si hay más de uno aplicable, se toma el de mayor fecha_desde.
- Si no hay plan aplicable → error claro.

Nunca se usa simplemente “el plan activo hoy”.

---

## 3.d Cambio de plan y efecto temporal

Un cambio de plan puede aplicarse:
- Al mes vigente (recalcula saldo del mes actual).
- A un período futuro específico.

Reglas:
- Nunca se recalculan períodos pasados cerrados.
- Si un período futuro aún no tiene deuda, al cobrarlo se crea con el plan que aplica a ese período.

---

## 3.e Imputación de pago (FIFO fuerte)

Cuando un pago cubre múltiples períodos:

1) Se ordenan por período ASC (más antiguo primero).
2) Se aplican montos dejando cada deuda vieja:
   - completamente pagada, o
   - si el pago no alcanza, solo la última puede quedar parcial.

Prohibido:
- Dejar parcial una deuda vieja si había dinero suficiente para completarla.

El operador puede ajustar el reparto, pero sin violar la regla anterior.

Ejemplo obligatorio:
- Debe Mar 20k, Abr 20k, May 20k.
- Paga 50k.
Resultado:
- Mar = pagada
- Abr = pagada
- May = parcial (10k pendiente)

---

## 3.f Pagos parciales

Se permiten pagos parciales.

Reglas:
- monto_pagado nunca supera monto_original.
- estado de deuda se actualiza según saldo.

---

## 3.g Integración con Caja y Cashflow

Operativo:
- Genera MovimientoOperativo en CajaOperativa.
- Impacta en Cashflow solo cuando la caja es VALIDADA por ADMIN.

Admin:
- Genera CashflowMovimiento directo.
- No requiere validación posterior.

---

## 3.h Estado del pago

Valores oficiales en columna estado de pagos:
- 'pagado'
- 'parcial'
- 'adeuda'
- 'COMPLETADO' (flujo de cuota)

La columna se mantiene como ENUM en base de datos.

---

## 3.i Reglas Freeze

- No se permite crear deuda pasada automáticamente.
- No se permite romper FIFO fuerte.
- No se permite recalcular períodos históricos cerrados.
- El plan aplicable siempre se determina por vigencia en el período.

Cualquier cambio futuro requiere versión V2 explícita.

---

## 3.j Estado

🔒 CONTRATO CERRADO.

Implementación verificada en backend y consistente con esquema de base de datos.