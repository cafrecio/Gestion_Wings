# Ficha de fuentes: Registrar un ingreso directo en el turno de caja (Operativo)

- **Página del manual:** `resources/manual/operativo/registrar-ingreso-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/movimiento`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Requisito** | Caja en estado Abierta. Para cuotas de alumnos usar el módulo Cobrar. | `app/Services/CajaService.php:278`<br>`resources/views/caja/movimiento.blade.php` | Comprobado: formulario suma saldo a caja pero no imputa cuotas de alumnos. |
| **Paso 1: Abrir movimiento** | En **Caja**, tocar botón **Movimiento**. Abre formulario **Nuevo movimiento**. | `resources/views/caja/index.blade.php:80`<br>`resources/views/caja/movimiento.blade.php:4` | Comprobado: acceso a `/caja/movimiento` carga formulario con botones INGRESO y EGRESO. |
| **Paso 2: INGRESO y rubro** | En **Tipo de movimiento** elegir **INGRESO**. Seleccionar **Medio de pago** (**Efectivo**), **Rubro** y **Subrubro**. | `resources/views/caja/movimiento.blade.php:45, 68, 88, 109`<br>`resources/js/caja-movimiento.js` | Comprobado: al elegir INGRESO filtra rubros de tipo INGRESO dinámicamente. |
| **Paso 3: Monto y registrar** | Ingresar **Monto**, **Observaciones** opcional y tocar botón **Registrar**. Suma dinero al turno. | `resources/views/caja/movimiento.blade.php:133, 146, 160`<br>`app/Http/Controllers/CajaWebController.php:470` | Comprobado: guardado exitoso crea movimiento de ingreso e incrementa el total de caja. |
| **Qué hago si: cuota por acá** | No desvincula deudas; cancelar desde **Detalle** de caja con motivo y cobrar por **Cobrar**. | `app/Http/Controllers/CajaWebController.php:601`<br>`resources/views/caja/detalle.blade.php:204` | Comprobado: botón **Cancelar** anula el movimiento erróneo. |
| **Qué hago si: error rubro/monto** | Si la caja sigue abierta, cancelar con motivo en Detalle. Si ya fue validada, ajuste en Cashflow (DU-27). | `app/Http/Controllers/CajaWebController.php:601`<br>`app/Services/CashflowService.php` | Comprobado: trazabilidad contable estricta según estado de la caja. |
