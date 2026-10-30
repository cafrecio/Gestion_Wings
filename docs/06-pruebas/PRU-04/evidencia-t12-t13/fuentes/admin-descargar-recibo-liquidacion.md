# Ficha de fuentes: Descargar el recibo oficial de liquidación

- **Página del manual:** `resources/manual/admin/descargar-recibo-liquidacion.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/recibos/liquidacion/{id}`
- **Controlador / Servicio:** `app/Http/Controllers/ReciboController.php:liquidacion`, `app/Services/ReciboService.php:generarReciboLiquidacion`
- **Vista Blade:** `resources/views/pdfs/recibo-liquidacion.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/liquidaciones/{id}` en estado PAGADA | Muestra botón `Recibo` en la barra de acciones | `descargar-recibo-liquidacion-paso1.png` |
| 2 | Tocar `Recibo` | `ReciboController::liquidacion` genera PDF en formato A5 vertical con anexo | `descargar-recibo-liquidacion-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Descargar recibo de liquidación no pagada | Bloquea la descarga en controlador con 400 | `Solo se pueden generar recibos de liquidaciones que han sido pagadas` | Sí | Qué hago si: ¿Qué hago si el botón Recibo no aparece? | — |
| Liquidación no encontrada | Error 404 | `Liquidación no encontrada` | No | — | Manejo de excepción HTTP estándar |
| Error interno al generar PDF | Falla en creación de archivo | `No se pudo generar el comprobante. Por favor, intentá nuevamente o comunicate con administración.` | No | — | Manejo de error de disco o DomPDF |
