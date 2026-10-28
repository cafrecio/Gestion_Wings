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
| Medio físico | ADMIN configura el medio correspondiente al efectivo del cajón; Transferencia no integra ese conteo. **El medio de pago asignado al cajón no se puede desactivar** (DU-16, T57). **El dueño puede cambiar el medio de pago del cajón, pero SOLO cuando no hay ninguna caja ABIERTA**; si hay caja abierta debe cerrarse antes de cambiarlo (DU-59, T57). Cada cambio registra autor y fecha (`configurado_por_id`, `configurado_at`). |
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
- ADMIN selecciona el TipoCaja activo que representa billetes del mostrador. Se guarda su
  ID y autor/fecha (`configurado_por_id`, `configurado_at`); cambiar su nombre no cambia el medio. No se deduce por nombre.
- El medio configurado para el mostrador **no se puede desactivar** (DU-16, T57).
- El dueño puede cambiar el medio del cajón en cualquier momento, **siempre y cuando no haya ninguna caja ABIERTA** (DU-59, T57). Si hay un turno abierto, el cambio queda bloqueado en interfaz y servidor hasta su cierre.
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

## Revisión de movimientos antes de validar — T36

Decisión de Carlos (DU-54), 17/10/2026: «Exigir todos tildados para validar».
Solo ADMIN activo puede tildar y destildar, sobre una caja CERRADA con conteo.
La revisión queda guardada por movimiento, con fecha, autor y huella del contenido.
Validar exige revisar todos los renglones de Detalle, incluidos los cancelados;
estos últimos siguen sin sumarse en Cashflow. Una caja vacía conserva su validación.
El control está en el servicio: también alcanza a Resumen y a las llamadas directas.
Si faltan revisiones, informa cuántas en castellano; desde Resumen lleva a Detalle.
No modifica el cierre físico ni genera asientos hasta completar la revisión.

Sobre un rechazo, Carlos decidió: «Destildar solo los movimientos que cambien».
Rechazar conserva las marcas vigentes. Editar o cancelar invalida la revisión de los
movimientos afectados; los demás conservan su tilde. Una pantalla vieja no puede
marcar como revisado un contenido que cambió después de abrirla.
Las cajas históricas ya VALIDADA mantienen la validación idempotente existente.
Implementación local; revisión independiente de Claude y aspecto con Carlos, sin despliegue.

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
- T39 (DU-21, Carlos 13/10 y 18/10): la anulación de un cobro del dueño lleva la fecha
  del cobro original, como el contraasiento de un directo. Plata, cuota, inscripción y
  recargo vuelven en ese mismo mes: queda como si el cobro no hubiera existido. El mes en
  que se anula no cambia lo cobrado.
- Caja ABIERTA/RECHAZADA lleva a su detalle o editor existentes. La caja VALIDADA
  queda solo para consulta; no se habilita rechazarla ni corregir su reflejo.
- Liquidaciones pagadas no se tocan. Las no pagadas llevan a su edición/detalle
  existente y conservan las restricciones de estado, tipo, motivo y monto vigente.
  No se inventa un egreso antes de pagarlas ni se edita su pago como un directo.
- Mostrador sin validar y liquidaciones sin pagar se muestran separados de los
  movimientos confirmados y no se agregan a los totales de Cashflow.
- Cashflow, Inicio, Reportes e Historial utilizan los mismos signos y valores financieros. T42 imputa el ingreso económico de cuotas adelantadas al mes pagado, sin mover su dinero de fecha ni alterar saldos; ver [Reportes V1, enmienda20/10](Wings-Contrato-Reportes-V1.md).
  Historial presenta egresos como salidas y contraasientos al revés de su original;
  los egresos operativos positivos y directos negativos se normalizan para sumar.
- API REST sigue deshabilitada. La aprobación visual de la propuesta no equivale
  a verificación de estas reglas ni a despliegue.

## Traspasos entre medios propios — T61

Aclaración de Carlos, 20/10/2026: formulario único con cuenta de salida, cuenta de destino e importe. Esta regla reemplaza la entrega anterior de dos movimientos independientes. Corrección local implementada; visual corregido aprobado por Carlos el 20/10/2026 («Apruebo el visual corregido»). Lógica pendiente de Claude; sin despliegue.

