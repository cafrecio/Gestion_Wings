# Prueba por días (PRU-04) — avances hasta el día 6

## Avance posterior — Codex CAB, 24/10/2026

Carlos autorizó ejecutar [Día 5](DIA-05.md) y [Día 6](DIA-06.md) en «Prueba humana III», conservando diferencias y sin repararlas. Se recorrieron las 27 situaciones: Día 5, 12 Sí y 2 Parcial; Día 6, 10 Sí, 2 Parcial y 1 No. Cierre financiero 15/10: deuda $1.612.000, ingresos $607.000, egresos $73.700, resultado $533.300 y disponible $2.148.300; cajas #3–#5 validadas.

La clase extra del 17/10 no se pudo crear: el navegador exige fecha desde 24/10 aunque el reloj de Wings está en 15/10. Sin asistencia muestra 34 frente a 32 esperados. Motivo del anulado en PDF, sin verlo en Detalle de caja; precio de Lucas en noviembre y recepción del aviso de Zoe sin acreditar. Capturas, esperados y resultados completos en ambos informes. T14 sigue en curso y falta revisión independiente; no repetir operaciones.

El resto de este documento conserva el **corte de Claude después del Día 4**. Su recuento de 35 casos y el dashboard del 23/10 aún no incorporan las dos jornadas nuevas; no se inventa aquí una nueva cobertura por caso.

## Revisión de Claude de los días 5 y 6 — 25/10/2026

Las cifras de plata de los dos días coinciden al peso con lo esperado (deuda $1.612.000; resultado de
octubre $533.300; disponible $2.148.300). De las diferencias que anotó Codex:

| Qué | Qué es | Qué hacer |
|---|---|---|
| 6.11 no deja crear la clase del 17/10 | **Defecto.** `clases/create` usa `min="{{ date('Y-m-d') }}"`: fecha real de la computadora, no la de Wings. Mismo patrón en la fecha de nacimiento de `alumnos/_form` (`max`). En producción no se nota; en el sitio de prueba traba | Usar la fecha de Wings en los tres lugares |
| 5.11 el detalle de caja no muestra el motivo del cobro cancelado | **Defecto menor.** Lo tacha y dice «Cancelado»; el motivo solo está en el recibo anulado. CAJ-033 pide verlo | Mostrar el motivo junto a «Cancelado» |
| 6.13 Sin asistencia 34 y no 32 | **Error del guion (Claude).** Dos clases del martes 13 a la noche quedaron sin lista el día 4 y entraron al contador al pasar el día | Nada |
| 5.7 el plan de $38.000 de Lucas | Aplica desde noviembre; hoy no se puede ver | Comprobarlo el día que corresponda |
| 6.3 aviso al dueño por Zoe | No se pudo confirmar desde la prueba | Carlos mira si le llegó |

**Para reparar de los días 4 a 6:** la decisión de Carlos sobre octubre en Cobrar (63 alumnos), la
fecha de Wings en Clases y Alumnos, y el motivo de cancelación en el detalle de caja. De presentación:
la ficha del alumno nuevo sin fecha de vencimiento ni total (día 4).

**Prueba técnica (seguridad, performance, comportamiento interno) — hecha el 25/10:** [informe](prueba-tecnica/INFORME.md).
Comportamiento interno 16/16 sobre los datos reales; seguridad 7/7, con un hallazgo (la política de
contenido solo informa, no bloquea); performance bien con los datos de hoy, pero **con dos años de datos
Reportes tarda 22 s** y Cashflow, Inicio del dueño, Sueldos, Reportes de Alumnos y Cobranza entre 1,4 y 3 s.

## Corte histórico después del día 4

24/10/2026 · Claude CAB. Armado desde los relatos de cada día (`DIA-01.md` a `DIA-04.md`), las
verificaciones (`VERIFICACION-DIA-01.md`, `VERIFICACION-DIA-04.md`), el tablero y el sitio de prueba
(solo lectura). Complementa el tablero de control de Codex (`control-integral/`): este documento responde
qué se probó, qué dio, qué se hizo y qué falta.

## En una línea

