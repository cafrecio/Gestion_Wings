# Ficha de fuentes: Cerrar y pagar una liquidación

- **Página del manual:** `resources/manual/admin/cerrar-pagar-liquidacion.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/liquidaciones/{id}`
- **Controlador / Servicio:** `app/Http/Controllers/LiquidacionWebController.php:cerrar`, `app/Http/Controllers/LiquidacionWebController.php:pagar`, `app/Services/LiquidacionService.php`
- **Vista Blade:** `resources/views/liquidaciones/show.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Tocar botón `Cerrar` en liquidación ABIERTA (cumplido fin de mes) | Cambia estado a CERRADA y congela importes y clases | `cerrar-pagar-liquidacion-paso1.png` |
| 2 | Seleccionar tipo de caja, fecha y tocar `Pagar` | `LiquidacionService::pagar` genera EGRESO en Cashflow y pasa estado a PAGADA | `cerrar-pagar-liquidacion-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Intentar cerrar antes de fin de mes | En vista oculta botón `Cerrar` y en servicio bloquea | `No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el ` | Sí | Qué hago si: ¿Qué hago si no veo el botón Cerrar? | — |
| Intentar cerrar o pagar con cuotas atrasadas pendientes | En vista avisa y en servicio bloquea | `Primero validá o rechazá cada cuota atrasada.` | Sí | Qué hago si: ¿Qué hago si al querer cerrar o pagar me dice que hay cuotas pendientes? | — |
| Tipo de caja sin indicar al pagar | Validación requerida en formulario | `Seleccioná el tipo de caja.` | No | — | Validación estándar de formulario requerida en el selector |
| Intentar cancelar una liquidación ya pagada | `Liquidacion::boot` bloquea | `No se puede cancelar una liquidación pagada.` | Sí | Qué hago si: ¿Puedo cancelar una liquidación si me equivoqué al cerrarla? | — |
| Liquidación pagada con éxito | Redirige con flash a pantalla de desglose | `Liquidación pagada. Movimiento registrado en cashflow.` | No | — | Flash message al confirmar el pago en la vista |
