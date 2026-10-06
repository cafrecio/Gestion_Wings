# A55 / A13 / B1 — pantallas para aprobar el texto

06/10/2026 — Codex CAB. Rama `a55-inscripcion`, código `31194c2`.
Solo preparación y capturas; no se corrigió código ni se mezcló con main.

## Qué se muestra

Una persona ficticia, un DNI y dos registros, Patín y Fútbol. Una sola inscripción
de $5.000, vinculada a Patín. Agosto ($48.000) e inscripción cobrados el 06/10 y
anulados por la ruta real: vuelve a quedar la inscripción pendiente. El historial
conserva `Ago 2026`, la fecha 06/10/2026 y el importe anulado $53.000.

La ficha de Fútbol muestra literalmente:

> Por única vez por persona. Figura en su registro de Patín, para no cobrarla dos veces.

**Pendiente:** Carlos aprueba ese texto o indica otro. No se declara cerrado A55,
A13 ni B1 por estas imágenes, ni se autoriza un merge por adelantado.

## Capturas reales

| Pantalla | Escritorio | Celular, contenido real de 375 px |
|---|---|---|
| Patín: inscripción pendiente | [Imagen](ficha-patin-escritorio.png) | [Imagen](ficha-patin-375.png) |
| Fútbol: renglón nuevo a aprobar | [Imagen](ficha-futbol-escritorio.png) | [Imagen](ficha-futbol-375.png) |
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
