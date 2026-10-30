# Ficha de fuentes: Cerrar el turno de caja y rendir el arqueo (Operativo)

- **Página del manual:** `resources/manual/operativo/cerrar-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/{cajaId}/cerrar`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Requisito: caja abierta o rechazada** | Solo se puede cerrar una caja que se encuentre en estado Abierta o Rechazada. | `app/Http/Controllers/CajaWebController.php:87-89`<br>`app/Services/CajaService.php:364-367` | `if ($caja->estado === CajaOperativa::ESTADO_VALIDADA) { return redirect()->route('web.caja.resumen', $id)->with('error', 'La caja ya está validada.'); }`<br>`if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA']) && !($caja->estado === 'CERRADA' && $caja->efectivo_contado === null)) { throw new \Exception('La caja no está en estado editable.'); }` | En una caja en estado `CERRADA` (con arqueo ya realizado) o `VALIDADA`, el botón **Cerrar** no aparece en el resumen. Si se invoca la ruta por GET o POST en una validada, redirige con: `La caja ya está validada.`. En servicio contable lanza: `La caja no está en estado editable.`. |
| **Paso 1: Pantalla Cierre** | En **Caja**, tocar botón **Cerrar**. Muestra **Efectivo inicial** y **Efectivo esperado**. | `resources/views/caja/resumen.blade.php:65`<br>`resources/views/caja/cierre.blade.php:2` | `@if(($esPropietario \|\| Auth::user()->isAdmin()) && ...)` | Despliega los valores teóricos acumulados durante el turno de mostrador. |
| **Paso 2: Efectivo contado y cambio retenido** | Ingresar **Efectivo contado** y **Cambio que queda**. Calcula en tiempo real diferencia y retiro. | `resources/views/caja/cierre.blade.php:35, 45`<br>`app/Services/CajaService.php:358-359` | `'efectivo_contado' => 'required\|numeric\|min:0\|decimal:0,2'`<br>`'cambio_retenido' => 'required\|numeric\|min:0\|lte:efectivo_contado\|decimal:0,2'` | Si se ingresa un importe de cambio retenido superior al efectivo contado, Wings rechaza la validación indicando que el cambio debe ser menor o igual al efectivo contado. |
| **Paso 3: Confirmar cierre** | Tocar botón **Cerrar**. Pasa el turno al estado *Cerrada* y bloquea nuevas operaciones. | `resources/views/caja/cierre.blade.php:60`<br>`app/Services/CajaService.php:380` | `if ($caja->estado !== CajaOperativa::ESTADO_ABIERTA && $caja->estado !== CajaOperativa::ESTADO_RECHAZADA)` | Pasa la caja al estado `CERRADA`, guarda los montos de arqueo y emite confirmación: `Caja cerrada.`. |
| **Qué hago si: discrepancia / diferencia** | Se permite cerrar con diferencia (faltante o sobrante). El dueño audita al validar. | `app/Services/CajaService.php:390` | `$caja->diferencia = $contado - $esperado;` | No bloquea el cierre: registra la diferencia contable para que el dueño la evalúe al auditar la rendición. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `La caja no está en estado editable.` | `app/Services/CajaService.php:366` | No | Excepción interna del servicio contable si la caja no está en estado editable. |
| `La caja ya está validada.` | `app/Http/Controllers/CajaWebController.php:88` | No | Redirección con error si se intenta acceder por URL directa a una caja ya validada por el dueño. |
| `La corrección no puede cambiar el efectivo que ya se contó y entregó.` | `app/Services/CajaService.php:376` | No | Regla contable interna al corregir caja rechazada; en pantalla se refleja al no alterar el retiro entregado. |
| `The cambio retenido field must be less than or equal to :efectivo_contado.` | `app/Services/CajaService.php:359` | No | Validación estándar del formulario; en Paso 2 se explica conceptualmente que el retiro es contado menos retenido. |
| `Caja cerrada.` | `app/Http/Controllers/CajaWebController.php:190` | No | Mensaje flash del backend tras redirección; en Paso 3 se explica que la caja pasa a estado Cerrada. |
