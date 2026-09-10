# Wings — Bitácora de Claude Code

> Memoria operativa entre las computadoras de CyE y CAB.
> No reemplaza `ESTADO-ACTUAL.md` ni el plan vigente: registra qué se hizo y qué sigue.
> La bitácora de Codex es `LOG-CODEX.md`. Son dos archivos separados a propósito.

## Cómo usar esta bitácora

- Leerla antes de comenzar una tarea.
- Agregar una entrada al cerrar cualquier trabajo que produzca cambios.
- Firmar como **Claude CyE** en CyE o **Claude CAB** en la casa de Carlos.
- Registrar hechos verificables y enlazar archivos o commits cuando corresponda.
- **Registrar también las verificaciones del trabajo de Codex**, con el resultado real,
  no con lo que el reporte dijo.
- No incluir contraseñas, tokens, datos personales ni información sensible.
- Mantener cada entrada corta: objetivo, cambios, decisiones, verificación y siguiente paso.

---

## 2026-09-10 — Claude CAB — COB-04: un cobro cancelado le sacaba el descuento

Rama `cob-anulado`, sobre `main`. **Decision de Carlos: un pago anulado NO cuenta como
primer pago.** Un cobro cancelado es un cobro que no ocurrio.

### Lo que pasaba

Al cancelar, el pago queda en `ANULADO` y la fila se conserva. Las dos decisiones del
descuento —la del servicio y la de la pantalla— preguntaban
`Pago::where('alumno_id')->exists()` **sin mirar el estado**. Un cobro cancelado le sacaba
el descuento de bienvenida para siempre.

Reproducido: alumno de alta el 20/08 adelanta septiembre, se cancela ese cobro por error,
despues le cobran agosto. Cobraba **60.000 en vez de 42.000**.

### Los informes no se contradecian: miraban casos distintos

Esto vale mas que la correccion. El plan decia "informes en desacuerdo" y quedo trabado
meses por eso. La realidad:

- Si el cobro cancelado era **del propio mes de alta**, la deuda ya estaba en 42.000 y
  cancelar no restaura el monto original. El segundo cobro daba 42.000 — bien, pero por
  accidente.
- Si el cobro cancelado era **de otro mes**, se perdia el descuento.

Cada informe habia mirado uno. Ninguno estaba equivocado.

### El segundo defecto, que aparecio al corregir el primero

Con el descuento habilitado despues de una cancelacion, `precioConDescuento()` tomaba como
base el monto de la deuda — que podia venir ya descontado del intento anulado — y
descontaba dos veces: 60.000 a 42.000 a **29.400**.

Lo agarro la prueba de cancelacion sobre el propio mes de alta, que escribi **antes** de
tocar codigo. Hoy pasaba, y la escribi igual porque el arreglo la iba a romper. Es la
primera vez en esta serie que un defecto mio no llega a existir.

La base pasa a ser el precio de lista del plan.

### Barrido

Se reviso donde mas se pregunta por pagos previos. `CobranzaEstadoService` (dos lugares) y
`LiquidacionService` (dos) ya filtraban por estado. Queda anotado como hallazgo lateral
`PagoService:103`, que verifica si existe un pago del mes sin filtrar estado: vive en el
pago de plan mensual, no en el circuito de cuotas, y no se toco.

### Verificacion

Suite completa **149 pruebas, 829 aserciones**. `DescuentoPrimerPagoMatrizTest` llego a
nueve casos.

### Siguiente paso

Verificacion en navegador junto con COB-08. Con esto el bloque 1 queda sin tareas
abiertas salvo COB-05, el cierre conjunto.

Firma: **Claude CAB**.

---

## 2026-09-10 — Claude CAB — COB-08: el descuento se comia la seña y cerraba el mes

Rama `cob-descuento`, sobre `cob-saldo`. Lo encontro **Codex CyE** verificando COB-02.

### El defecto

Plan de $60.000, descuento del 70%, seña de $10.000: cobraba **$7.000 y dejaba la deuda
entera PAGADA**. $35.000 sin cobrar, y el mes cerrado, asi que nadie lo vuelve a mirar.

El porcentaje se aplicaba al importe tipeado en vez de al precio del mes.
`aplicarPorcentajeAItems()` convertia los 10.000 en 7.000, y `ajustarDeudaConDescuento()`
escribia ese 7.000 como monto original: pagado 7.000 sobre debido 7.000 da PAGADA.

### Es un punto ciego de mi propia correccion de COB-03

En COB-03 restringi `ajustarDeudaConDescuento()` al periodo correcto, pero le segui pasando
el importe del item — que es lo tipeado por el factor. Con pago completo da bien, y por eso
paso. **Achique el daño sin ver la causa.**

### Carlos me marco el metodo, y tenia razon

Su reclamo, textual: *"en todas te olvidas de algo"*. Cierto: COB-03 dejo vivo a COB-08, y
COB-06 dejo vivo a COB-07. El patron de mi trabajo era corregir el caso reportado y dar por
cerrada la vecindad sin mirarla.

Asi que esta vez, **antes de tocar una linea**, escribi la matriz completa de casos que
pasan por el descuento: `DescuentoPrimerPagoMatrizTest`, siete casos por la ruta web real.

**Resultado: 4 de 7 rotos**, no uno.

| Caso | Estaba |
|---|---|
| Pago completo, deuda existente | Bien |
| Pago completo, sin deuda previa | Bien |
| **Parcial, deuda existente** | **Roto** — el que reporto Codex |
| **Parcial, sin deuda previa** | **Roto** |
| **Segundo cobro del saldo restante** | **Roto** |
| **Tramo del 40%** | **Roto** |
| Sin descuento | Bien |

Los tres extra no los habia reportado nadie. Escribir la matriz costo menos que las tres
idas y vueltas que habrian hecho falta para encontrarlos de a uno.

### La regla que queda fijada

> El descuento baja **el precio del mes**, nunca el importe que se entrega.

Alumno que entra el 20 con plan de 60.000: debe 42.000. Si entrega 10.000, quedan 32.000.

### Correccion

- `precioConDescuento()` calcula el precio del mes ya descontado. La base es la deuda si
  existe, o el precio del plan si hay que crearla. Nunca el importe pagado.
- `limitarAlSaldoConDescuento()` recorta el importe al saldo resultante, para que quien
  paga la cuota entera no sea rechazado por enviar el precio de lista.
- `aplicarPorcentajeAItems()` eliminado: era la fuente.
- La pantalla muestra el mes de alta **ya descontado** y `calcularTotal()` deja de aplicar
  el porcentaje.

Ese ultimo punto importa mas de lo que parece: **pantalla y servidor dejan de calcular cada
uno su version del mismo numero.** Ahora el servidor manda el tope y la pantalla lo muestra.
COB-06 y COB-07 existieron porque los dos hacian la cuenta por separado.

### Verificacion

Suite completa **147 pruebas, 805 aserciones**. Las cinco pruebas viejas de la regla de
primer pago siguen verdes, o sea que la semantica establecida no se movio.

### Siguiente paso

Verificacion en navegador de los cuatro casos que estaban rotos.

Firma: **Claude CAB**.

---

## 2026-09-10 — Claude CAB — COB-07: al subir de plan la pantalla anunciaba de menos

Rama `cob-saldo`, sobre `main`. Lo encontro **Codex CyE** verificando COB-02: subiendo de
$40.000 a $60.000 el plan cambia bien, pero la pantalla decia $40.000 y se registraban
$60.000. Freno y consulto en vez de corregir.

### Causa

`calcularTotal()` topea cada importe contra `chk.dataset.saldo`, que se renderiza con el
saldo del momento en que se abrio la pantalla. El manejador del cambio de plan
actualizaba el importe sugerido del campo pero **no ese tope**, asi que el total quedaba
planchado en el precio viejo. El servidor esta bien: eleva la deuda del mes al precio
nuevo y cobra eso.

Correccion: `chk.dataset.saldo` se mueve junto con el importe. Una linea.

### Lo que importa de este hallazgo no es la linea

**Es el tercero de la misma familia en dos dias.** COB-01 fue el campo de monto que no
llegaba limpio al servidor; COB-06, el descuento que la pantalla no anunciaba; este, el
tope que no se movia. El patron es siempre el mismo:

> La pantalla guarda una copia de un dato del servidor y no la actualiza cuando algo la
> cambia. La plata siempre estuvo bien; lo que miente es lo que lee el operativo antes de
> pedirla.

Los tres se encontraron de a uno, probando otra cosa. Antes de dar el circuito de cobro
por cerrado conviene ir a buscar las copias que quedan —`data-saldo`, `data-pagado`,
`data-precio`— en vez de esperar que aparezcan solas. Anotado en COB-07 del plan.

### Sobre la prueba, con honestidad

La regresion verifica que la linea este, no que el total sea correcto: el total lo calcula
el navegador y la suite corre sin JavaScript. **No hay forma de probar esta familia de
defectos con PHPUnit.** Por eso los tres los encontro Codex mirando la pantalla, no la
suite. Si el circuito de cobro va a seguir creciendo, en algun momento hay que decidir si
se agrega una prueba de navegador o si se asume que esta parte se valida a mano siempre.

### Verificacion

Suite completa **140 pruebas, 753 aserciones**.

### Limite conocido, no corregido

En una bajada de plan con asistencia del mes, el servidor deja la deuda en el precio viejo
y la pantalla sugiere el nuevo, mas bajo. Los dos numeros coinciden entre si, asi que no
es el defecto de arriba: se cobra el mas bajo y el mes queda parcialmente impago. Queda
anotado por si el recorrido humano lo levanta como confuso.

### Siguiente paso

