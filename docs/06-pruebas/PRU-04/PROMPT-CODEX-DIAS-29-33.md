# PROMPT — Prueba por días, tanda 2: días 29 a 33 (7 al 11 de noviembre)

> Prompt para Codex, escrito por Claude CAB el 29/10/2026. Carlos lo copia y se lo pasa a Codex.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Leer **entero**
`docs/06-pruebas/PRU-04/GUION-DIAS-29-33.md` antes del primer paso. Es la tanda 2 de 2 y sigue exactamente
donde terminó la tuya: 6/11 simulado, caja #53 de Rocío abierta. La tanda 1 ya la revisó Claude
(`ESTADO-PRUEBA.md`): no repetir nada de los días 24 a 28.

**Autorización de Carlos (25/10, 28/10 y 29/10/2026), para no volver a preguntar:** todas las operaciones de
plata de este guion en test.gestionar-te.com.ar (base `wingstest`, datos ficticios): cobros, cobros con
recargo, el cierre de la caja de Rocío por el dueño, el rechazo y la corrección de una caja, la deuda de
Julieta dada por perdida, el perdón de un recargo, la baja de Verónica y la reasignación de sus clases, la
reactivación de una clase y la eliminación del amistoso, el aumento de Fútbol Avanzadas, **prender el recargo
por mora al 10%** en Configuración, y las rutinas por consola.

**Qué cambió desde la tanda 1:**
- **Recargo por mora:** lo prende el dueño por pantalla el día 32 (32.5). El **día 33 el reloj va a las 06:13**,
  se espera a que pasen las 06:15 (corre solo el recargo) y después se pone a las 09:00. Si se fija directo a
  las 09:00, el recargo no corre y todo el día da distinto.
- **Fichas sin botón Eliminar:** ahora dicen por qué no se puede eliminar (pedido de Carlos). Se mira en 32.2.
- El guion se volvió a simular sobre la base que dejaste vos, con todo esto incluido.

**Cómo se trabaja** (el detalle está en el guion):
1. **Por día:** fijar el reloj (09:00; el 30 a las 11:00; el 33 como dice arriba); **en los días 29, 31 y 33
   parte de la pantalla va antes de la rutina** (está marcado); rutina de la mañana con `--simular` y, si da
   bien, sin `--simular`; las tareas de la tabla en orden, **por pantalla, como una persona**; la rutina de la
   noche cuando el día la tiene. **El 30, la noche no se corre si el dueño ya validó en 30.6 por pantalla.**
2. **No se repara nada.** Lo que no coincide con el esperado se anota tal cual (qué pasó y captura) y se sigue.
3. **No preguntar a Carlos** por algo que el guion resuelve. Si el guion no alcanza, anotar la duda en el
   informe y seguir. **Solo se frena un día entero si su rutina falla**: no operar ese día, anotar y avisar.
4. Consola solo para el reloj y las rutinas. Ninguna consulta ni cambio directo en la base.
5. Las clases se buscan por grupo, día y hora.
6. Claves de los usuarios: las mismas de los días anteriores. **Verónica** (29.7) la necesita: si no la
   tenés, la pasa Carlos acá: ______.
7. Si un ingreso con la clave correcta se rechaza (pasó dos veces con Rocío el día 28), anotar la hora y el
   texto exacto, y reintentar una sola vez.

**Qué entregar:**
- `DIA-29.md` a `DIA-33.md` en `docs/06-pruebas/PRU-04/`, uno por día: tabla con #, caso, resultado (cumple,
  no cumple o no probado), lo que mostró la pantalla con sus textos exactos y la evidencia.
- Capturas en `docs/06-pruebas/PRU-04/evidencia-dias-29-33/`, una por paso con resultado, nombradas por número
  de paso. Si una captura no muestra el texto, conservar el texto aparte (como hiciste en la tanda 1).
- Al final de cada día, las cifras de pantalla: Cobranza (deuda y estados), Reportes (ingresos, egresos y
  disponible por medio) y «Sin asistencia». El día 33, además, cómo muestra Reportes los recargos (33.9).
- Firmar en `docs/00-estado/LOG-CODEX.md`. Para el commit: `git status`, agregar por nombre, nunca
  `git add -A`. Si un archivo propio tiene cambios ajenos, frenar y avisar. No tocar código, vistas, el
  catálogo ni el tablero.
