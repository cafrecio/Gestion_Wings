# Ficha de fuentes: Pasar a un alumno a Inactivo (Operativo)

- **Página del manual:** `resources/manual/operativo/pasar-alumno-inactivo.md`
- **Rol:** Operativo
- **Pantalla:** `/alumnos/{alumnoId}`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Distinción: Inactivo vs Eliminación** | Inactivo conserva historial contable y deudas. La eliminación definitiva es acción exclusiva del dueño si nunca hubo cobros. | `app/Http/Controllers/AlumnoWebController.php:435`<br>`resources/views/alumnos/show.blade.php:504` | `@if(Auth::user()->isAdmin() && $puedeEliminar)` donde `$puedeEliminar = $alumno->esEliminable();` | El botón de eliminación permanente no aparece para el rol Operativo en la ficha. |
| **Paso 1 y 2: Interruptor Activo/Inactivo** | En la ficha del alumno, interruptor cambia entre Activo e Inactivo de inmediato. | `resources/views/alumnos/show.blade.php:38`<br>`app/Http/Controllers/AlumnoWebController.php:435` | `$alumno->update(['activo' => !$alumno->activo]);` | Al pasar a Inactivo, Wings actualiza la base y muestra el aviso de confirmación. En el 1° de mes no le genera cuota y no aparece en listas de asistencia. |
| **Paso 3: Reactivar al alumno** | Cambiar interruptor a **Activo** reactiva y genera automáticamente cuota de reingreso según el día. | `app/Http/Controllers/AlumnoWebController.php:438`<br>`app/Services/PagoCuotaService.php:380` | `if ($alumno->activo) { $cuota = app(PagoCuotaService::class)->crearCuotaReingreso($alumno); ... }` | Al reactivar, emite confirmación con el importe y porcentaje aplicado: `Alumno activado correctamente. Se generó la cuota de...`. |
| **Qué hago si: cobro de deuda vieja a inactivo** | Si el cobro incluye únicamente meses anteriores, Wings pregunta «¿Pasa a activo?» con opciones **Sí**, **No** y **Cancelar**. Con **No** cobra y sigue inactivo. | `app/Http/Controllers/CajaWebController.php:945-954` | `if (!$request->has('reactivar')) { ... 'requiere_reactivacion' => true, 'permite_no' => !$pagaElMes ... }` | Responde HTTP 409 con modal interactivo: al presionar **No**, cobra la deuda vieja, emite recibo y el alumno permanece inactivo sin generar cuota del mes. |
| **Qué hago si: cobro que incluye mes actual** | Si el cobro incluye el mes en curso o futuro, no aparece la opción «No»: solo **Sí** y **Cancelar**. | `app/Http/Controllers/CajaWebController.php:939-943` | `$pagaElMes = $periodosSolicitados->contains(fn ($periodo) => $periodo >= $periodoActual);`<br>`if ($request->has('reactivar') && !$request->boolean('reactivar') && $pagaElMes)` | Si se intenta forzar un cobro con `reactivar = false` incluyendo el mes actual, Wings responde HTTP 422: `{"success":false,"message":"El cobro incluye el mes en curso o uno futuro: para cobrarlo, el alumno tiene que volver a activo."}`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `:apellido, :nombre está inactivo. ¿Pasa a activo?` | `app/Http/Controllers/CajaWebController.php:951` | Sí | Qué hago si: ¿Qué hago si cobro a un alumno inactivo? |
| `El cobro incluye el mes en curso o uno futuro: para cobrarlo, el alumno tiene que volver a activo.` | `app/Http/Controllers/CajaWebController.php:942` | Sí | Qué hago si: ¿Qué hago si cobro a un alumno inactivo? |
| `Alumno activado correctamente.` | `app/Http/Controllers/AlumnoWebController.php:440` | No | Mensaje flash del backend tras activar; en Paso 3 se explica que reactiva la ficha y emite el aviso. |
| `Alumno desactivado correctamente.` | `app/Http/Controllers/AlumnoWebController.php:445` | No | Mensaje flash del backend tras desactivar; en Paso 2 se explica que emite el aviso de confirmación. |