Que Codex termine COB-02 y verifique este de paso, que es la misma pantalla.

Firma: **Claude CAB**.

---

## 2026-09-10 — Claude CAB — Carga del padron: el saldo inicial de todos, no solo de los deudores

Rama `carga-inicial`, sobre `cob-total`. Diseño de Carlos.

### De donde salio

De discutir el descuento de primer pago a un alumno de carga inicial. Carlos corto la
discusion con un objetivo mas simple y mejor: **bloquear el mes**. Que Wings arranque a
facturar el mes siguiente y que lo anterior quede cerrado.

Y con una correccion de metodo que me hizo, con razon: un script de migracion que corre
una sola vez **no esta atado a las validaciones del formulario**. Yo habia presentado las
guardas de `min:0.01` como si fueran un impedimento. No lo son: el script escribe filas
directo. Para eso existe.

Lo que si se sostiene, y es otra cosa, es que **el script corre una vez pero la fila
queda para siempre**. Un `Pago` de $0 diria "esta persona pago" y lo van a leer la
cobranza, los recibos y los reportes de aca en adelante.

### La forma que resulto

Verificando `CajaWebController::cobrar()` aparecio que la pantalla pregunta si existe
**cualquier** deuda del mes en curso, sin mirar el estado: si existe, no lo ofrece.

Entonces una `DeudaCuota` del mes de corte con **monto 0 y estado PAGADA** bloquea el mes,
sin inventar un pago. Dice "no debia nada", que es verdad, en vez de "pago", que no lo es.
Mismo resultado que buscaba Carlos, sin dejar una afirmacion falsa en la base.

### Lo que se hizo

- `wings:exportar-padron` — saca el padron completo de alumnos activos: DNI, Alumno,
  Deporte, DEBE vacio, y pares Periodo/Monto. El DNI va como texto porque hay documentos
  con cero adelante y Excel se los come.
- `wings:importar-padron` — lee el archivo completado. `SI` crea deudas `PENDIENTE` por
  cada par; `NO` crea la deuda del corte en cero `PAGADA`. Todo o nada, con
  `--solo-validar` para revisar sin escribir.
- `CargaSaldoInicialPadronService` — nuevo, al lado del viejo. **No se toco
  `CargaDeudaInicialExcelService`**: tiene otro orden de columnas, pruebas y
  documentacion propias, y romperlo no aportaba nada.

### ATENCION: ahora hay DOS importadores de carga inicial

No se reemplazo uno por otro. Conviven, y hay que saber cual usar:

| | `wings:importar-deuda-inicial` (viejo) | `wings:importar-padron` (nuevo) |
|---|---|---|
| Que filas lleva | Solo los deudores | **Todos** los alumnos, con DEBE por fila |
| Columnas | DNI, deporte, pares monto + mmYYYY | DNI, Alumno, Deporte, DEBE, pares mmYYYY + monto |
| Mes de corte | No lo toca | Lo cierra con una deuda en cero pagada |
| Cuando se usa | Deuda suelta sobre una base en marcha | **El arranque del club** |

El viejo **no sirve para el arranque**: el alumno que no figura se asume sin deuda, asi
que un olvido y una persona al dia se ven igual. Queda avisado en la cabecera de
`CARGA-DEUDA-INICIAL-EXCEL.md` y en `ESTADO-ACTUAL.md`, para que nadie siga el
instructivo equivocado.

### Por que cambia el resultado

Antes, el alumno que no figuraba en el Excel se asumia sin deuda. Un olvido de Vanina y
una persona al dia se veian igual. Ahora cada alumno tiene que decir SI o NO: **el
silencio deja de ser una respuesta valida**.

### Verificacion

Suite completa **139 pruebas, 751 aserciones**. Entre ellas, una que confirma lo que
importa: despues de importar, al deudor la pantalla le ofrece septiembre y al que dice NO
no se lo ofrece.

### Consecuencia asumida

En los reportes, la facturacion del mes de corte de los que dicen NO figura en cero. Esa
plata entro antes y fuera de Wings. Carlos lo decidio asi.

### Siguiente paso

Falta que Vanina termine de cargar alumnos para correrlo. Quedo anotado en
`CHECKLIST-CARLOS.md` y el procedimiento en
`docs/06-pruebas/CARGA-PADRON-SALDO-INICIAL.md`.

Firma: **Claude CAB**.

---

## 2026-09-10 — Claude CAB — COB-06: la pantalla prometia un total y se cobraba otro

Rama `cob-total`, sobre `cob-02`. Lo encontro **Codex CyE** verificando COB-03 por
navegador: la pantalla decia $38.000 antes de confirmar y el pago quedaba en $29.600.
Freno sin tocar nada y pregunto. Buen freno: era una diferencia real, aunque el defecto
no fuera el que parecia.

### La plata estaba bien; el que mentia era el cartel

$29.600 es correcto: $19.600 de agosto con el 70% mas $10.000 de septiembre. El importe
registrado nunca estuvo mal. Lo que estaba mal era el numero que lee el operativo para
pedirle la plata al alumno.

### Eran dos defectos encadenados

1. `calcularTotal()` suma los importes de los campos y los muestra sin descuento, porque
   el descuento lo calcula el servidor recien al confirmar.
2. Peor: **la pantalla ni siquiera anunciaba el descuento**. Su guardia exigia que el mes
   de alta fuera el mes en curso; `calcularReglaPrimerPago()` solo exige que el mes de
   alta este entre los periodos cobrados. Alta en agosto cobrando en septiembre: el
   servidor descuenta y la pantalla se calla.

Lo mas incomodo: el comentario de `CajaWebController::cobrar()` advertia el riesgo con
estas palabras —"si esta pantalla mostrara un descuento que el cobro no aplica, el
operativo cobraria un importe distinto del que le dijo al alumno"—. Quien lo escribio vio
el problema exacto. La guardia quedo solo en el anuncio, y ademas con otro criterio.

### Como se decidio cual lado estaba mal, sin preguntar

Parecia decision de negocio: ¿el descuento vale meses despues del alta? No hizo falta.
`DescuentoPrimerPagoSoloDelMesDeAltaTest::test_en_un_pago_de_varios_meses_el_descuento_alcanza_solo_al_mes_de_entrada`
usa exactamente ese caso —alta 20/08, cobrando en septiembre— y espera que agosto lleve
el 70%. La regla ya estaba decidida y cubierta. El que no la respetaba era el anuncio.

Vale como metodo: antes de subir una pregunta de negocio, buscar si ya esta contestada
en una prueba o un contrato.

### Correccion

La pantalla pasa a usar el mismo criterio que el servicio y expone el periodo con
descuento y el porcentaje para que el total los aplique. Los importes por periodo siguen
mostrandose enteros a proposito: es lo que se envia, y el descuento lo aplica el
servidor. Cambio de vista autorizado por Carlos.

### Un hallazgo lateral, registrado y sin tocar

`calcularReglaPrimerPago()` no exige que el mes de alta sea reciente, solo que este entre
los periodos cobrados. Un alumno traido de la carga inicial, con deuda de su propio mes
de alta, **recibe el descuento al pagar esa deuda**. La prueba existente solo cubre
cobrarle otro mes, asi que el caso esta descubierto. Queda en contradicciones abiertas:
es decision de Carlos, no la toque.

### Verificacion

Suite completa **133 pruebas, 726 aserciones**. Las cuatro pruebas viejas de la regla de
primer pago siguen verdes.

### Siguiente paso

Codex repite la verificacion de COB-03 por navegador sobre `cob-total`, que ya tiene
COB-03, COB-02 y esto.

Firma: **Claude CAB**.

---

## 2026-09-10 — Claude CAB — COB-02: el cambio de plan no salia de la pantalla

Rama `cob-02`, apoyada sobre `cob-03` para que el conteo de pruebas quede lineal y las
dos se mergeen en orden. `cob-03` quedo liberada para que Codex la pueda checkoutear.

### Que pasaba

El selector de plan estaba dibujado **fuera** del formulario: el control en la linea 54,
el `<form>` recien en la 83. Un form solo envia lo que tiene adentro, asi que
`new FormData(cobrarForm)` nunca juntaba `nuevo_plan_id`.

Lo que lo volvia invisible: el JavaScript de la vista **si** reacciona al clic. Pinta la
opcion elegida y actualiza el monto sugerido al precio del plan nuevo. La operativa ve
el importe correcto, cobra el importe correcto, y el recibo dice el importe correcto.

El backend, ademas, estaba completo y bien hecho: distingue subida de bajada y difiere
la bajada al mes siguiente si hubo asistencia. Nunca se ejecutaba por falta del dato.

**El daño no era del dia del cobro, era del mes siguiente.** El alumno quedaba en el
plan viejo, la corrida mensual generaba la deuda con el precio anterior, y seguia asi
todos los meses. Nadie se entera porque el mes del cambio el numero fue el correcto.

### La correccion

Se movio la apertura del formulario arriba del selector. `<form>` no dibuja nada, asi
que la pantalla queda identica: el diff no toca ni un div, ni una clase, ni un estilo,
ni un texto. Se descartaron las otras dos opciones —campo oculto sincronizado por JS, o
`formData.append()`— porque las dos vuelven a poner la plata a depender de que un script
corra a tiempo, que es exactamente la causa de COB-01.

Carlos autorizo el cambio de vista despues de que se le explicara el alcance concreto.
Es la primera vez en esta serie que se toca `resources/views/**`.

### Como se prueba algo que solo falla en el navegador

Un test HTTP que postee `nuevo_plan_id` pasa igual, porque el backend siempre funciono:
el defecto es que el navegador no manda el campo. Asi que la regresion se hace sobre el
**HTML renderizado**: busca la posicion de `nuevo_plan_id`, la de la apertura del
formulario y la del cierre, y exige que el campo caiga entre las dos. Roja antes, verde
despues. Sirve para cualquier control que tenga que viajar.

