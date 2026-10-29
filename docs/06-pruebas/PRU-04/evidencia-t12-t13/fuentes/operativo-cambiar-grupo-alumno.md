# Ficha de fuentes: Cambiar a un alumno de grupo o frecuencia (Operativo)

- **Página del manual:** `resources/manual/operativo/cambiar-grupo-alumno.md`
- **Rol:** Operativo
- **Pantalla:** `/alumnos/{alumnoId}/edit`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Paso 1: Entrar a edición** | En **Alumnos**, tocar **Editar** en la tarjeta del alumno. | `resources/views/alumnos/index.blade.php:213`<br>`resources/views/alumnos/edit.blade.php:23` | `Route::get('/alumnos/{id}/edit', ...)` | Acceso autorizado para Operativo. Profesores reciben `HTTP 403 Forbidden`. |
| **Paso 2: Elegir grupo y frecuencia** | Seleccionar nuevo **Grupo** y elegir el plan en **Frecuencia semanal**. | `resources/views/alumnos/_form.blade.php:118, 153`<br>`app/Http/Controllers/AlumnoWebController.php:505` | `$cambiaGrupo = $request->filled('grupo_id') && (int) $request->input('grupo_id') !== (int) $alumno->grupo_id;`<br>`if (!$tienePlanValido \|\| $cambiaGrupo) { $rules['plan_id'] = ['required', Rule::exists('grupo_planes', 'id')->where('grupo_id', ...)]; }` | Si se cambia de grupo pero se omite seleccionar la frecuencia semanal, Wings rechaza con el mensaje exacto: `Debe seleccionar la frecuencia semanal del nuevo grupo.`. |
| **Paso 3: Guardar cambio** | Botón **Guardar** registra la reasignación de grupo y recalcula la cuota según la regla vigente. | `resources/views/alumnos/edit.blade.php:31`<br>`app/Services/CambioPlanService.php:49` | `public function cambiar(Alumno $alumno, GrupoPlan $nuevo): AlumnoPlan` | Guarda la asignación, genera el registro histórico en `alumno_planes` y actualiza la ficha del alumno. |
| **Regla: diferimiento a mes siguiente** | Bajas de plan o subidas con cuota del mes ya saldada (`PAGADA`) rigen a partir del mes siguiente. Subidas con cuota impaga ajustan este mes. | `app/Services/CambioPlanService.php:21-29` | `public function seDifiere(Alumno $alumno, GrupoPlan $nuevo): bool {`<br>`if ($actual && $nuevo->clases_por_semana < $actual->clases_por_semana) return true;`<br>`$deuda = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', now()->format('Y-m'))->first();`<br>`return $deuda && ($deuda->estado === DeudaCuota::ESTADO_CONDONADA \|\| $deuda->saldo_pendiente <= 0); }` | Comprobado en servicio contable: ante reducción de días semanales o cuota saldada, el nuevo precio se programa para `today()->addMonthNoOverflow()`. Si es aumento y adeuda saldo, la cuota actual se ajusta por la diferencia. |
