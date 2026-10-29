# Ficha de fuentes: Registrar un ingreso directo en caja (Operativo)

- **Página del manual:** `resources/manual/operativo/registrar-ingreso-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/movimiento`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Requisito: caja abierta** | El turno de caja debe estar en estado *Abierta*. No usar para cuotas de alumnos (se cobran por **Cobrar**). | `app/Services/CajaService.php:278`<br>`app/Http/Controllers/CajaWebController.php:33` | `if ($this->aperturaNecesaria())` | Si no hay turno abierto, redirige a `/caja/apertura` exigiendo confirmar la apertura antes de ingresar movimientos. |
| **Paso 1: Botón Nuevo** | En **Caja**, tocar botón **Nuevo** en la barra de acciones del turno abierto. | `resources/views/caja/resumen.blade.php:63`<br>`resources/views/caja/movimiento.blade.php:2` | `@if(in_array($caja->estado, ['ABIERTA', 'RECHAZADA']) && $esPropietario)` | Botón **Nuevo** aparece solo si la caja está `ABIERTA` o `RECHAZADA`. |
| **Paso 2: Tipo INGRESO y rubro** | Seleccionar botón verde **INGRESO**, **Medio de pago** (Efectivo), **Rubro** y **Subrubro**. | `resources/views/caja/movimiento.blade.php:25-50` | `'tipo' => 'required\|in:INGRESO,EGRESO'`<br>`'subrubro_id' => 'required\|exists:subrubros,id'` | Al omitir el rubro o subrubro, el formulario no envía y marca el campo obligatorio. |
| **Paso 3: Monto y registrar** | Ingresar **Monto**, **Observaciones** y tocar botón **Registrar**. Suma dinero al turno. | `resources/views/caja/movimiento.blade.php:60-75`<br>`app/Http/Controllers/CajaWebController.php:337` | `'monto' => 'required\|numeric\|min:0.01'` | Asienta el movimiento como `INGRESO`, actualiza el saldo de la caja y emite comprobante en el detalle. |
| **Corrección: Editar / Eliminar** | En movimientos manuales (sin alumno), el detalle ofrece los botones **Editar** y **Eliminar** (no Cancelar). | `resources/views/caja/detalle.blade.php:207`<br>`app/Models/MovimientoOperativo.php:75`<br>`app/Services/CajaCorreccionService.php:19-24`<br>`app/Http/Controllers/CajaWebController.php:608` | `@elseif($mov->esManualEditable())`<br>`if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'], true))` | Si se intenta usar la ruta de cancelación de cobros (`web.caja.movimientos.cancelar`) sobre un ingreso manual sin alumno, Wings responde `HTTP 403: Solo se pueden cancelar cobros de cuota.`. Si se intenta eliminar con la caja cerrada o validada, rechaza con: `La caja no está en estado editable.`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Comentario / Motivo |
|---|---|---|---|
| `Debés abrir la caja y confirmar el efectivo antes de operar.` | `app/Services/CajaService.php:278` | Sí | Explica en el requisito inicial que sin caja abierta no se puede ingresar dinero. |
| `La caja no está en estado editable.` | `app/Services/CajaCorreccionService.php:24` | Sí | Explica que si la caja está Cerrada o Validada no se puede editar ni eliminar el movimiento. |
| `Solo se pueden cancelar cobros de cuota.` | `app/Http/Controllers/CajaWebController.php:608` | Sí | Explica que los ingresos manuales se corrigen con Editar o Eliminar, no con Cancelar. |
| `The monto field must be at least 0.01.` | `app/Http/Controllers/CajaWebController.php:337` | Sí | Explica que debe ingresarse el importe en pesos a registrar. |
| `Movimiento registrado.` | `app/Http/Controllers/CajaWebController.php:502` | Sí | Mensaje de éxito al asentar el ingreso. |
