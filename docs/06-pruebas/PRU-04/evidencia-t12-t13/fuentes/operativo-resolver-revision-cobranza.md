# Ficha de fuentes: Resolver casos en Revisión de cobranza (Operativo)

- **Página del manual:** `resources/manual/operativo/resolver-revision-cobranza.md`
- **Rol:** Operativo
- **Pantalla:** `/revision-cobranza`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Paso 1: Entrar a Revisión** | En menú lateral hacer clic en **Revisión**. Cartel indica: «Estos alumnos no generaron deuda automática porque no tuvieron asistencias el mes anterior. Contactar al tutor y resolver.». | `resources/views/revision-cobranza/index.blade.php:26`<br>`app/Http/Controllers/RevisionCobranzaWebController.php:30` | `@if($totalPendientes > 0)` | Muestra la nómina de alumnos retenidos para evitar deudas indebidas a inactivos. |
| **Paso 2: Interruptor Activo / Inactivo** | Formulario cuenta con interruptor **Activo** e **Inactivo**. Activo indica «Se generará la cuota de...». Inactivo indica «Se dará de baja al alumno al confirmar. Sus deudas anteriores se conservan.». | `resources/views/revision-cobranza/index.blade.php:162-164`<br>`resources/js/ds-app.js:658, 662` | `input.checked ? 'CONTINUA' : 'INACTIVO'`<br>`aviso.textContent = input.checked ? aviso.dataset.activo : 'Se dará de baja al alumno al confirmar. Sus deudas anteriores se conservan.';` | El interruptor selecciona la opción en el DOM. Al cambiar a Inactivo, el texto de consecuencia cambia al aviso de baja e inactividad. |
| **Paso 3: Nota y Confirmar** | En **Nota (contacto y motivo)** escribir detalle y tocar botón **Confirmar**. | `resources/views/revision-cobranza/index.blade.php:166, 170`<br>`app/Http/Controllers/RevisionCobranzaWebController.php:41-50` | `'resolucion' => 'required\|in:CONTINUA,INACTIVO'`<br>`'nota_resolucion' => 'required\|string\|min:5\|max:500'` | Si se intenta confirmar sin nota o con menos de 5 caracteres, Wings rechaza con el mensaje exacto: `La nota es obligatoria.` o `La nota debe tener al menos 5 caracteres.`. Con datos válidos, archiva la revisión y emite: `Alumno confirmado como activo. Se generó la deuda del período.` o `Alumno marcado como inactivo.`. |
| **Qué hago si: resolución duplicada** | Una revisión ya resuelta no puede volver a resolverse. | `app/Services/RevisionCobranzaService.php:31` | `if ($revision->estado_revision !== AlumnoRevisionCobranza::ESTADO_PENDIENTE) { throw new \Exception('Esta revisión ya fue resuelta.'); }` | Si se intenta enviar una resolución sobre un caso ya resuelto, Wings rechaza la transacción con la excepción: `Esta revisión ya fue resuelta.`. |
| **Qué hago si: cobro directo** | Si se cobra al alumno antes de resolver, el cobro puede resolver automáticamente la situación si incluye el mes actual. | `app/Models/AlumnoRevisionCobranza.php:40`<br>`app/Http/Controllers/AlumnoWebController.php:440` | `AlumnoRevisionCobranza::reactivarAuto($alumno->id, now()->format('Y-m'))` | Marca la revisión automáticamente como resuelta en el historial sin requerir acción manual adicional. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Comentario / Motivo |
|---|---|---|---|
| `Esta revisión ya fue resuelta.` | `app/Services/RevisionCobranzaService.php:31` | Sí | Explica que si otro usuario ya resolvió la revisión no se puede volver a enviar. |
| `La nota es obligatoria.` | `app/Http/Controllers/RevisionCobranzaWebController.php:48` | Sí | Explica que se debe escribir el detalle del contacto y motivo antes de confirmar. |
| `La nota debe tener al menos 5 caracteres.` | `app/Http/Controllers/RevisionCobranzaWebController.php:49` | Sí | Requisito de longitud mínima para la justificación. |
| `Alumno confirmado como activo. Se generó la deuda del período.` | `app/Http/Controllers/RevisionCobranzaWebController.php:57` | Sí | Mensaje de éxito al resolver con opción Activo (CONTINUA). |
| `Alumno marcado como inactivo.` | `app/Http/Controllers/RevisionCobranzaWebController.php:61` | Sí | Mensaje de éxito al resolver con opción Inactivo. |
