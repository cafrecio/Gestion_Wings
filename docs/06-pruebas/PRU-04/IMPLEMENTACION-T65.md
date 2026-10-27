# Implementación T65 — Eliminación de alumno o clase cargados por error

**Fecha:** 27 de octubre de 2026  
**Agente:** Gemini (Antigravity)  
**Máquina:** Casa de Carlos (CAB)  
**Casos asociados:** DU-48, ALU-024, CLA-021  
**Autorización de diseño:** Carlos autorizó en T65 botones y paneles de confirmación para eliminar alumno o clase cargados por error.

---

## 1. Contexto y Decisión de Negocio (DU-48)

Hasta esta tarea, en Wings un alumno solo se podía dar de baja (`activo = false`) y una clase solo se podía cancelar (`cancelada = true`). Carlos decidió el 13/10/2026 (DU-48) que el dueño (ADMIN) pueda **eliminar del todo** un alumno o una clase cargados por error:

- **Alumno (ALU-024):** El dueño puede eliminar un alumno que no tiene cobros ni asistencias. Si tiene cobros o asistencias, el sistema lo rechaza, lo explica detalladamente y ofrece darlo de baja. Al eliminarse, se cancela/elimina su cuota de alta impaga y su inscripción no cobrada; si la inscripción es compartida con otro deporte (mismo DNI), la inscripción del otro deporte **no se toca ni se borra** (se reasigna la FK para preservar la integridad). El DNI queda liberado para volver a cargarlo bien.
- **Clase (CLA-021):** El dueño puede eliminar una clase o una serie completa sin asistencias, sin validación manual para liquidación y sin detalles en liquidaciones (abiertas o cerradas). Si tiene asistencias, no se elimina: debe cancelarse con motivo. Al eliminarse, la clase desaparece de listados, filtros y del contador de «Sin asistencia».
- **Permisos (PERMISOS-ROLES):** Operativo y Profesor reciben `403` si intentan forzar la acción, y no ven los botones en pantalla.

---

## 2. Auditoría Integral de Tablas y Foreign Keys (AUD-025)

Siguiendo el requerimiento explícito de no depender de cascadas automáticas en BD y realizar borrado explícito y transaccional bajo `lockForUpdate()`, se auditó el esquema de base de datos:

### Tablas que apuntan a `alumnos`:
| Tabla | FK / Columna | ON DELETE en BD | Comportamiento en T65 |
|---|---|---|---|
| `pagos` | `alumno_id` | CASCADE | **Bloquea eliminación.** Si existe cualquier pago (incluso ANULADO), rechaza con motivo. |
| `asistencias` | `alumno_id` | CASCADE | **Bloquea eliminación.** Si existe cualquier asistencia (presente o ausente), rechaza con motivo. |
| `asistencia_excesos` | `alumno_id` | CASCADE | **Bloquea eliminación.** Si existe exceso, rechaza. |
| `movimientos_operativos` | `alumno_id` | SET NULL | **Bloquea eliminación.** Si existe movimiento de caja, rechaza. |
| `deuda_cuotas` | `alumno_id` | CASCADE | **Condicional:** Si tiene pagos (`monto_pagado > 0`) o estado distinto a `PENDIENTE`, **bloquea eliminación**. Si está impaga sin pagos, se borra explícitamente en la transacción. |
| `cargos_alumno` | `alumno_id` | RESTRICT | **Condicional:** Si tiene pagos aplicados (`pago_cargo_alumno`) o condonación, **bloquea eliminación**. Si es compartida con otro deporte (mismo DNI), **se preserva y se reasigna al otro alumno**. Si es exclusiva e impaga, se borran sus eventos (`cargo_alumno_eventos`), el cargo y `inscripcion_personas`. |
| `alumno_planes` | `alumno_id` | CASCADE | Se borran explícitamente en la transacción. |
| `alumnos_revision_cobranza` | `alumno_id` | CASCADE | Se borran explícitamente en la transacción. |

