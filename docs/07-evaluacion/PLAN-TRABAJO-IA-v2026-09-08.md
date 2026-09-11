# Wings — Plan de trabajo para IA

> **Version:** 2026-09-08.v4
> **Fecha de corte:** 8 de septiembre de 2026
> **Commit evaluado:** `97fb840`; cruce documental hasta `ad0491f`
> **Fuentes principales:** `Evaluacion Codex 8-9-26.md`, `Evaluacion Claude 8-9-26.md`,
> bitacoras y commits del 5 al 8 de septiembre.
> **Estado:** plan vigente. Reemplaza los indices anteriores como orden de trabajo.

## 0. Reglas de uso

1. Leer `AGENTS.md`, `CLAUDE.md`, ambas bitacoras y este documento antes de ejecutar.
2. Una tarea por vez y un commit por tarea. No mezclar correccion, rediseño y mejoras.
3. Reproducir primero todo hallazgo que las evaluaciones no reprodujeron.
4. Si el codigo contradice este plan, frenar segun `AGENTS.md` §6b.
5. Las tareas con vistas requieren autorizacion escrita de Carlos y las lecturas de diseño.
6. No usar `gestion_wings` ni la base del servidor para pruebas destructivas. Usar MariaDB descartable.
7. Cada cierre actualiza pruebas, contratos, estado, checklist y la bitacora del agente.
8. Claude verifica los commits de Codex y registra el resultado real en `LOG-CLAUDE.md`.

El índice HTML para Carlos conserva una copia local y permite trasladar las marcas
entre computadoras mediante un enlace o un archivo JSON exportado.

## 1. Indice ejecutivo

| Orden | Bloque | Objetivo | Salida |
|---:|---|---|---|
| 0 | Cierre del fin de semana | Sincerar servidor, base y documentos | Punto de partida reproducible |
| 1 | Cobros que pueden mentir | Reproducir y corregir tres caminos de cobro | Cobro, deuda y plan consistentes |
| 2 | Integridad financiera | Cerrar recibos, concurrencia e historia | Plata trazable e idempotente |
| 3 | Seguridad y recuperacion | Cerrar garantías exageradas y controles operativos | Recuperacion y alertas reales |
| 4 | Prueba integral | Recorrer el sistema completo con tres roles | Evidencia de aceptación actual |
| 5 | Entrega solicitada | Completar pedidos concretos de Carlos | Producto presentable y operable |
| 6 | Posterior | Reportes y evolución no bloqueante | Backlog separado |

## 2. Bloque 0 — cerrar lo hecho el fin de semana

### FDS-01 · Sincerar documentos de estado — CERRADA 08/09

**Ejecuta:** Codex CAB. **Estado:** cerrada y pendiente de verificacion por Claude.
**Diseño:** no.

- Corregir `ESTADO-ACTUAL.md`, `PLAN-PRODUCCION.md` y `CHECKLIST-CARLOS.md` contra lo
  verificado el 8/9.
- Deben reflejar: servidor en `9fdd03d`, Vanina ADMIN creada, base de entrega minima,
  129 pruebas sobre MariaDB, dump retirado, Cloudflare activo y Cobranza accesible al
  OPERATIVO.
- Quitar como abiertos: motor SQLite, dump, H-DI-01, boton Cobrar, doble plan activo y
  las cifras viejas de pruebas.
- Registrar como abiertos: npm, monitoreo, limites del backup, cobros del bloque 1 y
  procedimiento reproducible de entrega.

**Aceptacion:** ningun documento vigente contradice esos hechos ni presenta una
verificacion historica como actual.

**Resultado:** `ESTADO-ACTUAL.md`, `PLAN-PRODUCCION.md` y `CHECKLIST-CARLOS.md`
quedaron alineados al corte 08/09. Los bloques del plan de agosto dejaron de figurar
como orden vigente.

### FDS-02 · Revalidar servidor y operacion del fin de semana — CERRADA 09/09/2026

**Ejecuta:** Codex CyE; acceso por Personal recuperado el 09/09. **Prioridad:** inmediata.

- Confirmar commit desplegado, migraciones, modo produccion y preflight.
- Confirmar el estado minimo de la base sin exponer datos personales ni credenciales.
- Confirmar que scheduler y backups siguen ejecutandose.
- Confirmar por una falla controlada que la copia externa produce una señal observable.
- Verificar el monitoreo externo y operativo en funcionamiento; no inferirlo del repositorio.

**Aceptacion:** evidencia fechada en bitacora y sin secretos. Lo no comprobado queda
marcado como tal.

**Evidencia ya existente — no volver a resolverla desde cero:**