### Verificacion

Suite completa **132 pruebas, 723 aserciones**. Las 8 pruebas viejas de cambio de plan
siguen verdes, o sea que la logica de subida/bajada no se toco.

### Siguiente paso

Pendiente de verificar en navegador. Despues quedan COB-04 —que necesita decision de
Carlos y conviene reproducir antes— y unificar `cob-03` y `cob-02` en `main`.

Firma: **Claude CAB**.

---

## 2026-09-09 — Claude CAB — COB-03: la seña de otro mes borraba el saldo

Hecho en un **worktree separado** (`../Gestion_Wings_cob03`, rama `cob-03`, base de test
`wings_testing_cob03`) porque Codex estaba usando el arbol principal para verificar
COB-01 con checkouts. Misma maquina, mismo repo, sin pisarnos. Si repetimos el esquema,
conviene copiar `public/build` al worktree: sin el manifest de Vite fallan 25 pruebas
por una razon que no tiene nada que ver con lo que se esta probando.

### El defecto

Alumno de alta el 20/08, con descuento del 70% por regla de segunda quincena. Paga
agosto y aprovecha para dejar una seña de 10.000 de septiembre, que ya estaba cargado en
28.000. Resultado: **septiembre quedaba con monto original 10.000 y estado PAGADA**. Los
18.000 restantes desaparecian y el alumno figuraba al dia.

Mismo patron que COB-01: no falla, no avisa, deja el numero mal.

### Eran dos caminos, y el informe solo nombraba uno

1. `ajustarDeudas()` recorria **todos** los items del cobro bajando `monto_original` al
   monto enviado. Su propio comentario decia que existe para el periodo con descuento;
   el bucle no lo respetaba. `aplicarPorcentajeAItems()`, justo arriba, si discrimina.
2. `$montosOriginalesNuevasDeudas = array_column($items, 'monto', 'periodo')` incluia
   todos los periodos, asi que si la deuda del otro mes **no existia todavia**, nacia con
   el parcial como monto original desde `obtenerOcrearDeuda()`.

El segundo lo encontre buscando si la correccion del primero alcanzaba. No alcanzaba: se
habria arreglado el caso con deuda existente y quedado abierto el caso con deuda nueva,
que es igual de alcanzable.

### Correccion

El override de monto original queda restringido al periodo con descuento en los dos
caminos. `ajustarDeudas()` pasa a `ajustarDeudaConDescuento()`, que recibe un periodo y
un monto en vez de la lista entera. El cambio de firma es a proposito: con la lista
completa el defecto se puede reintroducir sin darse cuenta; con un periodo, no.

### Verificacion

Regresion `DescuentoNoAlteraOtroPeriodoTest`, roja antes y verde despues. Suite completa
**131 pruebas, 719 aserciones** sobre MariaDB. `git diff` de vistas y CSS vacio.

Dos escenarios descartados durante el armado, que valen para la proxima: el operativo no
puede crear deuda de un periodo pasado — corta con excepcion y `back()` —, y el FIFO solo
admite parcial en el ultimo periodo del cobro.

### Siguiente paso

COB-04, que necesita decision de Carlos. Reproducirlo primero para que decida sobre
hechos: hoy los dos informes dicen cosas distintas sobre que pasa al cancelar y volver a
cobrar la primera cuota.

Firma: **Claude CAB**.

---

## 2026-09-09 — Claude CAB — COB-01 corregido y la base del servidor ya es real

### Lo que cambia todo: el club esta cargando datos reales

Carlos lo informo hoy. `ESTADO-ACTUAL.md` §3 declaraba "sin alumnos, deudas, pagos,
clases ni operacion real" y eso ya era falso. Corregido. **No correr seeders contra esa
base** — FIN-01 ahora pega sobre personas reales. FDS-03 cambia de sentido: ya no se
puede "reconstruir" el estado, porque el estado incluye datos que solo existen ahi.

No revalidado por SSH desde esta computadora. El alcance de lo cargado es desconocido
para la documentacion.

### COB-01 — cobraba 28 en vez de 28.000, sin decir nada

Reproducido antes de tocar codigo. La prueba fallo con `monto_pagado: "28.00"` y la
deuda en `PENDIENTE`, mientras la pantalla devolvia exito.

Cadena completa: el script de `caja/cobrar.blade.php` va en `@push('scripts')` y se
registra **durante el parseo**; `ds-app.js` entra por `@vite` como modulo y se registra
**despues**. El primero en registrarse corre primero, asi que el handler de la vista arma
`new FormData(...)` — que es una foto del momento — cuando el campo todavia dice
`28.000`. `stripMoneyInputs` limpia el input despues, tarde. En el servidor
`is_numeric("28.000")` da `true` y `(float)` lo vuelve `28`.

Por eso nadie lo vio: no hay excepcion, no hay error, la pantalla confirma. Solo el
numero queda mal, y queda mal a la vez en deuda, caja, recibo y estado de cobranza.

**Corregido en el servidor**, normalizando antes de validar en `CajaWebController::pagar()`.
Dos razones para no arreglarlo en el JavaScript: no puede depender del orden de carga de
dos scripts, y asi no se toca ninguna vista, que `AGENTS.md` protege.

**Alcance auditado:** era el unico formulario roto. Los otros seis con `data-money`
(cashflow, caja editar/movimiento, grupos, profesores, tipos de caja) usan envio normal,
asi que la limpieza les corre bien. `caja/cobrar` es el unico que arma `FormData` dentro
de un handler de `submit`.

Verificado ademas que la normalizacion cubre `1.500.000`, que **antes rechazaba** la
validacion porque `is_numeric` con dos separadores da `false`. El sintoma cambiaba segun
el monto: hasta 999.000 cobraba mal en silencio, del millon para arriba rechazaba.

### Dos hallazgos nuevos, verificados contra el codigo

1. **`dia_generacion_deuda` no gobierna nada.** Existe como fila creada por migracion y
   ningun codigo la lee. El scheduler usa dia 1 fijo en `routes/console.php:12`. La
   pantalla de configuracion deja editar un numero que no hace nada. Estaba anotado como
   contradiccion abierta; queda confirmado.
2. **El alumno sin plan desaparece de la corrida mensual.**
   `GenerarDeudasMensualesCommand:77-81` lo saltea entero: no genera deuda, no entra a la
   cola de revision, y el aviso muere en la salida del cron. Esto **no es del arranque**,
   pasa todos los meses. Con una base cargandose a mano ahora, es facil de producir.

### Sobre el 1 de octubre

Reconfirmado contra el codigo actual (`GenerarDeudasMensualesCommand:84-102`): genera la
deuda solo con asistencia del mes anterior, o alta de menos de 15 dias **mas** pago en
esos 15 dias. Un alumno cargado a principios de septiembre sin asistencias registradas
cae en revision el 1/10.

Carlos definio que es por unica vez y no va al camino critico. Antes de elegir remedio
hay que **medirlo**: correr `cobranza:generar-deudas --periodo=2026-10` sobre una copia
descartable del respaldo y leer los contadores que el comando ya imprime.

### Verificacion

Suite completa verde: **130 pruebas, 710 aserciones** sobre MariaDB.
`DocumentacionNoMienteTest` se puso en rojo por el conteo y obligo a corregir los tres
documentos en el mismo turno, que es exactamente para lo que existe.

### Siguiente paso

COB-02: el cambio de plan no llega como `nuevo_plan_id`. Toca
`resources/views/caja/cobrar.blade.php`, asi que **requiere autorizacion de diseño de
Carlos** antes de empezar.

Firma: **Claude CAB**.

---

## 2026-09-07 — Claude CAB — Entrega preparada, menu agrupado y tres pendientes nuevos

Servidor en **`9fdd03d`**. La base quedo en el estado de entrega definido con Carlos, y
Vanina ya tiene su cuenta.

### La base del servidor, lista para que la cargue el cliente

Se borraron 6 rubros, 14 subrubros, 5 tipos de caja, 2 deportes, 3 niveles y las 7
sesiones abiertas. Respaldo previo al Drive antes de tocar. Queda solo lo que el codigo
busca por nombre o no se puede crear desde una pantalla:

| Que | Por que queda |
|---|---|
| Rubro `Cuotas` con `Cuota Mensual` | `PagoCuotaService` lo busca por nombre y explota si falta |
| Rubro `Sueldos` **vacio** | Sus subrubros los crea el alta de cada profesor y operativo |
| Las 2 configuraciones con valor | La pantalla edita claves, **no las crea** |
| Las 3 reglas de primer pago | 1-15 al 100%, 16-23 al 70%, 24-31 al 40%. Editables |

Deportes, niveles, grupos, tipos de caja y el resto de los rubros **los carga el
usuario**: esa carga es, ella misma, la prueba de esas pantallas.

### Las dos cuentas

`carlos.a.bonifacio@gmail.com` es **superadmin protegido**: no aparece en el listado de
usuarios para nadie mas y ninguna otra cuenta puede editarla. `vaninaatto@hotmail.com`
es **ADMIN duenia**. Verificado por consulta, no por confianza: Vanina se ve solo a si
misma; Carlos ve a las dos. Las claves se comprobaron contra el hash guardado.

### El menu, agrupado por cuanto se toca cada cosa

Eran 17 items casi planos con un separador mudo que mezclaba catalogos, plata y
sistema. Quedaron cuatro grupos con titulo: **Dia a dia**, **Plata**, **El club** y
**Sistema**. Revision bajo a Plata —era una tarea de fin de mes puesta como si fuera
diaria—, Profesores bajo a El club, y Movimientos y Cashflow subieron junto a
Liquidaciones. Verificado renderizando el sidebar con los tres roles.

