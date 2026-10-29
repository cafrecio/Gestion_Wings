# Ficha de fuentes: Dar de alta a un alumno nuevo (Operativo)

- **Página del manual:** `resources/manual/operativo/alta-alumno.md`
- **Rol:** Operativo
- **Pantalla:** `/alumnos/create`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Paso 1: Formulario de alta** | En **Alumnos**, tocar botón **Nuevo**. Abre formulario **Nuevo Alumno**. | `resources/views/alumnos/index.blade.php:82`<br>`resources/views/alumnos/create.blade.php:4, 8` | Comprobado: acceso a `/alumnos/create` carga formulario completo con botón **Guardar** y **Cancelar**. |
| **Paso 2: Datos personales** | **Nombre**, **Apellido**, **DNI**, **Fecha de nacimiento**, **Fecha de alta**, **Celular**, **Email**. Para menores: **Datos del tutor**, **Nombre del tutor**, **Teléfono del tutor** o **Mismo que el teléfono del tutor**. | `resources/views/alumnos/_form.blade.php:15-190` | Comprobado: validación exige datos obligatorios; casilla de tutor replica celular del alumno. |
| **Paso 3: Deporte y plan** | **Deporte**, **Grupo**, **Frecuencia semanal**, **Cuota al dar de alta** (**Sí.** o **No.**). Botón **Guardar**. | `resources/views/alumnos/_form.blade.php:105, 127, 160, 198, 202, 206`<br>`resources/views/alumnos/create.blade.php:15` | Comprobado: al seleccionar grupo carga frecuencias vía JS; guardado exitoso redirige a `/alumnos/{id}`. |
| **Qué hago si: DNI duplicado** | El DNI es único por persona en el club; no permite duplicados y exige buscarlo en listado. | `app/Http/Requests/AlumnoRequest.php:25-35`<br>`app/Models/Alumno.php` | Comprobado: intento con DNI repetido dispara error de validación `El DNI ya está registrado.`. |
| **Qué hago si: mitad de mes** | En **Cuota al dar de alta** se elige **Sí.** o **No.**; la matrícula es independiente. | `app/Support/FechaIngresoRegla.php`<br>`resources/views/alumnos/_form.blade.php:208` | Comprobado: opción **No.** crea al alumno sin cuota inicial de ese período. |
| **Qué hago si: cancelar** | Botón **Cancelar** vuelve al listado sin guardar. | `resources/views/alumnos/create.blade.php:14` | Comprobado: botón **Cancelar** redirige a `route('web.alumnos.index')`. |