Se hicieron **4 de los ~60 días** previstos (10 al 13/10 del calendario ficticio). Ejercitaron **35 de
los 331 casos** del catálogo. Todos los defectos que aparecieron están corregidos, salvo uno que
necesita una decisión tuya (abajo, «Lo que hay que decidir»). El sitio de prueba corre el mismo
código que hoy está en `main` (commit `56379295` del 21/10; después no hubo cambios de código).

## Qué hicimos cada día, qué dio y qué hicimos con eso

| Día | Qué se probó | Resultado | Qué se hizo con el resultado |
|---|---|---|---|
| **1 · sáb 10/10** | Dueño prepara el mostrador; Sandra abre caja con $10.000; 4 cobros (efectivo, Mercado Pago, inscripción + cuota); recibos; Mariela toma lista; cierre sin diferencia; dueño valida y revisa Inicio, Cobranza y Cashflow | Plata y caja bien ($164.000, diferencia $0). **Falló:** Inicio decía ingresos $0 y deuda distinta de Cobranza. Cobrar anunciaba 40 con deuda y listaba 100; «Total pendiente» sumaba meses adelantados | Corregido: T16 (clasificar Cuota e Inscripción), T17 y T19 (la deuda cuenta inscripciones en Inicio y Reportes), T29 (el buscador de Cobrar lista solo a quien debe). «La profesora ve clases ajenas»: no es defecto (suplencias) |
| **2 · dom 11/10** | Domingo sin actividad: Inicio, Cobranza (22 pasan de En plazo a Moroso), clases, Sandra no puede cobrar sin abrir, Reportes | Todo coincidió ($164.000 / $2.010.000). Sandra bloqueada ~10 min por «Demasiados intentos» | El bloqueo era el reloj simulado clavado: corregido (`2e059ff`). Caja ofrece abrir un domingo: queda así (decisión tuya: hay eventos y torneos) |
| **3 · lun 12/10 feriado** | Sandra cancela dos clases y el dueño una; profesoras; Sueldos | Plata intacta. **Falló:** la clase cancelada el mismo día desaparecía de Clases, también con el filtro | Corregido: T21. El límite de ingresos (5 por minuto) quedó como está; el aviso no dice cuánto esperar |
| **4 · mar 13/10** | 16 situaciones: apertura heredando cambio; alta de Mora (menor con tutor); 5 cobros (efectivo, MP, Galicia, parcial, adelanto de noviembre); venta de remera y gasto de limpieza; dos tomas de lista (una de una clase vieja); cierre $0; validación con tildes; Reportes, Inicio, Cobranza y Cashflow | 14 bien, 2 parciales. **Caja $0 de diferencia, todo concilia** ($1.926.500 disponible; resultado $311.500; adelanto fuera de octubre). Parciales: la ficha de Mora no muestra fecha de vencimiento ni total; el pago parcial se cargó a mano por cuota (no prueba reparto automático). Observación D4-OBS-01 (abajo) | Nada corregido todavía: los dos parciales son de presentación, sin error de importes. D4-OBS-01 espera decisión. El ingreso se trabó otra vez por el reloj simulado; se arregló en el sitio (sesión hasta cerrar el navegador) |

## Lo que hay que decidir antes del día 5

**Cobrar ofrece octubre a quien está Al día.** En el sitio de prueba, **63 de los 101 alumnos activos
no tienen guardada la cuota de octubre**: la primera carga los trajo «Al día» y no les creó octubre.
A todos ellos la pantalla de Cobrar les ofrece «Octubre $X» como si lo debieran, y arriba muestra en
rojo un «Total pendiente» por ese importe (`CajaWebController::cobrar` agrega la cuota en memoria, línea
798), aunque Cobranza y la ficha digan «Al día». Es la regla vigente
COB-028 («ofrece la cuota del mes aunque todavía no exista»), pensada para un alumno al que nunca se
le generó el mes. Pero con la primera carga el caso cambia de sentido: esos 63 ya pagaron octubre
afuera, y la operativa puede cobrárselo de nuevo. Lo vio Codex con Julieta Morales el día 4.