---

## PENDIENTES NUEVOS — pedidos por Carlos el 07/09

**1. El recibo hay que rediseñarlo entero.** Hoy es feo. Tiene que llevar **los colores
del club y el logo**. Falta definir los dos: no hay logo en el repositorio ni una paleta
del club escrita en ningun lado. Toca `ReciboService` y su plantilla.

**2. Costo de inscripcion del alumno nuevo — $5.000 hoy.** Va **dentro de la regla de
alumno nuevo**, y el valor se setea **desde la pantalla de Configuracion**, no escrito
en el codigo. Ojo con dos cosas ya verificadas: la pantalla de configuracion **edita
claves pero no las crea**, asi que la clave nueva tiene que nacer de una migracion; y
`Configuracion::set()` sobre una fila que no existe **no hace nada y no avisa**.

**3. Falta el favicon.**

**4. El ojo para ver la contraseña mientras se tipea.** En login, alta y edicion de
usuario. Ojo con C1: la CSP no admite JavaScript incrustado en la vista, asi que el
manejador va en un archivo `.js` aparte, no en un `onclick`.

---

## Credenciales en el historial de Git — cerrado el 07/09

Se probaron las seis credenciales del archivo contra **todo** el historial
(`git log --all -S`): **ninguna aparece**. El archivo nunca se commiteo y esta cubierto
por `.gitignore`.

El viejo `database/dump.sql` si dejo en commits anteriores el mail, nombre, DNI y
telefono de dos alumnas cargadas en marzo. **Carlos lo evaluo el 07/09 y decidio que no
amerita accion**: una casilla de correo no es un dato reservado. Queda escrito para que
no se vuelva a levantar como hallazgo nuevo.

---

## 2026-09-07 — Claude CAB — Despliegue de 25 commits, y la trampa que me puse solo

Servidor al dia: **`7abf327`**, 07/09 02:46. Respaldo previo tomado y subido al Drive
(`wings_2026-09-07_0240.tgz.enc`). Preflight aprobado en los 12 controles, antes y
despues. Verificado: `/login` 200, `/.env` 404, los 8 rubros con `Cuotas` y `Sueldos`
marcados reservados, y las dos migraciones nuevas aplicadas.

### El despliegue fallo dos veces por un archivo que cree yo

`apply_permissions` hace `chmod` sobre **todos** los archivos de la aplicacion. Alcanza
con que uno solo no pertenezca al usuario `wings` para que el paso falle, y el script
tira abajo el despliegue entero.

El archivo era `storage/logs/laravel.log`, **de root**, creado a las 02:41 — cuando
corri `php82 artisan wings:preflight` **como root** por SSH. Laravel escribio su log con
ese dueño y el despliegue, que corre como `wings`, no pudo tocarlo.

**No correr artisan como root en el servidor.** Deja archivos de root en `storage/` que
rompen el proximo despliegue. Si hace falta: `sudo -u wings php82 artisan ...`.

### El rollback no revierte migraciones, y eso deja el servidor desfasado

En el primer intento las migraciones **se aplicaron** —el paso dio OK— y despues el
rollback devolvio el codigo a `798bfa3`. Quedo la base adelantada y el codigo atrasado:
`migrations` en 74 con las columnas creadas, corriendo codigo que no las conoce.

No rompio nada porque las dos son aditivas, pero **no siempre va a ser asi**. Una
migracion que renombre o borre una columna deja el sitio caido tras un rollback.
Verificar el estado de la base despues de cualquier rollback, no solo el del codigo.

### Segunda trampa: el usuario `wings` no ve `composer`

`/usr/local/bin` no esta en su PATH, asi que el script corta con "no se encontro
composer". Se resuelve pasando `WINGS_COMPOSER_BIN=/usr/local/bin/composer`, que el
script ya contempla. La clave de `wings_migrate` va por `WINGS_MIGRATE_PASSWORD`,
extraida del archivo de credenciales sin pasar por el repositorio ni por pantalla.

**Comando completo que funciona:**

```bash
sudo -u wings WINGS_COMPOSER_BIN=/usr/local/bin/composer \
  WINGS_MIGRATE_PASSWORD='...' bash scripts/deploy.sh
```

### Estado de la base del servidor

**Sin datos**: 0 alumnos, 0 deudas, 0 pagos, 0 clases. Solo catalogos —8 rubros, 15
subrubros, 5 tipos de caja— y una cuenta de administrador. Por eso el respaldo bajo de
63K a 12K: no es una perdida, es que no hay nada cargado.

**Dato que corrige una suposicion:** los catalogos completos estan en el **servidor**.
La que esta a medias es `wings_test`, con 2 rubros y 2 tipos de caja.

---

## 2026-09-06 — Claude CAB — Deuda inicial: los dos Excel, generados y validados

**Orden para Codex:** `ORDEN-CODEX-DEUDA-INICIAL.md`, nueve pasos con el porqué del
orden al final. **Diseño:** `docs/06-pruebas/DISENO-DEUDA-INICIAL-V1.md`.

### Lo que se descubrió leyendo el codigo, y que cambio el diseño

`CobranzaEstadoService::calcularEstadoDesdeDeudas()` lineas 226-241, leido el cuerpo:

1. **Los 60 alumnos ya estan en DEUDOR, sin deber un peso.** La primera condicion es
   `!$tienePagos`: quien nunca pago es deudor aunque no tenga deuda. Hay 0 pagos.
2. **MOROSO era inalcanzable el 06/09.** Es `dia > dias_gracia`, con
   `dias_gracia_cobranza = 10` y hoy dia 6. Recien el 11/09, o bajando la
   configuracion desde la pantalla.

**Por eso el Excel solo no produce los cuatro estados.** Los produce el Excel mas una
tanda de cobros por pantalla. El archivo prepara el tablero; los cobros lo mueven.

### La reparticion, decidida con Carlos

60 alumnos: **48 con deuda, 12 limpios. 81 cuotas, $2.997.000.**

| Grupo | N | Deben | Demuestra |
|---|---:|---|---|
| Sin deuda | 12 | nada | Control: cobrar septiembre → AL_DIA |
| Solo septiembre | 20 | `092026` | El mes en curso. Cobro parcial → EN_PLAZO |
| Dos meses | 14 | `082026`+`092026` | FIFO: tiene que imputar agosto primero |
| Tres meses | 8 | `072026`+`082026`+`092026` | Acumulacion con tres periodos |
| Deuda vieja | 4 | meses de 2025 | Que el FIFO cruce de anio |
| Para condonar | 2 | un mes de 2025 | La condonada deja de contar como impaga |

Los montos de 2025 son **menores** al plan actual a proposito: la cuota vieja se
genero con el precio viejo, y asi se comprueba que el importador escribe el monto del
Excel y no recalcula. Sofia Morales (DNI `32123456`) va en dos filas, Patin y Futbol:
prueba que la clave es DNI+deporte.

### Verificacion

Generador reproducible en `docs/06-pruebas/generar-deuda-inicial.php`: lee la base,
reparte por DNI+deporte (no por posicion) y **falla si algun periodo es anterior al
alta del alumno**. Los dos archivos pasaron `--solo-validar`: el bueno aprueba **81
deudas**; el de rechazos falla **las ocho filas, informadas juntas, sin escribir
nada**. Ninguna deuda fue creada: solo se valido.

### Hallazgo bloqueante para el Paso 0

`wings_test` tiene **2 rubros y 2 tipos de caja**; `CatalogosSeeder` define **8 y 5**.
El unico subrubro que afecta caja es `Cuota Mensual`, asi que **hoy no se puede
registrar un gasto en la caja operativa** y el flujo caja → validacion → cashflow no
se puede terminar.

**Contradiccion documental abierta:** `PRUEBA-HUMANA-V1.md:44` dice "Rubros: 8, con 15
subrubros… el mismo punto de partida que va a tener el cliente el dia uno";
`PRIMERA-CARGA-V1.md:21` dice que la base queda a proposito solo con Cuotas y Sueldos.
Los dos no pueden ser ciertos. Se resuelve en el Paso 0, antes de cargar nada.

### Documentos que mi commit anterior dejo mintiendo, corregidos en este turno

`b2868ea` cerro el agujero de `RubroWebController::update()`, y tres documentos
seguian describiendolo como abierto: `ESTADO-ACTUAL.md` §F, el contrato de punitorios
y el de catalogos contables. Se corrigieron los tres.

**Siguiente paso:** Codex ejecuta la orden. En paralelo, planificacion del simulador
de tres meses (A2).

---

## 2026-09-06 — Claude CAB — Rubros reservados y el subrubro del operativo

**Punto 2 del cierre del dia, cerrado.**

### El agujero

`ProfesorWebController` buscaba `Rubro::where('nombre','Sueldos')` y, si no lo
encontraba, hacia `return`: el profesor quedaba guardado **sin subrubro**, con la
pantalla diciendo "creado correctamente". `RubroWebController::update()` no comprobaba
nada, asi que renombrar el rubro desde la pantalla bastaba para romperlo. Cambiarle el
`tipo` de EGRESO a INGRESO daba vuelta el signo de los sueldos en el cashflow.

### Que se hizo

- Columna `es_reservado_sistema` en `rubros` — el mismo blindaje que los subrubros
  tenian desde febrero, un nivel arriba. Marcados: **Sueldos** (buscado por nombre) y
  **Cuotas** (padre del subrubro reservado `Cuota Mensual`). Fuera de `$fillable`: lo
  fijan la migracion y el seeder, nunca un formulario.
