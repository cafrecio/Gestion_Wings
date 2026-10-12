# Informe de Implementación T28: Cashflow muestra a quién se le cobró y quién cobró

**Fecha:** 12/10/2026  
**Autor:** Gemini (CAB) — Firma: **LOG GEM CAB**  
**Tarea:** T28 — «Cashflow tiene que decir a quién se le cobró y quién cobró»  
**Origen:** Día 1 de la prueba por días (08/10/2026). Decisión de Carlos del 12/10/2026.  
**Estado:** Implementada y con diseño visual aprobado por Carlos (12/10/2026: «Para Escritorio. Opcion 1 OK; Para celular. Opcion 2 OK»). Listo para verificación de regla de negocio por otro agente.

---

## 1. Problema que resuelve

En Cashflow (`resources/views/cashflow/index.blade.php`), cuando una operativa cobraba cuotas o inscripciones en el mostrador y la caja se cerraba y validaba:
1. En la columna 5 («Observaciones» / «Detalle»), se mostraba la observación técnica con el ID del alumno (ej: `Pago cuota alumno #14 - Períodos: 2026-10`) en lugar del apellido y nombre del alumno (`Gomez, Martina`).
2. En la columna 7 («Registrado»), se mostraba al dueño que validó la caja (`Carlos Administrador`), y no a la operativa que cobró en el mostrador (`Sandra Mostrador`).
3. El dueño mirando Cashflow no podía saber a quién correspondía cada cobro ni quién lo había realizado efectivamente.

---

## 2. Reglas implementadas

1. **Identificación del alumno:** Todo cobro de cuota o de inscripción muestra en la columna **Detalle** el `Apellido, Nombre` del alumno.
2. **Identificación de quién cobró:** La columna **Registró** muestra a quien cargó el movimiento: la operativa si provino de una caja validada, o el dueño si lo cobró o cargó directo sin caja.
3. **Alcance:** Aplica a cobros de cuota y de inscripción, tanto para los que provienen de una caja validada como para los cobros directos del dueño sin caja.
4. **Gastos y otros movimientos:** Cualquier gasto o movimiento sin alumno conserva en la columna **Detalle** sus observaciones tal cual venía funcionando.
5. **Consistencia:** Cashflow y el Historial de Caja (`CajaWebController::historial`) dicen exactamente lo mismo para el mismo cobro (mismo alumno y mismo registrador).
6. **Totales e importes intocados:** No se modifica ningún importe, ningún saldo, ningún total ni el orden cronológico descendente de la lista.
7. **Idempotencia:** La validación de una caja continúa pasando sus movimientos a Cashflow exactamente una sola vez.

---

## 3. ¿Qué pasa con los movimientos históricos de cajas validadas previas al cambio?

**Respuesta para Carlos:**  
**No hace falta ninguna migración de base de datos ni scripts de relleno histórico.**  
La resolución se diseñó de manera dinámica en `CashflowWebController::index`:
- Cada asiento en `cashflow_movimientos` proveniente de una caja validada conserva su `referencia_tipo = 'CAJA_OPERATIVA'` y `referencia_id = $cajaId`.
- Los movimientos operativos originales (`movimientos_operativos`) de esa caja nunca se borran y ya tienen el `alumno_id` y el `usuario_id` (la persona que atendió y cobró en el mostrador).
- Al renderizar la página de Cashflow (30 movimientos por página), el controlador consulta en batch los `movimientos_operativos` de esas cajas y resuelve de forma inmediata el `Apellido, Nombre` del alumno y el usuario original que registró el movimiento.
- Por lo tanto, **los movimientos viejos ya existentes en Cashflow se muestran ahora automáticamente con el nombre del alumno y con el nombre de la operativa**, sin tocar la base de datos ni alterar registros históricos.
- Adicionalmente, para todas las cajas que se validen a futuro, `CashflowIntegracionCajaService::reflejarCajaEnCashflow` guarda `'usuario_admin_id' => $movimiento->usuario_id ?? $adminId`, asegurando consistencia a nivel de registro persistido.

---

## 4. Cambios de código y diseño visual aprobado

1. **`app/Services/CashflowIntegracionCajaService.php`:**  
   En `reflejarCajaEnCashflow`, al persistir cada `CashflowMovimiento` a partir del `MovimientoOperativo`, se almacena `'usuario_admin_id' => $movimiento->usuario_id ?? $adminId`.
