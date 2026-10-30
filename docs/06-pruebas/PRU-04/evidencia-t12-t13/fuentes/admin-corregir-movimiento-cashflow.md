# Ficha de fuentes: corregir-movimiento-cashflow

- **Página:** `resources/manual/admin/corregir-movimiento-cashflow.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Eliminar: pantalla con Motivo (opcional), Eliminar y Cancelar | `resources/views/cashflow/eliminar.blade.php:17`<br>`resources/views/cashflow/eliminar.blade.php:24`<br>`resources/views/cashflow/eliminar.blade.php:25` | `<label for="motivo" class="info-label mt-3"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block; vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 002 2z"/></svg>Motivo (opcional)</label>`<br>`<x-ds.button variant="secondary" style="width:96px; height:32px; font-size:0.82rem;" :href="route('web.cashflow.index')">Cancelar</x-ds.button>`<br>`<x-ds.button variant="danger" type="submit" style="width:96px; height:32px; font-size:0.82rem; background:none; color:var(--color-btn-danger); border-color:var(--color-btn-danger);">Eliminar</x-ds.button>` | Captura de la pantalla real (CapturasEtapaCTest). |
| Editar abre el mismo formulario con el que se cargó | `app/Http/Controllers/CashflowCorreccionWebController.php:28` | `return view('cashflow.movimiento', $datos + ['movimiento' => $movimiento, 'clave' => (string) Str::uuid(),` | Leído en el código; no ejecutado en pantalla. |
| Motivo (opcional) en el formulario de edición | `resources/views/cashflow/movimiento.blade.php:173`<br>`resources/views/cashflow/movimiento.blade.php:175` | `@if($movimiento)`<br>`<label for="motivo" class="{{ $labelClass }}"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 002 2z"/></svg>Motivo (opcional)</label>` | Leído en el código; no ejecutado en pantalla. |
| El motivo es opcional | `app/Services/CashflowCorreccionService.php:35` | `'motivo' => 'nullable\|string\|max:500'];` | Leído en el código; no ejecutado en pantalla. |
| Transferencia: solo crear y guardar, sin corrección | `routes/web.php:139` | `Route::post('/cashflow/transferencia', [\App\Http\Controllers\TransferenciaCashflowWebController::class, 'store'])->name('web.cashflow.transferencia.store');` | Leído en el código; no ejecutado en pantalla. |
| Solo directos, no anulados, no reservados | `app/Models/CashflowMovimiento.php:88` | `return $this->esDirecto() && $this->anulado_at === null && !$this->subrubro?->es_reservado_sistema;` | Captura de la pantalla real (CapturasEtapaCTest). |
| Editar / Historial / Eliminar en la fila | `app/Http/Controllers/CashflowWebController.php:211` | `if ($correccionesDisponibles && $mov->puedeCorregirse()) {` | Leído en el código; no ejecutado en pantalla. |
| Eliminar anula con asiento en contra | `app/Http/Controllers/CashflowCorreccionWebController.php:48` | `return redirect()->route('web.cashflow.movimientos.show', $id)->with('success', 'Movimiento anulado. El original y el asiento en contra se conservan.');` | Leído en el código; no ejecutado en pantalla. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Movimiento actualizado. El historial se conserva.» | `app/Http/Controllers/CashflowCorreccionWebController.php:42` | Sí — Editar un movimiento |
| «Movimiento anulado. El original y el asiento en contra se conservan.» | `app/Http/Controllers/CashflowCorreccionWebController.php:48` | Sí — Eliminar un movimiento |
| «El movimiento cambió mientras editabas. Recargá la pantalla antes de guardar.» | `app/Services/CashflowCorreccionService.php:67` | Sí — ¿Qué hago si dice «El movimiento cambió mientras editabas. Recargá la pantalla antes de guardar.»? |
