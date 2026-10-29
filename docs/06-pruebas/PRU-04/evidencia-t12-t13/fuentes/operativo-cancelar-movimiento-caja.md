# Ficha de fuentes: Cancelar un movimiento en caja (Operativo)

- **Página del manual:** `resources/manual/operativo/cancelar-movimiento-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/{cajaId}/detalle`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Requisito de estado** | Solo se puede cancelar mientras la caja está Abierta o Rechazada. Si está cerrada o validada no permite cancelación directa. | `app/Http/Controllers/CajaWebController.php:603`<br>`resources/views/caja/detalle.blade.php:190-205` | Comprobado: controlador rechaza con error si la caja no está `ABIERTA` o `RECHAZADA`. |
| **Paso 1: Detalle de caja** | En **Caja**, tocar botón **Detalle** en la tarjeta del turno para abrir el listado de operaciones. | `resources/views/caja/index.blade.php:75`<br>`resources/views/caja/detalle.blade.php:4` | Comprobado: acceso a `/caja/{id}/detalle` lista todas las operaciones del turno. |
| **Paso 2: Tocar Cancelar** | En la fila de la operación, tocar botón rojo **Cancelar**. Despliega panel **Cancelar cobro de cuota** («La deuda del alumno volverá a estado pendiente.»). | `resources/views/caja/detalle.blade.php:204, 305, 306` | Comprobado: clic en **Cancelar** despliega modal con la advertencia sobre la deuda. |
| **Paso 3: Motivo y confirmar** | Escribir motivo obligatorio y tocar botón **Confirmar** (o **Cerrar**). Tacha movimiento, descuenta dinero y restaura deuda. | `resources/views/caja/detalle.blade.php:315, 317`<br>`app/Http/Controllers/CajaWebController.php:615-630` | Comprobado: operación cancelada; badge en rojo «Cancelado: <motivo>» y cuota vuelve a `PENDIENTE`. |
| **Qué hago si: caja cerrada** | En estado Cerrada sin validar, la operativa pide al dueño que la rechace para poder cancelar. | `app/Http/Controllers/CajaWebController.php:603` | Comprobado: en caja `RECHAZADA` la botonera de cancelación vuelve a estar activa. |
| **Qué hago si: caja validada** | Caja validada no se reabre ni altera; ajuste compensatorio en Cashflow por el dueño (DU-27). | `app/Services/CashflowService.php`<br>`resources/manual/admin/corregir-movimiento-caja-validada.md` | Comprobado: regla inmutable de cierre de balances contables. |