- `RubroWebController`: un rubro reservado no cambia de nombre ni de tipo, y no se
  borra. **La observacion si se edita** — no la usa el codigo.
- `app/Services/SubrubroSueldoService.php`: unico lugar que resuelve "Sueldos" por
  nombre. Si falta el rubro **lanza excepcion**, no devuelve null.
- **Decision de Carlos:** el usuario OPERATIVO recibe su subrubro de sueldo en el alta,
  bajo Sueldos, con el nombre **`Op-Nombre Apellido`** (el del profesor sigue siendo
  `Deporte-Nombre Apellido`). `permitido_para=ADMIN` y `afecta_caja=false`: el sueldo lo
  liquida el admin por cashflow, no sale de la caja operativa. **Solo OPERATIVO** — al
  ADMIN se le carga a mano si corresponde. Vinculo por FK en `users.subrubro_id`, no por
  nombre (leccion D3). Nunca se le quita: puede tener movimientos imputados.

### Verificacion

`RubroReservadoTest`, 8 pruebas. **Dientes comprobados:** se desactivaron los tres
arreglos a la vez (las dos guardas de `RubroWebController` y la llamada del alta de
usuario) y **fallaron 4** — renombrar, tipo, eliminar y el subrubro del operativo.
Restaurados, verde. Comando: `php artisan test --filter=RubroReservadoTest`.

Suite: **119 pruebas, 689 aserciones**. `DocumentacionNoMienteTest` se puso en rojo por
el cambio de numero y se actualizaron los cuatro documentos que lo declaran.

### Estado de `wings_test`

Los 4 profesores ya tenian su subrubro. Los 2 operativos no: se les creo
`Op-Valentina Rios` (23) y `Op-Nicolas Herrera` (24). El admin queda sin subrubro, como
se decidio.

**Siguiente paso:** la prueba funcional planificada con Codex.

---

## 2026-09-06 — Claude CAB — CIERRE DEL DIA: todo lo pendiente

**Punto de partida del proximo chat.** Suite verificada hoy: **111 pruebas, 663
aserciones**. Base `wings_test`: 60 alumnos (Patin 40 / Futbol 20), 0 deudas, 0 pagos.
Servidor en `798bfa3`, detras de Cloudflare y cerrado a todo lo que no venga de ahi.

### Lo que se cerro hoy

Cloudflare completo (los tres pasos, con IPv6 cerrado aparte y la lista blanca del
equipo acotada a SSH). Despliegue de 34 commits. Validacion de reglas de primer pago
superpuestas. El descuento de primer pago acotado al mes de alta. El grupo obligado a
ser del deporte elegido (H06). Dos tramos de la CSP. Contrato de punitorios escrito.
Formato del Excel de Vanina definido. Codex cerro el seeder de 60 alumnos y el
importador de deuda.

### Pendiente, por orden de conveniencia

**1. C1 — CSP, lo que queda.** Protegido por `CspSinCodigoIncrustadoTest`, que fija los
numeros y no los deja crecer. Quedan **26 bloques `<script>` en 24 vistas** y **24
manejadores** (eran 40).

- **10 `onsubmit="return confirm(...)"`**: protegen ELIMINACIONES. No se tocaron a
  proposito. Si se mueven a JavaScript y el archivo no carga, el borrado se ejecuta
  sin preguntar. **Necesitan que alguien abra la pantalla y haga clic** — son diez
  clics. La herramienta de navegador estaba bloqueada.
- **14 `onclick`**: llaman funciones definidas en el `<script>` de su propia vista, asi
  que **no se pueden mover solos**: hay que sacar los dos juntos, pantalla por
  pantalla, y cada una necesita que alguien la use para confirmar que no se rompio.

**2. El agujero de los rubros que el sistema busca por nombre.** `ProfesorWebController`
linea 116 hace `Rubro::where('nombre', 'Sueldos')->first()`, y `RubroWebController::
update()` **no comprueba nada**: cualquiera puede renombrar ese rubro o cambiarle el
`tipo` de INGRESO a EGRESO. Si lo renombran, el alta de profesores deja de encontrarlo
**en silencio**. Es chico y no choca con nadie. **Era lo proximo que iba a hacer.**

**3. F — Punitorios por mora.** Contrato cerrado en
`Wings-Contrato-Punitorios-Mora-V1.md`, con 14 criterios de aceptacion que dicen como
se comprueba cada uno. **Sin implementar**: faltan las dos claves de configuracion, los
campos en `deuda_cuotas` y el rubro reservado `Punitorios` con el subrubro
`Punitorio Cuota`. Con `mora_porcentaje = 0` el sistema se comporta igual que hoy.
Ojo: toca `deuda_cuotas`, la misma tabla del importador de Codex.

**4. `dia_generacion_deuda` es una configuracion que no lee nadie.** Aparece solo en su
migracion; el dia esta escrito en `routes/console.php:12` con `->monthlyOn(1, '06:00')`.
El admin puede cambiarla, verla guardada, y no pasa nada.

**5. No hay monitoreo.** 0 servicios corriendo en el servidor. Si el sitio se cae, nadie
se entera. **Necesita una decision de Carlos**: por que canal quiere el aviso. Es lo
unico de esta lista que no puedo arrancar solo.

**6. Del indice de `ESTADO-ACTUAL.md`:** A2 (simulador de tres meses, depende del
importador), B3 (concurrencia), B4 (smoke de rutas que escriben, ahora posible porque
hay datos), E1 (`AUD-018`, `AUD-019`, `AUD-020`, `AUD-025`), E2 (reportes: contrato
escrito, sin implementar), E4 (eliminar `formas_pago`).

**7. Para el final, por orden de Carlos:** organizar el menu del administrador y los
dashboards. Van ultimos porque los reportes agregan pantallas y el menu se reordena
igual.

**8. Suelto:** la inconsistencia de activar/desactivar (seis pantallas usan el toggle;
tipos-caja y subrubros usan botones con palabras distintas) y tomar asistencia desde el
celular.

### Contradiccion resuelta hoy, que habia frenado a Codex

`PRIMERA-CARGA-V1.md` exigia **20 varones en Futbol**, y la alumna anotada en los dos
deportes es mujer. Carlos autorizo la excepcion en el chat **pero el documento quedo
sin corregir**, y Codex se freno porque no podia cumplir las dos reglas. Corregido: la
tabla ahora dice "20 varones, con una excepcion" y explica cual. Estado real
verificado: Sofia Morales, DNI 32123456, unica con DNI repetido.

**Es la tercera vez en el dia que una decision tomada solo en el chat frena el trabajo.
Ninguna decision se cierra hasta que esta en un documento.**

---

## 2026-09-06 — Claude CAB — Despliegue y los tres pasos de Cloudflare

**Objetivo:** poner el servidor al día y dejar el sitio detrás de Cloudflare, en el
orden que no rompe nada: **preparar la aplicación → activar el proxy → cerrar la
puerta de atrás.**

### Paso 1 — `trustProxies` (código)

`bootstrap/app.php` con los 22 rangos de Cloudflare (15 IPv4 + 7 IPv6), traídos en
vivo de `cloudflare.com/ips-v4` e `ips-v6` el 06/09. Nueva prueba
`tests/Feature/ConfianzaEnCloudflareTest.php`, que cubre las dos mitades: que se
confíe en Cloudflare **y que no se confíe en nadie más**.

**Verificación de que la prueba tiene dientes:** se quitó la configuración a
propósito y la primera prueba falló; se restauró y volvió a pasar. Commit `798bfa3`.
La suite quedó en **93 pruebas, 578 aserciones**.

### Despliegue

El servidor estaba **34 commits atrás**, en `b9e6af6` (30/08). Se desplegó hasta
`798bfa3`. Respaldo previo tomado y subido al Drive
(`wings_2026-09-06_1409.tgz.enc`). Preflight aprobado en los 12 controles.

### Paso 2 — Nube naranja

`wings.gestionar-te.com.ar` pasó a `proxied:true`. Verificado: 200 por Cloudflare,
sin bucle de redirecciones, los cinco encabezados de seguridad llegan, las seis
direcciones absolutas que arma la aplicación salen en `https`, cookies `secure`,
`.env` y `.git/config` en 404.

### Paso 3 — El servidor solo acepta tráfico de Cloudflare

Estaba confirmado que la puerta existía: por `2.25.204.38` con el nombre de Wings el
sitio **respondía 200**, salteándose Cloudflare entero. Se cerró con CSF (80 y 443
fuera de `TCP_IN`, reabiertos solo para los 15 rangos), más el cierre de IPv6, que
CSF no filtraba. Detalle completo en `VPS/ESTADO-SERVIDOR.md`.

**Verificado:** por Cloudflare 200, por la IP directa sin respuesta en 443 y en 80,
`gestionar-te.com.ar` / `www` / `webmail` siguen en 200, panel accesible, SSH entra.

### Tres cosas que aparecieron y conviene no olvidar

1. **El servidor no aloja solo Wings.** También `gestionar-te.com.ar`, `www`, `mail`
   y `webmail`. Cerrar 80 y 443 podía dejarlos fuera de línea. Se comprobó antes que
   todos los registros web ya estaban con proxy; el único directo es `panel`, que se
   usa por otros puertos.
2. **Se cargaron 14 de 15 rangos en el primer intento.** El archivo que publica
   Cloudflare termina sin salto de línea final y `while read` se come esa última
   línea sin avisar. Faltaba `131.0.72.0/22`. Rehecho con `awk`. **Es el mismo error
   silencioso que ya rompió `authorized_keys`.**