| Parte | Commit que la registro |
|---|---|
| Servidor, Cloudflare, scheduler y backups | `3470114` |
| Deploy, migraciones y preflight | `d859c6e` |
| Base minima de entrega y usuarios | `306fa19` |
| Revalidacion integral del 08/09 | `4e1674e` |

**Avance del 08/09:** monitor HTTPS externo activo en Better Stack con email; heartbeat
de scheduler y heartbeat de backup creados. En el repositorio quedaron los wrappers
para informar exito/fallo y Telegram sin versionar secretos. La prueba aislada pasa.

**Cierre 09/09:** scripts de `a3ddd7f` desplegados dentro de `81f27ef`; cron y
respaldo operativo instalados con helper. Configuracion secreta fuera de Git.
Preflight: 12 controles correctos; migraciones Ran. Fallos controlados del
scheduler (11:27 GMT-3) y copia Drive (11:28), seguidos de recuperacion real.
Copia Drive correcta 14:29:33 UTC. Monitor HTTPS y ambos heartbeats Up.
Carlos confirmo email y Telegram. Evidencia detallada en LOG-CODEX.md.
No se simulo caida HTTPS. La base minima conserva evidencia historica del 07/09,
no se volvio a inspeccionar su contenido; corrida mensual del 01/09 no demostrada.

### FDS-03 · Hacer reproducible el estado minimo de entrega — PAUSADA 09/09

Carlos ordeno frenarla: la base ya contiene carga real. Lo siguiente conserva el
alcance anterior como contexto, NO es una orden ejecutable. Requiere redefinicion.

**Ejecuta:** IA; decision de Carlos si requiere automatizacion. **Prioridad:** alta.

- Documentar exactamente que se conserva y que se limpia para preparar una entrega.
- No crear un seeder de datos reales ni borrar datos por inferencia.
- Decidir con Carlos si alcanza un procedimiento manual versionado o si hace falta un
  comando idempotente y protegido contra produccion.

**Aceptacion:** otra maquina puede reconstruir el mismo estado sin memoria del chat ni
scripts temporales.

### FDS-04 · Revalidar lo corregido durante la prueba humana — VERIFICADA 11/09/2026

Evidencia: `docs/06-pruebas/FDS-04-2026-09-11.md`. Bloqueo total de catalogos
reservados autorizado por Carlos, incluida observacion. FIN-01 no incluido.

**Ejecuta:** IA y Carlos. **Prioridad:** antes del bloque 4.

- Confirmar por pantalla Cobranza para ADMIN y OPERATIVO; PROFESOR rechazado.
- Confirmar boton Cobrar funcional y rubros reservados protegidos.
- Conservar como evidencia separada lo que ya paso y lo que se vuelve a probar.

## 3. Bloque 1 — cobros que pueden informar exito con datos incorrectos

Estas tareas se ejecutan en este orden. Cada reproduccion debe mirar pantalla, respuesta,
filas concretas y saldos; un test unitario aislado no reemplaza el flujo web.

### COB-01 · Monto con separador de miles — VERIFICADA 10/09

**Estado:** corregida por Claude CAB en `caa4976` y verificada por Codex CyE en
navegador; aclaracion de alcance confirmada por Carlos el 10/09.
**Diseño:** no se toco ninguna vista.

**Mecanismo confirmado:** el script de la vista va en `@push('scripts')` y se registra
durante el parseo; `ds-app.js` entra por `@vite` como modulo y se registra despues. Al
enviar, el handler de la vista corre primero y arma `new FormData(...)` cuando el campo
todavia dice `28.000`; la limpieza de `stripMoneyInputs` llega tarde. En el servidor
`is_numeric("28.000")` es `true` y `(float)` lo convierte en `28`. Cobraba 28 en lugar de
28.000, sin error y con mensaje de exito.

**Correccion:** normalizacion en el servidor antes de validar, en
`CajaWebController::pagar()`. No depende del orden de carga de scripts y no toca vistas.
Regresion en `CobrarPrimeraCuotaWebTest::test_el_cobro_web_interpreta_el_monto_con_separador_de_miles`,
que envia el payload real del navegador. Alcance auditado: era el unico formulario roto,
porque es el unico que arma `FormData` en un handler de `submit`.

Verificado que la normalizacion cubre uno y dos separadores y el valor ya limpio:
`28.000`, `1.500.000`, `1.234.567` y `30000` quedan correctos. El caso de dos
separadores antes lo rechazaba la validacion, porque `is_numeric("1.500.000")` es
`false`; ahora entra bien.

**Verificacion 09/09, Codex CyE:** comparacion real por Chrome en bases descartables
de `caa4976^` y `caa4976`. Request conserva `28.000` y `1.500.000`; antes guarda 28
o rechaza 422, despues importes correctos en cadena y PDF. Resumen $1.528.000.
Reporte: `docs/06-pruebas/COB-01-VERIFICACION-2026-09-09.md`.
**Aclaracion 10/09:** «arqueo» significaba el resumen por medio de pago ya
verificado. Freno levantado; no agregar funcionalidad. La verificacion no acredita
despliegue en produccion ni sincronizacion GitHub.

