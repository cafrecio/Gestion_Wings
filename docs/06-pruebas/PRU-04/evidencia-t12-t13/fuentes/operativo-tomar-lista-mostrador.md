# Ficha de fuentes: Tomar lista de asistencia desde el mostrador (Operativo)

- **Página del manual:** `resources/manual/operativo/tomar-lista-mostrador.md`
- **Rol:** Operativo
- **Pantalla:** `/clases/{claseId}`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Requisito: fecha de clase** | La clase debe ser de hoy o pasada. En clases futuras advierte: «Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.». | `resources/views/clases/show.blade.php:387, 477`<br>`app/Http/Controllers/ClaseWebController.php:385` | `@if($todaviaNoOcurrio)`<br>`$todaviaNoOcurrio = $clase->fecha->isFuture();` | En una clase futura, las casillas de verificación de presente aparecen deshabilitadas (`disabled`) y la vista muestra la alerta: `Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.`. |
| **Clase cancelada** | En clases suspendidas o canceladas, advierte: «Esta clase está cancelada. No se pueden registrar asistencias.» y no permite guardar. | `resources/views/clases/show.blade.php:383` | `@if($clase->cancelada)` | La alerta roja bloquea la toma de asistencia y el botón Guardar permanece deshabilitado. |
| **Paso 1: Ingresar a la clase** | En **Clases**, buscar la clase de hoy y tocar botón **Ver**. Abre ficha y nómina de inscriptos. | `resources/views/clases/index.blade.php:90`<br>`resources/views/clases/show.blade.php:1` | `Route::get('/clases/{id}', [ClaseWebController::class, 'show'])` | Carga la nómina de alumnos con su condición de cuota y asistencias semanales. |
| **Paso 2: Marcar Presente y Guardar** | Marcar casilla **Presente** y tocar botón **Guardar** al pie. | `resources/views/clases/show.blade.php:476, 545`<br>`resources/js/clases-show.js:77` | `document.getElementById('btn-guardar-asistencias').addEventListener('click', ...)` | Marcar casillas solo altera la vista; tocar **Guardar** es estrictamente obligatorio para persistir las asistencias en la base de datos con el aviso `Asistencias guardadas.`. |
| **Regla de motivo: Deudor o nuevo** | Motivo obligatorio si el alumno está Deudor o si es nuevo sin pagos de cuota y ya tiene 2 presentes. | `app/Services/ExcepcionCobranzaService.php:40-48`<br>`resources/views/clases/show.blade.php:484` | `$nuevo = !$conCuota->has($alumno->id) && !$conHistoriaInicial->has($alumno->id);`<br>`$tipo = $nuevo ? ((int) $previas->get($alumno->id, 0) >= 2 ? 'ASISTENCIA_NUEVO' : null) : ($estado === CobranzaEstadoService::ESTADO_DEUDOR ? 'ASISTENCIA_DEUDOR' : null);`<br>`'message' => $nuevo ? 'Todavía no registró un pago de cuota. Indicá el motivo para dejarlo entrar.' : 'Tiene deuda vencida. Indicá el motivo para dejarlo entrar.'` | Si el alumno es Deudor o nuevo con 2 presentes, Wings despliega el campo obligatorio **Motivo** con el mensaje exacto. Si se intenta guardar sin motivo, Wings bloquea el envío impidiendo registrar la asistencia. |
| **Qué hago si: corregir clase pasada** | Si la clase ya pasó y ya tenía lista tomada, Wings solicita motivo obligatorio de corrección. | `resources/views/clases/show.blade.php:530-536`<br>`app/Http/Controllers/ClaseWebController.php:390` | `$correccionAsistencia = $esPasada && $clase->asistencias()->exists();`<br>`if ($correccionAsistencia && empty($request->input('motivo_asistencias')))` | Despliega el campo de motivo general de corrección. Si se envía vacío, el guardado se rechaza exigiendo justificación. |
