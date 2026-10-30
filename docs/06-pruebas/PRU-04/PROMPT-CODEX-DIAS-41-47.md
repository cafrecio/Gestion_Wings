# PROMPT — Prueba por días, tanda 4: días 41 a 47 (19 al 25 de noviembre)

> Prompt para Codex, escrito por Claude CyE el 30/10/2026. Carlos lo copia y se lo pasa a Codex.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Leer **entero**
`docs/06-pruebas/PRU-04/GUION-DIAS-41-47.md` antes del primer paso, incluida la parte de arriba: dice para qué sirve la
semana y qué tiene que quedar listo para el 1/12. Es la tanda 4 de 5 y sigue donde dejaste el sitio: **18/11 simulado,
ninguna caja abierta**. No repetir nada de los días 34 a 40.

**Autorización de Carlos (25/10, 28/10, 29/10 y 30/10/2026), para no volver a preguntar:** todas las operaciones de este
guion en test.gestionar-te.com.ar (base `wingstest`, datos ficticios): altas, cobros, adelantos, cancelaciones y
anulaciones, el cambio de días de gracia y de inscripción, grupo, nivel, profesor y usuario nuevos, cancelación de
clases, gastos en Cashflow (también con fecha de octubre), cambio de profesora, la baja de Lautaro y las rutinas por
consola.

**Qué cambió desde la tanda 3:**
- El sitio tiene la etapa C de la Ayuda y el aviso de T98. Fue la única subida: **las reparaciones de T94 a T101 no están
  y no se esperan en esta tanda**. Hoy esos defectos tienen que aparecer tal como dice el guion.
- Esta tanda tiene **más pantalla que las anteriores**: las profesoras también operan, hay pasos en teléfono (375 de
  ancho) y errores a propósito. Hacelos como una persona, escribiendo lo que dice el guion.
- Algunas rutinas ya no tienen todas las listas del día: las que faltan van por pantalla o quedan sin tomar a propósito.

**Cómo se trabaja** (el detalle está en el guion):
1. **Reloj, siempre hacia adelante:** 09:00 del día. Excepciones: **43 a las 09:30, 44 a las 20:00**, y **46 a las 06:13,
   esperar a que corra el recargo de las 06:15 y recién ahí 09:00**. Las otras horas del guion (18:00, 18:30, 21:00) son
   del relato: no hace falta fijarlas.
2. **Rutina con `--simular` y, si da bien, sin `--simular`**, en el lugar que marca cada día. Prestá atención a estos:
   - **41:** 41.0 va **antes** de la rutina.
   - **43:** **no hay rutina de mañana**. La lista de Federadas la toma Lucía por pantalla (43.4).
   - **44:** sin rutina; el dueño valida por pantalla (44.2).
   - **46:** la rutina de mañana va **después de 46.1**.
   - **45:** la lista de Fútbol Principiantes queda **sin tomar**; Hernán la carga el 46 (46.3). No la completes antes.
3. **No se repara nada.** Lo que no coincide se anota tal cual (qué pasó y captura) y se sigue. Los defectos que el guion
   anuncia (41.2, 42.1, 45.8, 46.7) **se ejecutan igual**: justamente hay que dejarlos retratados. En especial, **no
   «arreglar» a Clara** en 42.1: esos $270.000 de saldo los necesita la tanda 5.
4. **Los que no vienen no se marcan presentes.** La lista está en «Punto de partida». Si por error marcás a alguno, frená y
   avisá: cambia lo que da el 1/12.
5. **No preguntar a Carlos** por algo que el guion resuelve. Si el guion no alcanza, anotar la duda en el informe y seguir.
   **Solo se frena un día entero si su rutina falla**: no operar ese día, anotar y avisar.
6. Consola solo para el reloj y las rutinas. Ninguna consulta ni cambio directo en la base. Lo marcado **Solo prueba
   técnica** no se hace: lo fuerza Claude en la revisión.
7. Comandos en el servidor con `/usr/bin/php82`, no con `php`. Claves de los usuarios: las mismas de las tandas
   anteriores. Las clases se buscan por grupo, día y hora. Thiago Ruiz, Camila Benítez y Mora Benítez tienen una ficha por deporte.
8. **Volumen:** en 47.9 anotá si alguna pantalla se pone lenta o molesta al cobrar quince seguidos. Con esa impresión de
   uso alcanza; los tiempos los mide Claude.

**Qué entregar:**
- `DIA-41.md` a `DIA-47.md` en `docs/06-pruebas/PRU-04/`, uno por día: tabla con #, caso, resultado (cumple, no cumple o
  no probado), lo que mostró la pantalla con sus textos exactos y la evidencia.
- Capturas en `docs/06-pruebas/PRU-04/evidencia-dias-41-47/`, una por paso con resultado, nombradas por número de paso. Los
  pasos de teléfono, a 375 de ancho. Si una captura no muestra el texto, conservar el texto aparte.
- Al final de cada día, las cifras de pantalla: Cobranza (deuda y estados), Reportes (ingresos, egresos y disponible por
  medio) y «Sin asistencia». Además: el 44, egresos de octubre; el 45, qué nombre figura en el gasto del dueño (45.8); el
  46, cómo se ve la diferencia de la caja de Pablo al validarla.
- Firmar en `docs/00-estado/LOG-CODEX.md`. Para el commit: `git status`, agregar por nombre, nunca `git add -A`. Si un
  archivo propio tiene cambios ajenos, frenar y avisar. No tocar código, vistas, el catálogo ni el tablero.
