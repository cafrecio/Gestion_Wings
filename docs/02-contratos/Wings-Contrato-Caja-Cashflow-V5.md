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
| Aviso por diferencia grande (T23) | Si lo declarado difiere > 50% (arriba o abajo) del cierre anterior (si dejó > $0), aviso enfático en pantalla; exige confirmación explícita para abrir. Decisión de Carlos 11/10/2026. |
| Rectificación de apertura (T23) | Con caja ABIERTA, el operativo o ADMIN pueden rectificar el inicial. Motivo obligatorio, conserva valor anterior y recalcula el esperado del turno. Bloqueado si la caja está CERRADA o VALIDADA. Decisión de Carlos 11/10/2026. |
| Detalle, registrador y validador en Cashflow (T28) | Un cobro de cuota o inscripción muestra en «Detalle» el `Apellido, Nombre` del alumno. «Registró» muestra quién cargó el movimiento: la operativa si provino de una caja validada, el dueño si cobró directo sin caja. Cada renglón de caja validada se vincula directamente por `movimiento_operativo_id` (migración), sin heurísticas ni adivinación de textos. Para renglones viejos sin ID: coincidencia exacta o conserva la observación original (sin fallbacks). Se incorpora la columna «Val.» (escritorio) y badge «Val: <iniciales>» (celular) con las iniciales de quien validó la caja (con tooltip de nombre completo); queda vacía en movimientos directos sin caja. Desempate de iniciales por segunda letra distintiva (ej. CB y CZ). Decisión de Carlos 12/10/2026. |

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
- Si lo declarado difiere más del 50% de lo que dejó el último cierre (si dejó > $0),
  el sistema emite un aviso enfático en pantalla. La persona puede continuar únicamente
  confirmando la diferencia en pantalla. En la primera apertura o si el cierre previo
  dejó $0 no aplica el aviso del 50% (en $0 solo rige el motivo habitual si difiere de cero).
- Mientras la caja permanezca ABIERTA, el efectivo inicial recibido puede rectificarse
  tanto por el operativo responsable del turno como por ADMIN. La rectificación exige
  motivo obligatorio, registra fecha, usuario y conserva el efectivo inicial previo
  (auditoría). Al rectificar, el efectivo esperado del turno se recalcula de inmediato
  con el nuevo importe inicial. Una caja CERRADA, VALIDADA o RECHAZADA no admite
  rectificación (tampoco por POST directo).
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

T37, decisión de Carlos del 17/10/2026: rechazar exige un motivo de texto
obligatorio, de hasta 500 caracteres; solo espacios equivale a vacío. No hay
un mínimo de longitud adicional. Controlador web y servicio verifican la regla.
Detalle y Resumen lo piden y muestran el error del servidor aunque el modal se cierre.
Se conserva el motivo en el resumen para que la operativa sepa qué corregir.
Las cajas rechazadas históricas sin motivo se conservan, sin rellenarlas.

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

## T27 — Correcciones del dueño desde Cashflow (Carlos, 12/10/2026)

- Cashflow es la entrada única. Acciones de fila con íconos, sin botón «Corregir».
- Solo ADMIN activo puede entrar o corregir, también ante pedidos directos. No se
  limita a quién creó un directo: el rol ADMIN trabaja sobre el negocio completo.
- Directo manual, aporte y retiro: Editar modifica el mismo registro y conserva
  historial completo de antes/después, actor, fecha y motivo opcional. El creador
  original no se reemplaza por quien corrige. Los reservados usan su propio flujo.
- Eliminar conserva el original y crea un único asiento de signo contrario en el
  mismo medio, subrubro, fecha y clasificación histórica. Original anulado y
  contraasiento no permiten nuevas correcciones directas. Ambos quedan visibles.
- Sin límite de antigüedad; no se admiten fechas futuras. La corrección actualiza
  los reportes del período original con historial; no se desplaza al mes actual.
- Un formulario viejo no puede pisar otro cambio. El doble envío de una operación
  no crea otra edición ni otro contraasiento. Escritura e historial son atómicos:
  fallo de cualquiera revierte toda la operación. Dos conexiones se serializan
  bloqueando el movimiento original antes de escribir.
- Importes de edición: componente y validación T24, positivos y hasta dos
  decimales. El signo contable lo determina el subrubro, no el texto ingresado.
- Cobro/inscripción del dueño lleva a la ficha y a su anulación actual. Si un Pago
  reúne ambos, se anula ese Pago completo. Su motivo y reglas existentes siguen.
- Caja ABIERTA/RECHAZADA lleva a su detalle o editor existentes. La caja VALIDADA
  queda solo para consulta; no se habilita rechazarla ni corregir su reflejo.
- Liquidaciones pagadas no se tocan. Las no pagadas llevan a su edición/detalle
  existente y conservan las restricciones de estado, tipo, motivo y monto vigente.
  No se inventa un egreso antes de pagarlas ni se edita su pago como un directo.
- Mostrador sin validar y liquidaciones sin pagar se muestran separados de los
  movimientos confirmados y no se agregan a los totales de Cashflow.
- Cashflow, Inicio, Reportes e Historial utilizan los mismos signos y valores.
  Historial presenta egresos como salidas y contraasientos al revés de su original;
  los egresos operativos positivos y directos negativos se normalizan para sumar.
- API REST sigue deshabilitada. La aprobación visual de la propuesta no equivale
  a verificación de estas reglas ni a despliegue.

### Aceptación de T27

`CashflowCorreccionesT27Test` y `CashflowCorreccionesConcurrentesT27Test` cubren
pedidos directos, permisos, importes, historial, atomicidad, reenvío y dos conexiones.
La entrega final requiere suite propia, capturas reales con marco 360 y login,
aprobación de Carlos y verificación por otro agente. T27 no se autocierra.
