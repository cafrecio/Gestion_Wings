# Caja y Cashflow — V5, enmienda A25

**Fecha/version:** 06/10/2026.v2. **Autor:** Codex CAB.
**Estado:** implementada, diseño aprobado por Carlos; pendiente verificación independiente.
No desplegada. [V4 antecedente](Wings-Contrato-Caja-Cashflow-V4.md).
[Decisiones de Carlos](../05-pendientes/A25-CAMBIO-INICIAL-CAJA.md).

Esta enmienda sustituye V4 §3.1, §3.3, §3.4 y las referencias a apertura automática
o validación que cierra sin contar. No cambia las reglas financieras del cobro,
sus comisiones, la inscripción ni la integración idempotente con Cashflow.

## Decisiones de Carlos — lectura común obligatoria

Consolidación documental solicitada el 06/10/2026: reúne sus respuestas, sin agregar
reglas nuevas a V5.v1. Codex, Claude y Gemini deben usar esta versión para A25;
V4 queda como antecedente, no como instrucción para abrir o cerrar el cajón.

| Tema | Regla aprobada y ejemplo |
|---|---|
| Cajón compartido | Uno para el club, no uno por operativa. Si Susana dejó $10.000, la siguiente operativa recibe esa propuesta. |
| Turnos | Cerrar el anterior antes de abrir otro; no dos turnos abiertos sobre el mismo cajón. |
| Primera apertura | Quien abre declara cuánto recibió; no se inventa un importe inicial. |
| Aperturas siguientes | Proponer el cambio del último cierre del club y pedir confirmación. Si propone $10.000 y cuenta $8.000, declara $8.000 con motivo; conservar ambos valores. |
| Cierre y retiro | Quien cierra cuenta y decide cuánto deja. Contado $35.000, cambio $10.000: retiro $25.000. Puede dejar $15.000 y retirar $20.000; no está atado al cambio inicial. |
| Diferencias | Inicio $10.000 + cobros $30.000 − gastos $5.000: esperado $35.000. Contado $34.000: faltante $1.000. Mostrar los tres y permitir cerrar; ADMIN revisa. |
| Medio físico | ADMIN configura una vez qué medio corresponde al efectivo del cajón; Transferencia no integra ese conteo. |
| Cobros del ADMIN | Vanina guarda su efectivo aparte: no aumenta el esperado del turno operativo. |
| Validación ADMIN | Debe contar y cerrar primero, incluso el turno de otra persona. Validar no sustituye el conteo. |
| Rechazo posterior | Corregir movimientos conserva lo contado y entregado; no cambia lo recibido por el turno siguiente. Respuesta de Carlos: «Sí, conservar lo que se contó y entregó». |

El cambio para vuelto es custodia física, no un ingreso nuevo ni el `saldo_inicial`
contable del TipoCaja. Tampoco se crea automáticamente un ajuste por la diferencia.
La aprobación de estas reglas y del diseño **no cierra A25**: continúa HECHO (Codex),
a revisar por otro agente, sin despliegue. El detalle normativo sigue a continuación.

## Apertura y turnos

- Un solo cajón compartido del club; como máximo un turno ABIERTA para todos los
  operativos. Cerrar el turno anterior antes de abrir el siguiente.
- ADMIN selecciona una vez el TipoCaja activo que representa billetes. Se guarda su
  ID y autor/fecha; cambiar su nombre no cambia el medio. No se deduce por nombre.
- La apertura es explícita: declara efectivo recibido no negativo y confirma haberlo
  contado. El primer movimiento y los POST directos no abren una caja por su cuenta.
- La primera apertura, sin cierre declarado previo, no presupone efectivo recibido.
- Las siguientes proponen el cambio retenido en el último turno cerrado del club,
  aunque todavía no esté validado. El origen queda guardado. Orden por ID de turno:
  volver a cerrar una rechazado antiguo no lo convierte en el último turno.
- Recibido distinto de lo heredado exige motivo; se guardan ambos valores. Un formulario
  cuyo cierre de origen quedó desactualizado se rechaza y debe volver a confirmarse.
- ADMIN puede abrir para un OPERATIVO activo, nunca una caja propia. Se registra quién
  declaró la apertura y quién es el operativo del turno.
- Continúa el bloqueo de operación con turno abierto de un día anterior.

## Conteo y cierre

Solo movimientos ACTIVO del medio físico guardado en el turno:

`esperado = inicial + ingresos de efectivo − egresos de efectivo`

`diferencia = contado − esperado`; `retiro/entrega = contado − cambio retenido`.

- No sumar transferencias, movimientos cancelados ni cobros directos ADMIN. Carlos
  decidió que Vanina guarda estos últimos aparte del cajón operativo.
- Contado y cambio retenido deben ser no negativos; retenido no supera contado.
  Se puede dejar más cambio que al abrir, si alcanza el efectivo realmente contado.
- Faltante o sobrante no bloquean cerrar. ADMIN revisa esperado, contado y diferencia.
- El dueño del turno o ADMIN cuentan y cierran. Guardar actor, fecha y condición ADMIN.
- Validar o rechazar exige caja CERRADA con conteo; ninguna de esas acciones cierra
  automáticamente una ABIERTA ni inventa un contado.
- Declaración inicial, retiro y diferencia NO crean ingresos/gastos, ni alteran el
  saldo inicial histórico del TipoCaja. Solo se documenta custodia física; no se
  automatizan ajustes financieros por faltantes/sobrantes.

## Rechazadas y conservación de la entrega

Se conserva la posibilidad existente de corregir movimientos de una RECHAZADA.
Al volver a cerrarla, recalcular esperado/diferencia pero conservar contado, cambio,
retiro, fecha y actor del cierre físico original. Ni un POST directo puede reescribirlos.
El resumen muestra esperado/diferencia actuales mientras se corrige, sin mutar el conteo.
No se recalcula ni modifica lo heredado/recibido del turno siguiente.

Ejemplo aprobado: lunes contó $35.000 y dejó $10.000; martes recibió $10.000.
Corregir el ingreso del lunes de $25.000 a $20.000 cambia el esperado de $35.000
a $30.000 y muestra sobrante $5.000, pero contado $35.000, cambio $10.000,
entrega $25.000 y apertura del martes permanecen iguales.

## Históricos y concurrencia

- Migración nueva; no modificar migraciones previas ni rellenar históricos. NULL
  significa no declarado, diferente de cero contado. Una histórica sin inicial/medio
  no tiene esperado ni diferencia calculables. Se muestra explícitamente.
- Una ABIERTA histórica sin declaración no permite nuevos movimientos: primero cerrar
  contando lo real. Una CERRADA sin conteo permite completar ese paso antes de validar.
  No se afirma que ese conteo de hoy fuese el efectivo de una fecha pasada.
- Aperturas y cierres serializan con fila persistente del cajón y bloqueo de turno.
  Movimientos manuales bloquean el turno hasta terminar: entran completos antes del
  cierre o son rechazados después, nunca cambian un turno ya contado/validado.
- Validación conserva la integración idempotente; declarar efectivo no crea reflejos.
- API REST sigue deshabilitada. No se reactivan sus antiguos caminos de apertura/cierre.

## Aceptación

Pruebas de apertura, diferencias, transferencia/admin excluidos, herencia, corrección,
roles, POST directo y dos conexiones están en `CajaCambioInicialA25Test` (22) y
`CajaArqueoConcurrenteA25Test` (4). Suite propia y capturas en la [entrega A25](../06-pruebas/PRU-02/IMPLEMENTACION-A25.md).
La autorización de diseño no equivale a verificación independiente ni despliegue.
