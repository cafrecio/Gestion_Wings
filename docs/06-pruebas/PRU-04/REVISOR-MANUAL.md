# Revisor independiente del manual del usuario

> Instrucciones para un agente revisor. Las escribió Claude CAB el 28/10/2026, por pedido de Carlos.
> Sirven para cualquier agente: un subagente, otra conversación u otro modelo. El revisor **no** escribió
> las páginas y arranca **sin** el contexto de quien las escribió.

## Tu trabajo

Te pasan una o más páginas de `resources/manual/<rol>/<pagina>.md`, cada una con su ficha de fuentes en
`docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/<rol>-<pagina>.md`. Tenés que encontrar todo lo que la
página dice y Wings no hace. **No corregís nada**: hacés un informe.

Partí de la idea de que la página tiene errores. Una página que suena bien no está verificada.

## Qué revisar, en este orden

1. **Correr `php scripts/manual/verificar-manual.php`.** Copiá en el informe cada problema que tenga la página.
2. **Cada paso numerado:** abrí el archivo y la línea que cita la ficha de fuentes y comprobá que dice eso.
   Si la ficha no cita fuente para un paso, es un hallazgo.
3. **Cada «Qué hago si…»:** buscá en el código la regla que lo gobierna: el controlador, el servicio y la
   condición que permite o rechaza. Leé el cuerpo de la función, no su nombre. Un camino que el código
   rechaza (por estado, por rol o por fecha) es un hallazgo **grave**: el lector va a quedar trabado.
4. **Rol:** la acción la puede hacer el rol de la página. Comprobalo en `routes/web.php` (middleware) y
   en el controlador. Contrato de referencia: `docs/02-contratos/PERMISOS-ROLES.md`.
5. **Capturas:** cada captura corresponde a la pantalla y al momento del paso que ilustra, con el usuario
   del rol de la página (el menú lateral lo delata). No vale una captura de otra pantalla parecida.
6. **Cada condición de estado, rol o fecha** que diga la página («si la caja está Abierta o Rechazada»,
   «solo el dueño», «si la clase ya pasó»): buscá vos el `if` que la decide, sin partir de la línea que
   cita la ficha, y comparalo palabra por palabra. Cada estado que la página nombra tiene que estar en ese
   `if`, y cada estado del `if` tiene que estar en la página. Agregado el 29/10/2026: en la etapa A la ficha
   de `rectificar-caja` citaba una línea que no era la regla y decía «comprobado», y la página permitía
   rectificar una caja Rechazada que Wings rechaza. Al revisor se le pasó.
7. **Lo que falta:** un caso común de esa tarea que la página no cubre y que deja al lector sin salida
   (por ejemplo, la caja cerrada pero todavía sin validar).
8. **Rechazos y avisos de la acción:** buscá en el controlador y en el servicio de la acción cada `throw`,
   `withMessages`, `abort`, `->with('error'` y cada `'message'` de panel de confirmación o modal que el
   usuario pueda encontrar. Comprobá que la página lo cuente con el texto literal de Wings (o que la ficha
   de fuentes justifique en su tabla de rechazos y avisos por qué no corresponde). Si la acción tiene un aviso
   o rechazo que el usuario ve a diario y el manual lo omite, es un hallazgo **medio**.
   **Regla del «Sí» en la tabla de rechazos (29/10/2026):** En la tabla «Rechazos y avisos de la acción» de la
   ficha de fuentes, cada fila con «Sí» debe nombrar obligatoriamente la sección exacta de la página donde está
   documentado (por ejemplo, «Qué hago si: …» o «Paso X: punto Y»). Si la página no lo dice de manera comprobable,
   no puede marcarse «Sí»: debe marcarse «No» justificando el motivo en la columna correspondiente. Una fila
   marcada «Sí» sin sección comprobable en la página es un hallazgo **medio**.

## Informe

Guardalo en `docs/06-pruebas/PRU-04/evidencia-t12-t13/revisiones/<fecha>-<rol>-<pagina>.md`:

| # | Gravedad | Línea de la página | Qué dice la página | Qué hace Wings (archivo:línea) |
|---|---|---|---|---|

- **Grave:** el lector no puede hacer lo que se le indica, o hace algo que pierde plata o datos.
- **Media:** un nombre, un mensaje o un camino distinto del real; el lector se confunde pero sale.
- **Menor:** redacción.

Al final: «Sin hallazgos graves ni medios» o la cantidad de cada uno. Separá lo que comprobaste de lo que
inferiste.
