# Prueba por días (PRU-04) — avances hasta el día 6

## Avance posterior — Codex CAB, 24/10/2026

Carlos autorizó ejecutar [Día 5](DIA-05.md) y [Día 6](DIA-06.md) en «Prueba humana III», conservando diferencias y sin repararlas. Se recorrieron las 27 situaciones: Día 5, 12 Sí y 2 Parcial; Día 6, 10 Sí, 2 Parcial y 1 No. Cierre financiero 15/10: deuda $1.612.000, ingresos $607.000, egresos $73.700, resultado $533.300 y disponible $2.148.300; cajas #3–#5 validadas.

La clase extra del 17/10 no se pudo crear: el navegador exige fecha desde 24/10 aunque el reloj de Wings está en 15/10. Sin asistencia muestra 34 frente a 32 esperados. Motivo del anulado en PDF, sin verlo en Detalle de caja; precio de Lucas en noviembre y recepción del aviso de Zoe sin acreditar. Capturas, esperados y resultados completos en ambos informes. T14 sigue en curso y falta revisión independiente; no repetir operaciones.

El resto de este documento conserva el **corte de Claude después del Día 4**. Su recuento de 35 casos y el dashboard del 23/10 aún no incorporan las dos jornadas nuevas; no se inventa aquí una nueva cobertura por caso.

## Guion de noviembre, días 24 a 33 — Claude CAB, 29/10/2026

Dos tandas: [días 24 a 28](GUION-DIAS-24-28.md) y [días 29 a 33](GUION-DIAS-29-33.md). Simuladas enteras sobre
una copia de `wingstest`; los esperados son lo que dio Wings en esa corrida. Preparan la Revisión del 1/12
(Pilar Ramos, Alma Navarro, Facundo Ríos y Valentina Suárez) y el alta tardía queda para la tanda 3.

## Revisión de Claude del día 23 (1/11, generación de noviembre y liquidaciones) — 27/10/2026

Revisado contra el [guion](GUION-DIA-23.md), el [informe de Codex](DIA-23.md) (commits `e4fc0270` a `e8d31257`),
el código y la base del sitio con el reloj en el 1/11.

**La generación de noviembre es exactamente la calculada aparte.** Corrió sola a las 06:00:07, como en
producción: 100 cuotas, $3.783.000, cero sobrantes, faltantes o importes distintos, y la segunda corrida no
creó nada. Cobranza: 100 En plazo y 3 Al día (los que ya habían pagado noviembre). **Las ocho liquidaciones
dan los importes esperados**, $1.370.280 en total; las 13 cuotas atrasadas de Hernán y Martín están validadas
con su registro. Controles internos 21/21 sobre la base.

**Lo que no se completó fue por orden de Carlos:** solo 3 de las 8 quedaron Cerradas (Lucía octubre y
septiembre, Verónica octubre; $345.000). Las otras 5 siguen Abiertas ($1.025.280). Ninguna pagada. El «por
pagar» del Inicio y de Reportes es $345.000, igual a la suma de las cerradas (LIQ-018 c).

