# Ficha de fuentes: Auditar el detalle y arqueo de una caja

- **Página del manual:** `resources/manual/admin/auditar-detalle-caja.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/caja/{id}/detalle`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Acceso exclusivo admin** | El dueño ingresa a `/caja/{id}/detalle` para auditar movimientos individuales, medios de pago y recibos. | `app/Http/Controllers/CajaWebController.php:576-583` | `if (!auth()->user()->isAdmin()) abort(403);` | Al ingresar a la URL con usuario profesor u operativo sin permiso de auditoría, el sistema responde HTTP 403 Forbidden. Con una caja inexistente (id 99999) devuelve HTTP 404 Not Found. |
| **Paso 1: Datos del turno y estado** | Muestra datos del turno (Operativo, Apertura, Cierre, Estado). Si está Cerrada, ofrece **Validar** o **Rechazar**. | `resources/views/caja/detalle.blade.php:38-66`<br>`app/Models/CajaOperativa.php:65` | `@if($caja->estado === 'CERRADA')`<br>`<x-ds.button ...>Validar</x-ds.button>`<br>`<x-ds.button ...>Rechazar</x-ds.button>`<br>`@endif` | Si la caja está en estado `ABIERTA` o `VALIDADA`, los botones de decisión **Validar** y **Rechazar** no se renderizan en el HTML de la barra de acciones. Al intentar enviar POST a validar en caja abierta, rechaza con HTTP 422: `Solo se pueden validar cajas cerradas.`. |
| **Paso 2: Tabla de movimientos y Recibo** | Lista cronológica de movimientos con fecha, medio, rubro, alumno y monto. Enlace **Recibo** abre el PDF oficial. | `resources/views/caja/detalle.blade.php:108-165`<br>`resources/views/caja/detalle.blade.php:152` | `@if($m->pago_id)`<br>`<a href="{{ route('web.recibos.cuota', $m->pago_id) }}?inline=1"...>Recibo</a>`<br>`@endif` | En movimientos que no provienen de un cobro de cuota (por ejemplo, gastos de caja o ingresos generales), el enlace **Recibo** no se muestra en la fila correspondiente. Si se solicita un recibo de un pago inexistente, responde HTTP 404. |
| **Qué hago si: anulación de cobro** | El dueño no cancela cobros desde el detalle. Si la caja está abierta/rechazada cancela la operativa; si está validada, va por Cashflow. | `app/Http/Controllers/CajaWebController.php:601`<br>`resources/views/caja/detalle.blade.php:155` | `if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA']))` | En caja cerrada o validada, el botón rojo de cancelar no se muestra. Al forzar una petición POST de cancelación sobre una caja validada, redirige con flash error: `Solo se puede cancelar un cobro con la caja abierta o rechazada.`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Deslizá la tabla para ver todas las columnas.` | `resources/views/caja/detalle.blade.php:83` | No | Indicación responsive para celulares cuando la tabla desborda horizontalmente. |
| `Esta caja no tiene movimientos.` | `resources/views/caja/detalle.blade.php:97` | No | Estado vacío informativo que solo se presenta si la caja fue abierta y cerrada sin ninguna operación. |
| `Solo se puede cancelar un cobro con la caja abierta o rechazada.` | `app/Http/Controllers/CajaWebController.php:601` | No | Explicado conceptualmente en «¿Puedo anular un cobro desde esta pantalla siendo administrador?» sin citar el error técnico de URL. |
| `Solo se pueden cancelar cobros de cuota.` | `app/Http/Controllers/CajaWebController.php:608` | No | Restricción interna que impide cancelar gastos o aportes mediante la ruta de cobros. |