Pasos originales, conservados como referencia de la prueba realizada:

1. Reproducir un cobro mostrado como `30.000` y comprobar el importe persistido.
2. Escribir regresion que reproduzca el payload real del navegador.
3. Corregir el orden o la normalizacion para que el servidor nunca interprete `30.000`
   como 30.
4. Probar importes con uno y dos separadores, decimales y entrada sin formato.

**Aceptacion:** pantalla, pago, deuda, caja y recibo coinciden en 30.000.

### COB-02 · Cambio de plan elegido fuera del formulario — VERIFICADA 10/09

**Estado:** reproducida y corregida por Claude CAB; verificada por Codex CyE en
navegador sobre 5be4970. **Diseño:** Carlos autorizo el cambio el 10/09, despues de que se le
explicara el alcance exacto.

**Que pasaba:** el selector de plan estaba dibujado **fuera** del formulario. Un form
solo envia los campos que tiene adentro, asi que `new FormData(cobrarForm)` no juntaba
`nuevo_plan_id` y la eleccion nunca llegaba al servidor. El JavaScript de la vista si
reaccionaba al clic: pintaba la opcion y actualizaba el monto sugerido. Por eso en
pantalla parecia aplicado.

El backend del cambio de plan ya estaba completo y correcto —distingue subida de bajada
y difiere la bajada al mes siguiente si hubo asistencia— pero nunca se ejecutaba porque
no recibia el dato.

**Daño real:** se cobraba el importe del plan nuevo y el alumno quedaba en el plan
viejo. El mes siguiente la corrida mensual generaba la deuda con el precio anterior, y
todos los meses posteriores tambien, sin ninguna señal.

**Correccion:** se movio la apertura del formulario para que arranque antes del selector
de plan. La etiqueta `<form>` no renderiza nada, asi que **la pantalla no cambia**: el
diff no toca un solo div, clase, estilo ni texto. No se agrego JavaScript ni campos
ocultos, para no volver a depender de que un script llegue a tiempo — que fue la causa
de COB-01.

**Regresion:** `CambioPlanCobroTest::test_el_selector_de_plan_viaja_dentro_del_formulario_de_cobro`
verifica sobre el HTML renderizado que `nuevo_plan_id` quede entre la apertura y el
cierre del formulario. Las 8 pruebas de cambio de plan que ya existian siguen verdes.

**Aceptacion:** el alumno termina en el plan elegido, el monto del periodo es correcto y
un rechazo del cobro no deja el plan cambiado. Los dos ultimos ya estaban cubiertos por
`CambioPlanCobroTest`; el primero es lo que faltaba y ahora esta.

**Resultado:** eleccion enviada, subida y bajada diferida comprobadas; rechazo
revierte plan/deuda sin pago ni movimiento. Capturas identicas antes/despues del
movimiento del form. Reporte COB-02-07-VERIFICACION-2026-09-10.md en docs/06-pruebas.
El defecto de parcial con descuento queda separado a cargo de Claude por Carlos.

### COB-03 · Parcial de otro periodo durante primer pago con descuento — VERIFICADA 10/09

**Verificacion Codex CyE:** sobre `cob-total` en `5238825`. Casos septiembre
existente y virtual: original 28.000, pagado 10.000, saldo 18.000. Pantalla, pago,
imputaciones, caja, resumen por medio y PDF coinciden en 29.600. Caso sin descuento
38.000 correcto. Total visible/cartel corregidos (COB-06 de la rama de Claude).
Suite de esa rama: 133 pruebas, 726 aserciones. Reporte del 10/09 en
`docs/06-pruebas/COB-03-VERIFICACION-2026-09-10.md`. Defecto reproducido en main.
Integrada a `main` el 10/09. **No desplegada:** el servidor sigue en `81f27ef`.

**Alcance original, ya reproducido para esta verificacion:**

**Estado:** reproducida en base y corregida por Claude CAB. Pendiente de verificacion por
otro agente o por Carlos en pantalla. **Diseño:** no se toco ninguna vista.

**Reproduccion:** alumno de alta el 20/08 (regla de segunda quincena, 70%) que paga
agosto con descuento y deja seña de 10.000 de septiembre, con septiembre ya cargado en
28.000. Resultado antes de la correccion: septiembre quedaba con `monto_original` 10.000
y estado `PAGADA`. Los 18.000 restantes desaparecian y el alumno figuraba al dia.

**Eran dos caminos, no uno.** El informe solo nombraba el primero:

1. `ajustarDeudas()` recorria **todos** los items del cobro y bajaba `monto_original` de
   cada uno al monto enviado, aunque el descuento correspondiera a un solo periodo.
   `aplicarPorcentajeAItems()`, justo arriba, si discrimina bien.
2. `$montosOriginalesNuevasDeudas` salia de `array_column($items, ...)`, con todos los
   periodos. Si la deuda del otro mes **todavia no existia**, nacia con el parcial como
   monto original desde `obtenerOcrearDeuda()`. Mismo daño por otra puerta.

**Correccion:** el override de monto original queda restringido al periodo con descuento
en los dos caminos. `ajustarDeudas()` pasa a `ajustarDeudaConDescuento()`, que recibe un
periodo y un monto en lugar de la lista entera: el nombre y la firma ahora impiden
reintroducir el defecto. Regresion en `DescuentoNoAlteraOtroPeriodoTest`.

**Aceptacion:** solo el mes de alta recibe descuento; el segundo periodo conserva su
monto original y saldo restante. Verificado: agosto 19.600 `PAGADA`, septiembre 28.000
con 10.000 pagados, saldo 18.000 y estado `PENDIENTE`.

### COB-04 · Cancelar y volver a cobrar la primera cuota — CORREGIDA 10/09

**Decision de Carlos, 10/09: un pago anulado NO cuenta como primer pago.** Un cobro
cancelado es un cobro que no ocurrio; que alguien se equivoque al cargar no cambia
cuando entro el alumno.

**Estado:** reproducida y corregida por Claude CAB. Pendiente de verificacion en
navegador. **Diseño:** no se toco ninguna vista.

**Lo que pasaba, reproducido:** al cancelar, el pago queda con estado `ANULADO` y la
fila se conserva (`cancelarCobroOperativo()`). La decision del descuento preguntaba
`Pago::where('alumno_id')->exists()` **sin mirar el estado**, en el servicio y en la
pantalla. Un cobro cancelado le sacaba el descuento de bienvenida para siempre.

Reproducido: alumno de alta el 20/08 que adelanta septiembre, se cancela ese cobro por
error, y despues se le cobra agosto — su mes de alta. Cobraba **60.000 en vez de
42.000**.

**Los informes no estaban en desacuerdo: describian casos distintos.** Si el cobro
cancelado era del propio mes de alta, la deuda ya habia quedado en 42.000 y cancelar no
restaura el monto original, asi que el segundo cobro daba 42.000 igual — bien, pero por
accidente, no por decision. Si el cobro cancelado era de otro mes, se perdia el
descuento. De ahi las dos versiones.

**Correccion:** las dos consultas filtran por `ESTADO_COMPLETADO`.

**Y un segundo defecto que aparecio al corregir el primero:** con el descuento ahora
habilitado despues de una cancelacion, `precioConDescuento()` tomaba como base el monto
de la deuda — que podia venir ya descontado del intento anulado — y descontaba dos
veces: 60.000 a 42.000 a 29.400. La base pasa a ser el precio de lista del plan.
Lo detecto la prueba de la cancelacion sobre el propio mes de alta, escrita antes de
tocar codigo justo para eso.

**Barrido:** se reviso donde mas se pregunta por pagos previos.
`CobranzaEstadoService` y `LiquidacionService` ya filtraban bien. Queda anotado como
hallazgo lateral `PagoService::103`, que verifica si existe un pago del mes sin filtrar
estado — vive en el pago de plan mensual, no en el circuito de cuotas.

**Aceptacion:** regla escrita, prueba automatizada y resultado visible coherente.
Cubierto por `DescuentoPrimerPagoMatrizTest`, que llego a diez casos.

**Tercer defecto, encontrado por Codex CyE antes de la verificacion visual:** la primera
correccion cambio la base del calculo en el servicio pero dejo intacta la de la pantalla,
que seguia multiplicando el monto de la deuda por el porcentaje. Con la deuda ya
descontada de un intento anulado mostraba **29.400 mientras el cobro registraba 42.000**.
Codex freno sin tocar nada y consulto.

**Correccion de fondo, no del sintoma:** `precioConDescuento()` pasa a ser publico y la
pantalla lo llama en vez de rehacer la cuenta. **Queda una sola implementacion del
importe.** Ese reparto —cada lado calculando su version del mismo numero— produjo COB-06,
COB-07 y este. Cortarlo vale mas que el arreglo puntual.

### COB-05 · Cierre conjunto del circuito de cobro

**FRENADA 11/09/2026 sobre a9795c6:** subida con 70% anuncia 60.000 y registra
42.000. Sin correccion por orden de Carlos. Reporte:
`docs/06-pruebas/COB-05-VERIFICACION-2026-09-11.md`. FIN-02 sigue sin verificar.