2. **`app/Http/Controllers/CashflowWebController.php`:**  
   En `index()`, para la página paginada actual, se cargan en batch los `Pago` correspondientes a movimientos con `REF_PAGO_CUOTA` y los `MovimientoOperativo` activos de las cajas con `REF_CAJA`. Se mapea cada movimiento para asignar `$mov->alumnoNombre` (o `null` si no tiene alumno) y `$mov->quienRegistro`.
3. **`resources/views/cashflow/index.blade.php`:**  
   Implementación responsive aprobada por Carlos el 12/10/2026:
   - **Escritorio (Opción 1):** Tabla de 7 columnas rebalanceada (`<colgroup>`: 95px, 35px, 95px, 230px, `<col>` flexible para Detalle, 110px, 160px para Registró completo). Sin espacios blancos sobrantes y sin cortes de nombres.
   - **Celular (Opción 2):** Vista en tarjetas verticales compactas en `< 768px` (sin scroll horizontal). Cabecera con fecha, chip de caja, tipo `I`/`E` y monto resaltado a la derecha; cuerpo con `Rubro — Subrubro`, alumno destacado o detalle de gasto; pie con `Registró: <nombre>`.

---

## 5. Pruebas automatizadas

Se incorporó una nueva suite permanente de 5 pruebas en `tests/Feature/CashflowDetalleAlumnoYUsuarioTest.php`:
1. `test_cobro_de_operativa_validado_muestra_alumno_y_operativa_en_cashflow`: Cuota cobrada por Sandra Mostrador en mostrador y validada por Carlos Administrador -> Cashflow muestra `Perez, Juan` en Detalle y `Sandra Mostrador` en Registró.
2. `test_cobro_del_duenio_sin_caja_muestra_alumno_y_duenio_en_cashflow`: Cuota cobrada directo por el dueño sin caja -> Cashflow muestra `Gomez, Martina` en Detalle y `Carlos Administrador` en Registró.
3. `test_inscripcion_muestra_alumno_y_quien_cobro_en_caja_y_sin_caja`: Inscripción cobrada por operativa en caja validada muestra alumno y operativa; inscripción cobrada por dueño muestra alumno y dueño.
4. `test_gasto_sin_alumno_conserva_su_observacion_y_muestra_quien_cargo`: Gasto de mostrador (Sandra) conserva `Compra de lavandina...` y muestra a Sandra; gasto directo de Carlos conserva `Abono mensual...` y muestra a Carlos.
5. `test_validar_caja_dos_veces_no_duplica_movimientos_en_cashflow`: Idempotencia estricta en la integración de caja con Cashflow.

Resultado: **5/5 tests aprobados (32 aserciones)** en `wings_testing_gemini`.

---

## 6. Capturas de pantalla reglamentarias finales

Guardadas en `docs/06-pruebas/PRU-04/evidencia-t28/` generadas desde la aplicación real (`CapturaT28Test.php`) y capturadas con Google Chrome headless (`tomar-capturas.mjs`):

| Vista | Dispositivo | Archivo |
|---|---|---|
| Cashflow con cobros de caja, cobro directo del dueño y gastos (Opción 1) | Escritorio (1366 × 950) | `docs/06-pruebas/PRU-04/evidencia-t28/cashflow-escritorio.png` |
| Cashflow con cobros de caja, cobro directo del dueño y gastos (Opción 2 tarjetas) | Celular (marco 360 px) | `docs/06-pruebas/PRU-04/evidencia-t28/cashflow-celular-360.png` |
| Control de método (Login) | Escritorio (1366 × 950) | `docs/06-pruebas/PRU-04/evidencia-t28/login-control-escritorio.png` |
| Control de método (Login) | Celular (marco 360 px) | `docs/06-pruebas/PRU-04/evidencia-t28/login-control-celular-360.png` |

---

## 7. Qué no se probó

- **Navegación manual interactiva:** No se recorrió la pantalla con clicks humanos en vivo en un servidor web local; la totalidad de los flujos se validó mediante suite automatizada de Feature (`CashflowDetalleAlumnoYUsuarioTest`) y renderizado real de la aplicación a HTML para Chrome headless.
- **Sitio de prueba (VPS):** No se desplegó al sitio de prueba ni a producción; el sitio de prueba y su reloj simulado permanecen intocados.
- **Liquidaciones de profesores (`REF_LIQUIDACION`):** No corresponden a cobros de alumnos ni estaban en el alcance de T28. Se conservan sin alteraciones.

---

## 8. Próximo paso

El aspecto visual ya fue evaluado y aprobado por Carlos el 12/10/2026. Según §6a de AGENTS.md, otro agente (Claude o Codex) debe verificar la regla de negocio y datos en el código y en la base de datos para cerrar definitivamente la tarea en el tablero.
