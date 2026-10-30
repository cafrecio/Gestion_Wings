# Ficha de fuentes: Revisar el desglose de una liquidación

- **Página del manual:** `resources/manual/admin/revisar-desglose-liquidacion.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/liquidaciones/{id}`
- **Controlador / Servicio:** `app/Http/Controllers/LiquidacionWebController.php:show`, `app/Services/LiquidacionService.php`
- **Vista Blade:** `resources/views/liquidaciones/show.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/liquidaciones/{id}` | Muestra cabecera con profesor, período, tipo, monto final y estado | `revisar-desglose-liquidacion-paso1.png` |
| 2 | Desplazarse a la tabla de detalles | Muestra clases computadas con fecha, minutos y subtotal o comisiones de alumnos | `revisar-desglose-liquidacion-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Cuotas atrasadas pendientes de validación | Exige resolver antes de cerrar o pagar | `Primero validá o rechazá cada cuota atrasada.` | Sí | Qué hago si: ¿Qué hago si hay cuotas atrasadas pendientes de revisión? | — |
| Liquidación no encontrada | Error 404 | `ModelNotFoundException` | No | — | Manejo de excepción HTTP estándar |
| Clases no computadas por falta de asistencia | Excluye del cálculo en servicio | `clases_sin_asistencia` | No | — | Criterio de exclusión interno de la consulta |
