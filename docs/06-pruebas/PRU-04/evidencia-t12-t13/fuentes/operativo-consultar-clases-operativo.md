# Ficha de fuentes: Consultar clases del día y asistencia (Operativo)

- **Página del manual:** `resources/manual/operativo/consultar-clases-operativo.md`
- **Rol:** Operativo
- **Pantalla:** `/clases`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **En tiempo real** | La pantalla muestra primero la ventana de Clases de hoy, ordenada cronológicamente con indicadores de estado. | `resources/views/clases/index.blade.php:77-83`<br>`app/Http/Controllers/ClaseWebController.php:30-45` | Comprobado: banner de Clases de hoy carga clases de la fecha corriente. |
| **Paso 1: Entrar a Clases** | En menú lateral hacer clic en **Clases**. Muestra **Clases de hoy**, fecha y cantidad de actividades. | `resources/views/layouts/ds-app.blade.php:80`<br>`resources/views/clases/index.blade.php:77, 80` | Comprobado: acceso a `/clases` muestra la sección de la fecha del día. |
| **Paso 2: Estados de clase** | Estados: **Programada**, **Por comenzar**, **En curso**, **Cerrada**, **Finalizada**, **Cancelada**. | `resources/views/clases/index.blade.php:49-55` | Comprobado: helpers `claseEstado`, `estadoLabel` y `estadoColor` calculan estado dinámico. |
| **Paso 3: Filtrar** | Selectores **Deporte**, **Grupo** o docente. Botón **Ver** abre detalle de clase y alumnos. | `resources/views/clases/index.blade.php:128, 139`<br>`resources/views/clases/_card.blade.php:50` | Comprobado: selectores filtran grilla; botón **Ver** vincula a `/clases/{id}`. |
| **Qué hago si: consulta familia** | Entrar a la clase con botón **Ver** para verificar si el alumno tiene marca de **Presente** o quedó sin marcar como ausente. | `resources/views/clases/show.blade.php:306-320` | Comprobado: lista de asistencia exhibe si el alumno fue tildado como **Presente** o si no registra marca. |
| **Qué hago si: tomar lista** | Desde mostrador se puede ingresar a la clase y registrar presentes con botón **Guardar** si el docente no tiene móvil. | `resources/views/clases/show.blade.php:545-553`<br>`resources/js/clases-show.js:136` | Comprobado: rol operativo tiene permisos para asentar asistencia y guardar con botón **Guardar**. |
