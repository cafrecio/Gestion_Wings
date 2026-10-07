# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md)

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

## 2026-10-06 — Claude CyE — A25 cerrado tras la segunda verificacion de Gemini

Gemini rehizo la verificacion (`a8fb0bc`). Contrastada: 23 capturas, 18 paginas y JSON
existen; ninguna prueba inventada; columnas, mensajes y permisos citados estan en el codigo.
Reproduje su recorrido en `wings_testing_claude`: 97 aserciones verdes, con sus importes.
A25 CERRADO, verifica Gemini; 42 de 72. Limites anotados al pie de su informe: no hubo
sesion interactiva de navegador (es una prueba HTTP mas paginas dibujadas), quedan nombres
escritos de memoria (dos capturas, una tabla, tres rutas) y son nueve intentos POST, no diez.
Su prueba habia entrado en `tests/Feature`: suite en rojo por `DocumentacionNoMienteTest`
(492 contra 491) y reescribia la evidencia en cada corrida. Movida a la carpeta de evidencia.
No corri la suite completa; si los cuatro guardianes documentales, verdes.

## 2026-10-06 — Claude CyE — anulado el cierre de A25 que hizo Gemini

Gemini cerro A25 en `d91a840`. Contrastado contra el repositorio, su informe no se sostiene:
las diez capturas que lista no existen (las reales son de Codex, el autor, con otros
nombres), los ocho nombres de prueba que cita no existen en `tests/`, y no hay recorrido en
pantalla, que era lo pedido y lo que Codex declaro no haber hecho. A25 es caja y plata.
Reabierto como HECHO (Codex), a revisar, en el tablero, los dos seguimientos, el estado, el
resumen y el plan; 41 de 72. El informe quedo con un aviso arriba, no borrado. En el tablero
lo tiene Carlos: decide quien lo verifica. Guardianes documentales 4/19 verdes.
Dos cosas mas del mismo commit: se llevo adentro la verificacion de celular de Codex, que
estaba sin commitear (no se perdio nada, pero es la regla 10); y A15/A16 siguen sin verificar.
No revise el resto de lo que Gemini verifico antes con este mismo metodo.

## 2026-10-06 — Claude CyE — un solo tablero de tareas, a pedido de Carlos

Carlos: "somos 4 trabajando en lo mismo, mi cerebro no da para llevar todas las tareas sin
un control". Medido sobre `DEFECTOS.md`: 27 de 72 sin dueño ni paso, y "verificado" usado
con dos sentidos opuestos. Aprobo la propuesta. Hecho: el estado vive en
`docs/00-estado/tareas.json`; `scripts/tablero/tablero.php` lo cambia, lo valida y genera
`TABLERO.html` para Carlos y el bloque de arriba de `QUIEN-HACE-QUE.md`. No guarda si falta
dueño, falta el paso o si verifica el mismo que lo hizo. Cargados los 72 defectos y tres
tareas que no figuraban en ningun lado (T1 a T3). Pantalla revisada en una captura.
**Falta, y es T3:** sacar el estado de `DEFECTOS.md`/`.html` y agregar la prueba que vigila
el tablero. No se hizo hoy porque Codex y Gemini estan escribiendo esos archivos y una
prueba nueva cambia el numero que vigila `DocumentacionNoMienteTest`. Hasta entonces se
siguen marcando los dos seguimientos y Claude los trae con `tablero.php traer`.
Contradiccion anotada, sin resolver: la hoja vieja daba A44 por cerrado y `DEFECTOS.md` no.

## 2026-10-06 — Claude CyE — la hoja de quien hace que estaba atrasada medio dia

Al retomar en un chat nuevo, `QUIEN-HACE-QUE.md` no nombraba A56, A15/A16 ni el paquete de
celular de Gemini, y seguia pidiendole a Carlos una redaccion que ya aprobo. Rehecha contra
las bitacoras, `DEFECTOS.md` y git: 30 cerrados de 72; frenan A13 y B1, que esperan que Codex
integre `a55-inscripcion` (tres commits fuera de main, comprobado con `git log`).
Lo unico que espera a Carlos es el permiso para publicar A15/A16, que Codex dejo preparado y
sin commit. Sumadas a la hoja las reglas del marco de 375 y de la carpeta compartida.
No verificado por mi: cuantas pantallas alcanza el cambio de `app.css` de Gemini.
Solo documentacion; el trabajo preparado de Codex no se toco.

## 2026-10-06 — Claude CyE — A37 verificado y cerrado

Gemini rehizo la evidencia desde la aplicacion y saco la maqueta escrita a mano. Verificado
contra la pantalla real con el marco de 375: la ficha entra, los datos van en una columna,
los tres botones de arriba se alcanzan y la fila del historial queda en dos renglones, con
el monto arriba y Recibo y Anular abajo. Captura de la verificacion en
`capturas-a13/verificacion-a37-375.png`. Cerrado por Claude, que no lo implemento.
Costo dos vueltas por dos errores distintos y conviene no olvidarlo: primero la evidencia
era una maqueta, despues la medicion de Claude capturaba con una ventana que Windows no deja
achicar. **El arreglo siempre estuvo bien.**
Avance: 27 cerrados de 72.

## 2026-10-06 — Claude CyE — el cashflow mostraba la devolucion como ingreso, y yo medi mal

**Lo encontro Codex** verificando A13: al anular el cobro del dueño, el contraasiento vive en
un subrubro de Cuotas con importe negativo, y la pantalla deducia el signo del **rubro**, no
del importe: ese menos $48.000 se dibujaba verde como un ingreso mas. El saldo siempre
estuvo bien; mentia lo que se leia. Ahora el signo, el color y la letra I/E salen del
importe. Carlos autorizo el cambio sobre capturas.
**A56, de paso.** Al mirarlo en el telefono, Carlos vio que Cashflow no entra: sus filtros
armaban una grilla propia de cuatro columnas fijas en vez de usar la barra compartida que
quedo responsive en A19. Corregido: se apilan, y los totales bajan de renglon.
**Correccion importante de metodo, y error mio.** Dije que A37 seguia roto en el celular. Era
falso: **mi captura medía mal**. Chrome en Windows no abre ventanas de menos de ~500px, asi
que `--window-size=375` dibuja a 500 y recorta a 375, y todo aparece cortado aunque este
bien. Lo comprobe capturando el login, que no puede estar roto, y tambien salia cortado. La
captura de celular se saca con un `<iframe>` de 375 dentro de una ventana grande; quedo en
`AGENTS.md` §1 junto con la prueba del login para detectar que el metodo miente.
**A37 estaba bien arreglado por Gemini.** Lo que seguia mal era su evidencia: la captura
salia de un HTML escrito a mano, no del sistema.
7 pruebas nuevas entre las dos cosas. Suite 444/2968. Sin deploy.

