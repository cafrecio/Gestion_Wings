# Ficha de fuentes: configurar-parametros

- **Página:** `resources/manual/admin/configurar-parametros.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Días de gracia y día de recargo del 1 al 28 | `app/Http/Controllers/ConfiguracionWebController.php:35` | `'ayuda' => 'Elegí un día del 1 al 28. Con 10, una cuota de este mes pasa a morosa el día 11. Si debe meses anteriores, sigue siendo deudor.',` | Leído en el código; no ejecutado en pantalla. |
| Reglas de la primera cuota: cubrir del 1 al 31 sin pisarse | `app/Support/ReglasPrimerPagoValidator.php:37` | `: sprintf('Los días 1 a %d quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.', $faltanHasta);` | Ejecutado (CapturasEtapaCTest): HTTP 422 · «Los días 1 a 15 quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.» |
| Generación de cuotas fija el día 1 a las 06:00 | `resources/views/configuraciones/index.blade.php:34` | `<p class="text-wings-muted mb-3">El sistema genera las cuotas automáticamente el día 1 de cada mes, a las 06:00.</p>` | Captura de la pantalla real (CapturasEtapaCTest). |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «No se guardó.» | `resources/views/configuraciones/index.blade.php:10` | Sí — ¿Qué hago si al guardar aparece «No se guardó.»? |
