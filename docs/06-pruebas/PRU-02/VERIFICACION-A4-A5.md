# Verificación independiente: A4 y A5

**Fecha:** 06/10/2026  
**Verificador:** Gemini (Oficina CyE) — `LOG GEM CYE`  
**Implementador:** Codex CAB (commits `373f9b4` y `74d8dae`)  
**Estado:** **APROBADOS Y CERRADOS**

---

## Resumen ejecutivo

Se realizó la verificación independiente de los defectos **A4** (aviso superior al fallar guardado de alumno y protección de salida sin guardar) y **A5** (filtrado dinámico y validación estricta de profesores por deporte en clases).

Ambos defectos se evaluaron en el código fuente, mediante la suite automatizada (`tests/Feature/FormulariosA4A5Test.php`: 11 tests pasando en base propia `wings_testing_gemini`), y con **pruebas reales en navegador Chrome Headless** interactivo con servidor local y renderizado con marco de 375 px según el protocolo de `AGENTS.md` §1.

Todas las comprobaciones resultaron satisfactorias. Se separan a continuación los hechos **verificados** mediante ejecución y capturas de los hechos **inferidos**.

---

## Punto por punto

### 1. Aviso al salir sin guardar (A4)
*Codex había advertido que no lo pudo comprobar en pantalla porque se le trabó el navegador.*

- **Qué se esperaba:**
  1. Si se modifica algún campo en el formulario de alumno y se intenta navegar por el menú lateral o por el botón "Cancelar" (Volver), debe aparecer un diálogo de confirmación advirtiendo que hay cambios sin guardar. Si el usuario cancela, no debe abandonar la página.
  2. Si el usuario intenta cerrar o recargar la pestaña del navegador con cambios sin guardar, el evento `beforeunload` debe solicitar confirmación nativa del navegador.
  3. Si no hay modificaciones (formulario limpio sin errores), debe permitir la navegación libre sin avisos molestos.

- **Qué pasó (VERIFICADO en Chrome Headless real):**
  Se ejecutó una batería de interacción sobre la pantalla real (`scratch/test-navegacion.mjs` con servidor local y Chrome DevTools Protocol):
  1. **Click en Cancelar (Volver) con cambios / rechazar salida:** Se disparó `window.confirm('Tenés cambios sin guardar. ¿Querés salir y perder lo cargado?')`. Al responder `false`, el evento de click fue interceptado con `defaultPrevented: true` y la navegación quedó bloqueada.
  2. **Click en Cancelar con cambios / aceptar salida:** Al responder `true`, el script marcó `descartando = true` y permitió la navegación normal (`defaultPrevented: false`).
  3. **Click en menú lateral con cambios:** Mismo comportamiento; `window.confirm` interceptó el click y previno la navegación.
  4. **Cierre / recarga de pestaña (`beforeunload`):** Con cambios sin guardar y sin descartar, el listener de `beforeunload` llamó `event.preventDefault()` y fijó `event.returnValue = ''`, activando la protección nativa del navegador contra pérdida de datos.
  5. **Formulario limpio sin cambios:** Ni Cancelar ni `beforeunload` solicitaron confirmación (`dialogCalled: false`, `clickDefaultPrevented: false`, `unloadDefaultPrevented: false`).

- **Evidencia / Capturas:**
  - Código en `resources/js/alumnos-form.js` (líneas 142 a 216).
  - Ejecución de suite CDP en `scratch/test-navegacion.mjs` (salida limpia: 6/6 tests exitosos).

---

### 2. Visibilidad del aviso superior sin scroll y conservación de datos (A4)

- **Qué se esperaba:**
  Que ante un error de validación (por ejemplo, menor de edad sin datos de tutor), el mensaje de error aparezca en la parte superior antes de los campos del formulario, visible inmediatamente sin necesidad de bajar la pantalla (scroll), tanto en escritorio como en celular a 375 px de ancho. Además, los datos ingresados previamente deben permanecer intactos.

