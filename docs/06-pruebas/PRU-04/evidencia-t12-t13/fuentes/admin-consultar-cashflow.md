# Ficha de fuentes: consultar-cashflow

- **Página:** `resources/manual/admin/consultar-cashflow.md` · **Rol:** admin · Ficha actualizada por Codex CAB el 01/11/2026 para T97.
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| El filtro de cuenta cambia filas y totales del listado | `app/Http/Controllers/CashflowWebController.php:69` | `->when($tipoCajaId, fn($q) => $q->where('tipo_caja_id', $tipoCajaId));` | Leído en el código; no ejecutado en pantalla. |
| El filtro de tipo cambia las filas | `app/Http/Controllers/CashflowWebController.php:77` | `->when($tipo, fn($q) => $q->whereHas('subrubro.rubro', fn($r) => $r->where('tipo', $tipo)))` | Leído en el código; no ejecutado en pantalla. |
| Transferencia vigente ofrece Anular; original anulado y contraasiento solo Ver | `app/Http/Controllers/CashflowWebController.php:221`<br>`app/Http/Controllers/CashflowWebController.php:242` | `CashflowMovimiento::REF_ANULACION_TRANSFERENCIA => 'Anulación de transferencia #'.$mov->referencia_id,`<br>`&& $mov->anulado_at === null && $transferencia && $transferencia->anulada_at === null) {` | Leído en el código; no ejecutado en pantalla. |
| Motivo visible con el mismo número de transferencia | `resources/views/cashflow/index.blade.php:223` | `<small style="display:block; margin-top:3px; font-size:0.7rem;"><span style="display:block;white-space:normal;">{{ $mov->origenCorreccion }}</span>@if($mov->motivoTransferencia !== null)<span style="display:block;white-space:normal;">Motivo: {{ $mov->motivoTransferencia }}</span>@endif</small>` | Leído en el código; no ejecutado en pantalla. |
| Los inversos compensan los originales también por mes y cuenta | `app/Services/AnulacionTransferenciaCashflowService.php:63`<br>`app/Services/AnulacionTransferenciaCashflowService.php:64`<br>`app/Services/AnulacionTransferenciaCashflowService.php:65` | `'fecha' => $pata->fecha->toDateString(), 'subrubro_id' => $pata->subrubro_id,`<br>`'tipo_caja_id' => $pata->tipo_caja_id,`<br>`'monto' => str_starts_with($pata->monto, '-') ? substr($pata->monto, 1) : '-'.$pata->monto,` | Leído en el código; no ejecutado en pantalla. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Traspaso entre medios» | `app/Support/ClasificacionSubrubros.php:25` | Sí — ¿Qué significan los movimientos con clasificación «Traspaso entre medios»? |
