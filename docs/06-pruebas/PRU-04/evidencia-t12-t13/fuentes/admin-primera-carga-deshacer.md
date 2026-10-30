# Ficha de fuentes: Deshacer la primera carga

- **Página del manual:** `resources/manual/admin/primera-carga-deshacer.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/sistema/primera-carga` (sección `#paso-4` en estado TERMINADA)
- **Controlador / Servicio:** `app/Http/Controllers/PrimeraCargaWebController.php:deshacer`, `app/Services/PrimeraCargaExcelService.php:deshacer`
- **Vista Blade:** `resources/views/primera-carga/index.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/sistema/primera-carga` con carga TERMINADA | Muestra botón `Deshacer` y leyenda de disponibilidad sin actividad posterior | `primera-carga-deshacer-paso1.png` |
| 2 | Tocar `Deshacer` y confirmar diálogo | `PrimeraCargaExcelService::deshacer` elimina registros importados y vuelve estado a PENDIENTE | `primera-carga-deshacer-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Ya existen cobros registrados en el sistema | Deshabilita el botón y muestra advertencia | `No se puede deshacer: ya hay un cobro registrado. Los alumnos y deudas se conservan.` | Sí | Qué hago si: ¿Qué hago si el botón Deshacer no aparece o está bloqueado? | — |
| Confirmación requerida en la acción | Diálogo nativo/HTML de confirmación | `¿Deshacer solamente esta carga? Se conservan los usuarios y catálogos.` | Sí | Paso 2. Confirmar la reversión | — |
| Intentar deshacer cuando el estado no es TERMINADA | Bloquea la acción en el controlador | `No hay una carga para deshacer.` | No | — | Protección a nivel de servicio/controlador |