- Probar sin descuento, cada tramo de descuento, parcial, varios periodos, cambio de plan,
  cancelacion y segundo cobro.
- Verificar importes en deuda, pago, imputaciones, movimiento, recibo y estado de cobranza.

**Aceptacion:** suite completa verde sobre MariaDB y recorrido humano corto sin diferencias.

### COB-06 · La pantalla anunciaba un total distinto del que se cobraba — CORREGIDA 10/09

**Origen:** lo encontro Codex CyE verificando COB-03 por navegador. La pantalla decia
$38.000 antes de confirmar y el pago quedaba en $29.600. **Corregida por Claude CAB;
Carlos autorizo el cambio de vista el 10/09.**

**La plata estaba bien.** $29.600 es el importe correcto: $19.600 de agosto con el 70%
mas $10.000 de septiembre. El que mentia era el numero de la pantalla.

**Eran dos defectos encadenados, los dos en la vista:**

1. `calcularTotal()` sumaba los importes de los campos sin aplicar el descuento, que el
   servidor recien calcula al confirmar.
2. Peor: la pantalla ni siquiera anunciaba el descuento. Su guardia exigia que el mes de
   alta fuera **el mes en curso**, mientras que `PagoCuotaService::calcularReglaPrimerPago()`
   solo exige que el mes de alta este entre los periodos que se cobran. Alta en agosto
   cobrando en septiembre: el servidor descontaba y la pantalla no decia nada.

El comentario de `CajaWebController::cobrar()` ya advertia el riesgo textualmente —"si
esta pantalla mostrara un descuento que el cobro no aplica, el operativo cobraria un
importe distinto del que le dijo al alumno"— pero la guardia quedo solo en el anuncio y
con un criterio distinto del real.

**Cual de los dos lados estaba mal:** el de la pantalla. La regla ya estaba decidida y
cubierta por `test_en_un_pago_de_varios_meses_el_descuento_alcanza_solo_al_mes_de_entrada`,
que usa exactamente ese caso y espera que agosto lleve el descuento. No hizo falta
decision de negocio.

**Correccion:** la pantalla pasa a usar el mismo criterio que el servicio —el mes de alta
tiene que estar entre los periodos ofrecidos— y expone el periodo con descuento y el
porcentaje para que `calcularTotal()` los aplique. Los importes por periodo siguen
mostrandose enteros a proposito: es lo que se envia, y el servidor aplica el descuento.

**Regresion:** `DescuentoPrimerPagoSoloDelMesDeAltaTest::test_la_pantalla_anuncia_el_descuento_del_mes_de_alta_aunque_se_cobre_despues`.
Las cuatro pruebas de la regla que ya existian siguen verdes.

**Pendiente:** que Codex repita la verificacion de COB-03 por navegador sobre la rama
`cob-total`, y confirme que el numero anunciado coincide con el registrado.

### COB-07 · Al subir de plan la pantalla anunciaba menos de lo que se cobraba — CORREGIDA 10/09

**Origen:** lo encontro Codex CyE verificando COB-02 por navegador. Subiendo de $40.000 a
$60.000 el plan cambia bien, pero la pantalla anunciaba $40.000 y se registraban $60.000.
**Corregida por Claude CAB; Carlos autorizo el cambio de vista el 10/09.**

**La plata estaba bien.** El servidor eleva la deuda del mes al precio nuevo y cobra eso
(`CajaWebController::pagar()`, rama del cambio de plan). El que mentia era el anuncio.

**Causa:** `calcularTotal()` topea cada importe contra `chk.dataset.saldo`, que se
renderiza con el saldo al abrir la pantalla. El manejador del cambio de plan actualizaba
el importe sugerido del campo pero **no ese tope**, asi que el total quedaba planchado en
el precio viejo.

**Tercero de la misma familia.** COB-01 fue el campo de monto contra el servidor; COB-06,
el descuento; este, el saldo. El patron es siempre el mismo: **la pantalla guarda una
copia del estado del servidor y no la actualiza cuando algo la cambia.** Antes de dar el
circuito por cerrado conviene revisar si queda alguna otra copia con la misma forma —
`data-saldo`, `data-pagado` y `data-precio` son las candidatas.

**Correccion:** `chk.dataset.saldo` se mueve junto con el importe sugerido. Una linea.

**Regresion:** `CambioPlanCobroTest::test_al_cambiar_de_plan_el_tope_del_total_se_mueve_con_el_precio`.
Verifica la linea, no el comportamiento: el total lo calcula el navegador y la suite corre
sin JavaScript. **La comparacion real entre lo anunciado y lo registrado solo se puede
hacer en navegador**, y es lo que queda pendiente.

