# Wings — Bitácora de CLAUDE, entradas archivadas el 12/10/2026

Entradas intactas, tal como estaban en la bitácora activa.

## 2026-10-11 — Claude CAB — T26: en el Historial de caja los limites son del operativo, no del duenio

Carlos: «El dueño debe ver todo, los limites no son para el». El Historial aplicaba a todos
los 90 dias, los rubros del operativo y el ocultar cancelados. Ahora el ADMIN ve cualquier
fecha, todos los rubros y lo cancelado, marcado y sin sumar; el operativo queda igual.
Decision mia, aprobada por Carlos: sin fecha pedida arranca en los ultimos 90 dias, para no
traer toda la historia cada vez. **Defecto encontrado leyendo el codigo:** la anulacion de un
cobro del duenio (asiento en contra, importe negativo) se mostraba y se sumaba como otro
ingreso; mismo error que A56 en Cashflow. Corregido: resta.
`HistorialDuenioSinLimitesTest`, 4 pruebas, las 4 fallan con el codigo anterior. Capturas en
`docs/06-pruebas/PRU-04/evidencia-historial/`; Carlos aprobo el diseno («Diseños OK»).
Limite visto: en celular la palabra «Cancelado» queda cortada. Contrato de permisos
actualizado. Falta que otro agente verifique la regla. Sin desplegar.
**Fuera del repositorio, a proposito:** Carlos y yo llevamos la tabla de casos de prueba por
pantalla y rol; los otros agentes no la reciben. De ahi salieron T23 a T27, MF1 y MF2.

## 2026-10-11 — Claude CAB — T21 cerrado; T22 a T24 y dos mejoras futuras al tablero

**T21 cerrado.** Gemini lo hizo en `a62867b`. Verificado por mi: el cambio toca el controlador
y las dos vistas de Clases, no el CSS compartido; sus 9 pruebas pasan en `wings_testing_claude`
y el guardian de documentacion tambien. No corri la suite completa. Carlos aprobo las capturas
(«Los png estan OK») sabiendo cuatro cosas que le marque y acepto: la tarjeta cancelada queda
cortada por la altura fija del bloque Hoy, dice «Asistencia: Pendiente», «Otras clases de hoy»
cuenta la cancelada, y la etiqueta Cancelada cambio en todos los listados. Las dos capturas
de la profesora son la misma imagen. **Sin desplegar** al sitio de prueba.
**Tareas nuevas, decididas por Carlos:** T22 rendimiento con dos anios de datos (mio, espera
la carga de volumen); T23 aviso si la apertura difiere mas de 50% y rectificacion con caja
abierta (Gemini); T24 un solo modo de cargar importes, punto decimal y dos decimales, en todo
el sistema (Codex); MF1 y MF2 son mejoras futuras, solo evaluar.

## 2026-10-11 — Claude CAB — T18 cerrado por Gemini, contrastado; T21 pasa a Gemini

Gemini verifico la segunda vuelta de T18 y lo cerro (`68c7a1b`). Contrastado contra el
repositorio: su commit solo toca documentos y su prueba de evidencia; las 14 pruebas existen
y afirman lo que el informe dice (mes futuro pendiente fuera de la deuda en cobro, buscador,
Cobranza, Inicio y Reportes; cuenta al empezar el mes por el importe pactado; mes 13 y 00
rechazados). Las corri en `wings_testing_claude`: 14 aprobadas, 157 aserciones. No corri la
suite completa. Limite: es una prueba por pedidos a la aplicacion, no un recorrido en pantalla.
Por decision de Carlos, T21 lo hace Gemini y no yo; pasado en el tablero con el plan acordado.
T14: se deja de ir dia por dia sin plan. Carlos y Claude llevan aparte, fuera del
repositorio, la lista de lo que se prueba por pantalla y rol; Codex sigue recibiendo el cuento.
Corregido el resumen comun, que daba T16, T18 y T19 por «hechos» cuando estan cerrados.

## 2026-10-10 — Claude CyE — DONDE QUEDE al cierre del 10/10

