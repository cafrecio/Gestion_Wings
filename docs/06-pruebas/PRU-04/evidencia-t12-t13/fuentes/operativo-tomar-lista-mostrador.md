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
| **Requisito: fecha de clase** | La clase debe ser de hoy o pasada. En clases futuras advierte: «Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.». | `resources/views/clases/show.blade.php:387, 477`<br>`app/Http/Controllers/ClaseWebController.php:366-372` | `@if($todaviaNoOcurrio)`<br>`if ($clase->todaviaNoOcurrio())` | En una clase futura, las casillas de verificación de presente aparecen deshabilitadas (`disabled`) y la vista muestra la alerta: `Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.`. |
| **Clase cancelada** | En clases suspendidas o canceladas, advierte: «Esta clase está cancelada. No se pueden registrar asistencias.» y no permite guardar. | `resources/views/clases/show.blade.php:383`<br>`app/Http/Controllers/ClaseWebController.php:356-364` | `@if($clase->cancelada)`<br>`if ($clase->cancelada)` | La alerta roja bloquea la toma de asistencia y el botón Guardar permanece deshabilitado. |
| **Paso 1: Ingresar a la clase** | En **Clases**, buscar la clase de hoy y tocar botón **Ver**. Abre ficha y nómina de inscriptos. | `resources/views/clases/index.blade.php:90`<br>`resources/views/clases/show.blade.php:1` | `Route::get('/clases/{id}', [ClaseWebController::class, 'show'])` | Carga la nómina de alumnos con su condición de cuota y asistencias semanales. |
| **Paso 2: Marcar Presente y Guardar** | Marcar casilla **Presente** y tocar botón **Guardar** al pie. | `resources/views/clases/show.blade.php:476, 545`<br>`resources/js/clases-show.js:77` | `document.getElementById('btn-guardar-asistencias').addEventListener('click', ...)` | Marcar casillas solo altera la vista; tocar **Guardar** es estrictamente obligatorio para persistir las asistencias en la base de datos con el aviso `Asistencias guardadas.`. |
| **Exceso del plan semanal** | Si el alumno excede las clases semanales de su plan, muestra «Excede el plan — indicar motivo» con opciones EXTRA o RECUPERA. No traba el guardado. | `resources/views/clases/show.blade.php:497-514`<br>`resources/js/clases-show.js:27-42`<br>`app/Http/Controllers/ClaseWebController.php:471-473` | `if (info.plan_semana && nuevaCuenta > info.plan_semana)`<br>`$motivo = in_array($item['motivo_exceso'] ?? '', ['EXTRA', 'RECUPERA']) ? ... : 'EXTRA';` | Si el alumno excede el plan semanal, se despliega el panel de exceso. Si no se selecciona opción, asigna EXTRA por defecto y guarda exitosamente. |
| **Regla de motivo: Deudor o nuevo** | Motivo obligatorio si el alumno está Deudor o si es nuevo sin pagos de cuota y ya tiene 2 presentes. | `app/Services/ExcepcionCobranzaService.php:40-48`<br>`resources/views/clases/show.blade.php:484` | `$nuevo = !$conCuota->has($alumno->id) && !$conHistoriaInicial->has($alumno->id);`<br>`$tipo = $nuevo ? ((int) $previas->get($alumno->id, 0) >= 2 ? 'ASISTENCIA_NUEVO' : null) : ($estado === CobranzaEstadoService::ESTADO_DEUDOR ? 'ASISTENCIA_DEUDOR' : null);`<br>`'message' => $nuevo ? 'Todavía no registró un pago de cuota. Indicá el motivo para dejarlo entrar.' : 'Tiene deuda vencida. Indicá el motivo para dejarlo entrar.'` | Si el alumno es Deudor o nuevo con 2 presentes, Wings despliega el campo obligatorio **Motivo** con el mensaje exacto. Si se intenta guardar sin motivo, Wings bloquea el envío impidiendo registrar la asistencia. |
| **Qué hago si: corregir clase pasada** | Si la clase ya pasó y ya tenía lista tomada, Wings solicita motivo obligatorio de corrección. | `resources/views/clases/show.blade.php:530-536`<br>`app/Http/Controllers/ClaseWebController.php:390-401` | `$correccionAsistencia = $esPasada && $clase->asistencias()->exists();`<br>`if ($esCorreccion && $motivoCorreccion === '')` | Despliega el campo de motivo general de corrección: `Esta clase ya tenía lista tomada: indicá el motivo de la corrección.`. Si se envía vacío, el guardado se rechaza exigiendo justificación. |
| **Qué hago si: liquidación cerrada** | Si la clase integra una liquidación cerrada, no se pueden modificar asistencias. | `app/Http/Controllers/ClaseWebController.php:375-384` | `if ($clase->integraLiquidacionCerrada())` | Si se intenta modificar asistencias de una clase liquidada cerrada, devuelve error 422: `No se pueden modificar asistencias: la clase integra una liquidación cerrada.`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Comentario / Motivo |
|---|---|---|---|
| `La clase todavía no ocurrió. La asistencia se toma desde el día de la clase.` | `app/Http/Controllers/ClaseWebController.php:367` | Sí | Explica en el requisito inicial que en clases futuras las casillas están deshabilitadas. |
| `La clase está cancelada. No se pueden registrar asistencias.` | `app/Http/Controllers/ClaseWebController.php:360` | Sí | Explica en «Qué hago si» que no se pueden registrar asistencias si la clase fue cancelada. |
| `No se pueden modificar asistencias: la clase integra una liquidación cerrada.` | `app/Http/Controllers/ClaseWebController.php:376` | Sí | Explica en «Qué hago si» que una clase con liquidación docente cerrada no admite modificaciones. |
| `Esta clase ya tenía lista tomada: indicá el motivo de la corrección.` | `app/Http/Controllers/ClaseWebController.php:396` | Sí | Explica que corregir una clase pasada con lista tomada exige justificación obligatoria. |
| `Tiene deuda vencida. Indicá el motivo para dejarlo entrar.` | `app/Services/ExcepcionCobranzaService.php:44` | Sí | Explica que si el alumno está deudor se debe ingresar el motivo obligatorio. |
| `Todavía no registró un pago de cuota. Indicá el motivo para dejarlo entrar.` | `app/Services/ExcepcionCobranzaService.php:46` | Sí | Explica que para un alumno nuevo con 2 presentes y cuota impaga se exige justificación. |
| `Excede el plan — indicar motivo` | `resources/views/clases/show.blade.php:505` | Sí | Explica el panel con opciones EXTRA o RECUPERA si el alumno supera las clases de su plan. |
| `El alumno indicado no pertenece al grupo de la clase.` | `app/Http/Controllers/ClaseWebController.php:428` | Sí | Explica en «Qué hago si» qué hacer si un alumno que vino no figura en la nómina del grupo. |
| `Asistencias guardadas.` | `resources/js/clases-show.js:145` | Sí | Aviso de éxito al guardar las asistencias en la base de datos. |
