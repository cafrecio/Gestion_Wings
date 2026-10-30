# Ficha de fuentes: Revisar el desglose de una liquidación

- **Página del manual:** `resources/manual/admin/revisar-desglose-liquidacion.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/liquidaciones/{id}`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Acceso exclusivo admin** | La auditoría del desglose de liquidación es exclusiva del dueño. Permite inspeccionar clases dictadas y cuotas computadas. | `routes/web.php:149`<br>`app/Http/Middleware/EnsureUserIsAdmin.php:17` | `if (!auth()->user()->isAdmin()) abort(403);` | Al ingresar a `/liquidaciones/{id}` con un profesor o usuario operativo, Wings aborta de inmediato con HTTP 403 Forbidden. Con id inexistente devuelve HTTP 404. |
| **Paso 1: Cabecera e importes generales** | En Liquidaciones, abrir el desglose. Auditar: Profesor, Deporte, Período, Tipo (*Por hora* o *Por comisión*), Monto final y Estado (*Abierta*). | `resources/views/liquidaciones/show.blade.php:30-85` | `if ($liquidacion->estaAbierta())` | Mientras la liquidación está en revisión figura como Abierta y permite recalcular o ajustar (si es comisión). |
| **Paso 2: Tabla analítica de clases o comisiones** | Por hora: fecha, grupo, minutos, tarifa congelada y subtotal. Solo clases dictadas con asistencia y no canceladas. Por comisión: alumnos con pago verificado. | `resources/views/liquidaciones/show.blade.php:145-230`<br>`app/Services/LiquidacionService.php:510-580` | `if ($clase->asistencia_tomada && !$clase->cancelada)` | Las clases que figuran como no dictadas o con lista pendiente no entran en el cómputo. Si la asistencia se carga tardíamente, el botón **Recalcular** las incorpora. |
| **Qué hago si: cuotas atrasadas pendientes** | Si ingresaron pagos de meses anteriores, Wings exige presionar **Validar** o **Rechazar** en cada una. Si se intenta cerrar o pagar sin resolverlas, rechaza con: «Primero validá o rechazá cada cuota atrasada.». | `resources/views/liquidaciones/partials/revision-atrasadas.blade.php:1-40`<br>`app/Services/LiquidacionService.php:285-287` | `if ($liquidacion->tieneAtrasadasPendientes()) throw new \Exception('Primero validá o rechazá cada cuota atrasada.');` | Con cuotas atrasadas sin resolver, el botón **Cerrar** se oculta y cualquier intento de enviar el formulario por POST es rechazado con el mensaje textual: «Primero validá o rechazá cada cuota atrasada.». |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Primero validá o rechazá cada cuota atrasada.` | `app/Services/LiquidacionService.php:286` | Sí | Qué hago si: ¿Qué hago si hay cuotas atrasadas pendientes de revisión? |
| `Revisá las cuotas atrasadas para cerrar.` | `resources/views/liquidaciones/partials/acciones.blade.php:10` | No | Mensaje visible en la barra de acciones superior mientras haya cuotas atrasadas pendientes. |
| `Elegí validar o rechazar.` | `app/Services/LiquidacionService.php:334` | No | Validación del endpoint de decisión de cuotas atrasadas. |
| `No se puede modificar una liquidación cerrada.` | `app/Models/Liquidacion.php:124` | No | Regla de inmutabilidad del modelo de liquidaciones. |