### Tablas que apuntan a `clases`:
| Tabla | FK / Columna | ON DELETE en BD | Comportamiento en T65 |
|---|---|---|---|
| `asistencias` | `clase_id` | CASCADE | **Bloquea eliminación.** Si existe cualquier asistencia (presente o ausente), rechaza. Debe cancelarse con motivo. |
| `clase_profesor` | `clase_id` | CASCADE | Se desvinculan explícitamente los profesores antes del borrado de la clase. |
| `liquidacion_detalles` | `referencia_id` (`tipo_referencia = 'clase'`) | Polimórfica | **Bloquea eliminación.** Si existe detalle en liquidación (abierta o cerrada), rechaza. |

---

## 3. Cambios Implementados

1. **Rutas (`routes/web.php`):**
   - `DELETE /alumnos/{id}` (`web.alumnos.destroy`) bajo middleware `ensure.admin.web`.
   - `DELETE /clases/{id}` (`web.clases.destroy`) bajo middleware `ensure.admin.web`.

2. **Modelos:**
   - `App\Models\Alumno`: métodos `esEliminable(): bool` y `motivoNoEliminable(): ?string`. Verifica ausencia de pagos, asistencias, movimientos operativos, cuotas con cobro/condonadas y cargos con cobro/condonados.
   - `App\Models\Clase`: métodos `esEliminable(): bool` y `motivoNoEliminable(): ?string`. Verifica ausencia de asistencias, que no esté validada para liquidación y que no integre ninguna liquidación (ni detalle directo ni liquidación cerrada por comisión/hora).

3. **Controladores:**
   - `AlumnoWebController`:
     - `show()`: pasa `$puedeEliminar` y `$motivoNoEliminable`.
     - `destroy()`: verificación estricta de rol ADMIN (403), transacción con `lockForUpdate()`. Si no es eliminable, redirige a la ficha con mensaje explicativo y ofrecimiento de dar de baja (`session()->flash('error', ...)`). Si es eliminable, elimina explícitamente revisiones, planes, deudas impagas; reasigna inscripción compartida o borra inscripción exclusiva; elimina alumno y libera el DNI.
   - `ClaseWebController`:
     - `show()` y `edit()`: pasan `$puedeEliminar` y `$motivoNoEliminable`.
     - `destroy()`: verificación estricta de rol ADMIN (403), soporte para parámetro `eliminar_serie`. Si es serie, bloquea y valida todas las clases de la serie; si alguna no es eliminable, rechaza indicando la fecha exacta y el motivo. Si todas son eliminables, desvincula profesores y elimina las clases en lote. Si es clase individual, valida y elimina desvinculando profesores.

4. **Interfaz de Usuario (Design System y AGENTS.md):**
   - Vistas: `resources/views/alumnos/show.blade.php` y `resources/views/clases/show.blade.php`.
   - Botón `Eliminar` con un solo verbo corto, solo visible para ADMIN y cuando el registro es eliminable.
   - Panel de confirmación inline integrado en la página (sin diálogos nativos `window.confirm`, `data-confirmar` ni `data-confirmar-envio`, MF10).
   - Scripts Vite sin JS incrustado en Blade: `resources/js/alumnos-show.js` y `resources/js/clases-show.js` manejan la apertura y cierre de los paneles.
   - Assets compilados con `npm run build`.

---

## 4. Evidencia Visual (Chrome Real a 1366 px y 375 px en marco)

Capturas generadas con Chrome headless en `docs/06-pruebas/PRU-04/evidencia-t65/`:

1. **Alumno eliminable (botón Eliminar al pie):**
   - Escritorio: [`01-alumno-eliminable-1366.png`](./evidencia-t65/01-alumno-eliminable-1366.png)
   - Celular (marco 375): [`01-alumno-eliminable-375.png`](./evidencia-t65/01-alumno-eliminable-375.png)

