# T38 — Reactivar a un alumno le genera la cuota del mes

17/10/2026 · Claude CAB.

**Decisiones de Carlos:**
- DU-12, 13/10: «Generarla al reactivar, con el porcentaje de reingreso del día. Siempre que
  no tenga ya generada la cuota del mes. La deuda que pudiese tener se mantiene.»
- 17/10, al empezar la tarea: la cuota del reingreso tiene **el mismo plazo que una cuota de
  alta**.

## Qué pasaba

- **Reactivar** (botón de la ficha o interruptor del listado) solo cambiaba `activo`. El alumno
  figuraba Al día y no aparecía en Cobranza hasta que alguien le cobraba.
- **El porcentaje de reingreso** se aplicaba únicamente si se le cobraba estando inactivo. Si
  primero se lo reactivaba y después se le cobraba, pagaba el 100 %.

## Qué cambió

| Dónde | Cambio |
|---|---|
| `PagoCuotaService::crearCuotaReingreso` | Crea la cuota del mes en curso: precio del plan × porcentaje de la regla de primer pago del día (100 % si hay cero o más de una regla). Guarda `porcentaje_alta` y `fecha_reingreso`. No hace nada si ya existe la cuota del mes o si el alumno todavía no empezó (ingreso en un mes que viene). Sin plan para el mes, avisa. |
| `AlumnoWebController::toggleActivo` | Reactivar ocurre en una sola transacción, con el alumno bloqueado: activa, crea la cuota y da por respondida la revisión pendiente del mes (`reactivarAuto`, como un pago). Si falla, no se reactiva y avisa, por pantalla o en JSON (422, `{error}`; el interruptor vuelve a su lugar con el cartel). El mensaje dice qué cuota se generó, el importe y el porcentaje de reingreso si no es 100. |
| `PagoCuotaService::completarReingresoPorCobro` | Cobrarle a un inactivo ya lo reactivaba. Ahora, si ese cobro no incluyó la cuota del mes, se genera como en el punto anterior. Si la creó el mismo cobro, se le graba el día de reingreso y su porcentaje. Sin plan, el cobro de la deuda vieja se hace igual. |
| `PagoCuotaService::calcularReglaPrimerPago` | Ajuste del caso 1, el de quien nunca pagó: lee `porcentaje_alta` solo de la cuota del alta. Si el mes del alta ya tiene una cuota de reingreso, no la recalcula. Sin esto, alguien dado de alta el 17 y reactivado el 24 pagaba el porcentaje del 17. |
| `deuda_cuotas.fecha_reingreso` | Migración nueva. Va solo en la cuota generada al reactivar; NULL en todas las demás. |
| `CobranzaEstadoService` | El plazo de cada cuota sale de su `fecha_reingreso`, o del mes de alta como antes: el más largo entre `dias_plazo_alta` y la gracia del mes. Busqué `MOROSO`, `DEUDOR` y `EN_PLAZO` en `app/`: no hay otro cálculo de estados fuera de este servicio. Que cada pantalla lo use lo infiero de eso; no lo recorrí pantalla por pantalla. |
| `CobranzaEstadoService::limiteDeLaCuota` y `PunitorioService` | El recargo por mora espera el mismo plazo (D2 del contrato de mora, ampliado). |

**Contratos:** enmienda del 17/10 en el de estados (§3) y en el de mora (D2).
**Tabla de pruebas:** ALU-018 pasa a vigente, con una línea nueva para el plazo; COB-028 ya
no incluye al reactivado. En la misma sincronización pasaron a la tabla viva PEN-001 (B11) y
CAJ-029 (T37), que estaban solo en la copia.

## Por qué en la cuota y no en el alumno

Si la fecha de reingreso estuviera en el alumno, una cuota que ya existía al reactivar (por
ejemplo, la del alta del mismo mes) ganaría un plazo que nadie decidió. En la cuota, el plazo
corre solo para la que se generó ese día.

`alumnos.fecha_alta` no se toca: la inscripción sigue contando desde el alta, como pide
`CobranzaInscripcionEstadoTest::test_borde_alumno_reactivado_conserva_fecha_alta_original`.

## Pruebas

`tests/Feature/ReingresoCuotaT38Test.php`: 18 pruebas, con los valores de `CatalogosSeeder`
(días 16-23 al 70 %, 24-31 al 40 %; gracia 10; plazo de alta 7; plan de $10.000).

- Reactivado el 20/10 por la ficha: cuota de octubre de $7.000 al 70 %, deuda de septiembre
  intacta, mensaje con importe y porcentaje. Por el interruptor del listado, lo mismo.
