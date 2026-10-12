# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md)

## 2026-10-12 — Claude CAB — ERROR MIO: borre una base local; T30 y T29 cerrados, suite verde

**El error.** Para comprobar que las pruebas de T30 rechazaban la base del club, corri una
con `DB_DATABASE=gestion_wings`. Las pruebas borran todas las tablas antes de ejecutar y el
rechazo corre despues: la base quedo vacia, sin respaldo. Ademas informe mal el dano: dije
que era la base de trabajo y que se perdian 70 alumnos. **La base de trabajo de CAB es
`wings_test` (lo dice `.env`) y esta intacta**; `gestion_wings` era una base vieja que la
aplicacion ya no usa. Carlos confirmo que no habia nada importante. La rearme: migraciones
completas y catalogos; sin usuarios, porque `wings:crear-admin` es interactivo.
**Regla que queda:** la suite se corre solo con `DB_DATABASE=wings_testing_claude`, nunca
contra otra base ni «para comprobar que la rechaza». Antes de decir que se perdio, leer `.env`.
**T30 cerrado.** Con permiso de Carlos corregi la condicion de `tests/Support/ImportesT30.php`:
ahora admite las cuatro bases descartables, como T24. **Suite completa en
`wings_testing_claude` sobre `ef12770`: 669 aprobadas, 2 omitidas, ningun rojo.**
**T29 cerrado por Gemini** (`ef12770`); sus 11 pruebas corren verdes en mi base.
Visto y no tocado: a `wings_test` le faltan 9 migraciones desde el 05/10.

## 2026-10-12 — Claude CAB — T30 devuelto por sus pruebas; reloj del sitio de prueba en hora real

**Sitio de prueba:** nadie podia entrar. El reloj simulado, fijado el 10/10, se freno en la
medianoche del lunes 12 y el limite de 5 ingresos por minuto dejo de vencer; habia bloqueos
con 19 y 14 intentos acumulados. Por orden de Carlos volvi el reloj a la hora real
(`wings:fecha-simulada --real`) y limpie la cache del sitio de prueba. Sigue en `2e059ff`:
**no se desplego nada**. Defecto de la herramienta, sin tarea todavia: con el reloj frenado
el limite de intentos deja a todos afuera.
**T30 (Codex), vistas aprobadas por Carlos:** lei el formateador unico `FormatoImporte` y
busque restos: lo que queda sin centavos son porcentajes, DNI, cantidades y los graficos,
que Carlos dejo afuera. Sus 5 pruebas pasan, pero **solo en `wings_testing_codex`**: tienen
una condicion que las hace fallar en cualquier otra base. Las corri una vez en esa base para
verlas pasar; en la mia la suite completa da 664 aprobadas, 2 omitidas y esas 5 en rojo.
Devuelto a Codex solo por eso. T24 ya permite las cuatro bases descartables.
**T29** lo verifica Gemini. Carlos instalo Wings en su telefono y abre como aplicacion.

## 2026-10-12 — Claude CAB — T28 cerrado en segunda vuelta; T11 verificado en codigo

**T28:** Carlos decidio que el cobro es de quien lo hizo y que quien valido se muestra aparte.
Gemini lo rehizo en `e1e73d8`: cada renglon de Cashflow guarda de que movimiento viene
(columna y migracion nuevas), se saco el segundo intento por importe y rubro, y hay una
columna «Val.» con iniciales. Verificado por mi: sus 10 pruebas y mis 2 pasan en
`wings_testing_claude`. Cerrado. Observacion que pasa a T22: `User::iniciales()` hace una
consulta por renglon. Las capturas de celular estan a 375 y el telefono de Carlos mide 360.
**T11:** el archivo de instalacion ahora declara el arranque en el login; 3 pruebas verdes.
Gemini informo que el servidor es Nginx y que habia que tocarlo a mano: **es Apache**
(comprobado con `ssh vps`, solo lectura), asi que su regla en `.htaccess` alcanza. La prueba
real es de Carlos en su telefono, despues de desplegar.
Sin suite completa en este paso. Nada de lo de ayer y hoy esta desplegado.

