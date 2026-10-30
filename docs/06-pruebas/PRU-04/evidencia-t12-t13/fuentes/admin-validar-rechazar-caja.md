# Ficha de fuentes: Validar o rechazar el cierre de caja de un turno

- **Página del manual:** `resources/manual/admin/validar-rechazar-caja.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/caja/{id}/resumen`
- **Controlador / Servicio:** `app/Http/Controllers/CajaWebController.php:validarCaja`, `app/Http/Controllers/CajaWebController.php:rechazarCaja`
- **Vista Blade:** `resources/views/caja/resumen.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/caja/{id}/resumen` con caja CERRADA | Muestra cuadro `Efectivo del mostrador` y botones `Validar` y `Rechazar` | `validar-rechazar-caja-paso1.png` |
| 2 | Tocar `Rechazar` | Abre modal `#modal-rechazar` con input `motivo` y botón `Rechazar` | `validar-rechazar-caja-paso2.png` |
| 3 | Tocar `Validar` | `CajaWebController::validarCaja` cambia a VALIDADA e impacta en Cashflow | `validar-rechazar-caja-paso3.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Motivo obligatorio al rechazar | Valida que el texto no esté vacío | `El motivo del rechazo es obligatorio.` | No | — | Validación estándar de formulario requerida en el controlador |
| Intentar validar una caja que no está CERRADA | Exige estado cerrado | `Solo se pueden validar cajas cerradas.` | No | — | La vista solo muestra los botones de validación si el estado es CERRADA |
| Intentar rechazar una caja que no está CERRADA | Exige estado cerrado | `Contá y cerrá la caja antes de rechazarla.` | No | — | La vista solo muestra el botón Rechazar si el estado es CERRADA |
| Caja validada exitosamente | Confirma pase a Cashflow | `Caja validada y reflejada en cashflow.` | No | — | Flash message al redirigir al resumen |
