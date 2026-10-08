# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md)

## 2026-10-08 — Claude CyE — A10 cerrado en segunda revision

Codex corrigio solo el enlace de Limpiar (`:href`). Antes de repetir guarde la evidencia de la
devolucion, con huellas, en `evidencia/a10-verificacion-claude/primera-revision/`.
Repetido en Chrome pulsando el boton, con fecha lejana, caja y tipo: en Dia, Semana, Mes y Año
conserva periodo y fecha y quita los dos filtros; el enlace llega con sus cuatro parametros.
Recorrido completo: 45 comprobaciones, 0 fallos, 18 capturas. Corrida nueva en mi base: las
diez A10 del autor y mi ensayo, 19 aprobadas, 6.819 aserciones. El controlador no cambio desde
la primera revision, asi que vale lo comprobado ahi. No corri la suite completa: la corre Codex.
No vi en rojo la regresion del autor; si mi recorrido en rojo sobre la version anterior.
**A10 CERRADO, verifica Claude; 55 de 72.** Tablero y los dos seguimientos al dia. Sin commit
ni despliegue. Codex actualiza entrega, resumen y contrato.

## 2026-10-08 — Claude CyE — A10 verificado y devuelto a Codex: Limpiar pierde el periodo

Verificacion independiente de la logica de A10 (hizo Codex; el aspecto lo aprobo Carlos).
**Devuelto por un defecto:** al pulsar Limpiar en el navegador el periodo vuelve a hoy, en los
cuatro modos. El enlace sale con el `&` escapado dos veces y solo llega `periodo`. Las pruebas
HTTP no lo ven porque decodifican la direccion antes de pedirla; la mia tampoco lo vio: lo
delato el texto del enlace y lo confirmo Chrome pulsando el boton.
**Comprobado y bien:** intervalos de dia, semana, mes y año contra un calculo propio (432
combinaciones, 442 pedidos), cruces de mes y año, bisiesto, enlaces anteriores, filtros,
paginacion, devolucion de cobro y reversion de egreso, saldo inicial fuera del resultado y
saldo disponible intacto, fecha conservada, 45 parametros invalidos sin 500, permisos y alta.
Ensayo propio fuera de la suite: 9 pruebas, 6.697 aserciones, en `wings_testing_claude`.
Navegador: login real, seleccion con teclado, Nuevo, pagina 2 y marco de 375; 12 capturas.
No corri la suite completa. Anotado sin frenar: una reversion de egreso mayor que los egresos
del periodo se restaria; hoy ningun camino la crea. Informe: `VERIFICACION-A10.md`.
Tablero: A10 devuelto, lo tiene Codex; 54 de 72. Sin commit ni despliegue.
Archivadas intactas seis entradas del 06/10 en `99-archivo/bitacoras/2026-10-08/`.

## 2026-10-08 — Claude CyE — A12 aprobado y A24 devuelto; el inicio del operativo estaba roto

Gemini publico el inicio nuevo (`a6ce0f5`) corriendo cuatro archivos de prueba: la pantalla
daba error 500 por una variable sin definir y fallaban cinco pruebas existentes. Carlos pidio
repararlo: una linea, `d664c35`; suite completa despues, 498 aprobadas y 2 omitidas.
Verifique la logica en ocho situaciones con pedidos reales (`VERIFICACION-A12-A24.md`).
**A12 cerrado. A24 devuelto a Gemini:** con el turno abierto por un compañero la pantalla
dice «podes cobrar» y Cobrar, Registrar y Detalle fallan los tres; con un turno de ayer sin
cerrar ofrece Abrir y el sistema lo rechaza. El sistema cumple el contrato V5 (un turno a la
vez); lo que miente es la pantalla. Quedo T8 para Carlos: si dos operativos atienden a la vez,
hoy solo cobra quien abrio. 50 de 72.
Tambien hoy: A26, A39 y A35 a Gemini para verificar; A32 a Codex; T1 lo mira Carlos desde el
celular cuando este desplegado.

## 2026-10-08 — Claude CyE — publicados el menu nuevo, A26, A38, A39 y A44

Carlos reviso todo en `docs/00-estado/PARA-DECIDIR.html` y aprobo: «Menu: Mucho mejor,
implementar!!», «A-26 OK», «A-38 OK», A44 tal cual con mejora futura. Sobre Movimientos:
«Solo puede ver los que corresponden a rubros del operativo. Nunca sueldos, alquileres».
El menu paso de un renglon de A39 a un rediseño: orden por tema para los dos roles y aspecto
nuevo, en `resources/css/sidebar.css` porque `app.css` lo tiene abierto Codex (T7).
Cerre la fuga de los filtros de Movimientos, que listaban al operativo los rubros del admin.
**Regla nueva de Carlos:** lo visual que el aprueba no pasa por otro agente. Quedo en
`AGENTS.md` §6a y el tablero acepta `verifica=Carlos`. Lo que no se ve sigue yendo a otro.
Estado: A38, A44 y el menu cerrados por Carlos (49 de 72). A26 y A39 a verificar solo por su
regla; A35 a confirmar como no-defecto; A32 va con A9. 500 pruebas, 9 nuevas.
Publicado desde una copia limpia y la carpeta compartida reconciliada sin pisar lo ajeno.
**Regla suya para mi:** lo que tenga que definir va con enlace al navegador, que cambia y por que.
**Pendiente:** el despliegue al sitio de prueba (entrada del 06/10), T3 y recortar esta bitacora.

## 2026-10-07 — Claude CyE — PARA RETOMAR EN CASA: todo lo sin subir esta en una rama

