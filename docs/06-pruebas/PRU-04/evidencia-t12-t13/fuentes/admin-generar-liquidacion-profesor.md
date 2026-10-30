# Ficha de fuentes: Generar la liquidación mensual de profesores

- **Página del manual:** `resources/manual/admin/generar-liquidacion-profesor.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/liquidaciones/crear`
- **Controlador / Servicio:** `app/Http/Controllers/LiquidacionWebController.php:create`, `app/Http/Controllers/LiquidacionWebController.php:store`, `app/Services/LiquidacionService.php:generarLiquidacionMensual`
- **Vista Blade:** `resources/views/liquidaciones/index.blade.php` y `resources/views/liquidaciones/create.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/liquidaciones` y tocar `Nuevo` | Muestra listado histórico y abre formulario `/liquidaciones/crear` | `generar-liquidacion-profesor-paso1.png` |
| 2 | Seleccionar profesor, mes, año y tocar `Generar` | `LiquidacionService::generarLiquidacionMensual` crea liquidación ABIERTA | `generar-liquidacion-profesor-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Liquidación ya existente para el profesor y período | Bloquea duplicado en `generarLiquidacionMensual` | `Ya existe una liquidación para el profesor en MM/AAAA.` | Sí | Qué hago si: ¿Qué hago si Wings dice que ya existe una liquidación para ese mes? | — |
| Profesor sin seleccionar | Valida campo requerido | `Seleccioná un profesor.` | No | — | Validación estándar de formulario |
| Mes o año sin seleccionar | Valida campos requeridos | `Seleccioná el mes.` / `Ingresá el año.` | No | — | Validación estándar de formulario |
| Liquidación generada con éxito | Redirige al show con flash | `Liquidación generada correctamente.` | No | — | Mensaje flash al redirigir al desglose |