**Limite conocido, no corregido:** en una bajada de plan con asistencia del mes, el
servidor deja la deuda en el precio viejo y la pantalla sugiere el nuevo, mas bajo. Los
dos numeros coinciden entre si —se cobra el mas bajo y el mes queda parcialmente
impago—, asi que no es el defecto de arriba. Queda anotado por si el recorrido humano lo
levanta como confuso.

### COB-08 · El descuento se aplicaba a la seña y cerraba el mes — VERIFICADA 10/09

**Origen:** lo encontro Codex CyE verificando COB-02. Plan de $60.000, descuento del 70%,
seña de $10.000: cobraba **$7.000 y dejaba la deuda entera PAGADA**, sin saldo.
**Corregida por Claude CAB; Carlos autorizo el cambio de vista el 10/09.**

**Es el peor de la serie.** Los anteriores mentian en pantalla; este pierde plata de
verdad y cierra el mes, asi que nadie lo vuelve a mirar. En el ejemplo quedaban $35.000
sin cobrar.

**Causa:** el porcentaje se aplicaba al importe tipeado en vez de al precio del mes.
`aplicarPorcentajeAItems()` convertia la seña de 10.000 en 7.000, y
`ajustarDeudaConDescuento()` escribia ese 7.000 como monto original del mes: pagado 7.000
sobre debido 7.000 da PAGADA.

**Punto ciego de la correccion de COB-03.** Ahi se restringio `ajustarDeudaConDescuento()`
al periodo correcto pero se le siguio pasando el importe del item, que es lo tipeado por
el factor. Con pago completo da bien y por eso paso. Se achico el daño sin ver la causa.

**Metodo, a pedido de Carlos:** en vez de corregir el caso reportado se escribio primero
la matriz completa de casos que pasan por el descuento —
`DescuentoPrimerPagoMatrizTest`, siete casos por la ruta web real. **Cuatro estaban
rotos, no uno:** parcial con deuda existente, parcial sin deuda previa, el segundo cobro
del saldo restante, y el tramo del 40%. Solo funcionaban el pago completo y el caso sin
descuento.

**Regla que queda fijada:** el descuento baja **el precio del mes**, nunca el importe que
se entrega. Un alumno que entra el 20 con plan de 60.000 debe 42.000; si entrega 10.000
quedan 32.000 pendientes.

**Correccion:**

- `precioConDescuento()` calcula el precio del mes ya descontado, tomando como base la
  deuda si existe o el precio del plan si hay que crearla. Nunca el importe pagado.
- `limitarAlSaldoConDescuento()` recorta el importe al saldo resultante, para que quien
  paga la cuota entera no sea rechazado por enviar el precio de lista.
- `aplicarPorcentajeAItems()` se elimina: era la fuente del defecto.
- La pantalla muestra el mes de alta **ya descontado** y `calcularTotal()` deja de aplicar
  el porcentaje. Pantalla y servidor usan ahora el mismo tope, en vez de calcular cada uno
  su version del mismo numero — que es lo que generaba COB-06 y COB-07.

**Verificacion Codex CyE:** cuatro casos por Chrome sobre d61cf42 correctos:
deuda existente y virtual conservan saldo 32.000 tras seña 10.000; segundo cobro
32.000 cancela; tramo 40% conserva 14.000. Campo descontado y carteles comprobados.
Suite 147 pruebas, 805 aserciones, verde. Reporte en
docs/06-pruebas/COB-08-VERIFICACION-2026-09-10.md. Sin despliegue.

## 4. Bloque 2 — integridad financiera e historia

| ID | Tarea | Prioridad | Condicion de cierre |
|---|---|---:|---|
| **FIN-01** | Evitar que `CatalogosSeeder` desproteja `Cuotas` y `Sueldos` — **NO APLICA 11/09** | — | Ver resultado abajo |
| **FIN-02** | Vincular recibo al pago exacto, no por texto/fecha/importe — **CORREGIDA 11/09** | Alta | Dos cobros iguales con medios distintos generan recibos correctos |
| **FIN-03** | Preservar o reconstruir imputaciones visibles al anular | Alta | PDF anulado conserva periodos, importe, motivo y marca ANULADO |
| **FIN-04** | Definir con Carlos que significa “balance” filtrado | Alta | Contrato define saldo acumulado o resultado del periodo antes de tocar codigo |
| **FIN-05** | Pago concurrente de liquidacion (AUD-018) | Antes del 25/09 | Dos conexiones reales producen un solo pago y un solo egreso |
| **FIN-06** | Comision historica (AUD-020) | Antes del 25/09 | Cambios posteriores del alumno no alteran liquidacion historica |
| **FIN-07** | Cobrar contra condonar simultaneamente | Alta | Locks compartidos; no queda deuda condonada y cobrada a la vez |
| **FIN-08** | Reglas de revision de cobranza | Media | Carlos define parciales, observaciones e importe historico; luego pruebas |
| **FIN-09** | Limites de fechas manuales | Media | Contrato y validaciones impiden imputaciones fuera del rango decidido |
| **FIN-10** | Solapamiento al editar clases (AUD-019) | Alta | Editar aplica el mismo control que crear |
| **FIN-11** | Concurrencia real de cobrar/cancelar/validar | Alta | Pruebas con dos conexiones; `MoneyLockingTest` queda descrito como estructural |