Se corto el chat de Codex con A6 a A10 a medio hacer y Carlos pidio subir todo para seguir en
su casa. **main no se toco.** Todo lo que estaba sin subir en CyE quedo en la rama
`en-curso/cye-2026-10-07` (commit `2f27d80`): lo de Codex (A6 a A10) y lo mio (A26, A38, A39,
A44). El diseño no esta commiteado porque no tiene la aprobacion completa: va como dos parches
en `docs/99-archivo/en-curso/2026-10-07/`, con un LEEME que dice como aplicarlos.
Comprobado en una copia aparte: rama mas parches reproduce igual la carpeta de CyE.
Gemini tenia todo subido (`c7cc6be`); su propuesta de A12/A24 espera a Carlos.
**Error mio, para no repetir:** el primer intento de resguardo fallo porque el hook de diseño
rechazo los commits, y el paso siguiente (`git checkout <rama> -- .`) piso con la version de
GitHub los 20 archivos modificados de Codex y mios. Se recuperaron enteros desde los objetos
que `git add` habia dejado en `.git`, cotejados uno por uno. En la carpeta compartida no se
usa `checkout -- .` ni nada que reescriba el arbol; el resguardo se hizo con un indice aparte.
**Al retomar:** Codex sigue A6-A10 desde la rama (le falta resolver las capturas: su control de
Chrome no respondia). Lo mio espera el OK de Carlos sobre dos capturas; pasos en la entrada de abajo.

## 2026-10-07 — Claude CyE — DONDE QUEDE: A26, A38, A39 y A44 hechos y SIN SUBIR

Carlos me habilito a programar estos: «Hace A38 39 y 26 / A35 y 32 / A38 A39 A26 A44».
**Hecho en la carpeta, sin commitear:** A26 celular opcional para menores (se guarda el del
tutor; `AlumnoWebController`, `alumnos/_form`, `alumnos-form.js`); A38 idioma fijo en
castellano (`config/app.php`, `lang/`); A39 Movimientos en el menu del operativo
(`layouts/ds-app`); A44 plantilla de correo propia (`resources/views/vendor/mail`).
Pruebas: `DefectosMenoresA26A38A39A44Test`, 8/28. Suite 499: 496 aprobadas, 2 omitidas; solo
falla el contador de `DocumentacionNoMienteTest`, que hay que pasar de 491 a 499 en
ESTADO-ACTUAL, CHECKLIST-CARLOS y PLAN-PRODUCCION al publicar.
**Por que no esta subido:** A26 y A39 se ven en pantalla. Le mostre a Carlos dos capturas
(`evidencia/a26-a38-a39-a44/`) y espero su OK de diseño. No escribir yo esa linea.
**Al tener el OK:** actualizar los tres contadores; marcar A26, A38, A39 y A44 como HECHO
(Claude), a revisar, en DEFECTOS.md, DEFECTOS.html y el tablero; A35 a verificar como «no es
un defecto» (captura mal rotulada); A32 sigue presente y va con A9, que tiene Codex. Agregar
los archivos por nombre: Codex tiene abiertos app.css, alumnos/index, clases/show y sus docs.
Entrega escrita: `docs/06-pruebas/PRU-02/IMPLEMENTACION-A26-A38-A39-A44.md`.
**Hoy tambien:** cerrados A15, A16, A14, A27 y A53 tras contrastar a Gemini (47 de 72);
bitacora archivada; A6 a A10 en Codex y A12/A24 en Gemini, los dos con propuesta a Carlos.
**Sigue pendiente:** el despliegue al sitio de prueba (entrada de abajo) y T3.

## 2026-10-06 — Claude CyE — PARA RETOMAR EN CASA: despliegue al sitio de prueba y primera carga

**Decidido por Carlos:** esta noche se despliega main al **sitio de prueba** (no produccion),
se borran **solo alumnos y deudas** (usuarios, catalogos y clases quedan) y se prueba la
primera carga por Excel como la haria una persona, con los mismos datos que habia.
**Servidor, leido el 06/10 con `ssh vps`, sin modificar:** prueba en `/home/wingstest/app`,
commit `443b0bc` del 23/09, 60 alumnos, 77 deudas, 0 pagos, 0 cajas, sin migraciones
pendientes; recibe 5. Produccion en `314e485` del 22/09, vacia de alumnos, con 7 migraciones
atrasadas; su despliegue pide la clave de `wings_migrate`, que esta en el disco D: de CAB.
**Hecho:** respaldo completo de la base de prueba en el servidor,
`/var/backups/wings/wingstest_antes-de-limpiar_2026-10-06_1845.sql.gz`. No confirme si la
rotacion de esa carpeta lo borra: copiarlo a otro lado antes de limpiar.
**No hecho:** el archivo con los 60 alumnos en las 38 columnas de la plantilla. Lo exporte y
cuadra (60 filas, 20 con deuda, 34 cuotas, $1.263.000) pero el control de seguridad de la
sesion bloqueo dos veces escribirlo en el repositorio, aun con autorizacion de Carlos, y quedo
en una carpeta temporal que no viaja. Se rearma desde el respaldo. El padron original si esta:
`docs/06-pruebas/PADRON-PRUEBA-v2.xlsx`.
**A saber antes de probar:** Sofia Morales (DNI 32123456) figura en dos deportes con distinta
fecha de nacimiento; ningun alumno tiene tutor ni debe inscripcion. No hay comando para
limpiar: es a mano sobre `wingstest`. El sitio de prueba no usa `deploy.sh` con clave aparte.
**Orden:** rearmar el archivo, resguardar el respaldo, desplegar, limpiar, probar.