| Caso | Qué es | Qué hacer |
|---|---|---|
| Ingresos de noviembre $103.000 (guion: $0) | **Error de mi guion.** Son los tres noviembres cobrados por adelantado (Julieta $30.000, Agustín $35.000, Felipe $38.000): Wings los cuenta como ingreso del mes que pagan (T42). Ningún cobro con fecha de noviembre | Nada |
| «El aumento rige desde noviembre» | **Mal dicho en mi guion.** El precio de $44.000 rige desde el 18/10 (día 9); lo ya generado de octubre no cambió, y noviembre es la primera cuota nueva de esas alumnas. Lo que Codex anotó es correcto | Nada |
| 23.6 Lucía: 20 en la pantalla, 17 generadas | Defecto ya conocido | T87 |
| Cierres con la confirmación del navegador | **Límite de la herramienta**, el mismo que con Julieta: el diálogo nativo traba al Chrome automático y Carlos tuvo que aceptarlos a mano. Carlos pidió una ventana propia de Wings | Mejora futura MF10 (Carlos, 27/10) |
| Revisión vacía | **No probado, no es un defecto.** Los 103 activos tuvieron al menos un presente en octubre (contado en la base), así que nadie podía ir a Revisión. El camino de Revisión (CBZ-013 a CBZ-017) queda sin probar. **Error mío de planificación:** el guion de octubre tenía que dejar alumnos preparados para Revisión y no lo hizo; Carlos lo había pedido | Guion de noviembre (abajo) |
| Constancia de la generación | **T92 corregida y aprobada en segunda vuelta por Codex (27/10).** La segunda corrida a mano (23.3) pisó la constancia de la primera: el registro de noviembre dice ahora 0 creadas y 103 existentes, en vez de las 100 que creó la automática (`GenerarDeudasMensualesCommand`, `updateOrCreate`). Las cuotas están bien; se pierde el dato de qué hizo la corrida automática. Además, la columna de la hora se actualizaba sola a la hora real del servidor al tocar la fila | T92 cerrada por Codex sobre6a06cf09: Revisión1→1→2, originales8/44, resueltas3/24, área45/151 y rápida934/6 omitidas. [Verificación](VERIFICACION-T92-CODEX.md). La constancia del sitio sigue sin repararse |
| Cancelar una clase ya liquidada | **Defecto de Wings, hallado en la prueba técnica.** Con la liquidación cerrada, asistencias, horario, validación y profesoras quedan bloqueados, pero «Cancelar» no lo mira: la operativa la cancela desde la ficha de la clase y la liquidación queda con una clase cancelada adentro (LIQ-008 c, CLA-013). Ya estaba decidido en DU-30 y anotado en T62; no lo vi al crear T90 | T90 **reparada por Claude el 27/10**, a verificar |
| 23.12 resumen por Telegram | No ejecutado (opcional) | Nada |

**Revisión, a preparar en el guion de noviembre** (para la generación del 1/12; la regla: va a Revisión el activo sin
ningún presente en el mes anterior, salvo un alta de los últimos 15 días que ya pagó):
- Tres alumnos que no vienen en todo noviembre (ausentes en cada lista). El 1/12: los tres en Revisión, sin cuota de diciembre.
  Uno lo resuelve el dueño y se intenta resolver dos veces (CBZ-014, CBZ-015); otro lo resuelve la operativa desde el
  mostrador; el tercero vuelve a clase o paga estando en Revisión (CBZ-016).
- Un alta entre el 17 y el 30/11 que no viene: si pagó, recibe cuota; si no pagó, va a Revisión (CBZ-017).
- Mirar cómo lo ve cada uno: Inicio y menú del dueño, tarjeta de la operativa en su Inicio, Reportes de Alumnos, resumen
  diario por Telegram, y que la profesora no lo vea. Y qué estado muestra Cobranza para un alumno en Revisión sin cuota.

**Casos nuevos acreditados:** CBZ-011, CBZ-012, LIQ-001, LIQ-002 y LIQ-018. LIQ-008 queda parcial (3 de 8 y
el defecto T90). LIQ-005 se probó en el día 22 (no cierra antes de fin de mes) y hoy cerró después: completo.

**Prueba técnica del día 23:** funcionamiento interno 21/21, seguridad 22/22 (cuatro pruebas nuevas, una
retrata T90) y velocidad por debajo de 271 ms ([informe](prueba-tecnica/INFORME.md)).

## Revisión de Claude de los días 19 a 22 — 27/10/2026

Revisado contra el [guion](GUION-DIAS-19-22.md), los informes de Codex ([19](DIA-19.md), [20](DIA-20.md),
[21](DIA-21.md), [22](DIA-22.md), commit `74cb2d13`), el código y la base del sitio al 31/10.

