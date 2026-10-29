# PROMPT — Prueba por días, tanda 1: días 24 a 28 (2 al 6 de noviembre)

> Prompt para Codex, escrito por Claude CAB el 29/10/2026. Carlos lo copia y se lo pasa a Codex.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Leer **entero**
`docs/06-pruebas/PRU-04/GUION-DIAS-24-28.md` antes del primer paso. Es la tanda 1 de 2; la tanda 2 (días 29 a
33) **no se corre** hasta que Claude revise esta.

**Autorización de Carlos (25/10 y 28/10/2026), para no volver a preguntar:** todas las operaciones de plata de
este guion en test.gestionar-te.com.ar (base `wingstest`, datos ficticios): cobros, cobros parciales con motivo,
cobros de alumnos inactivos con Sí o No, reactivaciones, bajas con el interruptor, eliminación de un alumno y
de una serie cargados por error, cierre y pago de las ocho liquidaciones, sueldos de las tres operativas,
canchas, transferencia entre medios, cambio de forma de liquidar de Patín, porcentajes de profesoras, clases
nuevas, canceladas y eliminadas, y las rutinas por consola.

**Qué cambió en el sitio desde el día 23** (desplegado por Claude el 28 y 29/10):
- Están todas las tareas que se cerraron estos días (T25, T45, T46, T51, T54, T62, T65, T70/T71, T84 a T93,
  B3). El guion las recorre; el catálogo del repo ya las describe.
- **Las confirmaciones nativas del navegador se aceptan solas** en el sitio de prueba (pedido de Carlos, para
  que el Chrome automático no se trabe). Los **paneles propios de Wings** no: se completan como dice la tabla.
- La rutina (`scripts/pruebas/rutina/rutina.php`) ahora también carga series de clases y lleva los motivos de
  B3 en las listas. Los planes de cada día están en `docs/06-pruebas/PRU-04/rutina/dia-24-*.json` a
  `dia-28-*.json`, ya en el servidor.

**Cómo se trabaja** (detalle en el guion):
1. Por día: fijar el reloj (`wings:fecha-simulada`, 09:00; **el día 27 a las 07:58** y esperar que pasen las
   08:00); rutina de la mañana con `--simular` y, si da bien, sin `--simular`; las tareas de la tabla en orden,
   **por pantalla, como una persona**; la rutina de la noche cuando el día la tiene. El día 28 no hay noche:
   Rocío deja la caja abierta a propósito.
2. **No se repara nada.** Lo que no coincide con el esperado se anota tal cual (qué pasó, captura) y se sigue.
3. **No preguntar a Carlos** por algo que el guion resuelve. Si el guion no alcanza para decidir, anotar la
   duda en el informe y seguir con el paso siguiente. **Solo se frena un día entero si su rutina falla**: no
   operar ese día, anotar el mensaje y avisar.
4. Consola solo para el reloj, las rutinas y el paso 24.8. Ninguna consulta ni cambio directo en la base.
5. Las clases se buscan por grupo, día y hora. Los números de liquidación (#2 a #9) sí son los del sitio.
6. Claves de los usuarios: las mismas de los días anteriores.

**Qué entregar:**
- `docs/06-pruebas/PRU-04/DIA-24.md` a `DIA-28.md`, uno por día: tabla con #, caso, resultado (cumple / no
  cumple / no probado), lo que mostró la pantalla con sus textos exactos, y la evidencia.
- Capturas en `docs/06-pruebas/PRU-04/evidencia-dias-24-28/`, una por paso con resultado, nombradas por número
  de paso.
- Al final de cada día, las cifras de cierre que muestra la pantalla (Cobranza: deuda y estados; Reportes:
  ingresos, egresos y disponible por medio; «Sin asistencia»), para compararlas con el cierre del guion.
- Firmar en `docs/00-estado/LOG-CODEX.md`. Commit: `git status`, agregar por nombre, nunca `git add -A`; si un
  archivo propio tiene cambios ajenos, frenar y avisar. No tocar código, vistas, el catálogo ni el tablero.
