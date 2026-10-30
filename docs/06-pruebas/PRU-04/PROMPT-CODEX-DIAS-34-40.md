# PROMPT — Prueba por días, tanda 3: días 34 a 40 (12 al 18 de noviembre)

> Prompt para Codex, escrito por Claude CAB el 30/10/2026. Carlos lo copia y se lo pasa a Codex.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Leer **entero**
`docs/06-pruebas/PRU-04/GUION-DIAS-34-40.md` antes del primer paso. Es la tanda 3 de 5 y sigue exactamente
donde quedó el sitio después de tu verificación del 30/10: **12/11 simulado a las 07:58**, ninguna caja abierta.
No repetir nada de los días 24 a 33 ni la verificación.

**Autorización de Carlos (25/10, 28/10, 29/10 y 30/10/2026), para no volver a preguntar:** todas las operaciones
de plata de este guion en test.gestionar-te.com.ar (base `wingstest`, datos ficticios): cobros con recargo, el
cobro de una cuota dejando el recargo y el del recargo solo, el cobro parcial con motivo y su resto, el cobro
adelantado de diciembre, el cambio de grupo de Lara, las dos altas y el primer cobro de Bautista, el gasto de
luz en Cashflow, la transferencia de $1.500.000 al Galicia, la cancelación de la clase de Adultos y las rutinas
por consola.

**Qué cambió desde la tanda 2:** nada en el método. El sitio tiene lo subido el 30/10 (MF12, MF13, los avisos
en castellano y la ficha que nombra «Inscripción»), que ya verificaste.

**Cómo se trabaja** (el detalle está en el guion):
1. **Por día:** fijar el reloj a las 09:00 del día (el 37, domingo, a las 20:00). **El día 34 no se vuelve atrás:**
   el 12/11 ya empezó a las 07:58 con tu verificación. **El día 38, 38.1 va antes de la rutina** (está marcado).
   Rutina de la mañana con `--simular` y, si da bien, sin `--simular`; las tareas de la tabla en orden, **por
   pantalla, como una persona**; la rutina de la noche cuando el día la tiene. **El sábado 36 el dueño no valida;
   lo hace el domingo 37** (rutina `dia-37-noche.json`, o por pantalla, pero no las dos).
2. **No se repara nada.** Lo que no coincide con el esperado se anota tal cual (qué pasó y captura) y se sigue.
3. **No preguntar a Carlos** por algo que el guion resuelve. Si el guion no alcanza, anotar la duda en el informe y
   seguir. **Solo se frena un día entero si su rutina falla**: no operar ese día, anotar y avisar.
4. Consola solo para el reloj y las rutinas. Ninguna consulta ni cambio directo en la base.
5. Las clases se buscan por grupo, día y hora. Las fichas repetidas (Camila Benítez y Mora Benítez tienen una por
   deporte) se cobran desde cada ficha.
6. Claves de los usuarios: las mismas de las tandas anteriores.

**Qué entregar:**
- `DIA-34.md` a `DIA-40.md` en `docs/06-pruebas/PRU-04/`, uno por día: tabla con #, caso, resultado (cumple, no
  cumple o no probado), lo que mostró la pantalla con sus textos exactos y la evidencia.
- Capturas en `docs/06-pruebas/PRU-04/evidencia-dias-34-40/`, una por paso con resultado, nombradas por número de
  paso. Si una captura no muestra el texto, conservar el texto aparte.
- Al final de cada día, las cifras de pantalla: Cobranza (deuda y estados), Reportes (ingresos, egresos y
  disponible por medio) y «Sin asistencia». El día 39, además, los saldos de Efectivo y Galicia después de la
  transferencia.
- Firmar en `docs/00-estado/LOG-CODEX.md`. Para el commit: `git status`, agregar por nombre, nunca `git add -A`.
  Si un archivo propio tiene cambios ajenos, frenar y avisar. No tocar código, vistas, el catálogo ni el tablero.
