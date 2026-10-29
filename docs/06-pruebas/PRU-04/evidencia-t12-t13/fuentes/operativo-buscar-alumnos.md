# Ficha de fuentes: Buscar alumnos en el listado (Operativo)

- **Página del manual:** `resources/manual/operativo/buscar-alumnos.md`
- **Rol:** Operativo
- **Pantalla:** `/alumnos`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Paso 1: Ingresar** | En menú lateral hacer clic en **Alumnos**. Muestra filtros, barra de estadísticas y tarjetas con riel de color por deporte. | `resources/views/layouts/ds-app.blade.php:88`<br>`resources/views/alumnos/index.blade.php:10, 71, 98` | Comprobado: listado carga con tarjetas, rails de color y contadores generales de alumnos. |
| **Paso 2: Buscador predictivo** | En **Buscar por nombre, apellido o DNI...**, autocompletado desde 1 carácter o Enter para filtrar. | `resources/views/alumnos/index.blade.php:19-30`<br>`resources/js/alumnos-index.js:101`<br>`app/Http/Controllers/AlumnoWebController.php:124` | Comprobado: tipear DNI o letras despliega sugerencias inmediatas y filtra por query `search`. |
| **Paso 3: Filtros por deporte** | Menú desplegable **Deporte** y **Grupo**. Botón **Limpiar** para reiniciar la lista. | `resources/views/alumnos/index.blade.php:41, 52, 64`<br>`app/Http/Controllers/AlumnoWebController.php:44-55` | Comprobado: selectores acotan nómina; botón **Limpiar** remueve parámetros y restaura listado completo. |
| **Qué hago si: no aparece** | Verificar filtros activos, usar botón **Limpiar** o buscar por dígitos de DNI sin puntos ni espacios. | `app/Http/Controllers/AlumnoWebController.php:38`<br>`app/Models/Alumno.php:30-45` | Comprobado: normalización de DNI busca coincidentes exactos o parciales numéricos. |
| **Qué hago si: ficha o cobrar** | Clic en el nombre o botón **Ver** para legajo; botón **Editar** para modificar; botón **Cobrar** en módulo Cobranza. | `resources/views/alumnos/index.blade.php:178, 224`<br>`resources/views/cobranza/index.blade.php:191` | Comprobado: botones canónicos vinculan a `/alumnos/{id}`, `/alumnos/{id}/edit` y `/caja/cobrar/{id}`. |
