# Ficha de fuentes: Corregir un movimiento mal cargado en una caja ya validada

- **Página del manual:** `resources/manual/admin/corregir-movimiento-caja-validada.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/cashflow`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Fundamento: caja validada inmutable** | Una caja validada no se edita ni borra; cualquier diferencia se compensa con un ajuste directo en Cashflow. | `app/Http/Controllers/CajaWebController.php:601`<br>`app/Models/CajaOperativa.php:80` | `if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA']))` | Al enviar una petición POST a `/caja/movimiento/{id}/cancelar` sobre una caja en estado `VALIDADA`, el controlador rechaza la operación y redirige con error flash: `Solo se puede cancelar un cobro con la caja abierta o rechazada.`. |
| **Paso 1 y 2: Formulario de nuevo movimiento** | Desde Cashflow, tocar el botón **Nuevo** para abrir la pantalla de carga de movimiento directo. | `resources/views/cashflow/index.blade.php:147`<br>`resources/views/cashflow/movimiento.blade.php:14` | `if (!auth()->user()->isAdmin()) abort(403);` | Si un usuario no administrador intenta acceder a `/cashflow/movimiento`, recibe HTTP 403 Forbidden. |
| **Paso 3 y 4: Cargar ajuste compensatorio** | Elegir tipo (**EGRESO** o **INGRESO**), medio, subrubro, monto y observación. Si la fecha es anterior, Wings exige confirmación. | `resources/views/cashflow/movimiento.blade.php:38-155`<br>`app/Http/Controllers/CashflowWebController.php:240-264` | `if ($esFechaVieja && !$request->boolean('confirmar_fecha_vieja'))`<br>`if ($saldoResultante < 0 && !$tipoCaja->permite_descubierto)` | Al cargar una fecha de un mes anterior sin marcar la casilla de confirmación, la pantalla recarga mostrando «Atención: fecha de un mes anterior» y el botón cambia a **Confirmar**. Con monto menor a 0.01 la validación rechaza con error de formulario. |
| **Qué hago si: caja cerrada sin validar** | Si la caja aún no fue validada, se puede rechazar con motivo para que la operativa cancele el cobro desde su sesión y vuelva a cerrar. | `app/Http/Controllers/CajaWebController.php:480, 601`<br>`resources/views/caja/detalle.blade.php:50` | `if ($caja->estado !== CajaOperativa::ESTADO_CERRADA)` | Al tocar Rechazar en una caja cerrada, el estado pasa a `RECHAZADA`. En este estado la operativa sí visualiza el botón rojo para cancelar el movimiento. En una caja ya validada el botón Rechazar no existe. |
| **Qué hago si: afectó deuda de alumno** | Una caja validada no permite devolver la deuda al alumno automáticamente. El cobro no se anula desde la ficha y reactivar solo acepta condonadas. | `app/Services/PagoCuotaService.php:380, 1266`<br>`app/Http/Controllers/CashflowCorreccionWebController.php:55` | `if ($movimiento->referencia_tipo === CashflowMovimiento::REF_CAJA) throw ...`<br>`abort_unless($mov->puedeCorregirse(), 403, 'Usá el recorrido propio de este movimiento.');` | Al intentar editar o anular directamente en Cashflow un movimiento proveniente de una caja validada, el sistema aborta con HTTP 403: `Usá el recorrido propio de este movimiento.`. En la ficha del alumno, intentar anular lanza: `Este cobro se hizo por caja: se anula desde el detalle de esa caja.`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Atención: fecha de un mes anterior` | `resources/views/cashflow/movimiento.blade.php:40` | Sí | Paso 3: Cargar el ajuste compensatorio |
| `Solo se puede cancelar un cobro con la caja abierta o rechazada.` | `app/Http/Controllers/CajaWebController.php:601` | No | Explicado conceptualmente en «¿Qué hago si la caja todavía no fue validada?» sin citar el texto literal de excepción HTTP. |
| `Este cobro se hizo por caja: se anula desde el detalle de esa caja.` | `app/Services/PagoCuotaService.php:1266` | No | Limitación interna del sistema detallada conceptualmente en «¿Qué pasa si el cobro mal hecho correspondía a la cuota de un alumno?». |
| `Solo se pueden reactivar cuotas CONDONADAS.` | `app/Services/PagoCuotaService.php:380` | No | Excepción técnica del servicio contable de cuotas. |
| `Usá el recorrido propio de este movimiento.` | `app/Http/Controllers/CashflowCorreccionWebController.php:55` | No | Bloqueo de seguridad al intentar corregir asientos automáticos de cierre de caja por fuera del circuito administrativo. |
