# T65 — Devolución de eliminación de alumno y clase

**Fecha:** 28/10/2026  
**Responsable:** Gemini (CAB)  
**Verificación previa:** Codex (`docs/06-pruebas/PRU-04/VERIFICACION-T65-CODEX.md`)  
**Verificador asignado:** Codex  

---

## 1. Problemas reportados y resolución

### H1 — Recargo impago (Punitorio): error 500 y reasignación errónea
- **Problema:** `cargos_alumno.deuda_cuota_id` tiene una clave foránea `RESTRICT` hacia `deuda_cuotas(id)`. Al eliminar un alumno, `AlumnoWebController::destroy` eliminaba las filas de `deuda_cuotas` antes de procesar los cargos, provocando error de clave foránea 500 (`cargos_alumno_deuda_cuota_id_foreign`). Además, si existía otro alumno con el mismo DNI, el paso 4 reasignaba todos los cargos indiscriminadamente al otro alumno, lo que transferiría el punitorio propio de la cuota del alumno eliminado hacia el hermano.
- **Resolución:**
  - En `AlumnoWebController::destroy`, se reordenó el flujo: se procesan los `cargos_alumno` antes de borrar `deuda_cuotas`.
  - Solo se reasigna al otro alumno con el mismo DNI el cargo de tipo `INSCRIPCION` (`$cargo->tipo === 'INSCRIPCION'`).
  - Los cargos ligados a cuotas propias (como `PUNITORIO`) u otros cargos del alumno no compartidos se eliminan explícitamente junto con sus eventos en `cargo_alumno_eventos` antes de borrar la cuota.
  - Se agregó la prueba `test_recargo_impago_con_otro_alumno_mismo_dni_no_se_transfiere_y_conserva_inscripcion` en `tests/Feature/EliminarAlumnoYClaseT65Test.php` comprobando que el punitorio no se transfiere y que la inscripción compartida se preserva.

### H2 — Sueldos seguía sumando la cuota borrada en el historial analítico
- **Problema:** `DB::table('deuda_cuotas')->delete()` no disparaba los eventos del modelo Eloquent. Por ende, `HistorialAnaliticoReportesObserver::deleted()` nunca era ejecutado y la cuota permanecía activa en `reporte_analitico_historial`, provocando que el reporte de sueldos siguiera contabilizando el valor de la cuota como adeudada/liquidable.
- **Resolución:**
  - En `AlumnoWebController::destroy`, las cuotas impagas del alumno se eliminan individualmente mediante `$cuota->delete()`, invocando los eventos de Eloquent y registrando la baja en `reporte_analitico_historial` de manera sincrónica y atómica dentro de la transacción.
  - Se actualizó el fixture en `tests/Support/EscenarioT65.php` configurando la cobertura `desde = '2026-10-01 00:00:00'` en `reporte_cobertura` y `reporte_analitico_cobertura` para que las pruebas que viajan a fechas como el 27/10 (`travelTo`) mantengan cobertura válida y no dependan de la hora o fecha calendario de ejecución.
  - `test_el_borrado_retira_la_cuota_del_reporte_actual_de_sueldos` pasa en verde.

### H3 — Excepción de cobranza sin origen
- **Problema:** `excepciones_cobranza.alumno_id` tiene FK `RESTRICT`. Si un alumno tenía registradas excepciones de cobranza, `Alumno::motivoNoEliminable()` no lo contemplaba y el borrado intentaba ejecutarse hasta chocar con la base foránea. Las excepciones nunca deben borrarse ya que documentan auditoría de decisiones.
- **Resolución:**
  - En `app/Models/Alumno.php`, se agregó la relación `excepcionesCobranza()` y en el método `motivoNoEliminable()` se agregó la verificación:
    ```php
    if (ExcepcionCobranza::where('alumno_id', $this->id)->exists()) {
        return 'Tiene excepciones de cobranza registradas.';
    }
    ```
  - Al detectar excepciones, `destroy()` rechaza con 422 y devuelve el mensaje estructurado que sugiere marcar al alumno como inactivo en lugar de eliminarlo.
  - Las pruebas `test_excepcion_sin_origen_debe_rechazar_con_explicacion_y_no_con_500` y `test_excepcion_real_de_asistencia_frena_antes_de_la_fk_y_conserva_su_origen` pasan en verde.

---

## 2. Auditoría de Observers en el flujo de borrado

Se revisaron todos los observers registrados en `EventServiceProvider.php` y en los modelos vinculados a tablas que se borran o limpian en `destroy()` de alumno y clase:

| Modelo | Observers asociados | Efecto en `destroy()` |
|---|---|---|
| `DeudaCuota` | `HistorialAnaliticoReportesObserver`, `HistorialReporteObserver` | **Activo:** ahora dispara `deleted()` al llamar `$cuota->delete()`, registrando la baja analítica e histórica. |
| `Alumno` | Ninguno | Sin observers. |
| `Clase` | Ninguno | Sin observers. |
| `ClaseProfesor` | Ninguno | Sin observers. |
| `AlumnoPlan` | Ninguno | Sin observers. |
| `CargoAlumno` | Ninguno | Sin observers. |
| `CargoAlumnoEvento` | Ninguno | Sin observers. |
| `AlumnoRevisionCobranza` | Ninguno | Sin observers. |
| `InscripcionPersona` | Ninguno | Sin observers. |

El único modelo en todo el árbol de borrado que requería disparar eventos de ciclo de vida es `DeudaCuota`, el cual queda cubierto.

---

## 3. Pruebas y verificación

- **Pruebas de verificación de Codex (`tests/Feature/VerificacionEliminarT65CodexTest.php`):**
  - **12/12 pasadas** (90 assertions). Todas las fallas H1, H2, H3 quedaron en verde.
- **Pruebas funcionales T65 (`tests/Feature/EliminarAlumnoYClaseT65Test.php`):**
  - **19/19 pasadas** (114 assertions), incluyendo la nueva prueba de punitorio con otro alumno del mismo DNI.
- **Pruebas de concurrencia T65 (`tests/Feature/EliminarT65ConcurrenteCodexTest.php`):**
  - **2/2 pasadas** (75 assertions).
- **Pruebas de Reportes de Sueldos (`tests/Feature/ReporteSueldosTest.php` y `ReporteSueldosOperativasT86Test.php`):**
  - **18/18 pasadas** (83 assertions).
- **Consistencia documental (`tests/Feature/DocumentacionNoMienteTest.php`):**
  - **PASS** (1181 pruebas reales declaradas en `PLAN-PRODUCCION.md`, `ESTADO-ACTUAL.md` y `CHECKLIST-CARLOS.md`).
- **Tablero (`tests/Feature/TableroAlDiaTest.php`):**
  - **PASS**.
- **Seguridad CSP (`tests/Feature/CspSinCodigoIncrustadoTest.php`):**
  - **PASS**.
- **Suite rápida (`php artisan test --exclude-group=lento`):**
  - **1054 pasadas, 6 omitidas, 0 fallidas** (8141 assertions, 377s).
- **Caché de vistas Blade:**
  - Compilación limpia sin errores (`php artisan view:cache && php artisan view:clear`).
- **Verificación de archivos de diseño:**
  - `git diff --stat -- resources/views resources/css` no contiene cambios relacionados a T65.
