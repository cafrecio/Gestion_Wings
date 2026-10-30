# Ficha de fuentes: Cerrar y pagar una liquidación

- **Página del manual:** `resources/manual/admin/cerrar-pagar-liquidacion.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/liquidaciones/{id}`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Cerrar liquidación (fin de mes)** | Solo se puede cerrar una liquidación abierta cumplido el período (`puedeCerrarse`). Cambia a Cerrada y congela importes. | `app/Services/LiquidacionService.php:284-292`<br>`resources/views/liquidaciones/partials/acciones.blade.php:7, 20` | `if ($liquidacion->estaCerrada()) throw new \Exception('La liquidación ya está cerrada.');`<br>`if (!$liquidacion->puedeCerrarse()) throw new \Exception("No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el {$desde}.");` | Si se intenta cerrar una liquidación de un mes no concluido o antes de su fecha de habilitación, el botón **Cerrar** no aparece y la llamada directa al servicio lanza la excepción con el aviso de fecha desde la cual se puede cerrar. |
| **Cuotas atrasadas pendientes** | Para cerrar o pagar, las cuotas atrasadas deben estar validadas o rechazadas una por una. | `app/Services/LiquidacionService.php:285-287`<br>`resources/views/liquidaciones/partials/acciones.blade.php:4-14` | `if ($liquidacion->tieneAtrasadasPendientes()) throw new \Exception('Primero validá o rechazá cada cuota atrasada.');` | Con cuotas atrasadas en estado `PENDIENTE`, el botón **Cerrar** se oculta mostrando el texto «Revisá las cuotas atrasadas para cerrar.». Si se fuerza la petición POST a cerrar, el controlador captura la excepción y responde con error. |
| **Pagar liquidación cerrada** | Se abona desde la sección Registrar pago seleccionando cuenta de salida, fecha y observaciones. Genera egreso en Cashflow y pasa a Pagada. | `app/Http/Controllers/LiquidacionWebController.php:407-470`<br>`resources/views/liquidaciones/show.blade.php:450-485` | `if ($liquidacion->estaPagada()) throw new \Exception('La liquidación ya está pagada.');`<br>`if ($saldoResultante < 0 && !$tipoCaja->permite_descubierto)` | Al intentar pagar desde una cuenta con saldo insuficiente que no admite descubierto, el sistema rechaza la operación informando `Saldo insuficiente en... Saldo disponible: $...`. Al pagar una liquidación ya pagada devuelve error. |
| **Qué hago si: fecha de mes anterior** | Si la fecha de pago elegida es de un mes anterior, Wings pide confirmación y cambia el botón a **Confirmar**. | `app/Http/Controllers/LiquidacionWebController.php:422-430`<br>`resources/views/liquidaciones/show.blade.php:481` | `if ($esFechaVieja && !$request->boolean('confirmar_fecha_vieja'))` | Al ingresar fecha de un mes anterior sin tildar la confirmación, la pantalla recarga con el aviso «Esta fecha es de un mes anterior. El pago va a quedar registrado con esa fecha y modificará el reporte de ese mes.» y el botón cambia a **Confirmar**. |
| **Qué hago si: cancelar liquidación** | Una liquidación cerrada sin pagar puede cancelarse con motivo para volver a revisar asistencias y regenerar. Una liquidación pagada no se puede cancelar. | `app/Models/Liquidacion.php:124`<br>`app/Http/Controllers/LiquidacionWebController.php:374-394`<br>`resources/views/liquidaciones/show.blade.php:489` | `if ($esCerrada && !$esPagada && !$esCancelada)`<br>`if ($liquidacion->estaPagada()) throw new \Exception('No se puede cancelar una liquidación pagada.');` | En liquidaciones en estado `PAGADA`, el panel **Cancelar liquidación** no se muestra en pantalla. Si se intenta forzar la cancelación por POST, el modelo aborta con la excepción `No se puede cancelar una liquidación pagada.`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el ` | `app/Services/LiquidacionService.php:290` | Sí | Qué hago si: ¿Qué hago si no veo el botón Cerrar? |
| `Revisá las cuotas atrasadas para cerrar.` | `resources/views/liquidaciones/partials/acciones.blade.php:10` | Sí | Qué hago si: ¿Qué hago si al querer cerrar o pagar me dice que hay cuotas pendientes? |
| `Primero validá o rechazá cada cuota atrasada.` | `app/Services/LiquidacionService.php:286` | Sí | Qué hago si: ¿Qué hago si al querer cerrar o pagar me dice que hay cuotas pendientes? |
| `Esta fecha es de un mes anterior. El pago va a quedar registrado con esa fecha y modificará el reporte de ese mes.` | `app/Http/Controllers/LiquidacionWebController.php:428` | Sí | Paso 2. Registrar el pago en Cashflow |
| `No se puede cancelar una liquidación pagada.` | `app/Services/LiquidacionService.php:498` | Sí | Qué hago si…: ¿Puedo cancelar una liquidación si me equivoqué al cerrarla? |
| `Seleccioná el tipo de caja.` | `app/Http/Controllers/LiquidacionWebController.php:417` | No | Validación estándar de formulario cuando no se selecciona la cuenta de egreso. |
| `La fecha de pago es obligatoria.` | `app/Http/Controllers/LiquidacionWebController.php:415` | No | Validación estándar de formulario si se borra el campo de fecha. |