## 2026-10-12 — Claude CAB — T28 verificado, con dos observaciones para Carlos

Gemini lo hizo en `2d215d2` y `1787dfd`; Carlos eligio el diseno sobre capturas (tabla
rebalanceada en escritorio, tarjetas en celular). Verificado por mi: sus 5 pruebas pasan y
agregue dos propias en `evidencia-t28/VerificacionT28ClaudeTest.php` (40 cobros del mismo
importe en una caja, dos paginas y filtro por medio de pago): cada renglon muestra a su alumno.
No lo cerre. **Observaciones:** (1) Cashflow no guarda de que movimiento viene cada renglon;
lo deduce comparando importe, rubro y observacion. Anda porque la observacion del cobro trae
el numero de alumno. Si no coincide hace un segundo intento solo por importe y rubro, que
puede mostrar a otro alumno. (2) `reflejarCajaEnCashflow` ahora guarda a la operativa en
`usuario_admin_id` en las copias nuevas; las viejas tienen al admin que valido. La pantalla
no lo necesita, porque resuelve el nombre al mostrar. Ojo en T27, si «movimientos del dueño»
se decide por ese campo. Sin suite completa: Codex tiene T30 a medio hacer en la carpeta.
**T27** obligatoria antes de produccion (Carlos); **T11** instalar en el telefono, a Gemini.

## 2026-10-12 — Claude CAB — T29 hecho; T24 y T26 cerrados; T28 y T30 repartidos

**T29, mio:** el buscador de Cobrar anunciaba 40 con deuda y listaba 100 (dia 1 de la
prueba). Sin buscar ahora trae solo a los que deben, los mismos del encabezado; a cualquier
otro se llega buscandolo. El que no debe sale con punto verde. `BuscadorCobrarSoloDeudoresTest`,
7 pruebas: 5 fallan con el codigo anterior, 2 son de resguardo. Cambie 3 pruebas viejas que
exigian el comportamiento anterior; una cubria al alumno sin la cuota del mes generada, que
ahora se encuentra buscando. Carlos aprobo las capturas («Ok las imagenes»).
Suite completa en `wings_testing_claude`: 647 aprobadas, 2 omitidas y en rojo solo el
guardian de documentacion, que pedia 650; el numero se corrigio en este commit y la cuenta
da 650. No volvi a correr el guardian: en la carpeta hay 5 pruebas de Gemini sin publicar.
**T24 cerrado:** regla de importes verificada en servidor y navegador, 6 pruebas verdes.
Tres avisos a Carlos: `48.000` termina guardado como 48.00 (decision suya); 45 lugares
muestran importes sin centavos (pasa a T30, Codex); dos formatos en pantalla.
**T26 cerrado por Gemini**, sus 8 pruebas corren verdes en mi base.
**T28** Cashflow con alumno y quien cobro: Gemini. Sin desplegar nada de esto.

## 2026-10-12 — Claude CAB — T23 verificado y cerrado

Gemini lo entrego en `03aaf11`; Carlos aprobo las capturas. Verificado por mi leyendo el
codigo y corriendo sus 8 pruebas en `wings_testing_claude` (41 aserciones, verdes): aviso
solo por encima del 50% y con cierre anterior mayor a cero, confirmacion para seguir,
rectificacion con motivo solo en caja ABIERTA, por el operativo del turno o el ADMIN, y
esperado recalculado. No corri la suite completa hoy; ayer dio 641/2 con T23 adentro.
**Observaciones, no bloquean:** una segunda rectificacion pisa el valor anterior, el motivo y
el autor de la primera, asi que el importe declarado al abrir se pierde; rectificar no avisa
si el nuevo valor es disparatado. Las cuatro cosas que Carlos no habia decidido quedaron
escritas en el contrato V5 como «acordadas» con Gemini: no tengo como comprobar ese acuerdo.
**Regla 10:** ese commit se llevo cambios sin publicar de Codex (T24) en
`CajaWebController.php`, que usan una clase (`ImportePesos`) que recien entro en `f62f1f2`.
Entre los dos commits lo publicado estaba incompleto; no probe que fallaba en ese estado.
Sin desplegar.

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