Opciones:
1. **Los cargados «Al día» tienen octubre pagado:** la primera carga (y una corrección para los 63)
   crea octubre como pagado. Cobrar deja de ofrecerlo y la historia queda completa.
2. **Cobrar no ofrece el mes en curso sin cuota guardada**, salvo que el dueño lo pida.
3. **Dejarlo como está** y que la operativa sepa que «Al día» manda sobre lo que ofrece Cobrar.


Mi recomendación: la 1. La primera carga dice «Al día» porque octubre está pagado; guardarlo así
es la verdad de los datos y no toca la regla para un alumno nuevo.

## Código que ya está en el sitio y la prueba nunca recorrió

Se programó y verificó con pruebas automáticas, pero ninguna jornada lo usó como lo usaría el club:
cambio de grupo y listas hacia adelante (T52), transferir de Efectivo a Banco (T61), Inicio de la
operativa con vencidos y en plazo (T74), adelantos y atrasadas en la liquidación (T42 y T43), fecha
de ingreso futura (T50), reactivar con cuota del mes (T38), cuota de un mes pasado (T58), el dueño
carga movimientos en Cashflow (T59), precios con vigencia (T44), profesora obligatoria en la clase
(T66), fichas de grupo y profesor con datos (T68), avisos en castellano (T47).

## Casos que la prueba no ejercitó

Ejercitados (35): ACC-001, ACC-003, ALU-002, ASI-001, ASI-005, ASI-009, CAJ-001, CAJ-004, CAJ-005,
CAJ-018, CAJ-025, CAJ-039, CBZ-001, CBZ-002, CBZ-018, CLA-001, CLA-011, CLA-014, CLA-018, COB-001,
COB-002, COB-003, COB-004, COB-005, COB-008, COB-012, COB-028, CSH-001, MOV-001, MOV-002, REC-001,
REP-001, REP-003, REP-006, REP-007. La primera carga del Excel (PCA-001) se hizo en T4, fuera de las
jornadas.

Ejercitado quiere decir que la jornada pasó por ese caso, no que lo agotó: la mayoría se probó en
su camino normal y no en sus variantes de error. Los **296** restantes, por módulo:

**Acceso y seguridad (ACC)** — 19 de 21 sin ejercitar:
ACC-002 Clave equivocada o mail que no existe · ACC-004 Intenta entrar una cuenta desactivada · ACC-005 Desactivan a un usuario mientras tiene la sesión abierta · ACC-006 Se vence la sesión con un formulario a medio llenar · ACC-007 Abrir una dirección interna sin haber ingresado · ACC-008 Salir y volver atrás con el navegador · ACC-009 La operativa fuerza direcciones que son solo del dueño · ACC-010 La profesora fuerza direcciones que no le corresponden · ACC-011 Forzar una acción del dueño sin pasar por la pantalla · ACC-012 La profesora intenta cancelar, reasignar o editar clases por pedido directo · ACC-013 Dirección que no existe o registro que no existe · ACC-014 Usar una dirección de acción como si fuera una pantalla · ACC-015 Escribir código o símbolos raros en los campos de texto · ACC-016 Cambiar a mano el número de la dirección para ver lo de otro · ACC-017 El mismo usuario en dos dispositivos y la opción Recordarme · ACC-018 Abrir el ingreso estando ya adentro (aplicación instalada en el teléfono) · ACC-019 Aparece un error interno del sistema · ACC-020 Cabeceras de seguridad y política de contenido · ACC-021 Consultas internas y direcciones viejas

