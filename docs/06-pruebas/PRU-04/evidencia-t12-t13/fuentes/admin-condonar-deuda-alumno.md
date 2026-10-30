# Ficha de fuentes: condonar-deuda-alumno

- **Página:** `resources/manual/admin/condonar-deuda-alumno.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Condonar cuota: solo admin y cuota pendiente | `resources/views/alumnos/show.blade.php:214` | `$puedeCondonarCuota = Auth::user()->isAdmin() && $deuda->estado === \App\Models\DeudaCuota::ESTADO_PENDIENTE;` | Captura de la pantalla real (CapturasEtapaCTest). |
| Motivo de 10 a 500 caracteres | `app/Http/Controllers/AlumnoWebController.php:211` | `'motivo.min' => 'El motivo de la condonación debe tener al menos 10 caracteres.',` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «El motivo de la condonación debe tener al menos 10 caracteres.» |
| Se perdona solo el saldo | `app/Services/PagoCuotaService.php:359` | `// Se perdona solo el saldo; original, pagos e imputaciones son historia.` | Leído en el código; no ejecutado en pantalla. |
| Reactivar en Cuotas condonadas | `resources/views/alumnos/show.blade.php:273` | `data-abrir-reactivar="{{ route('web.deudas.reactivar', $deuda->id) }}">Reactivar</button>` | Leído en el código; no ejecutado en pantalla. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Se perdona el saldo pendiente. No mueve plata. Podés reactivarlo con motivo desde esta ficha.» | `resources/views/alumnos/show.blade.php:463` | Sí — Pasos para condonar |
| «Deuda condonada correctamente.» | `app/Http/Controllers/AlumnoWebController.php:231` | Sí — Pasos para condonar |
| «El motivo de la condonación debe tener al menos 10 caracteres.» | `app/Http/Controllers/AlumnoWebController.php:211` | Sí — ¿Qué hago si dice «El motivo de la condonación debe tener al menos 10 caracteres.»? |
