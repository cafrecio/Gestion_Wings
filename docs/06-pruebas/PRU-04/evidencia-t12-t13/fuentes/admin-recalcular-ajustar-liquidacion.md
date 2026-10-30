# Ficha de fuentes: Recalcular o ajustar una liquidación abierta

- **Página del manual:** `resources/manual/admin/recalcular-ajustar-liquidacion.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/liquidaciones/{id}`
- **Controlador / Servicio:** `app/Http/Controllers/LiquidacionWebController.php:recalcular`, `app/Http/Controllers/LiquidacionWebController.php:ajusteFinal`, `app/Services/LiquidacionService.php`
- **Vista Blade:** `resources/views/liquidaciones/show.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Tocar botón `Recalcular` en `/liquidaciones/{id}` abierta | `LiquidacionService::recalcular` actualiza clases y horas manteniendo tarifa | `recalcular-ajustar-liquidacion-paso1.png` |
| 2 | Cargar importe en campo `monto_final` y guardar | Actualiza `monto_final` y muestra diferencia calculada automáticamente | `recalcular-ajustar-liquidacion-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Ayuda en campo de monto ajustado | Informa cálculo automático de diferencia | `Total a pagar, desde $ 0. La diferencia se calcula automáticamente.` | Sí | Opción 2. Ajustar el monto final a pagar | — |
| Modificar liquidación que ya está cerrada | `Liquidacion::boot` bloquea la edición | `No se puede modificar una liquidación cerrada` | Sí | Qué hago si: ¿Qué hago si la liquidación ya fue cerrada y necesito cambiar el monto? | — |
| Monto final negativo | Validación de formulario | `El campo monto final debe ser al menos 0.` | No | — | Validación estándar de formulario |
