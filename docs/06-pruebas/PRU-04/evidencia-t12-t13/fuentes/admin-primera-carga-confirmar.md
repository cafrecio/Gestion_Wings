# Ficha de fuentes: Confirmar la importación masiva de primera carga

- **Página del manual:** `resources/manual/admin/primera-carga-confirmar.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/sistema/primera-carga#paso-4`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Acceso exclusivo y estado pendiente** | La importación masiva inicial solo la ejecuta el administrador y solo está disponible mientras la primera carga esté en estado pendiente. | `app/Http/Controllers/PrimeraCargaWebController.php:75, 99`<br>`app/Models/PrimeraCarga.php:25` | `if (!auth()->user()->isAdmin()) abort(403);`<br>`if (!PrimeraCarga::pendiente()) throw ValidationException::withMessages(['archivo' => 'La primera carga ya está terminada.']);` | Si un usuario no admin ingresa a la ruta, responde HTTP 403. Si se intenta enviar una carga por POST cuando el sistema ya fue importado, rechaza con: `La primera carga ya está terminada.`. |
| **Paso 1: Revisar resumen consolidado** | En la sección 4, auditar contadores de alumnos, cuotas y deuda total antes de tocar **Cargar**. No entra dinero a caja. | `resources/views/primera-carga/index.blade.php:118-121`<br>`app/Http/Controllers/PrimeraCargaWebController.php:78` | `if ($revision['errores']) throw ValidationException::withMessages(['archivo' => 'Corregí todos los errores y revisá otra vez.']);` | Si el archivo tiene errores pendientes en el paso 3, el botón **Cargar** no se muestra en el paso 4 y la pantalla indica: `Pendiente. Primero revisá el Excel. La carga se habilita solamente con cero errores.`. |
| **Paso 2: Confirmación obligatoria** | Al tocar Cargar, se abre el cuadro con la advertencia: «Se cargará todo en una sola operación. Confirmá el resumen de arriba. No entra dinero a caja.». Tocar **Confirmar**. | `resources/views/primera-carga/index.blade.php:128-135`<br>`app/Http/Controllers/PrimeraCargaWebController.php:76` | `$request->validate(['confirmar' => ['required', 'accepted']], ['confirmar.accepted' => 'Confirmá el resumen antes de cargar.']);` | Al omitir el campo oculto `confirmar` enviando el formulario por POST directo, Wings rechaza la importación con el error de validación: `Confirmá el resumen antes de cargar.`. |
| **Finalización y estado de alumnos** | Al terminar, muestra «Carga terminada. Los alumnos están en Alumnos y las deudas en Cobranza. Quien no debía arranca al día.». Quien no debía arranca al día en Alumnos. | `resources/views/primera-carga/index.blade.php:123`<br>`app/Services/PrimeraCargaExcelService.php:290-310` | `if ($terminada)` | Tras completar la carga, el asistente oculta el formulario de subida, fija el badge en verde y muestra el mensaje de carga terminada. Si un alumno no tenía deuda, queda registrado activo sin cuotas impagas. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Se cargará todo en una sola operación.` | `resources/views/primera-carga/index.blade.php:131` | Sí | Paso 2: Confirmar la operación definitiva |
| `Confirmá el resumen de arriba. No entra dinero a caja.` | `resources/views/primera-carga/index.blade.php:131` | Sí | Paso 2: Confirmar la operación definitiva |
| `Carga terminada.` | `resources/views/primera-carga/index.blade.php:123` | Sí | Paso 2: Confirmar la operación definitiva |
| `Los alumnos están en Alumnos y las deudas en Cobranza. Quien no debía arranca al día.` | `resources/views/primera-carga/index.blade.php:123` | Sí | Paso 2: Confirmar la operación definitiva |
| `Confirmá el resumen antes de cargar.` | `app/Http/Controllers/PrimeraCargaWebController.php:76` | No | Validación requerida de formulario al no tildar la confirmación. |
| `Primero revisá el Excel en esta sesión.` | `app/Http/Controllers/PrimeraCargaWebController.php:107` | No | Validación de seguridad interna ante sesiones expiradas. |
| `La primera carga ya está terminada.` | `app/Http/Controllers/PrimeraCargaWebController.php:99` | No | Excepción al intentar cargar una segunda vez cuando ya está terminada. |
