# Ficha de fuentes: Confirmar la importación masiva de primera carga

- **Página del manual:** `resources/manual/admin/primera-carga-confirmar.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/sistema/primera-carga#paso-4`
- **Controlador / Servicio:** `app/Http/Controllers/PrimeraCargaWebController.php:cargar`, `app/Services/PrimeraCargaExcelService.php:cargar`
- **Vista Blade:** `resources/views/primera-carga/index.blade.php` (sección `#paso-4`)

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Revisar contadores en sección 4 y tocar `Cargar` | Despliega formulario de confirmación con advertencia de no entrada a caja | `primera-carga-confirmar-paso1.png` |
| 2 | Tocar `Confirmar` | `PrimeraCargaExcelService::cargar` importa en transacción y marca TERMINADA | `primera-carga-confirmar-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Advertencia previa a confirmar | Exige ratificar antes de escribir | `Se cargará todo en una sola operación. Confirmá el resumen de arriba. No entra dinero a caja.` | Sí | Paso 2: Confirmar la operación definitiva | — |
| Carga confirmada con éxito | Informa finalización y disponibilidad de módulos | `Carga terminada. Los alumnos están en Alumnos y las deudas en Cobranza. Quien no debía arranca al día.` | Sí | Paso 2: Confirmar la operación definitiva | — |
| Cargar sin haber revisado previamente | Exige pasar por la revisión | `Primero revisá el Excel en esta sesión.` | No | — | La UI no muestra el botón Cargar si no hay revisión aprobada |
| Intentar cargar dos veces | Bloquea por estado TERMINADA | `La primera carga ya está terminada.` | No | — | Regla interna de protección cuando el estado ya cambió |
