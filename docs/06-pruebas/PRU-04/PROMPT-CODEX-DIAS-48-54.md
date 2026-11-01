# PROMPT — Prueba por días, tanda 5: días 48 a 54 (26/11 al 2/12)

> Prompt para Codex, escrito por Claude CAB el 01/11/2026. **Todavía no se entrega:** antes Claude tiene que desplegar `main` en el sitio de prueba y correr ahí `dia-48-manana.json --simular`.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Leer **entero** `docs/06-pruebas/PRU-04/GUION-DIAS-48-54.md` antes del primer paso. Es la última tanda y sigue donde dejaste el sitio: **25/11 simulado, ninguna caja abierta**. No repetir nada de los días 41 a 47.

**Autorización de Carlos, para no volver a preguntar:** todas las operaciones de este guion en test.gestionar-te.com.ar (base `wingstest`, datos ficticios): altas, cobros, adelantos, cancelaciones y anulaciones, condonaciones de cuota y de inscripción, transferencias y su anulación, ajustes de saldo, gastos, cambios de frecuencias, grupos, niveles, deportes, medios de pago, subrubros, configuración e inscripción, usuarios (también un segundo dueño), desactivar y reactivar usuarios, resolver la Revisión, generar, cancelar, ajustar, eliminar y pagar liquidaciones, cancelar clases, reactivar a Lautaro y las rutinas por consola.

**Qué cambió desde la tanda 4:**
- El sitio tiene `main`: T94 a T102 y T105 a T109. Son las reparaciones que venís retratando: ahora se prueban.
- Vienen **tres defectos nuevos**, anunciados en el guion (48.4 y 53.4). **Se ejecutan igual** y se anotan.
- Clara y Thiago se limpian **por pantalla**, en 49.2 y 49.3. No por otro camino.
- El guion marca avisos donde el catálogo está viejo (50.6, 52.1 y 52.7): anotalos y seguí.

**Cómo se trabaja** (el detalle está en el guion):
1. **Reloj, siempre hacia adelante:** 09:00 del día. Excepciones: **50 a las 09:30, después 10:30 y 12:30; 51 a las 20:00; 53 a las 05:55**, esperar las 06:00 y las 06:15, y después 08:30 y 09:00.
2. **Rutina con `--simular` y, si da bien, sin `--simular`**, en el lugar que marca cada día:
   - **50:** la rutina va **después de 50.2**: Pablo ya abrió por pantalla.
   - **51:** sin rutina.
   - **52:** Hernán y Lucía **no cargan** sus listas. Las de Fútbol Principiantes e Intermedias van el 53.
   - **52:** la noche **no valida**: se valida en 53.1.
   - **53:** la rutina va **después de 53.3**.
3. **No se repara nada.** Lo que no coincide se anota tal cual, con captura, y se sigue.
4. **Los que no vienen no se marcan presentes.** La lista está en «Punto de partida». Si por error marcás a alguno, avisá: cambia lo que da el 1/12.
5. **Teléfono:** con el método del guion, y dejando `window.innerWidth` en la evidencia. Si no da 375, **no probado**.
6. **Decisiones:** no preguntar a Carlos lo que el guion resuelve. Si el guion no alcanza, anotá la duda y seguí con el resto. **Solo se frena un día entero si su rutina falla**, y aun así seguí con lo que no dependa de ese día.
7. Consola solo para el reloj y las rutinas. Ninguna consulta ni cambio directo en la base. Lo marcado **Solo prueba técnica** no se hace.
8. Comandos en el servidor con `/usr/bin/php82`, no con `php`. Claves de los usuarios: las mismas de las tandas anteriores; la de Vanina Socia está en el guion. Las clases se buscan por grupo, día y hora. Thiago Ruiz, Camila Benítez, Mora Benítez y, desde 49.4, Mateo Rodríguez tienen una ficha por deporte.

**Qué entregar:**
- `DIA-48.md` a `DIA-54.md` en `docs/06-pruebas/PRU-04/`, uno por día: tabla con #, caso, resultado (cumple, no cumple o no probado), lo que mostró la pantalla con sus textos exactos y la evidencia.
- Capturas en `docs/06-pruebas/PRU-04/evidencia-dias-48-54/`, una por paso con resultado, nombradas por número de paso. Si una captura no muestra el texto, conservar el texto aparte.
- Al final de cada día, las cifras de pantalla: Cobranza (deuda y estados), Reportes (ingresos, egresos y disponible por medio) y «Sin asistencia». Además: el 48, las tarjetas de 48.9; el 53, la constancia de la generación y la lista de Revisión; el 54, noviembre y octubre en Reportes.
- Firmar en `docs/00-estado/LOG-CODEX.md`. Para el commit: `git status`, agregar por nombre, nunca `git add -A`. Si un archivo propio tiene cambios ajenos, frenar y avisar. No tocar código, vistas, el catálogo ni el tablero.
