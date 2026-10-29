# Control de la corrección de la etapa 1 de T12/T13 (Gemini, `120ae713`) — Claude CAB, 29/10/2026

**Resultado: aprobada para seguir a la etapa 2**, con dos detalles menores que Gemini corrige en la primera tanda.
Falta el OK de Carlos.

## Lo que se comprobó

- `php scripts/manual/verificar-manual.php`: 3 páginas, 0 problemas.
- Los siete puntos de la devolución están corregidos. Se comprobó cada uno contra el código: el mensaje de caja
  sin abrir (`CajaService.php:278`), **Efectivo recibido** y la casilla «Confirmo que conté y recibí este
  efectivo.» (`caja/apertura.blade.php`), Cancelar solo con la caja abierta o rechazada
  (`CajaWebController.php:603`), sin estado parcial, medios de pago sin nombrar y el hueco de la cuota
  pagada en caja validada, dicho tal cual.
- Página nueva de la profesora: los mensajes de clase cancelada, de clase futura y de corrección existen. La
  regla de la corrección es la del código: pide motivo solo si la clase ya pasó y tenía lista
  (`ClaseWebController.php:390-392`).
- Capturas miradas: la de la profesora muestra el menú de la profesora; la del dueño, el formulario real de
  Nuevo movimiento.

## Detalles menores (no frenan)

1. `cobrar-cuota.md`, paso 1: «o entrá por **Cobranza**. Se abre la pantalla **Cobrar cuota**». Desde Cobranza,
   el botón Cobrar de cada fila va directo al cobro del alumno, no a la pantalla con el buscador.
2. `corregir-movimiento-caja-validada.md`, paso 3: elegir «la fecha del turno» pone una fecha pasada, y Wings
   pide confirmarla (`cashflow/movimiento.blade.php`, `confirmar_fecha_vieja`). La página no lo dice.
3. El revisor independiente informó 0 hallazgos; estos dos se le pasaron. Sirve como dato: el revisor no
   reemplaza el control final.