3. **La primera verificación dio un falso "sigue abierto".** La dirección desde la
   que trabajo estaba en `csf.allow` con acceso a **todos** los puertos, así que
   desde acá el bloqueo no se veía — y esa entrada era, ella misma, una puerta de
   atrás. Quedó acotada al puerto 22.

**Decisión registrada:** el token de API de Cloudflare puede editar DNS pero **no**
leer la configuración de cifrado de la zona. No se pudo confirmar por API que el modo
sea "Full"; se comprobó por comportamiento (no hay bucle, que es lo que produciría
"Flexible"). Si hace falta tocar esa opción, hay que ampliar el token.

**Pendiente detectado, sin relación con esto:** el registro comodín `*` está con
proxy, así que `mail.gestionar-te.com.ar` resuelve a Cloudflare, que no hace de
intermediario para IMAP ni SMTP. Si algún cliente de correo usa ese nombre, ya venía
fallando desde antes.

**Siguiente paso:** C1 (el CSP) y la primera carga que está corriendo Codex.

---

## 2026-09-01 — Claude CAB — Versión que corre en el servidor

**Verificado por SSH contra el servidor, no inferido del repositorio local.**

| Qué | Valor |
|---|---|
| Commit desplegado | **`b9e6af6`** — *fix: dos campos que reventaban con pantalla de error en vez de avisar* |
| Fecha de ese commit | 2026-08-30 17:41 |
| Rama | `main` |
| PHP | 8.2.33 |
| Laravel | 12.68.0 |
| Migraciones pendientes | **0** |
| URL | `https://wings.gestionar-te.com.ar` |

### El servidor está 17 commits atrás

**No tiene nada de lo que se hizo después de la tarde del 30/08.** Lo que le falta y
sí importa:

| Falta en el servidor | Commit |
|---|---|
| **Primera cuota con descuento** | `846347f` |
| Cobro de la primera cuota de un alumno nuevo | `f066c42` |
| Condonación de deuda | `5f77c85` |
| Precio de plan mayor a cero | `824fdd8` |

**Consecuencia práctica:** en el servidor **no se le puede cobrar la primera cuota a un
alumno nuevo**. Es el defecto que bloqueaba el go-live y está arreglado en el
repositorio, pero no desplegado.

### Por qué no está desplegado

Se cortó a propósito el 30/08: la suite estaba en rojo por la primera cuota incompleta
y no se sube con una prueba fallando. Ese defecto ya se cerró (`846347f`), así que
**hoy no queda motivo para no desplegar** salvo decidir el momento.

El servidor quedó, eso sí, en un estado **consistente**: está en el commit anterior al
`wip`, no a mitad de camino.

### Cómo se despliega

Con `scripts/deploy.sh`, que ya se estrenó y tiene vuelta atrás probada. Corre como
usuario `wings` y pide la contraseña de `wings_migrate` sin mostrarla.

---

## 2026-08-30 (tarde y noche) — Claude CAB

**Objetivo:** dejar el sistema publicado y seguro, y verificar el trabajo de Codex.

### Lo que se hizo

**Servidor.** Wings quedó en línea en `https://wings.gestionar-te.com.ar`. El bloqueante
era PHP 7.4 —el proyecto exige 8.2— y se resolvió con el repositorio Remi, que instala
en paralelo sin tocar el PHP que usa el panel CWP. Base con usuario de privilegio
mínimo, certificado de Let's Encrypt, redirección desde HTTP y `wings:preflight`
aprobado en sus 12 verificaciones.

**Backups.** Diarios, cifrados, con rotación y subida a Google Drive por rclone.
**La restauración se probó**: se recuperó en una base descartable y coincidieron las 15
tablas. La clave de cifrado quedó también fuera del servidor, sin eso los respaldos
serían inservibles justo cuando se los necesita.

**Seguridad.** Las 44 advertencias de dependencias bajaron a **cero**. Se midió la CSP
en modo reporte y se cerraron **55 de las 85 violaciones** sacando el bloque de código
incrustado del layout general, que estaba en todas las pantallas. La vista solo perdió
líneas: 160 eliminadas, 0 agregadas.

**Documentación separada.** El servidor y las credenciales salieron de este repositorio:
son de la plataforma Gestionar-te, no del producto. Quedan en
`D:\CAB Consultores\Gestionar-te\VPS`, y en el repo solo un puntero.

**Contratos nuevos:** reportes del administrador, y la regla de que cobrar dejando una
deuda anterior avisa pero no bloquea.

### Verificaciones del trabajo de Codex — resultado real

| Tarea | Verificación |
|---|---|
| Superadmin protegido | **Correcta.** Probado con dos cuentas: el admin común no la ve, no la edita, no le cambia la clave ni el rol, y la base confirma que nada cambió |
| Dependencias | **Correcta.** 0 advertencias, solo cambió `composer.lock`, recibo real generado y válido |
| CSP en modo reporte | **Correcta.** Header solo de reporte, ninguna vista tocada |
| `deploy.sh` | **Correcta.** Corrí su prueba de vuelta atrás: ante una migración fallida restaura el commit y sale de mantenimiento. Después se estrenó en un despliegue real |
| Condonación | **Correcta.** Verificada contra la base, no contra el informe |

### Errores propios que costaron tres ciclos de Codex

Codex frenó tres veces seguidas, y las tres tenía razón. En el prompt de condonación
afirmé, sin leer el código:

1. Que el servicio exigía el motivo. **No lo exigía**: la validación vivía solo en el
   validador de la API apagada.
2. Que el rechazo al operativo daba 403. **Da 302**, y yo mismo lo había medido esa
   misma mañana en la matriz de roles.
3. Que al condonar el alumno dejaba de ser deudor. **Un alumno sin ningún pago sigue
   siendo DEUDOR**, según `CobranzaEstadoService.php:234` y el contrato §3.

**El mecanismo de la falla:** los gatillos de `AGENTS.md` §6c hablan de "antes de
afirmar". Un prompt no se vive como una afirmación sino como una instrucción, así que la
regla no se activa. Pero **cada criterio de aceptación es una afirmación sobre cómo se
comporta el sistema**, y se estaban redactando por expectativa y no por verificación.

**Corrección adoptada:** cada criterio de aceptación lleva al lado con qué se verificó.
Si no se puede escribir el comando que lo comprobó, no se escribe el criterio.

### Estado al cerrar

Tres commits subidos: condonación y precio positivo terminados, **primera cuota
incompleta y marcada `wip` con una prueba en rojo**. El descuento de primera cuota se
aplica al cobro pero no a la deuda cuando esa deuda nace en el mismo cobro: el alumno
paga lo que se le pidió y queda debiendo la diferencia.

**El servidor no tiene nada de esto**: sigue en el commit anterior, con la suite verde.

**Siguiente paso:** cerrar la primera cuota, rearmar `wings_test` desde cero —quedó con
datos cargados a mano por los tres— y retomar la prueba funcional. Después, el simulador
de tres meses especificado en `SIMULADOR-TRES-MESES-V1.md`.

---

## 2026-08-30 — Claude CAB

**Objetivo:** arranque de jornada en la máquina de casa: bajar el repo, leer
`AGENTS.md` y `CLAUDE.md` antes de tocar la base, y dejar el entorno sincronizado.

**Cambios:** ninguno de código. Se aplicó en la base local la única migración
pendiente (`2026_08_27_000001_normalizar_niveles_catalogos`) y se corrió el setup
post-pull del checklist (`composer install`, `npm install`, `npm run build`).

### Decisiones y hallazgos

**1. No se hizo dump, en ninguna de las dos direcciones.** Exportar quedó prohibido
por `AGENTS.md` §3. Importar no correspondía: el pull no modificó `database/dump.sql`.
La instrucción vieja que tenía Claude en memoria —dumpear antes de cada commit, y
además sin `--ignore-table`— quedó corregida.

**2. El hook `pre-commit` está limpio en CAB.** Solo existe el `.sample` de Git. El
paso A1 del checklist ya está cubierto en esta máquina; no hay nada que auto-exporte.

**3. `database/dump.sql` sigue trackeado.** La tarea 1.2 continúa abierta, como
indica la bitácora de Codex.

**4. Corrección de premisa sobre el dump.** `AGENTS.md` §3 justifica sacarlo diciendo
que contiene "datos personales reales de alumnos". Se verificaron filas concretas: los
36 alumnos son generados por seeder (DNI correlativos desde `3000000x`, celulares
correlativos, emails vacíos). **No hay datos de chicos reales.** Lo que sí hay y no
debería estar en un repositorio son **11 sesiones y 3 access tokens**. La decisión de
sacar el dump sigue siendo correcta; el motivo escrito no lo es y conviene corregirlo
para que nadie lo discuta después desde una premisa falsa.

**5. Contradicción detectada, no resuelta.** La migración de niveles trata
`Principiantes` como nombre legacy a eliminar cuando no tiene grupos asociados, pero
`CatalogosSeeder.php:36` lo crea como uno de los tres niveles canónicos. En CAB no
tuvo efecto porque `Principiantes` tiene 2 grupos y quedó conservado. El riesgo
aparece en un `migrate` sobre una base ya sembrada donde ese nivel todavía no tenga
grupos: se borraría un catálogo válido en silencio. No se modificó nada — queda para
que lo defina quien es dueño de esa tarea.

**Verificación:** 38 pruebas y 130 aserciones aprobadas; 127 rutas; migración aplicada
sin borrar filas y los tres niveles (`Principiantes`, `Intermedias`, `Avanzadas`)
intactos después de correrla; build de Vite correcto.

**Siguiente paso:** tarea 1.2 sigue en manos de Codex. Del lado de Claude, pendientes
del cierre anterior: nombre de grupo vacío en el detalle de liquidación (llega a los
recibos de profesores) y `fecha_alta` ausente del formulario de alumno.