**Sitio de prueba** (`test.gestionar-te.com.ar`) en `2e059ff` + docs: 100 alumnos de la primera
carga (verificada dos veces contra la base, 0 diferencias) y tres dias de PRU-04 hechos por
Codex y verificados por mi. Produccion sin tocar (una sola consulta de lectura).
**Reloj simulado:** quedo en lunes 12/10/2026. Se mueve con `php82 artisan wings:fecha-simulada
"AAAA-MM-DD HH:MM"` como wingstest. Desde hoy avanza solo y frena a la medianoche simulada;
clavado, el limite de 5 ingresos por minuto no se limpiaba y nadie podia entrar. No fijarlo
justo a las 08:00 (resumen diario al mail y Telegram reales de Carlos).
**Cerrado hoy:** T1, T15 (errores en castellano, Codex), T16 (clasificacion de subrubros, mio;
Codex lo devolvio dos veces: cambio de tipo de rubro y guardados simultaneos), T17 (Inicio con
inscripciones, Gemini), T19 (misma deuda en Inicio, Reportes y Cobranza: activos, cuotas +
inscripciones, mio), T20 (Cobrar en el celular, Gemini), boton Cobrar del admin en Caja.
**Abierto:** T18 cobro adelantado: pantalla aprobada por Carlos, Gemini la devolvio (mes futuro
contaba como deuda en Cobranza; periodo «mes 13» daba error), corregido en `567f030`; falta que
Carlos la vuelva a pasar a Gemini. Decision de Carlos: al anular un adelantado el mes queda
pendiente con el importe pactado. T21 clase cancelada el mismo dia no se ve: lo hago yo, plan
acordado (mostrarla en «Hoy» apagada con motivo + que el filtro Cancelada encuentre las de hoy).
T14 sigue: Carlos decide el dia 4. T12 manual (Gemini, despues de la prueba), T13 Ayuda dentro
de Wings (falta quien lo programa). B12 espera la prueba manual de Carlos.
**Decisiones de Carlos del dia:** un mes que no empezo no es deuda; deuda = alumnos activos,
cuotas + inscripciones, igual en todas las pantallas; pagar adelantado congela el precio y el
importe es editable; Indumentaria es aporte de los duenos, el resto del catalogo es del club;
domingos y feriados pueden ser activos; la profesora puede abrir clases ajenas (suplencias).
**Como trabajar con Carlos (lo marco hoy, fuerte):** una cosa por vez y bien hecha; no leer
entregas de Gemini o Codex hasta que el las pase; primero los prompts de los otros, despues lo
mio. Gemini informo dos veces trabajo que no hizo (Excel a mano): sus informes se revisan
contra la base. Sin probar por nadie: llenar la plantilla de primera carga a mano.
**Antes de produccion:** clasificar sus subrubros uno por uno con Carlos (T16).

## 2026-10-10 — Claude CyE — A23 cerrado, B10 hecho y publicado, sitio de prueba actualizado

**A23 cerrado:** opere el sistema por pedidos reales y compare el inicio del admin contra la
cuenta a mano en cuatro momentos; 52 de 52 valores coinciden y no hay doble conteo al validar
la caja (`B12-A23/VERIFICACION-A23-INICIO.md`). Mi primera comparacion dio mal por armar mal
los importes de mi propia prueba, no por el inicio.
**B10 hecho por mi**, a pedido de Carlos: campo «CBU o alias» en profesor, ficha, pago de
liquidacion y usuario operativo; control al guardar. Carlos aprobo las capturas («Ok»). Falta
que Codex verifique la regla. En el camino vacie dos controladores con un comando mal
escrito; restaurados desde git en el momento. No editar archivos con php en linea: usar Edit.
**Carlos miro desde su celular** (mide 360 de ancho, no 375): salieron A57 y A58, que hizo
Gemini y Carlos aprobo en el chat de Gemini. El arreglo de A58 apila en una columna los datos
de TODAS las tarjetas en celular (16 vistas), no solo Clases: avisado a Carlos para que lo mire.
**Suite completa en `wings_testing_claude`: 581 aprobadas, 2 omitidas.** Las pruebas de
Reportes ya corren en cualquier base de pruebas.
**Tarde:** A42 y B10 cerrados (B10 lo devolvio Codex y la segunda vuelta la verifico Gemini).
Carlos dio OK a las vistas del celular; falta Cobrar, que necesita alumnos. Reporto que
Reportes «no lleva a ningun lado»: era la redireccion muda a Primera carga; ahora avisa
(`3cb0264`, desplegado en test). Pendiente: B11 al final y solo, prueba manual de la primera
carga, T9 metodo comun en `Gestion_CAB`, T10 borradores de privacidad.
**PRU-03:** Gemini hizo la primera carga en test (90 alumnos). Verifique las 90 filas contra
la base dos veces: 0 diferencias (hoy 100 alumnos). Gemini informo 10 altas a mano con
cronometro: falso. T15 verificado. Dia 1 de PRU-04 verificado; T16 y T18 hechos por mi, test en `3a3849a`.