- **Qué pasó (VERIFICADO):**
  1. El componente `resources/views/alumnos/_errores.blade.php` genera el bloque `<div id="alumno-error-resumen" class="ds-flash ds-flash--error">` inmediatamente arriba del formulario (`.filtros-card`), antes del primer input.
  2. En escritorio, el bloque se ubica entre `y ≈ 120px` y `y ≈ 220px`.
  3. En celular a 375 px (renderizado dentro del marco estándar de 375 px), se ubica entre `y ≈ 160px` y `y ≈ 310px`, ocupando el primer tercio visible de la pantalla, sin requerir scroll.
  4. Los datos ingresados se conservan mediante `value="{{ old('campo', ...) }}"` en Blade y el select dinámico de planes preserva el plan seleccionado mediante `data-plan-actual="{{ $currentPlanId }}"` en `resources/js/alumnos-form.js`.
  5. Si el navegador rechaza por validación HTML5 nativa antes del envío POST, el listener de evento `invalid` captura los campos inválidos y construye el resumen arriba con enlaces ancla directos al campo observado.

- **Evidencia / Capturas:**
  - Control de método: [login-celular-375.png](evidencia/verificacion-a4-a5/login-celular-375.png) (el login entra completo sin cortes en marco 375 px).
  - Edición con error en escritorio: [editar-alumno-error-escritorio.png](evidencia/verificacion-a4-a5/editar-alumno-error-escritorio.png).
  - Edición con error en celular 375 px: [editar-alumno-error-celular-375.png](evidencia/verificacion-a4-a5/editar-alumno-error-celular-375.png).
  - Alta con error en escritorio: [crear-alumno-error-escritorio.png](evidencia/verificacion-a4-a5/crear-alumno-error-escritorio.png).
  - Alta con error en celular 375 px: [crear-alumno-error-celular-375.png](evidencia/verificacion-a4-a5/crear-alumno-error-celular-375.png).

---

### 3. Rechazo en servidor de profesor de otro deporte (A5)

- **Qué se esperaba:**
  Que si un usuario altera el formulario HTML o envía por HTTP una petición forzada asignando un profesor de un deporte distinto al deporte del grupo de la clase, el backend rechace la operación sin crear la clase ni corromper asignaciones.

- **Qué pasó (VERIFICADO en código y tests):**
  1. En `app/Http/Controllers/ClaseWebController.php`, la función privada `validarProfesoresDelDeporte(Request $request, int $deporteId)` ejecuta:
     ```php
     if ($ids && Profesor::whereIn('id', $ids)->where('deporte_id', $deporteId)->where('activo', true)->count() !== count($ids)) {
         throw ValidationException::withMessages([
             'profesores' => 'Solo se pueden asignar profesores activos del deporte de la clase.',
         ]);
     }
     ```
  2. Esta validación se ejecuta en:
     - `store()`: creación de clase única y series recurrentes (dentro de transacción de base de datos).
     - `update()`: edición de clase existente.
     - `actualizarProfesores()`: reasignación de profesores.
  3. En la prueba `a5 alta rechaza profesor de otro deporte sin crear clase` y `a5 serie rechazada no deja clases parciales`, el envío directo por POST de un profesor ajeno retorna error de validación 422 / redirección con error y confirma que `Clase::count() === 0`.

---

### 4. Clase sin grupo/deporte definido o profesor inactivo (A5)

- **Qué se esperaba:**
  Verificar el comportamiento cuando el grupo todavía no fue seleccionado, y cuando un profesor está marcado como inactivo (`activo = false`).

- **Qué pasó (VERIFICADO):**
  1. **Clase sin grupo seleccionado todavía:**
     - En la interfaz: el `<select id="grupo_id">` inicia con la opción `"Seleccionar grupo..."`. El script `resources/js/clases-form.js` oculta todas las casillas de profesores (`display: none`, `disabled = true`, `checked = false`) y el texto de ayuda `#profesores-deporte-aviso` informa: *"Elegí un grupo para ver sus profesores activos."*.
     - En el servidor: `grupo_id` es campo requerido (`required|exists:grupos,id`). No es posible persistir una clase sin grupo/deporte.
  2. **Profesor inactivo:**
     - En la interfaz: `ClaseWebController@create` y `edit` filtran explícitamente `$profesores = Profesor::where('activo', true)...`, de modo que los inactivos ni siquiera aparecen listados.
     - En el servidor: la consulta `where('activo', true)` dentro de `validarProfesoresDelDeporte()` rechaza con `ValidationException` cualquier intento de asignar un profesor inactivo (comprobado en test `a5 rechaza profesor inactivo del mismo deporte`).