- Solo ADMIN registra transferencias. El formulario pide cuenta de salida, destino, importe positivo, fecha y observación opcional. Las cuentas deben ser distintas y activas.
- Se generan dos movimientos vinculados por una cabecera, por igual importe y fecha: salida negativa y entrada positiva. Se guardan ambos en una transacción o ninguno; reenviar la misma operación no la duplica.
- Rubro **Transferencias internas — salidas**, subrubro **Salida a otro medio**, de EGRESO. Rubro **Transferencias internas — entradas**, subrubro **Entrada desde otro medio**, de INGRESO. Ambos son exclusivos del sistema, activos, ADMIN, afectan caja y clasificación `TRASPASO`. No admiten edición, renombre ni baja manual; el sistema los asigna sin pedirlos en el formulario.
- El movimiento directo común no acepta un traspaso suelto. La corrección individual de los movimientos vinculados se bloquea para no romper su igualdad. Un circuito propio de corrección/anulación de transferencias no forma parte de esta corrección.
- Cada cuenta cambia por el importe transferido. Ejemplo: Efectivo −$300.000 y Banco +$300.000. Ingresos/gastos del club, aportes/retiros, resultado y disponible global no cambian. Ambos movimientos sí cuentan en los totales de entradas/salidas de Cashflow.
- Cashflow conserva los saldos actuales compartidos con Reportes, independientes de los filtros del listado, con pendientes sin validar explícitos.
- Las fotos históricas de clasificación se conservan. No se enlazan ni reescriben las antiguas cargas independientes; una salida histórica en septiembre y entrada en octubre siguen afectando sus respectivos globales mensuales. Las nuevas transferencias tienen una sola fecha para ambas patas.
- Catálogo idempotente sin duplicar ni pisar clasificaciones anteriores. Una incompatibilidad de catálogo impide registrar la transferencia; no se la corrige por conjetura.
- [Implementación vigente y texto para T13](../06-pruebas/PRU-04/IMPLEMENTACION-T61.md). [Entrega anterior conservada como antecedente](../06-pruebas/PRU-04/IMPLEMENTACION-T61-ANTECEDENTE-DOS-CARGAS.md).

### Exclusividad de TRASPASO — segunda vuelta T61, 21/10/2026

`TRASPASO` es exclusivo de las transferencias del sistema y sus dos subrubros reservados «Salida a otro medio» y «Entrada desde otro medio». No se ofrece ni se acepta al crear o editar subrubros comunes. Tampoco puede elegirse uno antiguo con esa clasificación al registrar/editar movimientos de caja, cargar/corregir Cashflow o por otra puerta que genere movimientos fuera de Transferir.

La defensa también alcanza métodos internos, reflejos de caja, pagos y sus contraasientos: si el concepto o la foto que se trasladaría es TRASPASO, se rechaza sin crear una pata suelta. No se reclasifican ni enlazan registros históricos automáticamente; Carlos decide su tratamiento. Un valor antiguo fuera de las opciones exige una elección explícita en el formulario, no recibe NEGOCIO por defecto. La transferencia ya aprobada conserva sus reglas y su implementación.

Segunda vuelta entregada por Codex; verificación independiente de Claude y revisión visual del contenido pendientes. [Entrega y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-T61.md#segunda-vuelta--h1-a-h3). Sin despliegue.
### Aceptación de T27

`CashflowCorreccionesT27Test` y `CashflowCorreccionesConcurrentesT27Test` cubren
pedidos directos, permisos, importes, historial, atomicidad, reenvío y dos conexiones.
La entrega final requiere suite propia, capturas reales con marco 360 y login,
aprobación de Carlos y verificación por otro agente. T27 no se autocierra.

### Correcciones manuales de caja — T51 / DU-07, 28/10/2026

- Editar conserva una foto anterior y posterior; eliminar conserva la foto anterior aunque el movimiento desaparezca. Cada registro guarda ID del movimiento, caja, autor y fecha/hora. Historia visible en Detalle para ADMIN y dentro del dominio OPERATIVO; la operativa puede consultar la de su caja.
- La modificación y su historia se guardan en una sola transacción, bloqueando primero caja y después movimiento. Si falla la historia, tampoco se modifica ni elimina el movimiento.
- Solo movimientos manuales activos de cajas ABIERTAS o RECHAZADAS. Escribe ADMIN o la operativa titular; PROFESOR no accede. Cobros vinculados a alumno/pago y conceptos reservados no admiten Editar/Eliminar, tampoco por rutas forzadas. La cancelación con motivo conserva su circuito.
- Arqueo y gasto pendiente mensual usan el movimiento vigente. La historia es informativa: no genera asientos ni duplica importes. Una caja rechazada conserva lo contado/entregado según A25.
- Implementada localmente; entrega a Claude y revisión visual de Carlos pendientes. [Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T51.md) · [Capturas](../06-pruebas/PRU-04/APROBAR-T51.html).