**La plata cierra.** Deuda $0 y 103 activos Al día, como el guion. Hay dos diferencias, explicadas y sin
error de Wings:
- Disponible y efectivo **+$250.000** y gastos **−$250.000**: el sueldo de Sandra no se pudo pagar (22.5).
- Ingresos **−$121.000**: **error de mi guion**. Wings clasifica las ventas de Indumentaria (remeras $18.000 + $18.000
  y patines $85.000) como aporte de los dueños, no como ingreso del negocio (`ClasificacionSubrubros`). El
  disponible no cambia por eso.

Los controles internos sobre la base dan lo mismo que leyó Codex: disponible $4.959.900,75 y deuda $0.

| Caso | Qué es | Qué hacer |
|---|---|---|
| 22.5 sueldo de Sandra (CSH-015) | **Defecto de Wings.** Al dar de alta una operativa se crea su renglón de sueldo marcado como reservado del sistema; Cashflow no ofrece los reservados y lo rechaza aunque se fuerce el envío. Las liquidaciones son solo de profesores. **No hay forma de pagarle el sueldo a una operativa.** El contrato de Reportes dice que ese renglón se crea a mano: está desactualizado | T86 |
| 22.6 Lucía: 20 clases en vez de 17 | **Defecto de la pantalla.** «Nueva liquidación» cuenta las clases pendientes de todos los meses al lado del mes elegido. Lucía tiene 17 en octubre (el cálculo da 17 × $12.000 = $204.000) y 3 de septiembre validadas a mano que nunca se liquidaron. Codex hizo bien en frenar | T87; generar octubre y septiembre en el próximo paquete |
| 21.2 parcial sin motivo (COB-009) | **Defecto de Wings**, previsto al armar el guion: ni la pantalla ni el servidor piden motivo | T88 |
| Gasto mayor que el cajón (MOV-004) | **Defecto de Wings**, comprobado en la prueba técnica: se acepta y el esperado queda negativo | T88 |
| 21.1 deudor en la lista (ASI-013) | **Falta en Wings:** la lista no marca a quien no puede entrar por deuda | Decide Carlos si se programa |
| 20.8 y 21.7 Movimientos (MOV-011, MOV-012) | **Falta en Wings:** no muestra quién hizo cada cobro ni enlaza el recibo; el enlace a la caja de otra operativa lleva a «Sin permiso» | T89 |
| 22.4 historial de la corrección (CSH-007) | **Falta en Wings:** la página del historial existe (`cashflow/movimientos/{id}`), pero ninguna pantalla enlaza a ella | T89 |
| 19.6 clase con todos ausentes | **Defecto menor:** el Inicio de la operativa la rotula «Sin lista» porque solo mira los presentes (`operativo/dashboard`) | T89 |
| 19.6b sin hora por movimiento (CAJ-017) | Falta menor: el detalle de caja no muestra la hora | T89 |
| 21.5 Cashflow por año | Observación: la vista anual suma la transferencia en ingresos y egresos brutos; el resultado y el Inicio no cambian | Nada |
| 19.1 apertura con un cero de más (CAJ-007) y 19.3 cobrar de más (COB-010) | **No probados:** la herramienta no pudo escribir 100000 y el formulario de $50.000 no se envió | Repetir en el próximo paquete |
| 19.5 eliminar Librería | **Bien**, con la confirmación del navegador hecha por Carlos. Borrar un movimiento usa la misma confirmación nativa que el Chrome de Codex no acepta | Nada |
| 20.9 Rocío ve 3 cajas | **Error de mi guion:** olvidé su caja del 19/10, que está dentro de los 30 días. Wings hizo lo correcto | Nada |

**Prueba técnica de los días 19 a 22:** funcionamiento interno 16/16, seguridad 18/18 (con 5 pruebas que
retratan los hallazgos de arriba) y velocidad por debajo de 192 ms ([informe](prueba-tecnica/INFORME.md)).

## Revisión de Claude de los días 17 y 18 — 27/10/2026