**Alumnos (ALU)** — 25 de 26 sin ejercitar:
ALU-001 Alta de un adulto con ingreso de hoy · ALU-003 Alta con datos mal cargados · ALU-004 La misma persona dos veces en el mismo deporte · ALU-005 La misma persona se anota en un segundo deporte · ALU-006 Alta con fecha de ingreso de un mes ya cerrado · ALU-007 Alta con fecha de ingreso futura · ALU-008 Guardar dos veces la misma alta · ALU-009 Cambia el precio mientras el alta está abierta · ALU-010 Alta sin importe de inscripción configurado · ALU-011 Salir del formulario con datos sin guardar · ALU-012 Corregir nombre, teléfono o tutor de un alumno con historia · ALU-013 Cambiar a un alumno de grupo dentro del mismo deporte · ALU-014 Cambiar el plan de un alumno desde la ficha · ALU-015 Corregir la fecha de ingreso · ALU-016 Corregir el DNI de un alumno *(regla sin programar)* · ALU-017 Dar de baja a un alumno que debe · ALU-018 Reactivar a un alumno dado de baja · ALU-019 Forzar un plan o un grupo que no corresponde · ALU-020 Buscar y filtrar en el listado de alumnos · ALU-021 Qué ve y qué puede hacer cada rol en la ficha · ALU-022 Dos personas editan al mismo alumno a la vez *(regla sin programar)* · ALU-023 Alumno que quedó sin plan · ALU-024 Alumno cargado por error *(regla sin programar)* · ALU-025 Dar de baja con un toque desde el listado · ALU-026 Lo que muestra el listado de alumnos

**Asistencia (ASI)** — 13 de 16 sin ejercitar:
ASI-002 Suplencia sin cambiar la profesora asignada · ASI-003 No vino nadie *(regla sin programar)* · ASI-004 Corregir una asistencia ya guardada · ASI-006 Asistencia de una clase ya liquidada · ASI-007 Alumno que viene más veces de las que paga · ASI-008 El mismo alumno presente en dos clases a la misma hora · ASI-010 Forzar una asistencia adulterada · ASI-011 Tomar asistencia de una clase que todavía no ocurrió *(regla sin programar)* · ASI-012 Dos personas guardan la misma lista · ASI-013 El deudor que viene a clase *(regla sin programar)* · ASI-014 Tomar asistencia desde el teléfono · ASI-015 Guardar la lista sin conexión · ASI-016 Qué ve la profesora de cada alumno en la lista

**Caja (CAJ)** — 36 de 42 sin ejercitar:
CAJ-002 Querer cambiar el medio del cajón después de elegido *(regla sin programar)* · CAJ-003 Abrir caja sin mostrador configurado · CAJ-006 Lo recibido no coincide con lo que dejó el turno anterior · CAJ-007 Un cero de más en la apertura · CAJ-008 Apertura con el importe mal escrito o sin confirmar · CAJ-009 Abrir con otro turno todavía abierto · CAJ-010 Dos aperturas al mismo tiempo · CAJ-011 Abrir con una pantalla vieja · CAJ-012 El dueño abre la caja a nombre de una operativa · CAJ-013 Rectificar lo declarado en la apertura · CAJ-014 Rectificar cuando no corresponde · CAJ-015 Turno abierto de un día anterior · CAJ-016 El dueño cierra el turno que una operativa dejó abierto · CAJ-017 Qué muestra la caja del día · CAJ-019 Cierre con faltante · CAJ-020 Cierre con sobrante · CAJ-021 Cierre con datos imposibles · CAJ-022 Dejar más cambio del que se recibió · CAJ-023 Entra un cobro mientras se cierra · CAJ-024 Cerrar dos veces · CAJ-026 Validar o rechazar una caja que sigue abierta · CAJ-027 Validar dos veces · CAJ-028 Una operativa intenta validar o rechazar · CAJ-029 Rechazar una caja *(regla sin programar)* · CAJ-030 Corregir una caja rechazada y volver a cerrarla · CAJ-031 Cambiar lo contado al volver a cerrar una rechazada · CAJ-032 Varias cajas pendientes validadas fuera de orden · CAJ-033 Detalle de una caja cerrada con diferencia · CAJ-034 Abrir la caja de otra operativa · CAJ-035 Listado de cajas según quién mira · CAJ-036 Lo que no es efectivo no entra al arqueo · CAJ-037 Abrir y cerrar sin ningún movimiento · CAJ-038 Dos turnos en el mismo día · CAJ-040 Los tildes de verificación al validar una caja *(regla sin programar)* · CAJ-041 El dueño opera sobre la caja abierta de una operativa · CAJ-042 Cajas anteriores al sistema de conteo

