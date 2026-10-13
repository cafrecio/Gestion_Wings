# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md)

## 2026-10-13 — ORDEN DE CARLOS PARA LOS TRES AGENTES — esto no puede volver a pasar

Escrito por Claude CyE en las tres bitácoras por orden de Carlos («ponelo en todas las
bitácoras como algo que no puede pasar»). Regla completa en `AGENTS.md` §5-bis.

**1. Prohibido escribir JavaScript dentro de una vista.** Ni `<script>` sin `src`, ni
`onclick` u otro atributo de evento. Va en un archivo de `resources/js/`. Antes de
escribir, buscar en `ds-app.js` si ya existe. El botón de mostrar la contraseña del
ingreso se escribió adentro de la pantalla el 21/07 y ahí sigue, aunque `ds-app.js` ya lo
resuelve para el formulario de usuarios. Cada pantalla así rompe la política de seguridad
y manda un aviso al servidor en cada carga. Quedan 11 bloques en 10 vistas: los saca T73.

**2. Una limpieza no queda «en avance» sin dueño ni fecha.** Sacar el código de las
vistas empezó el 13/09 y quedó a medias un mes, sin tarea en el tablero. Si un trabajo se
corta, queda una tarea T con lo que falta.

**3. Un número en un documento se corrige cuando cambia.** `ESTADO-ACTUAL` y
`PLAN-PRODUCCION` decían «19 bloques en 17 vistas» y `AGENTS.md` «24», cuando eran 11.
Quien baja el número en `CspSinCodigoIncrustadoTest` lo baja también en los documentos.

## 2026-10-13 — Claude CyE — Tabla de pruebas: donde vive y que hay que leer antes de actuar

Carlos y otra sesion mia armaron hoy la **Tabla de pruebas Wings**, que no estaba registrada
en el repositorio: https://claude.ai/artifact/Dc1Ya2fCaxGGiZxGcDgA46 (privada). La pagina es
solo la vista; los datos se leen con la herramienta `ArtifactData` (colecciones `catalogo`,
`pruebas`, `dias`, `dudas`). Al 13/10: 330 casos y 1.435 lineas en 20 modulos; 21 pruebas de
los dias 1 a 3; 56 dudas para Carlos, 7 respondidas (DU-01, 02, 04 a 08).
**Regla que me llevo:** ponerse al dia es leer el repositorio Y esa tabla. Una respuesta de
Carlos en `dudas` es una decision suya. Hoy le di a Carlos un prompt de T18 ya cerrada por
contestar de memoria; me lo marco.
**T31** pasada a Gemini con prompt; medido en el sitio de prueba: buscador 38, Cobranza 35,
los 3 de diferencia deben solo la inscripcion. Coincide con los casos CBZ-005, ALU-006 y
PCA-007 de la tabla. **Ojo:** DU-08, respondida hoy («plazo propio contado desde el alta, 2
clases»), toca el mismo calculo de estado y no esta en el prompt ni tiene tarea.
En la tabla, D03-01 a 03 figuran «arreglado, falta desplegar»: T21 ya esta en el sitio de
prueba desde el 12/10; falta repetir el caso alli para marcarlo resuelto (T4).
El indice `codebase-memory-mcp` no conecto en esta sesion: busque leyendo archivos.
**Tarde:** Carlos respondio las 58 dudas. De ahi salieron **T32 a T72** (41 tareas) y MF4,
cada una con su duda y sus casos. Orden y motivos en `PRU-04/DUDAS-RESPONDIDAS-2026-10-13.md`.
Copia de la tabla para Codex y Gemini en `PRU-04/tabla-de-pruebas/` (ellos no abren el artifact).
**T32 hecho** (`c699bc1`) y hallazgo del sistema (`602efcc`): el limite de ingresos compartia
contador con los avisos de seguridad; esa era la causa real de los Demasiados intentos.

## 2026-10-12 — Claude CAB — T27 verificado y cerrado; sitio de prueba desplegado en b2efc2e

**T27 (Codex), requisito de produccion:** el duenio edita o anula sus movimientos directos
desde Cashflow, con historial y asiento en contra. Lei servicio, rutas y migracion; sus 18
pruebas pasan en `wings_testing_claude`. Cerrado. Codex encontro y corrigio un error mio de
T26: un gasto directo del duenio salia como ingreso en el Historial. Observacion: cualquier
ADMIN corrige el directo de otro ADMIN; queda registrado quien.
**Suite completa sobre `b2efc2e`: 687 aprobadas, 2 omitidas.**
**Despliegue al sitio de prueba, por orden de Carlos:** `2e059ff` -> `b2efc2e`, a mano con el
usuario `wingstest` siguiendo los pasos de `scripts/deploy.sh`. Respaldo previo comprobado:
`/root/respaldos-manuales/wingstest_antes-del-despliegue_2026-10-12_2301.sql.gz` (46 tablas).
Tres migraciones, sin errores; composer y npm sin cambios, solo recompilar el diseno.
**Comprobado despues:** sin migraciones pendientes, fuera de mantenimiento, mismos datos
(100 alumnos, 4 pagos, 1 caja, 5 de cashflow, 7 usuarios), login 200, archivos de diseno
200, archivo de instalacion con arranque en el login y tipo correcto, cero errores en el log.
**No comprobado:** ninguna pantalla con sesion iniciada; no tengo claves del sitio de prueba.
Produccion no se toco.

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


## 13/10/2026 — T7 y tablero

**T7 hecha por mi, `d4f02d4`, desplegada en el sitio de prueba.** Las reglas del menu pasaron
de `sidebar.css` al final de `app.css` sin cambios; `sidebar.css` ya no existe. Verificado:
el CSS compilado de antes y el de despues tienen las mismas reglas en el mismo orden, y en el
sitio de prueba compare los estilos calculados de los 67 elementos del menu a 1366 y 360:
iguales. Suite en `wings_testing_claude`: 736 aprobadas, 2 omitidas. Falta el OK de Carlos.
**Tablero:** T6 paso a MF5 y T9 a CAB01, en un grupo aparte «De otros proyectos» que no
cuenta como pendiente de Wings (`tablero_es_de_otro_proyecto`). T2 la tiene Gemini.
El cambio de estado de T7 quedo en la carpeta sin publicar: `tareas.json` tenia cambios de
Gemini sin publicar y no los mezcle en mi commit.
