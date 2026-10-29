# Ficha de fuentes: Cancelar un movimiento en caja (Operativo)

- **Página del manual:** `resources/manual/operativo/cancelar-movimiento-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/{cajaId}/detalle`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Requisito: caja abierta o rechazada** | Solo se puede cancelar cobros mientras la caja está Abierta o Rechazada. Si está cerrada o validada no permite cancelación. | `app/Http/Controllers/CajaWebController.php:601`<br>`resources/views/caja/detalle.blade.php:201` | `if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA']))` | En caja en estado `CERRADA` o `VALIDADA`, el botón rojo **Cancelar** no se muestra. Al invocar la ruta directamente por GET o POST, Wings redirige con el mensaje de error: `Solo se puede cancelar un cobro con la caja abierta o rechazada.`. |
| **Paso 1: Detalle de caja** | En **Caja**, tocar botón **Detalle** en la tarjeta del turno para abrir el listado de operaciones. | `resources/views/caja/index.blade.php:75`<br>`resources/views/caja/detalle.blade.php:4` | `@if($esPropietario \|\| Auth::user()->isAdmin())` | Acceso a `/caja/{id}/detalle` lista todas las operaciones del turno con sus importes y estados. |
| **Paso 2: Tocar Cancelar** | En la fila del cobro de cuota, tocar botón rojo **Cancelar**. Despliega panel **Cancelar cobro de cuota**. | `resources/views/caja/detalle.blade.php:201, 305`<br>`app/Http/Controllers/CajaWebController.php:608` | `if (is_null($movimiento->alumno_id)) { abort(403, 'Solo se pueden cancelar cobros de cuota.'); }` | El botón Cancelar solo aparece para cobros de cuota con alumno asociado (`es_cobro_cuota`). Si se intenta forzar la cancelación sobre un movimiento manual sin alumno, devuelve `HTTP 403: Solo se pueden cancelar cobros de cuota.`. |
| **Paso 3: Motivo y confirmar** | Escribir motivo obligatorio y tocar botón **Confirmar**. Tacha movimiento, descuenta dinero y restaura deuda a pendiente. | `resources/views/caja/detalle.blade.php:315, 317`<br>`app/Http/Controllers/CajaWebController.php:625` | `Validator::make($request->all(), ['motivo' => 'required\|string\|max:500'])->validate();` | Al enviar motivo vacío, el formulario no se envía. Con motivo válido, la operación queda tachada con badge rojo `Cancelado: <motivo>` y la cuota vuelve a `PENDIENTE`. |
| **Qué hago si: caja cerrada sin validar** | Si la caja está Cerrada, la operativa pide al dueño que la rechace para que vuelva a estar en estado Rechazada y habilitar el botón Cancelar. | `app/Http/Controllers/CajaWebController.php:601`<br>`resources/views/caja/detalle.blade.php:201` | `if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA']))` | Comprobado: al pasar la caja a estado `RECHAZADA`, el botón **Cancelar** vuelve a renderizarse en la tabla y el controlador admite la solicitud. |
| **Qué hago si: caja validada** | Caja validada nunca se reabre ni altera; corrección monetaria compensatoria en Cashflow por el dueño. | `app/Services/CashflowService.php`<br>`resources/manual/admin/corregir-movimiento-caja-validada.md` | Balance contable cerrado y auditado inmutable. | En caja `VALIDADA` todos los botones de alteración y cancelación permanecen bloqueados. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Comentario / Motivo |
|---|---|---|---|
| `Solo se puede cancelar un cobro con la caja abierta o rechazada.` | `app/Http/Controllers/CajaWebController.php:601` | Sí | Explica que con la caja cerrada o validada no se puede cancelar el cobro. |
| `Solo se pueden cancelar cobros de cuota.` | `app/Http/Controllers/CajaWebController.php:608` | Sí | Explica que la cancelación aplica exclusivamente a cobros de cuotas con alumno asociado. |
| `El campo motivo es obligatorio.` | `app/Http/Controllers/CajaWebController.php:625` | Sí | Explica que se debe escribir el motivo obligatorio de la anulación. |
| `Cobro cancelado y deuda revertida.` | `app/Http/Controllers/CajaWebController.php:630` | Sí | Mensaje de éxito al cancelar la operación. |