**Catálogos (CAT)** — 10 de 10 sin ejercitar:
CAT-001 Deportes: alta y edición · CAT-002 Cambiar cómo liquida un deporte que ya tiene liquidaciones *(regla sin programar)* · CAT-003 Desactivar un deporte o borrar un nivel en uso *(regla sin programar)* · CAT-004 Niveles: alta, edición y nombre repetido · CAT-005 Medios de pago: alta y saldo inicial · CAT-006 Desactivar un medio de pago *(regla sin programar)* · CAT-007 Crear un subrubro y dejarlo listo para usar · CAT-008 Cambiar un subrubro que ya tiene movimientos · CAT-009 Rubros y subrubros reservados · CAT-010 Catálogos y configuración para quien no es dueño

**Cobranza (CBZ)** — 19 de 22 sin ejercitar:
CBZ-003 Cambia el mes: de Moroso a Deudor · CBZ-004 Paga el mes nuevo y sigue debiendo el viejo · CBZ-005 El que debe solo la inscripción también es moroso o deudor · CBZ-006 La misma persona debe en dos deportes · CBZ-007 Condonar el saldo de una cuota · CBZ-008 Una operativa intenta condonar · CBZ-009 Condonaciones que no corresponden · CBZ-010 Perdonar la inscripción · CBZ-011 El día 1 se generan las cuotas del mes · CBZ-012 La generación mensual corre dos veces · CBZ-013 Sin asistencias el mes pasado: va a Revisión · CBZ-014 Resolver una revisión · CBZ-015 Resolver dos veces la misma revisión · CBZ-016 El alumno en Revisión vuelve a clase o paga · CBZ-017 Ingreso reciente sin asistencias todavía · CBZ-019 Cobranza como lista para llamar · CBZ-020 El proceso del día 1 no corrió · CBZ-021 Revisión: filtros e historia · CBZ-022 Vencidos y en plazo en el Inicio de la operativa

**Configuración (CFG)** — 6 de 6 sin ejercitar:
CFG-001 Cambiar los días de gracia · CFG-002 Valores imposibles en Configuración · CFG-003 Cambiar el importe de la inscripción · CFG-004 Reglas del primer pago según el día de ingreso · CFG-005 Avisos por mail y por Telegram · CFG-006 La pantalla de Configuración

**Clases (CLA)** — 21 de 25 sin ejercitar:
CLA-002 Crear una clase única · CLA-003 Crear una serie con horarios distintos por día · CLA-004 Clase que ocupa dos bloques de cancha · CLA-005 Clase con datos imposibles · CLA-006 Serie con un choque de horario · CLA-007 Profesora de otro deporte o inactiva · CLA-008 Lo que la operativa puede y no puede con las clases · CLA-009 Cambiar la profesora de una clase de hoy · CLA-010 Cambiar la profesora de una clase pasada · CLA-012 Cancelar una clase que ya tiene la lista tomada *(regla sin programar)* · CLA-013 Cancelar una clase que ya está en una liquidación *(regla sin programar)* · CLA-015 Volver a activar una clase cancelada *(regla sin programar)* · CLA-016 Editar el horario de una clase · CLA-017 Validar a mano una clase sin asistencia · CLA-019 Crear dos veces la misma clase o serie · CLA-020 Clase con dos profesoras · CLA-021 Clase o serie creada por error *(regla sin programar)* · CLA-022 El estado de una clase a lo largo del día · CLA-023 Clase sin profesora asignada *(regla sin programar)* · CLA-024 Validar una clase por error · CLA-025 La operativa y la cancelación de series