- **Evidencia / Capturas:**
  - Formulario de clase en escritorio: [crear-clase-escritorio.png](evidencia/verificacion-a4-a5/crear-clase-escritorio.png).
  - Formulario de clase en celular 375 px: [crear-clase-celular-375.png](evidencia/verificacion-a4-a5/crear-clase-celular-375.png).
  - Edición de clase en escritorio: [editar-clase-escritorio.png](evidencia/verificacion-a4-a5/editar-clase-escritorio.png).
  - Edición de clase en celular 375 px: [editar-clase-celular-375.png](evidencia/verificacion-a4-a5/editar-clase-celular-375.png).

---

### 5. Edición de alumno no rompe funciones existentes

- **Qué se esperaba:**
  Que la edición de alumnos conserve la integridad de:
  - Exigencia de tutor para menores de 18 años (`nombre_tutor` y `telefono_tutor` obligatorios).
  - Conservación o cambio de plan activo (`AlumnoPlan`).
  - Sincronización de inscripción ante cambios en la fecha de alta mediante `InscripcionService`.

- **Qué pasó (VERIFICADO):**
  1. En `AlumnoWebController@update`, las reglas invocan `$rules = $this->validationRules($request)` donde `$esMenor` exige obligatoriamente los datos del tutor.
  2. La actualización del plan solo crea un nuevo `AlumnoPlan` si el usuario selecciona una frecuencia distinta a la actual, preservando el histórico.
  3. Si se corrige la fecha de alta, se exige `motivo_fecha_alta` y se invoca `$inscripcion->sincronizar(...)`, manteniendo el cargo de inscripción auditado.
  4. Comprobado en `FormulariosA4A5Test@test_a4_rechazo_conserva_datos_y_no_modifica_alumno`.

---

## Separación entre Verificado e Inferido (AGENTS.md §6c)

| Aspecto | Estado | Modo de comprobación |
|---|---|---|
| Cartel "No se guardó" visible arriba en desktop | **VERIFICADO** | Captura real de pantalla [editar-alumno-error-escritorio.png](evidencia/verificacion-a4-a5/editar-alumno-error-escritorio.png) |
| Cartel visible sin scroll en celular a 375 px | **VERIFICADO** | Captura real dentro de marco 375 px [editar-alumno-error-celular-375.png](evidencia/verificacion-a4-a5/editar-alumno-error-celular-375.png) |
| Diálogo confirm al hacer click en Cancelar (Volver) | **VERIFICADO** | Ejecución de click interactivo con CDP en Chrome (`test-navegacion.mjs`: Test 2 y 3) |
| Diálogo confirm al hacer click en menú lateral | **VERIFICADO** | Ejecución interactiva con CDP en Chrome (`test-navegacion.mjs`: Test 4) |
| Prevención nativa al cerrar pestaña (`beforeunload`) | **VERIFICADO** | Dispatch de evento interactivo en Chrome (`test-navegacion.mjs`: Test 5) |
| Salida sin cartel si no hay cambios | **VERIFICADO** | Ejecución en formulario limpio (`test-navegacion.mjs`: Test 6) |
| Filtro de profesores por deporte en frontend | **VERIFICADO** | Comprobación de DOM y atributos `data-profesor-deporte` en `clases-form.js` y capturas |
| Rechazo backend a profesor de otro deporte | **VERIFICADO** | Ejecución de prueba unitaria/feature `FormulariosA4A5Test` y revisión de código |
| Rechazo backend a profesor inactivo | **VERIFICADO** | Test `a5 rechaza profesor inactivo del mismo deporte` en `FormulariosA4A5Test` |
| Tutor obligatorio para menores en edición | **VERIFICADO** | Test `a4 edicion presenta el mismo resumen` en `FormulariosA4A5Test` |
| Comportamiento en navegadores móviles Safari/iOS | **INFERIDO** | Inferido a partir del soporte estándar de la API HTML5 (`beforeunload`, `window.confirm` y validación nativa `invalid`) comprobado en Chrome. No se probó en dispositivo físico iOS. |

---

## Conclusión

La solución entregada por Codex CAB para A4 y A5 es sólida, respeta el design system, no modifica CSS ni inventa componentes, delega la interactividad en archivos JavaScript propios y asegura la integridad tanto en cliente como en servidor.

**Decisión:** Se da por **CERRADO** el defecto A4 y el defecto A5.
