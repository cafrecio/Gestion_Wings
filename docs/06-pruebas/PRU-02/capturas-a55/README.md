# A55 / A13 / B1 — capturas y redacción aprobada

06/10/2026 — Codex CAB. Rama `a55-inscripcion`, código `31194c2`.
Preparación original conservada. Codex CyE aplicó el 06/10 la redacción aprobada y rehizo las capturas de Fútbol y el login móvil antes de integrar.

## Qué se muestra

Una persona ficticia, un DNI y dos registros, Patín y Fútbol. Una sola inscripción
de $5.000, vinculada a Patín. Agosto ($48.000) e inscripción cobrados el 06/10 y
anulados por la ruta real: vuelve a quedar la inscripción pendiente. El historial
conserva `Ago 2026`, la fecha 06/10/2026 y el importe anulado $53.000.

La ficha de Fútbol muestra literalmente:

> La inscripción se cobra una sola vez, aunque el alumno practique varios deportes. Podés consultar el estado de ese cargo en su ficha de Patín.

## Redacción aprobada por Carlos — 06/10/2026

Carlos pidió aclarar el mensaje y aprobó con «OK» esta redacción exacta:

> La inscripción se cobra una sola vez, aunque el alumno practique varios deportes. Podés consultar el estado de ese cargo en su ficha de Patín.

El deporte mencionado debe seguir siendo el del registro que tiene el cargo, como
en la rama actual; Patín es el ejemplo mostrado, no un nombre fijo para todos.
**Aplicada por Codex CyE el 06/10:** solo el renglón aprobado, sin CSS ni otros cambios visuales. Las capturas de Fútbol fueron reemplazadas por respuestas reales con la nueva redacción. Las imágenes de Patín e historial conservan el ensayo original.
No se declara cerrado A55, A13 ni B1, ni se hizo merge o despliegue.

## Capturas reales

| Pantalla | Escritorio | Celular, contenido real de 375 px |
|---|---|---|
| Patín: inscripción pendiente | [Imagen](ficha-patin-escritorio.png) | [Imagen](ficha-patin-375.png) |
| Fútbol: aviso con la redacción aprobada | [Imagen](ficha-futbol-escritorio.png) | [Imagen](ficha-futbol-375.png) |
| Historial del cobro anulado | [Imagen](historial-anulado-escritorio.png) | [Imagen](historial-anulado-375.png) |
| Login: control de la medición | [Imagen](login-escritorio.png) | [Imagen](login-375.png) |

## Procedencia y límites

- Base exclusivamente `wings_testing_codex`; producción y base de trabajo no tocadas.
- Respuestas HTTP reales de Laravel, autenticadas como ADMIN; no HTML escrito a mano.
  Se reutilizó el escenario de `SaldoYAnulacionCoherentesTest` en un generador temporal.
  Un ensayo de generación, 12 aserciones, pasó; no se corrió ni certificó la suite completa.
- Estilos/JS compilados desde esta rama. Chrome sin ventana sobre las respuestas guardadas,
  servidas por HTTP junto a sus assets. Los recortes del historial salen de esa misma ficha.
- Celular: iframe de 375 dentro de una ventana de 600, según el método de
  [marco-375.html](../capturas-cashflow/marco-375.html); no ventana de Chrome de 375.
  Se comprobó `innerWidth = 375`; las fichas tienen `scrollWidth = 375` y CSS cargado.
  Login revisado visualmente: formulario y logo completos, sin recorte horizontal.
- Los HTML, perfil de Chrome y generadores temporales no se publican; solo estas imágenes
  y su explicación. No son una verificación de navegación interactiva ni de concurrencia.

## Reproducción del texto nuevo — Codex CyE

[Generador documental](../evidencia/cierre-a13-b1-a55/GenerarCapturasA55Test.php): 1 ensayo, 12 aserciones, base wings_testing_codex. Respuestas de Laravel sobre la rama a55-inscripcion, Chrome con marco 375. Login completo y texto dinámico inspeccionados. El entorno del worktree emitió un aviso de deprecación; no se presenta como suite completa. Los archivos PNG se guardan desde la captura del navegador, sin maqueta.