**Cobrar (COB)** — 26 de 34 sin ejercitar:
COB-006 Cobro de dos meses adeudados juntos · COB-007 Pagar el mes nuevo dejando impago el viejo · COB-009 El pago parcial tiene que pedir motivo *(regla sin programar)* · COB-010 Cobrar de más *(regla sin programar)* · COB-011 Importes mal escritos · COB-013 Adelantado fuera de lo permitido · COB-014 Cobrar dos veces lo mismo · COB-015 Viene a pagar un alumno dado de baja *(regla sin programar)* · COB-016 El dueño cobra una cuota sin caja · COB-017 Cancelar un cobro del turno · COB-018 Cancelar un cobro de una caja cerrada o validada · COB-019 Cancelar un cobro adelantado · COB-020 Cancelar un cobro que incluía la inscripción · COB-021 El dueño anula un cobro propio hecho sin caja · COB-022 Cobro con fecha de pago de un mes anterior · COB-023 Cobrar una cuota que ya no se puede cobrar · COB-024 Cambiar el plan en el momento de cobrar · COB-025 Descuento del mes de ingreso · COB-026 Forzar un cobro adulterado · COB-027 La operativa y el dueño cobran la misma cuota a la vez · COB-029 Cobrar un mes pasado que nunca se generó *(regla sin programar)* · COB-030 Muchos cobros seguidos en un turno · COB-031 Cobrar solo la inscripción, o plata que no la alcanza · COB-032 Doble clic en un pago parcial · COB-033 Avisos y confirmaciones dentro del cobro · COB-034 Cómo se comporta la pantalla de cobro

**Cashflow (CSH)** — 18 de 19 sin ejercitar:
CSH-002 El dueño carga un gasto directo · CSH-003 Importes con centavos · CSH-004 Aporte y retiro del dueño · CSH-005 Fechas del movimiento directo · CSH-006 Movimiento directo mal cargado · CSH-007 Corregir un movimiento directo · CSH-008 Eliminar un movimiento directo · CSH-009 Lo que no se corrige desde Cashflow · CSH-010 Corregir dos veces o con la pantalla vieja · CSH-011 Un dueño corrige el movimiento de otro dueño · CSH-012 Entrar a Cashflow sin ser dueño · CSH-013 Fin de mes: saldos contra la cuenta a mano · CSH-014 Quién pagó, quién cobró y quién validó · CSH-015 Pagar el sueldo de una operativa · CSH-016 Gasto mayor que el saldo del medio *(regla sin programar)* · CSH-017 Corregir llevando el movimiento a otra fecha · CSH-018 Llevar el efectivo del cajón al banco · CSH-019 Períodos de Cashflow

**Grupos y planes (GRU)** — 7 de 7 sin ejercitar:
GRU-001 Crear un grupo con sus planes · GRU-002 Grupo mal cargado · GRU-003 Aumentar el precio de un plan · GRU-004 Quitar un plan en uso o el único plan · GRU-005 Editar un grupo con la pantalla vieja · GRU-006 Desactivar un grupo con alumnos y clases *(regla sin programar)* · GRU-007 Grupos para quien no es dueño

**Liquidaciones (LIQ)** — 20 de 20 sin ejercitar:
LIQ-001 Liquidación de una profesora que cobra por hora · LIQ-002 Liquidación de un profesor que cobra por comisión · LIQ-003 Liquidar con clases del mes todavía sin asistencia · LIQ-004 Generar dos veces la liquidación del mismo mes · LIQ-005 Liquidar un mes que todavía no terminó *(regla sin programar)* · LIQ-006 Liquidación que no se puede generar · LIQ-007 Recalcular una liquidación abierta · LIQ-008 Cerrar una liquidación · LIQ-009 Pagar una liquidación · LIQ-010 Pagos que no corresponden · LIQ-011 Pagar dos veces · LIQ-012 Cancelar una liquidación cerrada sin pagar · LIQ-013 Tocar una liquidación pagada · LIQ-014 Eliminar una liquidación abierta · LIQ-015 Ajustar el importe final de una comisión · LIQ-016 Entrar a Liquidaciones sin ser dueño · LIQ-017 Cambia la tarifa después de liquidar · LIQ-018 Listado de liquidaciones · LIQ-019 La pantalla de Nueva liquidación · LIQ-020 Lo que muestra la liquidación al pagar

