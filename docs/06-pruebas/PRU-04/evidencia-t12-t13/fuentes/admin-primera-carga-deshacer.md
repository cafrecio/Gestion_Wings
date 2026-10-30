# Ficha de fuentes: Deshacer la primera carga

- **Página del manual:** `resources/manual/admin/primera-carga-deshacer.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/sistema/primera-carga` (sección `#paso-4` en estado TERMINADA)
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Requisito: estado TERMINADA** | Deshacer solo existe cuando hay una carga finalizada previamente. Si no hay carga, no se puede deshacer. | `app/Services/PrimeraCargaExcelService.php:325-327`<br>`app/Models/PrimeraCarga.php:30` | `if ($estado->estado !== 'TERMINADA' \|\| !$estado->detalle) throw ValidationException::withMessages(['archivo' => 'No hay una carga para deshacer.']);` | Al enviar petición POST a `/sistema/primera-carga/deshacer` con la carga en estado `PENDIENTE`, el servicio lanza la excepción con el mensaje: «No hay una carga para deshacer.». |
| **Condición de cobros registrados** | Si ya hay un cobro registrado, el botón Deshacer se oculta y la pantalla muestra «No se puede deshacer: ya hay un cobro registrado. Los alumnos y deudas se conservan.». | `resources/views/primera-carga/index.blade.php:124-125`<br>`app/Services/PrimeraCargaExcelService.php:332` | `@if($hayCobros)`<br>`if (Pago::query()->lockForUpdate()->first()) throw ValidationException::withMessages(['archivo' => 'No se puede deshacer: ya hay un cobro registrado.']);` | Tras registrar el primer cobro de cuota, el botón **Deshacer** desaparece de la vista. Si se fuerza el envío POST directo, el servicio valida la existencia de pagos y rechaza de inmediato la reversión. |
| **Condición de actividad de alumnos** | Si las profesoras ya tomaron asistencia a los alumnos, si entraron en revisión, excesos o movimientos, no se puede deshacer. | `app/Services/PrimeraCargaExcelService.php:334-338` | `foreach (['asistencias', 'alumnos_revision_cobranza', 'asistencia_excesos', 'movimientos_operativos'] as $tabla) { if (DB::table($tabla)->whereIn('alumno_id', $detalle['alumnos'])->exists()) throw ValidationException::withMessages(['archivo' => 'No se puede deshacer: los alumnos ya tienen actividad posterior a la carga.']); }` | Al registrar una asistencia de clase con un alumno importado e intentar deshacer la carga, Wings interrumpe la transacción y devuelve el error: «No se puede deshacer: los alumnos ya tienen actividad posterior a la carga.». |
| **Condición de deudas o planes nuevos** | Si a los alumnos se les asignaron nuevos planes, cargos o deudas posteriores, no se puede deshacer. | `app/Services/PrimeraCargaExcelService.php:339-343` | `if (CargoAlumno::... || DeudaCuota::... || AlumnoPlan::...) throw ValidationException::withMessages(['archivo' => 'No se puede deshacer: hay nuevas deudas, cargos o planes posteriores a la carga.']);` | Al agregar un plan arancelario nuevo o una deuda manual posterior a un alumno de la planilla, el intento de deshacer es rechazado con: «No se puede deshacer: hay nuevas deudas, cargos o planes posteriores a la carga.». |
| **Paso 1 y 2: Confirmación y reversión limpia** | El botón muestra «Disponible mientras no haya cobros ni actividad posterior.». Pide confirmación: «¿Deshacer solamente esta carga? Se conservan los usuarios y catálogos.». Vuelve a PENDIENTE con mensaje de éxito. | `resources/views/primera-carga/index.blade.php:125`<br>`app/Http/Controllers/PrimeraCargaWebController.php:94` | `data-confirmar="¿Deshacer solamente esta carga? Se conservan los usuarios y catálogos."`<br>`with('success', 'Se deshizo solamente la primera carga. Los catálogos y usuarios se conservan.')` | Al confirmar el diálogo sin cobros ni actividad posterior, Wings elimina los alumnos y deudas generadas por la planilla en una transacción segura, dejando intactos los usuarios y catálogos, y vuelve al paso 1. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Disponible mientras no haya cobros ni actividad posterior.` | `resources/views/primera-carga/index.blade.php:125` | Sí | Paso 1: Localizar la opción en Primera carga |
| `¿Deshacer solamente esta carga? Se conservan los usuarios y catálogos.` | `resources/views/primera-carga/index.blade.php:125` | Sí | Paso 2: Confirmar la reversión |
| `Se deshizo solamente la primera carga. Los catálogos y usuarios se conservan.` | `app/Http/Controllers/PrimeraCargaWebController.php:94` | Sí | Paso 2: Confirmar la reversión |
| `No se puede deshacer: ya hay un cobro registrado. Los alumnos y deudas se conservan.` | `resources/views/primera-carga/index.blade.php:124` | Sí | Qué hago si: ¿Qué hago si Wings rechaza deshacer la carga? |
| `No se puede deshacer: ya hay un cobro registrado.` | `app/Services/PrimeraCargaExcelService.php:332` | No | Mensaje de excepción interna del servicio si se invoca por API con cobros existentes. |
| `No se puede deshacer: los alumnos ya tienen actividad posterior a la carga.` | `app/Services/PrimeraCargaExcelService.php:336` | Sí | Qué hago si: ¿Qué hago si Wings rechaza deshacer la carga? |
| `No se puede deshacer: hay nuevas deudas, cargos o planes posteriores a la carga.` | `app/Services/PrimeraCargaExcelService.php:342` | Sí | Qué hago si: ¿Qué hago si Wings rechaza deshacer la carga? |
| `No hay una carga para deshacer.` | `app/Services/PrimeraCargaExcelService.php:326` | Sí | Qué hago si: ¿Qué hago si Wings rechaza deshacer la carga? |