- Lo que ve la operativa: la ficha muestra el aviso con la cuota generada, y Cobranza filtrada
  por «En plazo» lista al alumno.
- Ya tenía la cuota del mes: no se crea otra ni se toca. Baja y vuelta en el mismo mes: no
  duplica. Dar de baja no genera nada. Ingreso en un mes que viene: no genera nada.
- Sin plan: no se reactiva, por pantalla y por JSON. Sin una regla única para el día: 100 %.
- Revisión pendiente del mes: queda respondida.
- Plazo: en plazo del 20 al 26, moroso el 27; reactivado el 28, en plazo hasta el 03/11 y
  deudor el 04/11; las otras cuotas del alumno siguen con su vencimiento.
- Mora al 5 %: el 21 lleva recargo quien no se reactivó y el reactivado no; el 26 todavía no;
  el 27 sí, $350 (5 % de $7.000).
- Cobro a un inactivo: solo deuda vieja → se genera octubre; parte de octubre → el saldo queda
  con plazo; a un activo → no cambia.
- Alta el 17 y reingreso el 24: el cobro respeta el 40 % del reingreso.

Rompí a propósito cada pieza (plazo en estados, mora, cobro y el ajuste del caso 1). En cada
caso alguna prueba falló. Después restauré el código.

Suite completa en `wings_testing_claude`, 17/10: **819 aprobadas, 2 omitidas, 0 fallidas**,
7585 aserciones, 1302 s. Corrió con el trabajo sin commitear de Codex (T36) y de Gemini (T41)
en la misma carpeta. Empezó antes de los dos últimos cambios (el ingreso en un mes que viene y
la prueba de lo que ve la operativa); después repetí T38 y las pruebas de cobro, estados, mora,
alta y revisión: 147 aprobadas. El único fallo fue el contador de pruebas de los documentos,
que en ese momento estaba editando Codex.

## En el navegador

Chrome real, base descartable `wings_testing_claude`, servidor local en el puerto 8738,
operativa sintética. Scripts y resultado en
[`evidencia-t38-claude/`](evidencia-t38-claude/) (`preparar-local.php`, `recorrer-local.mjs`,
`resultado-navegador.json`); las credenciales efímeras se borraron al terminar.

| Paso | Qué se vio |
|---|---|
| Ana (debe septiembre), ficha, Reactivar | Pidió confirmación («¿Reactivar a Ana Reingreso?»). Aviso: «Alumno activado correctamente. Se generó la cuota de octubre: $7,000.00 (70 % por reingreso).» Septiembre $10.000 y octubre $7.000; estado Deudor por septiembre. [1366](evidencia-t38-claude/02-ficha-reactivado-1366.png), [375 sin desborde](evidencia-t38-claude/03-ficha-reactivado-375.png) |
| Cobranza filtrada por Deudor | Ana figura: manda la deuda de septiembre. [Captura](evidencia-t38-claude/04-cobranza-deudor-ana-1366.png) |
| Bruno (sin deuda), interruptor del listado de inactivos | Se reactivó por el interruptor; en Cobranza figura En plazo, octubre $7.000 (1 cuota). Totales: $24.000 adeudado, 1 en plazo, 1 deudor. [Cobranza](evidencia-t38-claude/05-cobranza-en-plazo-bruno-1366.png), [ficha](evidencia-t38-claude/06-ficha-bruno-1366.png) |

## Sin verificar o fuera de alcance

- **Vistas:** no se tocó ninguna. El aviso usa los mensajes que ya existen. En el listado, el
  interruptor no muestra qué cuota se generó; la ficha sí. La ficha pinta todas las cuotas
  impagas con el color del estado general del alumno (ya era así).
- **Celular:** solo la ficha a 375. Cobranza no se miró en celular.
- **Cobro a un inactivo que nunca pagó:** sigue la regla del alumno nuevo (§4, caso 1), no la
  del reingreso: su descuento es el del mes de alta. El botón Reactivar, en cambio, siempre
  usa el porcentaje del día. Lo dejé como estaba porque el código lo documenta como decisión.
- **Revisión resuelta como «Continúa»** para un alumno que alguien dio de baja mientras esperaba:
  le crea la cuota sin reactivarlo. Ya pasaba antes; no lo cambié.
- **El pago guarda `porcentaje_aplicado` = 100** cuando se cobra una cuota de reingreso de
  quien ya pagó alguna vez. El importe correcto está en la cuota; ese campo del pago no lo
  muestra ninguna pantalla ni recibo.
- **Cuando el sitio de prueba llegue a fin de mes**, el generador del día 1 manda a revisión a
  un reactivado que no vino a clase. Es la regla de §2 y no cambia.
