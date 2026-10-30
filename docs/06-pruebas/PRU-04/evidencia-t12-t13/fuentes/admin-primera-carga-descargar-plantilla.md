# Ficha de fuentes: Descargar la plantilla de primera carga

- **Página del manual:** `resources/manual/admin/primera-carga-descargar-plantilla.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/sistema/primera-carga` (pasos 1 y 2)
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Paso 1: Preparar catálogos deportivos** | Antes de descargar la plantilla, debés tener cargado al menos un deporte con su grupo y su precio mensual activo. Si falta, tocar **Preparar**. | `app/Services/PrimeraCargaExcelService.php:40`<br>`resources/views/primera-carga/index.blade.php:35-50` | `if (!$service->catalogos()) throw ValidationException::withMessages(['archivo' => 'Prepará al menos un grupo con un plan activo y precio.']);`<br>`@if($catalogos)` | Si no hay ningún grupo configurado con precio activo, el botón primario **Continuar** no aparece y en su lugar se ofrece **Preparar** hacia Grupos. Si se envía POST directo a `/sistema/primera-carga/continuar`, rechaza con: «Prepará al menos un grupo con un plan activo y precio.». |
| **Indicador verde y botón Continuar** | Cuando los catálogos están listos, muestra «Listo. Podés descargar la plantilla.» y habilita el botón **Continuar**. | `resources/views/primera-carga/index.blade.php:39, 49` | `{{ $catalogos ? 'Listo. Podés descargar la plantilla.' : 'Pendiente. Falta al menos un grupo con un plan activo y precio.' }}` | Con catálogos vacíos, el indicador se muestra en color naranja de advertencia con el mensaje de pendiente y no deja descargar el modelo. |
| **Paso 2: Descargar plantilla oficial** | En la sección 2, tocar **Descargar** para obtener la planilla en formato .xlsx con listas desplegables. Solo se completa la hoja Alumnos. | `app/Http/Controllers/PrimeraCargaWebController.php:38-44`<br>`resources/views/primera-carga/index.blade.php:60` | `if (!PrimeraCarga::pendiente()) throw ValidationException::withMessages(['archivo' => 'La primera carga ya está terminada.']);` | Si se solicita descargar la plantilla cuando la carga inicial ya fue finalizada previamente, el controlador bloquea la descarga y lanza el error: `La primera carga ya está terminada.`. |
| **Qué hago si: faltan catálogos** | Si Wings muestra «Prepará al menos un grupo con un plan activo y precio.», entrar a Grupos y crear al menos un grupo con plan y precio activo. | `app/Http/Controllers/PrimeraCargaWebController.php:32` | `if (!$service->catalogos())` | Al no tener catálogos y forzar la navegación al paso 2, el sistema no renderiza el botón de descarga y redirige al paso 1. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Listo. Podés descargar la plantilla.` | `resources/views/primera-carga/index.blade.php:39` | Sí | Paso 1: Preparar los catálogos deportivos |
| `Prepará al menos un grupo con un plan activo y precio.` | `app/Http/Controllers/PrimeraCargaWebController.php:32` | Sí | Qué hago si: ¿Qué hago si al querer continuar me dice que faltan catálogos? |
| `Pendiente. Falta al menos un grupo con un plan activo y precio.` | `resources/views/primera-carga/index.blade.php:39` | No | Texto alternativo del indicador cuando los catálogos están incompletos. |
| `Faltan planes activos con precio.` | `app/Http/Controllers/PrimeraCargaWebController.php:41` | No | Mensaje de excepción alternativa al invocar la ruta directa de descarga sin catálogos. |
| `La primera carga ya está terminada.` | `app/Http/Controllers/PrimeraCargaWebController.php:40` | No | Excepción técnica de ruta si se intenta descargar la plantilla tras finalizar la importación. |
