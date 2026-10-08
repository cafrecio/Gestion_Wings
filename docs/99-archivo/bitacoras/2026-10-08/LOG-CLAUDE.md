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