### FIN-01 · Resultado — NO APLICA 11/09

**Decision de Carlos, 11/09:** ese proceso no se va a correr contra el servidor.

**Verificado que no hay camino automatico que lo corra ahi:**

- `scripts/deploy.sh`, el despliegue real del servidor, **no ejecuta seeders**: hace
  mantenimiento, `git pull --ff-only`, Composer, build, migraciones, caches, permisos y
  preflight, con rollback.
- El unico proceso que corria la carga de catalogos solo era `deploy-wings.bat`, un script
  de Windows para XAMPP de febrero, anterior a toda la proteccion de rubros. **No puede
  ejecutarse en el servidor, que es Linux.** Ademas ocultaba el fallo con "puede ser normal
  si ya habia datos". Se elimino a pedido de Carlos: era la trampa exacta que describe
  FIN-01, esperando que alguien lo corriera sobre una base local.

**Lo que queda:** `CatalogosSeeder` sigue existiendo porque es la forma documentada de
armar una base nueva (`migrate --seed`). Correrlo a mano sobre una base con datos sigue
siendo un error; `CHECKLIST-CARLOS.md` lo advierte.

### FIN-02 · Resultado — CORREGIDA 11/09

**Ejecuta:** Claude CAB. Pendiente de verificacion en navegador. **Diseño:** no se toco
ninguna vista.

**Lo que se afirmaba, revalidado contra el codigo antes de tocarlo:**
`ReciboService::obtenerTipoCajaPago()` buscaba el movimiento de caja por texto de
observaciones, importe y fecha, y tomaba el primero. El importe y los periodos del recibo
nunca estuvieron en riesgo —salen del pago directamente—; **lo que podia mentir era el
medio de pago**.

**Caso real, ligado al flujo de COB-04:** se cobra en efectivo, se nota que era
transferencia, se cancela y se vuelve a cobrar el mismo dia por el mismo importe. El
recibo del cobro vigente tomaba el primer movimiento que coincidia —el cancelado— y decia
**Efectivo**. Como Wings no tiene arqueo, esa diferencia en la caja no la ve nadie.

**Correccion:** el movimiento se busca por `pago_id`, que se escribe siempre al crearlo.

**Barrido:** es el unico lugar del sistema que buscaba movimientos por texto. No hay otro
con el mismo patron.

**Regresion:** `ReciboMedioDePagoTest`, tres casos: un cobro simple, dos cobros iguales el
mismo dia con medios distintos, y cancelar y volver a cobrar por otro medio. Los dos
ultimos fallaban antes de la correccion.

**Destraba:** ENT-05, el acceso directo al recibo despues de cobrar, dependia de esto.

## 5. Bloque 3 — seguridad, despliegue y recuperacion

| ID | Tarea | Prioridad | Condicion de cierre |
|---|---|---:|---|
| **SEG-01** | Retirar Axios y actualizar dependencias — VERIFICADA 11/09 | Alta | Audit cero, build y 154 pruebas/920 aserciones; cobros Chrome correctos. Ver SEG-01-REVISION-2026-09-11.md. Sin deploy |
| **SEG-02** | Revocar sesiones y `remember_token` al cambiar clave | Alta | Sesion y cookie anteriores dejan de autenticar |
| **SEG-03** | Unificar minimo de contraseña web/consola | Media | Una regla documentada y pruebas en ambos caminos |
| **SEG-04** | Ejecutar preflight antes de `artisan up` | Alta | Un preflight fallido nunca publica el release |
| **SEG-05** | Ocultar excepciones crudas de recibos | Alta | Usuario ve mensaje util; detalle queda solo en log |
| **SEG-06** | Restauracion integral | Alta | Ensayo aislado repone base, archivos y configuracion y deja Wings utilizable |
| **SEG-07** | Verificacion fuerte del backup | Alta | Compara contenido financiero e incluye imputaciones, no solo conteos |
| **SEG-08** | Fallo de copia externa observable | Cerrada 09/09, FDS-02 | Copia fallida alertada y recuperacion Drive comprobada |
| **SEG-09** | Monitoreo de sitio, scheduler y backup | Cerrada 09/09, FDS-02 | HTTPS Up; fallos scheduler/backup recibidos por Carlos en email y Telegram |
| **SEG-10** | Integracion continua | Media | Push/PR ejecuta suite MariaDB y build sin depender de memoria humana |
| **SEG-11** | CSP definitiva | Supervisada | Report-only, inventario cero de JS bloqueable, recorrido visual y recien luego bloqueo |

