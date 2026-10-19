# T63 y T57 — Verificación de Claude · 18/10/2026 · APROBADAS Y CERRADAS

Verificado sobre `32e09e5` y `c2db5d4` (Gemini CAB). Aspecto aprobado por Carlos el 18/10:
«Las imágenes de T63 y T57 están OK».

## T63 — No se desactiva un deporte o un grupo en uso (DU-41)

- `DeporteWebController::toggleActivo` y `GrupoWebController::toggleActivo` frenan la
  desactivación si hay alumnos activos o clases futuras: no canceladas, de hoy en adelante, en la
  hora de Wings. El aviso dice cuántos hay de cada uno. JSON 422 con `{error}` (el interruptor
  vuelve a su lugar con el cartel) o redirección con el aviso. Reactivar no se frena.
- En el deporte, cuenta a los alumnos del deporte y a los de cualquiera de sus grupos.

## T57 — El medio de pago del cajón (DU-16, DU-59)

- **No se puede desactivar el medio del cajón:** ni con el interruptor
  (`TipoCajaWebController::toggleActivo`) ni desde su formulario de edición (`update`).
- **Cambiar el medio del cajón** (`CajaService::configurarMostrador`) se frena si hay una caja
  ABIERTA. El control corre con el cajón bloqueado, el mismo bloqueo que usa abrir caja, así que
  no pueden cruzarse. Queda quién lo cambió y cuándo.
- **Las cajas que ya existen no cambian:** cada una guarda su medio de efectivo al abrirse y el
  arqueo usa ese, no el del cajón.

## Pruebas

Las de Gemini (`DesactivarDeporteGrupoEnUsoTest`, `MedioPagoCajonTest`,
`CapturaConfiguracionCajaTest`) y las de caja, grupos, deportes y medios de pago: 167
aprobadas, 1 omitida, en `wings_testing_claude`. Mis controles,
[`evidencia-t63-t57-claude/VerificacionT63T57ClaudeTest.php`](evidencia-t63-t57-claude/VerificacionT63T57ClaudeTest.php)
(se corren desde esa carpeta, sin tocar `tests/`), 2 en verde:

| Control | Resultado |
|---|---|
| Cambiar el medio con una caja cerrada sin validar | Se permite; esa caja conserva su medio y su arqueo no cambia |
| Clase de hoy y clase de ayer | La de hoy frena la desactivación del grupo; con solo la de ayer, se desactiva |

## Observación que no cambia el veredicto

Elegir otra vez el mismo medio vuelve a registrar quién y cuándo, aunque no cambie nada.