Revisado contra el [guion](GUION-DIAS-17-18.md), los informes de Codex ([17](DIA-17.md), [18](DIA-18.md),
commit `760c42a3`), el código y la base del sitio. **La plata cierra al peso:** la única diferencia en
los dos cierres son los $143.000 y la alumna activa de Julieta, que el guion ya preveía. Los controles
internos sobre la base dan lo mismo que leyó Codex: deuda $234.000, disponible $3.483.700,75.

| Caso | Qué es | Qué hacer |
|---|---|---|
| 17.7 Julieta, por tercera vez sin hacer | **Límite de la herramienta**, no de Wings. «Reactivar» usa la confirmación nativa del navegador, y el Chrome automático no la puede aceptar | Decide Carlos: lo hace él a mano en el sitio, o se cambia esa confirmación por la ventana propia de Wings, como el resto de las pantallas. Mientras tanto, la diferencia sigue |
| 17.1 clave de Rocío y 17.5 usuario de Martín | **Bien.** Se hicieron por la vía técnica, a pedido de Carlos, y el ingreso real se comprobó. Defecto menor: si se saltea el formulario, la clave corta se rechaza **en inglés**. La pantalla lo frena antes, en castellano (`minlength`), pero el servidor no tiene ese texto traducido (`UsuarioWebController`: falta `password.min`) | Traducir el mensaje; va a la lista de reparaciones |
| 17.6 lista atrasada de la #76 | **Correcto: pasa a Sí.** No pidió motivo porque era la primera carga, y propuso «Clase adicional» para Lucas y Tomás, que ya habían venido esa semana | Nada |
| 18.4 / 18.5 lista guardada vacía por error | **Error de ejecución**, anotado por Codex. La corrección de Martín, el mismo día, no pidió motivo, que es lo correcto; la lista quedó con 14 presentes | Nada |

**Prueba técnica de los días 17 y 18:** funcionamiento interno 16/16 sobre la base del sitio, seguridad
12/12 y velocidad entre 2 y 211 ms por pantalla ([informe](prueba-tecnica/INFORME.md)).

## Revisión de Claude de los días 13 a 16 — 26/10/2026

Revisado contra el [guion](GUION-DIAS-13-16.md), los cuatro informes de Codex ([13](DIA-13.md),
[14](DIA-14.md), [15](DIA-15.md), [16](DIA-16.md)) y el código en `345a3997`. **Ningún defecto de Wings
nuevo.** La plata cierra al peso: todas las diferencias de los cuatro cierres son exactamente los
$143.000 y la alumna activa de Julieta (13.11), que no se hizo. Ingresos esperados el domingo
$1.967.850,75 − $143.000 = $1.824.850,75, el número leído. Lo mismo en MP y disponible. La lista #21
(18/11 en vez de 19/12) es Julieta, que sigue inactiva.

