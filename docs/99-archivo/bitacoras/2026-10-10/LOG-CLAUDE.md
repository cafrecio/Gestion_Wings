# Wings — Bitácora de CLAUDE — entradas archivadas el 10/10/2026

Sacadas de `docs/00-estado/LOG-CLAUDE.md` para mantenerla dentro del límite. No se modificaron.

## 2026-10-09 — Claude CyE — sitio de prueba en 22977b6, con Inicio del admin y Reportes

Codex publico A23 y B12 (Inicio del admin, Reportes, ajuste de liquidaciones). Desplegado en
test: `b7cf0a7` → `22977b6`, tres migraciones, sin errores. Respaldo previo en
`/root/respaldos-manuales/`. La base sigue sin alumnos y con la primera carga pendiente.
Comprobado dentro del servidor: mientras la carga este pendiente, el admin es llevado a
Primera carga tambien desde Inicio y desde Reportes, asi que esas pantallas no se ven hasta
cargar alumnos. El operativo no entra a Reportes (403), como definio Carlos en la encuesta.
**Sigue:** la prueba manual que dirige Carlos. A23 y B12 esperan su OK. T9 (metodo comun,
en el repositorio `Gestion_CAB`) quedo para el 10/10.
Carlos pidio honestidad sobre el metodo: mi diagnostico y sus decisiones estan en memoria
(`metodo-trabajo-entre-proyectos`). Se mantienen los tres agentes.

## 2026-10-08 — Claude CyE — DONDE QUEDE al cierre del 08/10

**Avance: 60 de 72, ninguno frena.** main en verde y al dia. Sitio de prueba desplegado en
`b7cf0a7`, sin alumnos, con la primera carga pendiente (entrada de abajo). Produccion sin tocar.
**Lo proximo es la prueba manual en el sitio de prueba, que dirige Carlos conmigo.** Antes de
empezar: el admin tiene que configurar que medio de pago es el efectivo del mostrador; la
operativa no entra a Alumnos hasta que termine la primera carga; falta decidir con que datos se
carga (el Excel de los 60 no esta armado; el padron viejo es `PADRON-PRUEBA-v2.xlsx`).
**A24 CERRADO (18:45):** repeti el recorrido de ocho situaciones sobre `decfb8c`; ningun boton
del inicio del operativo falla. 60 de 72. Sitio de prueba vuelto a desplegar en `b7cf0a7`, ya
con esa correccion. **Lo que tengo a mi nombre:** T3, terminar el tablero unico, y T7, pasar
`sidebar.css` a `app.css`: los dos quedaron libres porque Codex ya publico A6-A10.
**Lo que espera a Carlos:** T1 (mirar cuatro pantallas desde el celular en test), y A23 y B12,
que esta definiendo con Codex (`PLAN-REPORTES-TABLERO-v2026-10-08.md`, sin subir, de Codex).
**Reglas de Carlos de hoy, ya en memoria y en AGENTS.md:** lo visual que el aprueba no pasa por
otra IA; lo que tenga que decidir va con enlace al navegador, que cambia y por que
(`docs/00-estado/PARA-DECIDIR.html`); las cajas de los operativos son individuales.
**Cuidados:** hay otra sesion de Claude en esta maquina (verifico A10 y escribe en esta
bitacora). Para publicar con archivos ajenos abiertos: copia limpia con `git worktree`, y
despues mezclar la carpeta a tres bandas; nunca `checkout -- .`. Queda la rama remota
`en-curso/cye-2026-10-07`, que ya se puede borrar: todo lo suyo esta en main.

## 2026-10-08 — Claude CyE — sitio de prueba desplegado y sin alumnos, listo para la primera carga

Pedido de Carlos: desplegar en test y dejar la base sin alumnos para hacer la primera carga
como la va a hacer Vanina. Hecho en `test.gestionar-te.com.ar`: `443b0bc` → `d577cb7`, cinco
migraciones, sin errores en el log. Respaldo previo fuera de la carpeta que rota:
`/root/respaldos-manuales/wingstest_antes-del-despliegue_2026-10-08_1639.sql.gz`, junto al del 06/10.
Borrado en una transaccion: 60 alumnos, 77 deudas, 60 planes de alumno y 60 revisiones. Quedan
7 usuarios, catalogos y 77 clases; sin cajas ni pagos. Primera carga en PENDIENTE.
Comprobado dentro del servidor, sin claves: el admin es llevado a Primera carga y la plantilla
baja; operativo y profesor abren lo suyo. Mientras la carga este pendiente el operativo no
entra a Alumnos (403), que es lo previsto en P1.
El despliegue fue a mano con el usuario `wingstest`, siguiendo los pasos de `scripts/deploy.sh`,
que es solo para produccion. Produccion no se toco: sigue en `314e485` con 7 migraciones sin correr.
Corregidos cuatro documentos que daban a produccion en `81f27ef`.
**Sigue:** la prueba manual que dirige Carlos. No esta hecho el archivo con los 60 alumnos en
formato de plantilla (bloqueado el 06/10); el padron original es `docs/06-pruebas/PADRON-PRUEBA-v2.xlsx`.

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

