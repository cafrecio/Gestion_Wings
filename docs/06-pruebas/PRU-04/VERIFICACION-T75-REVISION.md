# T75 — Verificación de Claude de la interacción de Revisión · 18/10/2026 · APROBADA

Lo visual de las cinco vistas lo aprobó Carlos. A Claude le tocaba solo la interacción nueva
de Revisión de cobranza (`a8ab707`): un interruptor elige Continúa o Inactivo, con nota
obligatoria, y solo Confirmar envía.

## Qué leí

- `revision-cobranza/index.blade.php`: un formulario por revisión. Tiene un campo oculto
  `resolucion` que arranca en CONTINUA, el interruptor encendido, la nota obligatoria (5 a 500)
  y Confirmar.
- `ds-app.js:558-568`: al mover el interruptor actualiza `resolucion`, `aria-checked` y el aviso
  de la consecuencia. No hay JavaScript en la vista. El manejador general de interruptores no lo
  toca, porque este no tiene `data-url`.
- El servidor (`RevisionCobranzaService::resolver`) no cambió.

## En Chrome

Base descartable `wings_testing_claude`, dos revisiones pendientes. Scripts y resultado en
[`evidencia-t75-claude/`](evidencia-t75-claude/).

| Caso | Interruptor | Se envía | Aviso |
|---|---|---|---|
| Al abrir | Encendido | CONTINUA | «Se generará la cuota de 2026-10 al confirmar.» |
| Apagado con un clic | Apagado | INACTIVO | «Se dará de baja al alumno al confirmar…» |
| Otra pantalla y Atrás | Apagado | INACTIVO | Igual |
| Recargar la página | Encendido | CONTINUA | Vuelve al inicial |

Lo que se ve y lo que se envía coinciden siempre. Ese era el riesgo: un campo oculto que
quedara distinto del interruptor.

**Resultado en la base:**
- Sin nota, el formulario no se envía.
- Bruno, confirmado con el interruptor apagado: revisión RESUELTO/INACTIVO, alumno inactivo,
  sin cuota nueva, nota guardada.
- Carla, confirmada como estaba: RESUELTO/CONTINUA, sigue activa, se le generó la cuota de
  octubre.

## Observación que no cambia el veredicto

El aviso muestra el período como «2026-10» y no como «octubre 2026». El aspecto ya está
aprobado; si se quiere, se cambia aparte.