| Caso | Qué es | Qué hacer |
|---|---|---|
| 13.11 Julieta no se reactivó ni cobró | **Límite de la herramienta.** El navegador automático no pudo aceptar la confirmación nativa | Hacerlo a mano en el sitio (reactivar y cobrar $143.000 por MP); arrastra la diferencia hasta entonces |
| 13.8 no deja escribir otro contado al recerrar | **Correcto, doble protección.** La pantalla ni lo ofrece, y el servidor lo rechaza igual con el texto de CAJ-031 (`CajaService.php:375`, leído en el código; no ejercitado en el sitio) | Nada |
| 14.1 DNI «45A12» sin aviso | **No hay regla.** El alta pide DNI de hasta 20 caracteres, sin exigir números (`AlumnoWebController.php:378`); ningún contrato dice otra cosa. El guion daba por hecho una regla que no existe | Decide Carlos: ¿solo números, o se admiten letras (documentos extranjeros)? |
| 14.9 aviso al dueño por el cobro del 30/09 | **Correcto: pasa a Sí.** El aviso sale por Telegram, no en Inicio (`AvisoAdminService::fechaVieja`). Carlos lo recibió (captura, 26/10 02:01 hora real) con Zoe, fecha 30/09, $30.000, Pablo Ledesma y «la caja abierta de hoy» | Nada |
| 15.1 corregir la #49 no pidió motivo | **Error del guion (Claude).** La lista la tomó la rutina ese mismo día; Wings pide motivo solo si la clase ya pasó (`ClaseWebController.php:374`) | Nada |
| 15.6 cambio de horario de la #77 sin motivo | **Error del guion (Claude).** Clase futura (31/10); el motivo se pide solo para clases pasadas (`ClaseWebController.php:508`) | Nada |
| 15.2 Rocío no pudo entrar | **Error de ejecución.** El día 9 se le puso una clave al azar que no se guardó | Darle una clave conocida y repetir 15.2 |
| 15.3–15.5 Martín por hora | **Error del guion (Claude).** La forma de pago la define el deporte, y Fútbol liquida por comisión. Arrastra la #76 sin asistencia | Repetir con un porcentaje, o elegir un deporte por hora |
| 16.1 no se ve quién cerró | **Se guarda, pero no se ve.** `usuario_cierre_id` y `cerrada_por_admin` se graban (`CajaService.php:380`); ninguna pantalla los muestra | Mejora de presentación si Carlos la quiere |
| 16.5 deja generar octubre sin terminar | Anotado como pedía LIQ-019; no se generó | Decide Carlos si debe bloquearse |
| 16.9 Botiquín visto por la operativa | Permiso correcto en el catálogo; formulario de la operativa sin probar (el domingo es solo del dueño) | Comprobarlo en el próximo día con operativa |

**Pendiente de Carlos:** el DNI con letras (14.1) y la liquidación del mes en curso (16.5).

**Resumen diario por Telegram: el último llegó el 22/10.** Causa, leída en el código y sin comprobar en el
servidor: el resumen está programado para las 08:00 (`routes/console.php:20`), y el reloj de prueba arranca
cada día a las 15:00 y se frena a la medianoche (`RelojSimulado.php:88`). Las 08:00 nunca llegan.
En producción no pasa, porque ahí se ignora la fecha simulada. Además, si no hay nada pendiente, el resumen no se manda. **Para el próximo guion:** Julieta a mano, Rocío con clave conocida, Martín por comisión,
Botiquín visto por la operativa.

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

**Reparado el mismo día, verificado por otro agente y desplegado en el sitio de prueba el 25/10 (`b816d181`), junto con T77 y T78:** T80 pone la política de contenido en bloqueo
([informe](IMPLEMENTACION-T80.md)). T79 baja, con dos años de datos, Reportes de 22 s a 2,6 s y Cashflow
de 3 s a 0,1 s, con los mismos números ([informe](IMPLEMENTACION-T79.md)). Inicio, Sueldos, Reportes de
Alumnos y Cobranza siguen entre 1,1 y 2 s con dos años de datos y tres veces los alumnos del club.

## Corte histórico después del día 4

24/10/2026 · Claude CAB. Armado desde los relatos de cada día (`DIA-01.md` a `DIA-04.md`), las
verificaciones (`VERIFICACION-DIA-01.md`, `VERIFICACION-DIA-04.md`), el tablero y el sitio de prueba
(solo lectura). Complementa el tablero de control de Codex (`control-integral/`): este documento responde
qué se probó, qué dio, qué se hizo y qué falta.

## En una línea

Se hicieron **4 de los ~60 días** previstos (10 al 13/10 del calendario ficticio). Ejercitaron **35 de
los 331 casos** del catálogo. Todos los defectos que aparecieron están corregidos, salvo uno que
necesita una decisión tuya (abajo, «Lo que hay que decidir»). El sitio de prueba corre el mismo
código de `main` al 25/10 (commit `ec1b0c6e`, con T77 a T81 y el script de rutina; antes, `b816d181` y `56379295`).

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