---

## 2026-08-31 (tarde) — Claude CyE

**Objetivo:** dejar de escribir reglas que dependen de que alguien las lea, y construir
controles que corran solos.

### El problema de fondo, marcado por Carlos

Venia escribiendo mis propias reglas en `AGENTS.md`, que es el archivo de Codex y que
**no se me carga solo**. `CLAUDE.md` si se carga, y lo tenia abandonado. Escribir la
regla se sentia como accion y no cambiaba nada.

### Lo que se construyo

**1. Hook `commit-msg` — guardia del diseno.**

Si un commit toca `resources/views` o `resources/css`, la maquina lo rechaza salvo que
el mensaje lo declare con `Diseno-autorizado: <motivo>`.

**No prohibe tocar el diseno: exige justificarlo.** Si el cambio esta pedido, se declara
y pasa, y el hook lista que archivos entraron.

**Probado en las dos direcciones:** sin declarar rechaza y no crea el commit;
declarandolo pasa. Se instala por maquina con `scripts/hooks/instalar.sh`, porque los
hooks viven en `.git/` y no se versionan.

**2. `DocumentacionNoMienteTest`.**

Pone la suite en rojo si `ESTADO-ACTUAL.md` o `CHECKLIST-CARLOS.md` declaran una
cantidad de pruebas distinta de la real.

**Ya se probo solo, dos veces:** al agregar la prueba, y despues al revertir un commit
de prueba. Las dos veces la suite se puso en rojo hasta actualizar el documento.

Los patrones estan anclados a la linea exacta: uno suelto agarraba el "268 pruebas GET"
de la matriz de permisos en vez del numero de la suite.

**3. Reglas propias movidas a `CLAUDE.md`**, con el caso real que origino cada una.

### Documentacion sincerada

`ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md` reescritos contra el
estado verificado. `ESTADO-ACTUAL` suma una definicion explicita de **publicado para
pruebas** contra **en produccion**, que faltaba: el sistema responde por HTTPS pero
nadie del club lo usa.

### Verificacion del indice de Codex

De sus afirmaciones, verifique estas:

| Afirmacion | Resultado |
|---|---|
| `DemoSeeder` no cumple `DATASET-SEEDER-V1` | **Cierta.** Crea 19 alumnos, la spec pide 15, y no verifica ni un estado esperado |
| `DemoSeeder` reexporta el dump solo | **Cierta.** `:691-692` corre `mysqldump` |
| No hay integracion continua | **Cierta** |
| `dump.sql` sigue versionado | **Cierta** |

### Agujero propio del procedimiento

**Desplegue el sistema al servidor y no deje registro de que commit quedo corriendo.**

`deploy.sh` si imprime el SHA en su ultima linea, pero eso queda en la pantalla de quien
desplego y se pierde. Ningun log dice que version esta en produccion, asi que la unica
forma de saberlo es entrar al servidor a mirar.

**Corregido:** `deploy.sh` ahora escribe fecha, SHA y asunto del commit en
`storage/logs/despliegues.log` del servidor. No depende de que alguien se acuerde.

**Sigue pendiente:** verificar que commit esta corriendo hoy. No tengo acceso al
servidor desde la maquina de CyE. El ultimo dato registrado es del 30/08 y decia que el
servidor estaba en un commit anterior a los arreglos de la primera cuota.

**Siguiente paso:** bloque A del plan — rearmar `wings_test` e implementar el seeder de
prueba segun `DATASET-SEEDER-V1.md`. Destraban la prueba humana. El simulador de tres
meses es independiente: arma su propia base descartable y sus propios 20 alumnos.

---

## 2026-08-31 — Claude CyE

**Objetivo:** verificar el cierre de la primera cuota y sincerar la documentacion.

### Verificacion del commit `846347f`

| Chequeo | Resultado real |
|---|---|
| `--filter=CobrarPrimeraCuota` | 3 de 3, 22 aserciones |
| Suite completa | 76 pruebas, 313 aserciones |
| Vistas y CSS | 0 archivos tocados |
| Camino operativo | Fix presente en `registrarPagoCuotaOperativo:52` |
| Camino admin | Fix presente en `registrarPagoCuotaAdmin:141` |
| Camino FIFO | `validarFifo` recibe y propaga el monto descontado |
| Prueba que lo cubre | `test_admin_y_fifo_autocrean_deudas_con_monto_descontado` |

**Aprobado.** El enfoque es el correcto: la deuda nace con el monto descontado en vez
de nacer mal y corregirse despues. El parche a posteriori no podia funcionar, porque
para entonces `monto_pagado` ya bloqueaba el UPDATE.

### Hallazgo que se me habia escapado, detectado por Codex

**El dump tiene dos puertas.** Desactive el hook de git el 25/08 y di el mecanismo por
cerrado. **`DemoSeeder.php:691-692` reexporta el dump solo**, corriendo `mysqldump`
sobre `database/dump.sql`. No busque la segunda puerta.

### Error propio corregido: detectar no es cerrar

Detecte que `ESTADO-ACTUAL.md` declaraba 14 pruebas cuando hay 76, y que describia
como abierto un defecto ya cerrado. **Lo reporte y no lo arregle.** Carlos lo marco:
si no se actualiza, no se actualiza solo, y la proxima orden se escribe sobre
informacion falsa. Ya me paso dos veces.

**Corregido en este turno:**

- `ESTADO-ACTUAL.md` reescrito con el estado verificado, mas una definicion explicita
  de "publicado para pruebas" contra "en produccion", que no estaba y se prestaba a
  confusion.
- `CHECKLIST-CARLOS.md`: servidor, dominio y backups pasan a resueltos; se corrigio el
  procedimiento de levantar la base, que todavia mandaba a importar el dump; la seccion
  de SSH paso a referencia.
- `PLAN-PRODUCCION.md`: D1 y D2 marcados cerrados, fechas vencidas eliminadas, y la
  lista de lo que falta con cuales bloquean el go-live y cuales no.
- **Regla nueva en `AGENTS.md` §6d:** una tarea no termina cuando el codigo funciona,
  sino cuando todo lo que el cambio volvio falso quedo corregido. Antes de cerrar,
  preguntarse que documento se acaba de dejar mintiendo.

**Siguiente paso:** el seeder de prueba segun `DATASET-SEEDER-V1.md`. Es el que
destraba la prueba humana y el simulador de tres meses.

---

## 2026-08-28 — Claude CyE

**Objetivo:** revisar una por una las 12 tareas del bloque 1, preguntando de cada
una: qué hace, para qué, si el problema es real, si omitimos algo, y qué aporta a
dejar el sistema funcionando en el servidor.

### Contexto nuevo que cambió decisiones

Carlos informó la arquitectura destino: un dominio `gestionar-te.com.ar` con la web
comercial, y subdominios por cliente. Wings es un caso particular con base propia;
el futuro `gestionclubes` será multi-inquilino sobre base compartida.

**Consecuencia inmediata verificada:** `SESSION_DOMAIN` debe quedar vacío. Si se
pone el dominio padre, la cookie de sesión vale para todos los subdominios y un
cliente de gestionclubes llevaría sesión válida hacia el sistema de Wings. Entra
como chequeo del módulo 1.7.

**Consecuencia 2:** el servidor se arma para varios sitios desde el principio —una
carpeta por sitio, un nginx por subdominio, certificado wildcard— no para uno solo.
Rehacerlo después cuesta; hacerlo bien de entrada, no.

### Cambios al plan

| Qué | Decisión |
|---|---|
| **1.2** sacar el dump | **Movida al go-live** como paso 7.3b. No aporta nada a que el sistema funcione; su valor aparece justo antes de cargar datos reales |
| **1.6b** CSP | **Reformulada y sube a prioritaria.** Medido: 1.054 estilos inline en 66 vistas, 447 valores distintos, y 24 archivos con script incrustado |
| **1.7 + 1.8** | **Fusionadas en un módulo.** Eran la misma lista vista dos veces; separadas se desincronizan |
| **1.10** asistencias | **Separada en dos prioridades** |
| Estilos inline | **A sección 8**, mejoras posteriores al go-live |

### El hallazgo que abarató la CSP

Un estilo inline no ejecuta código, solo pinta. El riesgo real es el JavaScript.
Entonces: `script-src` estricto y `style-src` permisivo. Hay protección contra
inyección **y no se rompe una sola pantalla**. Los 1.054 estilos quedan como están.

Eso baja la tarea de "rehacer el diseño" a "ordenar 24 archivos de JavaScript".

Sacar los estilos inline pasa a ser un problema de **mantenimiento**, no de
seguridad: hoy cambiar un color implica tocar 66 archivos. Va después del go-live y
con Carlos mirando, porque toca vistas y CSS.

### Verificación que corrigió una afirmación mía

Sobre la tarea 1.10 afirmé que se podía marcar asistencia de un alumno ajeno.
Carlos observó que el profesor solo ve a los de su grupo. **Verificado en
`ClaseWebController.php:283`**: la lista se arma con `where('grupo_id', ...)` y solo
alumnos activos. Por pantalla no es alcanzable.

La tarea queda partida: el **guardado a medias es ALTA** —pasa sin que nadie haga
nada raro, y alimenta la liquidación del profesor— y el **alumno ajeno es BAJA**,
porque requiere armar un pedido a mano salteando la interfaz.

### Regla de negocio confirmada

La regla de cuándo aplica un cambio de plan estaba escrita pero sujeta al visto
bueno del cliente. **Vanina lo dio.** Asentado en
`Wings-contrato-estadosAlum-cobranza-asistencia-V1.md`.

