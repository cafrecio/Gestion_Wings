# Ficha de fuentes: Cambiar a un alumno de grupo o frecuencia (Operativo)

- **Página del manual:** `resources/manual/operativo/cambiar-grupo-alumno.md`
- **Rol:** Operativo
- **Pantalla:** `/alumnos/{alumnoId}/edit`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Regla de aranceles** | Bajas rigen desde el mes siguiente. Subidas ajustan este mes si la cuota sigue impaga o parcial; si ya está saldada, rigen desde el mes siguiente. | `resources/views/alumnos/_form.blade.php:156`<br>`app/Services/PagoCuotaService.php:850-910` | Comprobado: regla textual visible en la vista y ejecutada por el servicio de planes. |
| **Paso 1: Entrar a edición** | En **Alumnos**, tocar botón **Editar** en tarjeta o ficha. | `resources/views/alumnos/index.blade.php:224`<br>`resources/views/alumnos/edit.blade.php:4` | Comprobado: acceso a edición del alumno. |
| **Paso 2: Elegir grupo y plan** | En **Grupo** elegir categoría. En **Frecuencia semanal** elegir clases por semana y precio. | `resources/views/alumnos/_form.blade.php:127, 160`<br>`resources/js/alumnos-form.js` | Comprobado: cambio de grupo actualiza frecuencias disponibles dinámicamente. |
| **Paso 3: Guardar** | Tocar botón **Guardar**. Recalcula cuenta y muestra ficha actualizada. | `resources/views/alumnos/edit.blade.php:30`<br>`app/Http/Controllers/AlumnoWebController.php:325` | Comprobado: actualización de plan activo / programado persistida correctamente. |
| **Qué hago si: clases previas** | Las asistencias anteriores no se pierden ni modifican: quedan en el grupo previo. | `app/Models/Asistencia.php`<br>`app/Models/Clase.php` | Comprobado: histórico de clases pasadas mantiene asistencias tomadas. |
| **Qué hago si: cuota saldada** | Si la cuota del mes ya está `PAGADA`, el aumento aplica desde el 1° del mes siguiente. | `app/Services/PagoCuotaService.php:875` | Comprobado: no se genera deuda forzada sobre cuota con saldo cero. |
| **Qué hago si: baja de días** | Al reducir días, la cuota del mes en curso no se reduce; aplica desde el período siguiente. | `app/Services/PagoCuotaService.php:860` | Comprobado: plan menor se programa con fecha `inicio_vigencia` al primer día del mes siguiente. |
