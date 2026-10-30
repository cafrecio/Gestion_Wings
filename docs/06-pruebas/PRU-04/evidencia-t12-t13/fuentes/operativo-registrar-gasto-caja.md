# Ficha de fuentes: Registrar un gasto o salida del cajón (Operativo)

- **Página del manual:** `resources/manual/operativo/registrar-gasto-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/movimiento`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Regla del dinero del dueño** | El dueño no retira dinero sin asentar. Todo retiro se carga como EGRESO en la caja del mostrador por la operativa. | `app/Http/Controllers/CajaWebController.php:336`<br>`app/Services/CajaService.php:300` | `'tipo' => 'required\|in:INGRESO,EGRESO'` | Mantiene la trazabilidad: el saldo físico esperado del arqueo final coincide con el efectivo real remanente en el cajón. |
| **Paso 1: Entrar a movimiento** | En **Caja**, tocar botón **Nuevo**. Abre formulario de registro de movimiento. | `resources/views/caja/resumen.blade.php:63`<br>`resources/views/caja/movimiento.blade.php:2` | `@if(in_array($caja->estado, ['ABIERTA', 'RECHAZADA']) && $esPropietario)` | Con caja cerrada o validada el botón no se exhibe. |
| **Paso 2: Opción EGRESO y rubro** | Seleccionar botón rojo **EGRESO**, **Medio de pago** (Efectivo), **Rubro** y **Subrubro**. | `resources/views/caja/movimiento.blade.php:25-50` | `'tipo' => 'required\|in:INGRESO,EGRESO'` | Al seleccionar EGRESO, la interfaz cambia el color del botón a rojo y filtra los rubros de salida habilitados. |
| **Paso 3: Monto y registrar** | Ingresar **Monto**, **Observaciones** y tocar botón **Registrar**. Descuenta del saldo de caja. | `resources/views/caja/movimiento.blade.php:60-75`<br>`app/Services/CajaService.php:614-616` | `'monto' => 'required\|numeric\|min:0.01'`<br>`if ($disponible - $viejo + $nuevo < 0)` | Si el gasto supera los billetes del cajón, rechaza con: `El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.`. Con saldo suficiente, descuenta del saldo del turno. |
| **Corrección: Editar / Eliminar** | En egresos manuales de mostrador, el detalle ofrece los botones **Editar** y **Eliminar** (no Cancelar). | `resources/views/caja/detalle.blade.php:207`<br>`app/Models/MovimientoOperativo.php:75`<br>`app/Services/CajaCorreccionService.php:19-24`<br>`app/Http/Controllers/CajaWebController.php:608` | `@elseif($mov->esManualEditable())`<br>`if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'], true))` | Si se intenta usar la ruta de cancelación de cuotas sobre un gasto manual, devuelve `HTTP 403: Solo se pueden cancelar cobros de cuota.`. Si se intenta eliminar un egreso con la caja cerrada o validada, rechaza con: `La caja no está en estado editable.`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.» | `app/Services/CajaService.php:615` | Sí | Qué hago si: ¿Qué hago si el gasto supera el efectivo que hay en el cajón? |
| `La caja no está en estado editable.` | `app/Services/CajaCorreccionService.php:24` | No | Excepción contable interna; la página explica en Qué hago si que no se edita en caja validada. |
| `Solo se pueden cancelar cobros de cuota.` | `app/Http/Controllers/CajaWebController.php:608` | No | Excepción interna; la página explica que se usa Editar y Eliminar sin citar el error de abort. |
| `El subrubro ':nombre' es reservado del sistema y no puede usarse manualmente.` | `app/Services/CajaService.php:627` | No | Excepción técnica del sistema; el selector de la pantalla ya filtra solo subrubros no reservados habilitados para mostrador. |
| `The monto field must be at least 0.01.` | `app/Http/Controllers/CajaWebController.php:337` | No | Validación estándar del formulario; en Paso 2 se indica ingresar el importe numérico en pesos. |
