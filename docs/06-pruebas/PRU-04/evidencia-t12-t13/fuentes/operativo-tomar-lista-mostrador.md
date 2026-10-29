# Ficha de fuentes: Tomar lista de asistencia desde el mostrador (Operativo)

- **Página del manual:** `resources/manual/operativo/tomar-lista-mostrador.md`
- **Rol:** Operativo
- **Pantalla:** `/clases/{claseId}`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Requisito** | La clase debe ser del día o transcurrida. En futuras Wings advierte: «Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.». | `resources/views/clases/show.blade.php:387`<br>`app/Http/Controllers/ClaseWebController.php:110` | Comprobado: al abrir clase futura los checkboxes se deshabilitan con la alerta exacta. |
| **Paso 1: Entrar a clase** | En **Clases**, buscar clase de hoy y tocar botón **Ver**. Abre ficha con nómina de alumnos. | `resources/views/clases/index.blade.php:89`<br>`resources/views/clases/show.blade.php:405` | Comprobado: ficha muestra datos del grupo, rail y lista de deportistas inscriptos. |
| **Paso 2: Marcar y guardar** | Casilla **Presente** para cada alumno asistente y botón **Guardar** al pie. Wings registra las marcas y muestra «Asistencias guardadas.». | `resources/views/clases/show.blade.php:545-553`<br>`resources/js/clases-show.js:76-146`<br>`app/Http/Controllers/ClaseWebController.php:480-520` | Comprobado: al tildar **Presente** y tocar **Guardar**, se persisten las asistencias en la base y se emite el aviso de confirmación. |
| **Casos especiales** | Alumno con deuda o exceso de plan: requiere campo **Motivo** obligatorio para admitir el ingreso. | `resources/views/clases/show.blade.php:483`<br>`resources/js/clases-show.js:97` | Comprobado: si se tilda **Presente** para alumno moroso sin motivo, el formulario exige completarlo antes de guardar. |
| **Qué hago si: corregir marca** | Volver a entrar a la clase, modificar casillas y tocar **Guardar**. Si la clase ya pasó con lista tomada, exige motivo de corrección. | `resources/views/clases/show.blade.php:533`<br>`resources/js/clases-show.js:80`<br>`app/Http/Controllers/ClaseWebController.php:376` | Comprobado: corrección en clase cerrada exige motivo obligatorio. |
| **Qué hago si: no figura** | Verificar en **Alumnos** si pertenece a ese grupo; si vino a recuperar clase coordinar con profesor. | `app/Models/Grupo.php`<br>`app/Models/Alumno.php` | Comprobado: la lista exhibe únicamente alumnos con inscripción activa en el grupo. |
