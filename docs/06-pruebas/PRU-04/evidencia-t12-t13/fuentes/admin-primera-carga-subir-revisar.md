# Ficha de fuentes: Subir y revisar el archivo de primera carga

- **Página del manual:** `resources/manual/admin/primera-carga-subir-revisar.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/sistema/primera-carga#paso-3`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Seguridad de la revisión** | El paso de revisión no guarda nada en la base de datos: sirve para auditar sintaxis, fechas y campos obligatorios de forma segura. | `app/Services/PrimeraCargaExcelService.php:80-160`<br>`resources/views/primera-carga/index.blade.php:86` | `if (!PrimeraCarga::pendiente()) throw ValidationException::withMessages(['archivo' => 'La primera carga ya está terminada.']);` | Si se intenta subir o revisar una planilla cuando la primera carga ya fue terminada, el sistema aborta la petición con: `La primera carga ya está terminada.`. |
| **Paso 1: Seleccionar archivo y revisar** | En la sección 3, seleccionar el Excel completado (.xlsx) y tocar **Revisar**. Wings audita la hoja Alumnos completa. | `app/Http/Controllers/PrimeraCargaWebController.php:49-59`<br>`resources/views/primera-carga/index.blade.php:104-112` | `$request->validate(['archivo' => ['required', 'file', 'mimes:xlsx', 'max:10240']], ...);` | Al intentar subir un archivo con extensión no permitida (por ejemplo `.csv` o `.pdf`), o sin adjuntar nada, Wings rechaza con el cuadro de alerta «No se cargó el archivo.» e informa el motivo de validación. |
| **Paso 2: Informe de errores y descarga marcada** | Si hay errores, muestra el total de fallas y el aviso «No se cargó nada.». El botón **Descargar** entrega el Excel con la columna **Errores** (AM). | `resources/views/primera-carga/index.blade.php:88, 96, 110`<br>`app/Services/PrimeraCargaExcelService.php:210` | `@if($erroresRevision)`<br>`@if($revision \|\| $terminada)` | Con un archivo con errores, el botón para avanzar al paso 4 permanece bloqueado. Wings lista cada fila observada y genera el archivo de descarga con los mensajes de corrección en la última columna. |
| **Paso 2: Archivo aprobado** | Cuando la planilla no contiene inconsistencias, muestra «Listo. Archivo revisado sin errores.» y habilita el paso 4. | `resources/views/primera-carga/index.blade.php:99`<br>`app/Http/Controllers/PrimeraCargaWebController.php:58` | `$request->session()->put('primera_carga_paso', $resultado['errores'] ? 3 : 4);` | Al subir un archivo corregido sin fallas, la pantalla avanza automáticamente al paso 4 y muestra el indicador verde de aprobación. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `No se cargó nada.` | `resources/views/primera-carga/index.blade.php:88` | Sí | Paso 2: Analizar los resultados de la revisión |
| `Listo. Archivo revisado sin errores.` | `resources/views/primera-carga/index.blade.php:99` | Sí | Paso 2: Analizar los resultados de la revisión |
| `No se cargó el archivo.` | `resources/views/primera-carga/index.blade.php:22` | Sí | Qué hago si: ¿Qué hago si Wings rechaza el archivo al subirlo? |
| `Elegí el Excel que completaste.` | `app/Http/Controllers/PrimeraCargaWebController.php:50` | No | Validación requerida cuando se envía el formulario sin seleccionar archivo. |
| `Guardá y elegí un archivo Excel .xlsx.` | `app/Http/Controllers/PrimeraCargaWebController.php:50` | No | Validación de formato de archivo cuando no es hoja de cálculo xlsx. |
| `El archivo puede pesar hasta 10 MB.` | `app/Http/Controllers/PrimeraCargaWebController.php:50` | No | Validación de tamaño máximo de archivo. |
| `La primera carga ya está terminada.` | `app/Http/Controllers/PrimeraCargaWebController.php:48` | No | Bloqueo de seguridad si se intenta revisar cuando la importación ya culminó. |
