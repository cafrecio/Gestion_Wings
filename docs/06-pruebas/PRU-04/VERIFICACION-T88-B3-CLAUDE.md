# Verificación de T88 y B3 — Claude, 27/10/2026

Entregas de Codex: dinero en `17f99d13`, asistencia en `51d26fb8`. Verificado contra el
pedido, el contrato de cobranza (§4 a §9b) y el código; no contra los informes.
Base descartable `wings_testing_claude`.

## Resultado

| Parte | Resultado |
|---|---|
| T88: gasto en efectivo mayor que el cajón | **Aprobada en lógica.** Aspecto pendiente de Carlos. |
| B3 dinero: cobro parcial y cobro que deja un mes anterior impago | **Aprobada en lógica.** Aspecto pendiente de Carlos. |
| B3 asistencia | **Devuelta a Codex.** Un defecto que frena el despliegue (abajo). |

## Defecto que devuelve B3: todo alumno de la primera carga es «nuevo»

**Verificado con una prueba y con datos del sitio de prueba.**

`ExcepcionCobranzaService::condicionesAsistencia` define como nuevo a quien no tiene un
pago de cuota en Wings (`$nuevo = !$conCuota->has($alumno->id)`). Los alumnos de la
primera carga pagaron antes de que Wings existiera: la carga crea deudas y planes, nunca
pagos (`PrimeraCargaExcelService`). Para el sistema, todos son nuevos, y desde su tercera
clase registrada en Wings la profesora tiene que escribir un motivo para cada uno, en
cada clase, hasta que paguen una cuota por Wings.

- **Prueba:** [VerificacionB3PrimeraCargaClaudeTest](evidencia-verificacion-t88-b3-claude/VerificacionB3PrimeraCargaClaudeTest.php).
  Un alumno de la primera carga, al día, con dos clases previas, marcado presente sin
  motivo: **422**, no se guarda la asistencia. Lo esperado es que entre.
- **Sitio de prueba, 27/10:** 103 alumnos activos, **25 sin ningún pago**, todos de la
  primera carga y todos con 3 a 14 presentes. Con B3 desplegado, a esos 25 les pedirían
  motivo en cada clase de noviembre.
- **Producción:** todavía sin alumnos. El día de la primera carga real, **todo el club**
  queda en esta situación.

**Por qué no es una política nueva.** El contrato, §4, enmienda del 06/09, dice:
*«Planteado por Carlos el 06/09: los alumnos que se cargan en la primera carga no tienen
que reconocerse como nuevos.»* Lo pendiente era cómo identificarlos, y el dato ya existe:
`primera_carga.detalle.alumnos` guarda los ids cargados. En el sitio de prueba están los
100 alumnos (241 a 340). Codex lo dejó anotado como pregunta abierta; con la regla
vigente, un alumno de esa lista tiene historia, no es nuevo.

**Lo que se pide al devolver:**
1. Un alumno de la lista de la primera carga no es nuevo: se lo trata como alumno con
   historia (§6). Si es DEUDOR, pide motivo; si es MOROSO o está al día, entra.
2. La prueba de esta verificación pasa a `tests/Feature`, en rojo sin el arreglo y en
   verde con él. Sumar el caso DEUDOR de la primera carga.
3. Revisar si otro lugar usa «sin pagos = nuevo» con el mismo efecto sobre la primera
   carga (el descuento ya lo resolvió por el mes de alta, §4).

## Lo que se verificó y pasa

| Punto | Cómo | Estado |
|---|---|---|
| Gasto mayor que el cajón se rechaza en el servidor, también al editar y al cambiar de medio | Lectura de `CajaService::exigirEfectivoDisponible` y pruebas de Codex corridas acá | Verificado |
| No hay caja abierta sin efectivo declarado que esquive el control | `abrirCajaSiNoExiste` y `registrarMovimientoEnCaja` rechazan esa caja antes | Verificado por lectura |
| Dos gastos al mismo tiempo no usan una foto vieja del cajón | `T88EfectivoConcurrenteTest`, dos conexiones | Verificado (corrida acá) |
| Un cobro completo con descuento de alta no se toma como parcial | `DescuentoPrimerPagoSoloDelMesDeAltaTest`, sin cambios y sin motivo, pasa | Verificado |
| Parcial y mes anterior impago piden motivo; con motivo se guarda junto con el pago | `T88B3DineroTest` (18 casos) | Verificado (corrida acá) |
| Las pruebas existentes que Codex tocó solo agregan un motivo a cobros que sí son parciales; importes y saldos esperados sin cambios | Diff de las 16 pruebas | Verificado |
| Sin confirmaciones nativas nuevas ni JavaScript en las vistas | Diff de vistas y `cobrar.js` (se retiraron `confirm` y `alert`). El `confirm` de `clases-show.js:281` es anterior (19/10, profesoras) y cae en MF10 | Verificado |
| Pruebas de Codex y técnicas | T88/B3 más CambioPlanCobro: 49 aprobadas, 214 aserciones | Verificado |
| Suite rápida en `wings_testing_claude` (incluye T54 y T65 ya commiteadas) | 1021 aprobadas, 6 omitidas, 7935 aserciones, salida 0 | Verificado |

## Para que Carlos sepa (no son defectos)

- **El aviso inmediato del cobro con deuda anterior se fue.** Antes, cada cobro que
  dejaba un mes anterior impago mandaba un aviso al dueño en el momento
  (`CobroConDeudaAnteriorNotification`). Ahora va, con las demás excepciones, al Inicio
  y al resumen diario. Así lo pedía el prompt de Claude («no mandes un mensaje por cada
  excepción»). Si Carlos quiere el aviso inmediato, se repone.
- **El motivo ya no queda escrito en las observaciones del pago:** queda en el registro
  de excepciones, que el dueño ve en Inicio.
- **Preguntas que dejó Codex, sin decidir:** qué pasa con la excepción si se anula el
  cobro o se corrige la asistencia que la originó (mantenerla, marcarla anulada o
  sacarla del resumen).

## Despliegue

Ni T88 ni B3 van al sitio de prueba todavía: falta el aspecto de Carlos, y B3 asistencia
pondría a 25 alumnos a pedir motivo en cada clase.

## Segunda vuelta de B3 asistencia — 27/10, commit `1de85aef`

**Aprobada en lógica.** Falta el aspecto de Carlos.

- `condicionesAsistencia` ahora excluye de «nuevo» a los ids de `primera_carga.detalle.alumnos`,
  y solo con la carga TERMINADA. Una carga pendiente, sin detalle o con ids inválidos no
  exime a nadie. *Verificado por lectura; `detalle` se lee como array desde el modelo.*
- La prueba roja de esta verificación pasa con el arreglo. Las 7 de `B3PrimeraCargaTest`
  cubren al día, DEUDOR (pide motivo de deuda), MOROSO, en plazo, alta posterior (sigue
  nueva), carga pendiente y la etiqueta «Nuevo» en la lista. Corridas acá con B3 y T88:
  46 aprobadas, 206 aserciones.
- Barrido de «sin pagos = nuevo»: el descuento ya usaba el mes de alta. `PagoService`
  queda en la API apagada, y el borrado y la generación de deuda no clasifican nuevos.
  *Revisado en la tabla del informe de Codex; no releído línea por línea.*

**Para Carlos:** capturas en [APROBAR-B3-ASISTENCIA.html](APROBAR-B3-ASISTENCIA.html) y
[APROBAR-B3-PRIMERA-CARGA.html](APROBAR-B3-PRIMERA-CARGA.html). Con su OK, T88 y B3 van
juntas al sitio de prueba.
