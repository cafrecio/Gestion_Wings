# Ficha de fuentes: administrar-rubros

- **Página:** `resources/manual/admin/administrar-rubros.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| El operativo ve un subrubro solo si es Ambos y Afecta caja | `app/Http/Controllers/CajaWebController.php:1140` | `$q->where('permitido_para', 'OPERATIVO')->where('afecta_caja', true);` | Leído en el código; no ejecutado en pantalla. |
| Qué es: ingreso → Del club o Aporte; egreso → Del club o Retiro | `app/Support/ClasificacionSubrubros.php:64` | `return $tipoRubro === 'INGRESO' ? [self::NEGOCIO, self::APORTE] : [self::NEGOCIO, self::RETIRO];` | Leído en el código; no ejecutado en pantalla. |
| Cambiar el tipo con un subrubro incompatible se rechaza | `app/Http/Controllers/RubroWebController.php:85` | `if ($noCorresponden->isNotEmpty()) {` | Leído en el código; no ejecutado en pantalla. |
| No elimina un rubro con subrubros | `app/Http/Controllers/RubroWebController.php:107` | `->with('error', 'No se puede eliminar: tiene subrubros asociados.');` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «No se puede eliminar: tiene subrubros asociados.» |
| Pausar / Activar en la fila del subrubro | `resources/views/rubros/index.blade.php:87` | `<button type="submit" class="ds-btn-row {{ $subrubro->activo ? 'ds-btn-row--dang' : 'ds-btn-row--sec' }}">{{ $subrubro->activo ? 'Pausar' : 'Activar' }}</button>` | Captura de la pantalla real (CapturasEtapaCTest). |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Rubro creado correctamente.» | `app/Http/Controllers/RubroWebController.php:37` | Sí — Crear un rubro |
| «Subrubro creado correctamente.» | `app/Http/Controllers/SubrubroWebController.php:62` | Sí — Crear un subrubro |
| «No se puede eliminar: tiene subrubros asociados.» | `app/Http/Controllers/RubroWebController.php:107` | Sí — ¿Qué hago si dice «No se puede eliminar: tiene subrubros asociados.»? |
| «No se puede editar: rubro reservado del sistema.» | `app/Http/Controllers/RubroWebController.php:46` | Sí — ¿Qué hago si dice «No se puede editar: rubro reservado del sistema.»? |
