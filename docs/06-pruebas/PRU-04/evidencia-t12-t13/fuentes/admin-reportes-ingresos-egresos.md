# Ficha de fuentes: reportes-ingresos-egresos

- **Página:** `resources/manual/admin/reportes-ingresos-egresos.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Perder la deuda de un inactivo: solo el dueño, con confirmación | `resources/views/cobranza/_deuda-inactivos.blade.php:19` | `@if(auth()->user()->isAdmin())` | Leído en el código; no ejecutado en pantalla. |
| No hay meses futuros | `app/Http/Controllers/ReporteWebController.php:51` | `'mes' => ['sometimes', 'required', 'date_format:Y-m', 'after_or_equal:1900-01', 'before_or_equal:'.today()->format('Y-m')],` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «El campo mes debe ser una fecha anterior o igual a 2026-10.» |
| Aviso de resultado parcial | `resources/views/reportes/index.blade.php:108` | `<details class="filtros-card rv-detail" style="margin-top:12px"><summary>Sin clasificar · {{ $reporte['sin_clasificar'] }}</summary><p class="rv-note">El resultado es parcial: falta clasificar movimientos del negocio.</p></details>` | Captura de la pantalla real (CapturasEtapaCTest). |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «No vuelve a cobrarse al reactivar.» | `resources/views/cobranza/_deuda-inactivos.blade.php:20` | Sí — Deuda de alumnos inactivos |
| «El resultado es parcial: falta clasificar movimientos del negocio.» | `resources/views/reportes/index.blade.php:108` | Sí — ¿Qué hago si aparece «El resultado es parcial: falta clasificar movimientos del negocio.»? |