Bajar aplica el mes siguiente si ya asistió a alguna clase este mes, y el mes en
curso si no asistió. Subir aplica siempre en el mes en curso.

### Contraseñas de entrega

Se descartó el patrón `V4n1n4*2026`: es el nombre de la persona con sustituciones
típicas más el año, que es el primer patrón que prueban las herramientas, y lo
adivina cualquiera que la conozca.

**Criterio adoptado:** frases de tres palabras del tipo `gimnasia-patin-jueves`.
Más fáciles de recordar y tipear, y más resistentes. Una por persona, sin cambio
periódico obligatorio.

### Estado del bloque 1

Doce tareas revisadas: 3 cerradas, 9 pendientes.

**Bloqueantes:** 1.5 crear-admin, 1.6b CSP, 1.7 configuración, 1.9 FIFO, 1.9b
atomicidad del plan.
**Altas:** 1.6 headers, 1.10 guardado de asistencias, 1.11 login, 1.13 cierre.
**Bajas:** 1.12 tabla muerta.

**Siguiente paso:** decidir si se revisan los bloques 2 a 6 con el mismo método, o
se pasa directo al rediseño del plan por prioridades.

---

## 2026-08-28 — Claude CyE

**Objetivo:** corregir un patrón propio de afirmar hechos sin verificarlos.

**Error 2, detectado por Carlos:** afirmé que el dump exponía datos personales de
36 menores reales. Los alumnos los había creado un seeder anterior: DNI
correlativos desde `30000001`, celulares `11-4500-0001`, emails vacíos, token
llamado `test`. Llegué a la conclusión con un `COUNT` y `SUM(edad<18)`, **sin mirar
una sola fila**. Un `LIMIT 5` lo resolvía; lo corrí recién cuando me corrigió.

**Error 1, mismo patrón:** relaté AUD-021 como crítico heredando la severidad de la
auditoría. Un grep mostraba que solo vive en un controlador de API y la API está
apagada. No era alcanzable.

**Consecuencia real:** decisiones del proyecto discutidas sobre datos falsos, y
tiempo perdido.

**Corrección:** protocolo de verificación agregado a `AGENTS.md` sección 6c, con
los dos casos documentados como ejemplo. Cinco reglas: contar no es mirar;
severidad sin alcanzabilidad es ruido; separar verificado de inferido; reportar en
vez de alarmar; heredar un hallazgo es inferir, no verificar.

**Reclasificación:** **B2 baja de crítico a higiene.** No hay fuga de datos
personales, no hay que limpiar la historia de Git, no hay que rotar nada con
urgencia. La tarea 1.2 sigue valiendo, pero por otros motivos: el día que se carguen
alumnos reales ese archivo empieza a tenerlos, son 500 KB que cambian enteros en cada
commit, y el seeder ya lo reemplaza.

**Siguiente paso:** actualizar el plan con la reclasificación de B2.

---

## 2026-08-26 — Claude CyE

**Objetivo:** verificar el cierre de las tareas 1.3 y 1.4 reportado por Codex.

**Verificación:** sobre una base descartable `wings_verif`, con el comando literal
`migrate:fresh --seed`. La base de trabajo no se tocó y la descartable se eliminó al
terminar.

- Conteos 8 / 15 / 5 / 2 / 3 / 0 / 0: coinciden con el reporte.
- Niveles exactamente 3: Principiantes, Intermedias, Avanzadas.
- Solo corre `CatalogosSeeder`.
- Segunda ejecución sin cambios.
- Guarda de producción: `DemoSeeder` aborta con `RuntimeException`.
- Suite 38 tests, 130 aserciones.
- `git diff --stat -- resources/views resources/css` vacío.
- La migración de normalización cuenta los grupos antes de borrar y conserva el nivel
  si tiene alguno. La base de Carlos tiene `Principiantes` con 2 grupos: protegido.

**Resultado:** 1.3 y 1.4 cerradas. **B1 cerrado**: una instalación nueva ya no crea
cuenta con contraseña conocida ni movimientos de cashflow de prueba.

**Siguiente paso:** tarea 1.2, sacar `dump.sql` del repositorio. Estaba esperando que
existiera este seeder.

---

## 2026-08-26 — Claude CyE

**Objetivo:** primera verificación de la tarea 1.3, reportada como terminada.

**Resultado: rechazada.** Tres defectos que el reporte no detectó:

1. `DatabaseSeeder` nunca llamaba a `CatalogosSeeder`. El criterio "base vacía
   correcta" se había validado con `--class=CatalogosSeeder`, no con el
   `migrate --seed` literal.
2. El seeder nuevo no reemplazaba a los viejos, se sumaba: rubros 6 a 9, subrubros
   14 a 21, tipos de caja 3 a 6.
3. Los niveles quedaban en 5. Era la misma contradicción que Codex había reportado
   dos días antes, sin resolver.

**Decisión:** 1.3 y 1.4 se resuelven juntas. El criterio de aceptación nuevo nombra
el comando exacto y prohíbe invocar seeders a mano, porque el error estuvo en el
método de validación, no en el criterio.

**Siguiente paso:** reenviar la corrección a Codex.

---

## 2026-08-26 — Claude CyE

**Objetivo:** corregir el plan de producción, que era irreal.

**Punto de partida verificado:** D1 al 15%. Solo cerradas 1.1 y 1.11b. Faltaban 44
horas de trabajo contra dos días. Sin acceso SSH, D2 bloqueado.

**Hallazgo:** faltaba un bloque entero. `docs/06-pruebas/DATASET-SEEDER-V1.md`
especifica el seeder de prueba (15 alumnos, 5 cajas, 22 clases, 3 liquidaciones,
7 horas) y nunca había estado en el plan. El recorrido funcional lo necesita.

**Hallazgo 2:** verificado en `GenerarDeudasMensualesCommand.php:84-101`, el proceso
mensual genera la cuota solo si el alumno tuvo asistencias el mes anterior, o se dio
de alta hace menos de 15 días y ya pagó. Una base recién cargada no cumple ninguna:
manda a todos a la cola de revisión y no genera ninguna deuda. **La cuota del primer
mes se carga a mano junto con los datos iniciales.**

**Decisiones:**

- Son dos seeders con objetivos opuestos: el de catálogos va ahora, el de prueba al
  final, como dice su propia especificación.
- Fecha expresada como función del bloqueante real: go-live = acceso SSH + 5 días
  hábiles. Con el fin de semana disponible, martes 1/09.
- Archivo renombrado a `PLAN-PRODUCCION.md`; la fecha en el nombre ya no aplicaba.

**Siguiente paso:** conseguir el acceso SSH, que mueve la fecha.

---

## 2026-08-25 — Claude CyE

**Objetivo:** verificar los 57 hallazgos de la auditoría v03 uno por uno contra el
código actual, en vez de heredar sus severidades.

**Resultado:**

- **Cuatro ya estaban cerrados** por el ciclo de Codex: AUD-014 (locks en
  `PagoCuotaService:351,365`), AUD-016 (`validarCaja` con transacción y lock),
  AUD-017 (borra imputaciones al cancelar), AUD-024 (filtra `ESTADO_COMPLETADO`).
- **Uno nuevo entra como bloqueante B12** (AUD-012): en `CajaWebController::pagar()`
  el cambio de plan se graba en la línea 631 y el pago se ejecuta en la 674, sin
  transacción común. Arreglar el FIFO aumenta los rechazos, o sea que multiplica los
  planes huérfanos. **1.9 y 1.9b van juntas.**
- **Seis quedan abiertos y no bloquean**, cada uno por un motivo verificado. AUD-021
  no es alcanzable: solo vive en `PagoCuotaController`, que es de API, y la API está
  apagada. AUD-020 y AUD-018 son del módulo de liquidaciones, que arranca vacío y no
  se usa hasta fin de septiembre. AUD-025 no tiene ruta `DELETE` que lo alcance.

**Lección registrada:** severidad sin alcanzabilidad es ruido. Relaté las severidades
de la auditoría antes de comprobar si eran alcanzables, y eso generó alarma
injustificada sobre AUD-021.

**Siguiente paso:** reflejar todo en el plan.

---

## 2026-08-25 — Claude CyE

**Objetivo:** responder si había errores funcionales, y crear `AGENTS.md`.

**Verificación ejecutada:**

- 0 errores de sintaxis PHP en `app/`, `database/`, `routes/`, `config/`.
- 127 rutas cargan; las 54 vistas referenciadas existen; 0 `@include` rotos.
- **268 pruebas GET × 4 roles: 0 errores 500, 0 accesos indebidos.** ADMIN 59 rutas,
  OPERATIVO 19 con las 47 de admin cerradas, PROFESOR solo `clases` y `clases/{id}`,
  anónimo solo `login`. Coincide con `PERMISOS-ROLES.md`.

**Bug encontrado y corregido:** `clases/index.blade.php` declaraba cuatro funciones
PHP sueltas y `liquidaciones/index.blade.php` una más. Al renderizarse dos veces en
el mismo proceso, PHP tira error fatal por redeclaración. En producción con PHP-FPM
no rompe, pero bloqueaba el smoke test y cualquier corrida en CI. Corregido con
`function_exists`: 10 líneas de guardas, cero markup, cero CSS.

**Cambios:** creado `AGENTS.md` con el diseño como regla dura. Codex lee `AGENTS.md`,
no `CLAUDE.md`, así que hasta entonces entraba al repo sin ninguna regla.

**Decisión:** desactivado el hook `pre-commit` (tarea 1.1). Renombrado, no borrado.
Reexportaba la base con datos de alumnos en cada commit.

**Siguiente paso:** el hook sigue armado en la máquina de la casa. Los hooks no se
versionan.