2. **Alumno — Panel de confirmación abierto:**
   - Escritorio: [`02-alumno-panel-confirmacion-1366.png`](./evidencia-t65/02-alumno-panel-confirmacion-1366.png)
   - Celular (marco 375): [`02-alumno-panel-confirmacion-375.png`](./evidencia-t65/02-alumno-panel-confirmacion-375.png)

3. **Alumno con cobro — Rechazo de eliminación y ofrecimiento de baja:**
   - Escritorio: [`03-alumno-con-cobro-ofrece-baja-1366.png`](./evidencia-t65/03-alumno-con-cobro-ofrece-baja-1366.png)
   - Celular (marco 375): [`03-alumno-con-cobro-ofrece-baja-375.png`](./evidencia-t65/03-alumno-con-cobro-ofrece-baja-375.png)

4. **Clase eliminable (botón Eliminar en cabecera de acciones):**
   - Escritorio: [`04-clase-eliminable-1366.png`](./evidencia-t65/04-clase-eliminable-1366.png)
   - Celular (marco 375): [`04-clase-eliminable-375.png`](./evidencia-t65/04-clase-eliminable-375.png)

5. **Clase con serie — Panel de confirmación (Eliminar clase o Eliminar serie):**
   - Escritorio: [`05-clase-panel-confirmacion-serie-1366.png`](./evidencia-t65/05-clase-panel-confirmacion-serie-1366.png)
   - Celular (marco 375): [`05-clase-panel-confirmacion-serie-375.png`](./evidencia-t65/05-clase-panel-confirmacion-serie-375.png)

---

## 5. Pruebas Automatizadas

Archivo de pruebas: `tests/Feature/EliminarAlumnoYClaseT65Test.php` (18 pruebas, 105 aserciones):
- `test_operativo_y_profesor_reciben_403_al_intentar_eliminar_alumno`
- `test_operativo_y_profesor_reciben_403_al_intentar_eliminar_clase`
- `test_duenio_elimina_alumno_sin_cobros_ni_asistencias_limpia_deuda_e_inscripcion_y_libera_dni`
- `test_alumno_con_pago_no_se_elimina_explica_por_que_y_ofrece_darlo_de_baja`
- `test_alumno_con_pago_anulado_tampoco_se_elimina`
- `test_alumno_con_asistencia_presente_o_ausente_no_se_elimina`
- `test_alumno_con_movimiento_de_caja_no_se_elimina`
- `test_alumno_con_cuota_con_pago_o_condonada_no_se_elimina`
- `test_alumno_con_inscripcion_cobrada_no_se_elimina`
- `test_alumno_en_segundo_deporte_con_inscripcion_compartida_se_elimina_sin_tocar_la_inscripcion_del_otro_deporte`
- `test_duenio_elimina_clase_sin_asistencias_ni_liquidaciones`
- `test_clase_con_asistencia_no_se_elimina_debe_cancelarse`
- `test_clase_en_liquidacion_abierta_o_cerrada_no_se_elimina`
- `test_clase_validada_para_liquidacion_no_se_elimina`
- `test_serie_se_elimina_completa_si_todas_estan_limpias`
- `test_serie_rechaza_eliminacion_si_alguna_clase_tiene_asistencia_o_liquidacion_e_indica_cual`
- `test_concurrencia_cobro_entrante_durante_intento_de_eliminacion_rechaza_el_borrado`
- `test_pantalla_ficha_alumno_y_clase_muestran_boton_eliminar_solo_para_admin_y_cuando_es_eliminable`

Todas ejecutadas en `wings_testing_gemini`: 18 aprobadas, 0 fallidas.
Verificación CSP: `CspSinCodigoIncrustadoTest` 2 aprobadas (0 bloques y 0 manejadores inline).
Sintaxis: `php -l` sin errores en ningún archivo tocado.
Vistas Blade: `php artisan view:cache` compila sin errores.
