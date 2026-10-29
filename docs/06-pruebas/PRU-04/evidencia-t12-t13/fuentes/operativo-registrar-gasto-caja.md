# Ficha de fuentes: Registrar un gasto menor o salida del cajón [DU-20] (Operativo)

- **Página del manual:** `resources/manual/operativo/registrar-gasto-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/movimiento`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Regla DU-20** | El dueño no retira efectivo directamente; la operativa registra el EGRESO en su turno abierto para que el arqueo cuadre. | Decisión de diseño DU-20<br>`app/Http/Controllers/CajaWebController.php:480` | Comprobado: todo egreso de caja física queda imputado en el turno abierto de mostrador. |
| **Paso 1: Nuevo movimiento** | En **Caja**, tocar botón **Movimiento**. Abre formulario de movimiento. | `resources/views/caja/index.blade.php:80`<br>`resources/views/caja/movimiento.blade.php:4` | Comprobado: formulario accesible desde la botonera de la caja abierta. |
| **Paso 2: EGRESO y rubro** | En **Tipo de movimiento** elegir botón rojo **EGRESO**. Elegir **Medio de pago** (**Efectivo**), **Rubro** y **Subrubro**. | `resources/views/caja/movimiento.blade.php:53, 68, 88, 109`<br>`resources/js/caja-movimiento.js` | Comprobado: al elegir EGRESO filtra rubros de egreso y cambia estilo visual a borde rojo. |
| **Paso 3: Monto y registrar** | Cargar **Monto**, **Observaciones** con detalle de la compra o retiro y tocar **Registrar**. Descuenta del saldo de caja. | `resources/views/caja/movimiento.blade.php:133, 146, 160`<br>`app/Http/Controllers/CajaWebController.php:485` | Comprobado: egreso registrado descuenta del efectivo esperado del turno. |
| **Qué hago si: tickets** | Guardar tickets dentro del cajón; respaldan el efectivo contado al momento de la rendición. | Regla operativa del club (DU-20) | Comprobado: saldo físico más comprobantes iguala saldo teórico. |
| **Qué hago si: egreso de más** | En caja abierta/rechazada, cancelar desde **Detalle** con motivo para restituir el saldo. Si está validada, ajuste en Cashflow (DU-27). | `app/Http/Controllers/CajaWebController.php:601`<br>`resources/views/caja/detalle.blade.php:204` | Comprobado: cancelación de egreso suma nuevamente el importe a la caja. |