**Movimientos de caja (MOV)** — 16 de 18 sin ejercitar:
MOV-003 Rubros que puede usar la operativa · MOV-004 Gasto en efectivo mayor que la plata del cajón *(regla sin programar)* · MOV-005 Movimiento con datos mal cargados · MOV-006 El dueño quiere cargar un movimiento de caja · MOV-007 Corregir un movimiento manual del turno · MOV-008 Eliminar un movimiento manual del turno *(regla sin programar)* · MOV-009 Modificar movimientos según el estado de la caja · MOV-010 Agregar a una caja rechazada un gasto que se había olvidado · MOV-011 Los cobros de cuota los ven todas las operativas · MOV-012 Historial de la operativa · MOV-013 Historial del dueño: sin límites · MOV-014 El Historial dice lo mismo que Caja y Cashflow · MOV-015 Guardar dos veces el mismo movimiento · MOV-016 Tocar movimientos de una caja ajena · MOV-017 Editar o eliminar un cobro de cuota como si fuera un movimiento manual · MOV-018 El enlace a la caja desde Movimientos

**Primera carga (PCA)** — 8 de 8 sin ejercitar:
PCA-001 Primera carga completa desde el Excel · PCA-002 Excel con errores · PCA-003 Archivo que no corresponde · PCA-004 Cambia algo entre la revisión y la carga · PCA-005 Cargar dos veces · PCA-006 Deshacer la primera carga · PCA-007 Alumno cargado con inscripción pendiente y sin cuotas · PCA-008 La primera carga es solo del dueño

**Reglas definidas y todavía no programadas (PEN)** — 3 de 3 sin ejercitar:
PEN-001 Recargo por mora · PEN-002 Clases particulares *(regla sin programar)* · PEN-003 Justificar una excepción de acceso a clase *(regla sin programar)*

**Profesores (PRO)** — 5 de 5 sin ejercitar:
PRO-001 Alta de una profesora · PRO-002 Profesora mal cargada · PRO-003 Cambiar la tarifa o el nombre de una profesora · PRO-004 Desactivar a una profesora con clases futuras *(regla sin programar)* · PRO-005 Ficha de una profesora

**Recibos (REC)** — 5 de 6 sin ejercitar:
REC-002 Recibo con inscripción, varios meses o pago parcial · REC-003 Recibo de un cobro cancelado o anulado · REC-004 Quién puede abrir un recibo de cuota · REC-005 Recibo de una liquidación pagada · REC-006 Abrir muchas veces el mismo recibo

**Reportes e Inicio (REP)** — 13 de 17 sin ejercitar:
REP-002 Inicio de la operativa · REP-004 Reporte con cajas todavía sin validar · REP-005 El mes cerrado no cambia · REP-008 Entrar a Reportes o al Inicio del dueño sin serlo · REP-009 Movimiento en un subrubro sin clasificar · REP-010 Cuadre diario de plata · REP-011 Finanzas filtrado por deporte · REP-012 Comparación con el mes anterior y evolución · REP-013 Una caja de octubre validada en noviembre · REP-014 Alumnos que dejaron de venir *(regla sin programar)* · REP-015 Cómo le va a cada grupo · REP-016 El Inicio de la operativa a lo largo del día · REP-017 Los avisos del Inicio del dueño llevan a su lista

**Transversales (SIS)** — 14 de 14 sin ejercitar:
SIS-001 Los importes se escriben igual en todas las pantallas · SIS-002 Los importes se muestran igual en todas las pantallas · SIS-003 Un día entero desde el teléfono · SIS-004 Fechas y horas en hora de Argentina · SIS-005 Doble clic y botón atrás en todos los formularios · SIS-006 Textos, idioma y mensajes · SIS-007 El sistema con mucho volumen · SIS-008 Dos pestañas, dos equipos, dos personas · SIS-009 El reloj simulado del sitio de prueba · SIS-010 Lo que no tiene vuelta atrás *(regla sin programar)* · SIS-011 Respaldo y vuelta atrás de los datos · SIS-012 Fichas sin «Próximamente»: datos reales · SIS-013 El menú de cada rol · SIS-014 Los interruptores Activo de los listados

