# Ficha de fuentes: Cobrar un pago parcial de cuota (Operativo)

- **Página del manual:** `resources/manual/operativo/cobrar-pago-parcial.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/cobrar/{alumnoId}`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Antes de empezar** | Necesitás tener tu turno de caja abierto del día. Sin caja abierta, Wings muestra «Debés abrir la caja y confirmar el efectivo antes de operar.». | `app/Services/CajaService.php:278`<br>`resources/views/caja/apertura.blade.php:53` | Comprobado: al ingresar a `/caja/cobrar/{id}` sin caja abierta, el sistema bloquea con la alerta literal. |
| **Paso 1: Buscar alumno** | Desde **Caja** tocar **Cobrar** (o desde **Cobranza**). Buscar por nombre o DNI y tocar **Cobrar** en la tarjeta. | `resources/views/caja/cobrar-cuota.blade.php:54-78`<br>`resources/views/cobranza/index.blade.php:224-226` | Comprobado: búsqueda local por DNI localiza al alumno y abre su ficha de cobro `/caja/cobrar/{id}`. |
| **Paso 2: Modificar importe** | Marcar cuota adeudada e ingresar importe menor en casilla de la cuota o en **Importe que entrega (dejar vacío para cobrar el total)**. | `resources/views/caja/cobrar.blade.php:151-160, 280-284`<br>`resources/js/cobrar.js:93-109` | Comprobado: campo editable admite monto parcial (ej: $10.000 sobre $25.000) y calcula total a cobrar. |
| **Paso 3: Medio y confirmar** | Seleccionar **Medio de pago** (**Efectivo**), **Observaciones** opcional, verificar **Total a cobrar:** y tocar botón **Cobrar**. Emite recibo oficial. | `resources/views/caja/cobrar.blade.php:253, 288, 305`<br>`app/Http/Controllers/CajaWebController.php:1054` | Comprobado: cobro asentado por $10.000; se genera recibo y la cuota queda con saldo pendiente de $15.000. |
| **Qué hago si: estado cuota** | En Wings no existe estado "parcial". La cuota continúa en estado `PENDIENTE` en el modelo y muestra el saldo restante. | `app/Models/DeudaCuota.php:15-18`<br>`app/Services/PagoCuotaService.php:215` | Comprobado: `estado = PENDIENTE`, `saldo_pendiente = 15000`. |
| **Qué hago si: saldo restante** | Al volver a cobrar se tilda la misma cuota con el saldo restante como sugerido; al completarse pasa a `PAGADA`. | `app/Services/PagoCuotaService.php:230-245` | Comprobado: cobro de los $15.000 restantes cancela la deuda y cambia el estado a `PAGADA`. |
| **Qué hago si: cobro erróneo** | En caja abierta/rechazada, cancelar desde **Detalle** de la caja con motivo obligatorio. Si está validada, ajuste en Cashflow (DU-27). | `app/Http/Controllers/CajaWebController.php:601-630`<br>`resources/views/caja/detalle.blade.php:180` | Comprobado: botón **Cancelar** revierte la entrega y restaura el saldo previo de la cuota. |