`SEG-11` nunca habilita CSP bloqueante en un solo paso. Los estilos inline siguen la
decision vigente de `AGENTS.md`.

## 6. Bloque 4 — prueba integral y gate

### PRU-01 · Base conocida para la prueba

Definir una base descartable reproducible, sin copiar datos reales ni depender de un
dump. No confundir la carga humana de entrega con un seeder decorativo.

### PRU-02 · Recorrido humano completo

Ejecutar los flujos de ADMIN, OPERATIVO y PROFESOR: catalogos, alumno, deuda, cobro,
parcial, cancelacion, caja, validacion, cashflow, clase, asistencia, liquidacion y
recibos. Retomar los pasos antes bloqueados, pero sobre la version corregida.

### PRU-03 · Estados de cobranza

Revalidar los 60 casos preparados. Antes de usar “DEUDOR” para reclamar, Carlos debe
confirmar el significado del alumno sin pagos y sin saldo pendiente.

### PRU-04 · Ensayo del proceso mensual

Ejecutar en ambiente descartable la generacion, revisiones y cambio de mes. Verificar
que la falta de asistencias produce la cola decidida y que una falla genera alerta.

### PRU-05 · Gate de entrega

No declarar listo hasta cerrar COB, FIN y SEG de prioridad alta, obtener suite verde,
recorrido humano firmado y verificacion actual del servidor.

## 7. Bloque 5 — pedidos concretos y operacion diaria

| ID | Pedido | Dependencia |
|---|---|---|
| **ENT-01** | Inscripcion configurable del alumno nuevo | Carlos confirma regla contable; migracion crea la clave |
| **ENT-02** | Rediseño completo del recibo | Bloqueado hasta recibir logo y paleta del club; requiere autorizacion de diseño |
| **ENT-03** | Favicon | Carlos entrega o aprueba el recurso grafico |
| **ENT-04** | Ojo de contraseña en alta/edicion de usuarios | Login ya lo tiene; JS externo compatible con CSP; diseño autorizado |
| **ENT-05** | Acceso directo al recibo despues de cobrar y desde la ficha | Resolver FIN-02 primero; diseño autorizado |
| **ENT-06** | Avisos de cajas/revisiones/liquidaciones para ADMIN | Definir contenido con Carlos; diseño autorizado |
| **ENT-07** | Lista de cobranza util para llamar | Despues de prueba humana; importe, antiguedad, periodos y contacto |
| **ENT-08** | Tablero administrativo util | Despues de prueba humana; no copiar sin criterio el tablero operativo |

## 8. Bloque 6 — posterior, no bloquea la entrega inicial

| ID | Tema | Alcance |
|---|---|---|
| **POS-01** | Reportes acordados | Implementar despues de la prueba humana segun `Wings-Contrato-Reportes-V1.md` |
| **POS-02** | Auditoria e historial de contactos | Trazar cambios administrativos y gestiones de cobranza |
| **POS-03** | Recuperacion de acceso | Evaluar autoservicio y MFA segun necesidad real |
| **POS-04** | Tarifas | Historial de precios y aumentos masivos |
| **POS-05** | Evolucion de producto | Evaluar ficha medica, familias, portal, pagos online, WhatsApp, bancos, multi-sede, torneos e indumentaria; no son compromisos actuales |

Medir antes de corregir cualquier riesgo de precision por `float`. AUD-025 solo entra
antes de agregar rutas destructivas hoy inexistentes.

## 9. Definiciones que este plan no reabre

- API REST apagada a proposito.
- Recibo no fiscal aceptado.
- Exportables fuera de la version inicial.
- El OPERATIVO trabaja sobre todo el dominio operativo, no solo “lo suyo”.
- Sueldos por persona/deporte es una decision del modelo.
- Carga inicial real es humana; no se inventan datos del club.
- Liquidaciones cerradas no se reabren; las correcciones son compensatorias.
- CSP gradual y diseño protegido.

## 10. Como marcar avances

Cada tarea pasa por: `PENDIENTE` → `EN CURSO` → `VERIFICADA` → `CERRADA`.

- **VERIFICADA:** otro agente o Carlos reprodujo el criterio de aceptacion.
- **CERRADA:** commit subido, documentos sincerados y entorno sincronizado.
- El HTML de Carlos es un tablero local; la fuente versionada del estado sigue siendo
  este archivo mas las bitacoras y los commits.
