# Ficha de fuentes: Rectificar la apertura de caja (Operativo)

- **Página del manual:** `resources/manual/operativo/rectificar-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/{cajaId}/rectificar`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Requisito: caja abierta** | Podés rectificar el efectivo declarado en la apertura únicamente si tu caja continúa en estado **Abierta**. Siempre exige motivo. | `app/Http/Controllers/CajaWebController.php:102, 113`<br>`app/Services/CajaService.php:161`<br>`resources/views/caja/resumen.blade.php:68` | `if ($caja->estado !== CajaOperativa::ESTADO_ABIERTA) { return redirect()->route('web.caja.resumen', $id)->with('error', 'Solo se puede rectificar una caja en estado ABIERTA.'); }`<br>`@if($caja->estado === 'ABIERTA' && ($esPropietario \|\| Auth::user()->isAdmin()))` | Si la caja se encuentra en estado `RECHAZADA`, `CERRADA` o `VALIDADA`: el botón **Rectificar** no aparece en la vista de resumen. Si se intenta acceder directamente por URL a `/caja/{id}/rectificar` o enviar POST a la ruta de guardado, Wings redirige inmediatamente al resumen mostrando el mensaje de error: `Solo se puede rectificar una caja en estado ABIERTA.`. |
| **Paso 1: Pantalla** | En **Caja**, tocar botón **Rectificar** en la caja abierta. Muestra **Responsable**, **Apertura** y **Efectivo declarado actual**. | `resources/views/caja/rectificar.blade.php:18-20`<br>`resources/views/caja/resumen.blade.php:69` | `route('web.caja.rectificar', $caja->id)` | Acceso autorizado para el responsable del turno o admin; despliega los datos originales de la apertura para su corrección. |
| **Paso 2: Valor y motivo** | Ingresar **Nuevo efectivo recibido**, **Motivo de la rectificación** y tocar **Guardar**. Recalcula saldo esperado. | `resources/views/caja/rectificar.blade.php:29, 36, 44`<br>`app/Services/CajaService.php:152-184` | `Validator::make($datos, ['efectivo_inicial' => 'required\|numeric\|min:0', 'motivo_rectificacion' => 'required\|string\|min:3'])->validate();` | Al omitir el motivo de rectificación, el formulario no envía. Con motivo válido, actualiza el saldo inicial, recalcula el efectivo esperado del turno y emite el mensaje: `Apertura rectificada correctamente.`. |
| **Qué hago si: caja rechazada** | Con caja rechazada no se puede rectificar la apertura. Operativa puede agregar/cancelar movimientos y tocar **Cerrar** para un nuevo arqueo. | `resources/views/caja/resumen.blade.php:47, 65, 68`<br>`app/Http/Controllers/CajaWebController.php:102` | `@if($caja->estado === 'RECHAZADA' && $caja->motivo_rechazo)`<br>`@if(...) <x-ds.button variant="primary" href="{{ route('web.caja.cierre', $caja->id) }}">Cerrar</x-ds.button> @endif` | En caja rechazada el botón Rectificar no existe. La operativa ve el recuadro rojo con el **Motivo de rechazo** y cuenta con el botón **Cerrar** para realizar nuevamente el conteo y rendición del turno. |
| **Qué hago si: validada** | Caja validada no se puede rectificar; corrección monetaria posterior por ajuste en Cashflow (DU-27). | `app/Services/CashflowService.php` | Balance contable definitivo e inalterable. | Caja en estado `VALIDADA` no posee botón ni ruta de rectificación habilitada. |
| **Qué hago si: volver atrás** | Botón **Volver** regresa al resumen sin modificar el efectivo inicial declarado. | `resources/views/caja/rectificar.blade.php:43` | `<x-ds.button variant="secondary" href="{{ route('web.caja.resumen', $caja->id) }}">Volver</x-ds.button>` | Al presionar **Volver**, retorna al resumen de caja sin alterar el importe inicial ni los balances. |