**Usuarios (USU)** — 12 de 12 sin ejercitar:
USU-001 Alta de una operativa nueva · USU-002 Alta de usuario con datos mal cargados · USU-003 Alta de un usuario para una profesora · USU-004 Cambiar el nombre de un usuario que ya trabajó *(regla sin programar)* · USU-005 Cambiarle la clave a otro usuario · USU-006 El dueño intenta cambiarse el rol o desactivarse · USU-007 Desactivar a una operativa que tiene la caja abierta *(regla sin programar)* · USU-008 Cambiarle el rol a alguien que ya trabajó *(regla sin programar)* · USU-009 La cuenta de dueño protegida · USU-010 Reactivar un usuario desactivado · USU-011 Crear dos veces el mismo usuario o guardar dos veces · USU-012 Cambiar la propia clave u olvidarla

## Propuesta de día 5 — miércoles 14/10 (para que la apruebes)

Sigue con los mismos datos: no repite cobros del día 4. Antes de ejecutarlo, cada alumno nombrado se
comprueba contra la base (la lección del día 4: Sofía no estaba «al día» como decía el cuento).

| # | Situación | Casos | Qué agrega |
|---|---|---|---|
| 1 | Sandra abre heredando los $10.000 | CAJ-005 | Rutina |
| 2 | Sandra mira su Inicio: vencidos y en plazo, contra Cobranza y Cobrar | CBZ-022, REP-002 | T74, nunca recorrido |
| 3 | La mamá de Mora paga inscripción y octubre | COB-005, COB-025, REC-002 | Primer cobro de alumna nueva, recibo con inscripción |
| 4 | Sofía Gómez paga solo la inscripción que debe | COB-031, CBZ-005 | Depende de la decisión de arriba: qué le ofrece Cobrar |
| 5 | Un alumno que debe dos meses los paga juntos | COB-006 | Varios meses en un cobro |
| 6 | Sandra se equivoca de alumno, cancela el cobro y lo hace bien | COB-017, REC-003 | Cancelación en el turno |
| 7 | Un alumno pasa de Principiantes a Intermedias | ALU-013, ASI-009 | T52: aparece en el grupo nuevo desde hoy, sigue en las listas viejas donde tenía marca |
| 8 | Se corrige el teléfono de un tutor | ALU-012 | Edición con historia |
| 9 | Lucía toma lista en Patín Principiantes con Mora presente | ASI-001 | Primera clase de Mora |
| 10 | Hernán cubre una clase de Lucía sin cambiar la asignación | ASI-002 | Suplencia |
| 11 | Sandra cierra con $500 de menos | CAJ-019, CAJ-033 | Faltante: se registra y se sigue (tu regla del 22/10) |
| 12 | El dueño valida con la diferencia a la vista | CAJ-025 | Validación con faltante |
| 13 | El dueño lleva al banco lo retirado del turno anterior | CSH-018 | T61, nunca recorrido |
| 14 | El dueño paga la luz desde Cashflow | CSH-002 | Gasto directo |
| 15 | Cierre: Inicio, Cobranza, Cashflow y Reportes dicen lo mismo | CBZ-018, REP-001 | Cuadre del día |

Lo hace Codex por pantalla, como prueba humana.

## Prueba interna (Claude) con los mismos casos del día

Mientras Codex recorre por pantalla, yo pruebo por dentro las mismas operaciones:

- **Seguridad:** cada operación del día forzada por fuera de la pantalla, por cada rol (operativa en
  rutas del dueño, profesora en plata, número de otro registro en la dirección, envío sin token,
  texto con código en los campos, doble envío). Casos ACC-009, ACC-010, ACC-011, ACC-015, ACC-016, COB-014.
- **Performance:** consultas y tiempo de cada pantalla que usa el día, con los 101 alumnos reales del
  sitio y con volumen aumentado (dos años de datos), para ver cuáles crecen mal (SIS-007).
- **Comportamiento interno:** después de cada operación, lo que quedó en la base: caja = suma de sus
  movimientos, reflejo en Cashflow una sola vez, estados de cobranza, historial de reportes,
  auditorías, nada huérfano ni duplicado.

Las pruebas que escriben van en una copia local de la base del sitio de prueba, nunca en el sitio:
así no se ensucia la historia que sigue Codex. En el sitio solo leo.
