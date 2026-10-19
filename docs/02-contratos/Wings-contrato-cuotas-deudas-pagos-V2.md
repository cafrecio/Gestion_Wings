# Wings-Contrato-Cuotas-Deudas-Pagos-V2.md

**Caso de uso (Index):** 3) Cuotas – Pagos + Imputación
**Versión:** V2
**Estado:** VIGENTE; T44 implementada localmente, pendiente verificación de Claude (18/10/2026)
**Incluye:** Registro de pago, auto-creación de deuda futura, imputación FIFO fuerte, integración con caja y cashflow.
**No incluye:** Motor mensual de generación de deuda, estados dinámicos (Al día / Moroso / Deudor), reglas de asistencia, recibos PDF (ver Caso 7).

---

## 3.a Registro de pago de cuota

✅ **Nota (2026-07-26):** este es el único flujo de pago vigente. Existió antes un "flujo de pago regular" (tabla `pagos` con `plan_id`, `mes`/`año`, `regla_primer_pago_id`, `forma_pago_id`, estado en `pagado/parcial/adeuda`), implementado en `PagoService.php` y alcanzable solo por la API. Quedó **legacy**: la API está deshabilitada desde S2, `forma_pago_id` se eliminó en D4 y esos valores de estado se eliminaron del ENUM en D6. `PagoService.php` sigue en el repo pero sin consumidor real. Este contrato documenta exclusivamente el flujo de cuota (el que se usa hoy).

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
- Si hay más de uno aplicable, se toma el de mayor fecha_desde y luego el de mayor id.
- Su tarifa se resuelve por `GrupoPlan::precioParaPeriodo`: existentes toman el último
  cambio anterior al mes; las altas nuevas, el vigente el día del ingreso. Ver [Planes V5](Wings-contrato-alumno-grupo-deporte-deuda-v5.md#2j-precios-con-vigencia--t44-carlos-18102026).
- Si no hay plan aplicable → error claro.

Nunca se usa simplemente “el plan activo hoy”.

---

## 3.d Cambio de plan y efecto temporal

Decisión de Carlos, 18/10/2026:

- Una bajada de frecuencia siempre empieza el primer día del mes siguiente, con o sin asistencias.
- Una subida empieza en el mes actual si la cuota sigue pendiente o parcialmente pagada:
  se ajusta el importe según el nuevo plan y su tarifa aplicable, conservando lo ya pagado.
- Si el mes está saldado o condonado, la subida queda para el primer día del mes siguiente.
- No se recalculan períodos pasados cerrados. La tarifa del catálogo por sí sola nunca
  recalcula una cuota ya generada. El ajuste por cambio inmediato de plan es la excepción
  explícita confirmada por Carlos; no es un efecto automático del aumento de tarifa.
- La pantalla y el guardado usan `CambioPlanService`. Un plan futuro no se muestra como
  vigente antes de tiempo. Cobrar, edición de ficha y el servicio legado comparten la decisión.


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

✅ **Corregido (2026-07-26):** valor real de la columna `estado` de `pagos`, tras la limpieza de **D6.0**:
- `'COMPLETADO'` (pago de cuota, el único flujo vigente)
- `'ANULADO'`

Los valores `'pagado'`, `'parcial'`, `'adeuda'` **ya no existen** en el ENUM real de la base de datos. Pertenecían al "flujo de pago regular" (ver nota de la sección 3.a más abajo), que es **legacy** — no se usa desde que existe el flujo de cuota vía `DeudaCuota`/`PagoCuotaService`. La columna se mantiene como ENUM en base de datos.

---

## 3.i Reglas Freeze

- No se permite crear deuda pasada automáticamente.
- No se permite romper FIFO fuerte.
- No se permite recalcular períodos históricos cerrados.
- El plan aplicable siempre se determina por vigencia en el período.

Cualquier cambio futuro requiere versión V3 explícita.

---

## 3.j Estado

Contrato V2 vigente.

T44 implementada localmente; la evidencia y los límites de verificación se registran en [la entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T44.md).
