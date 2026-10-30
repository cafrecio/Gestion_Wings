# Ficha de fuentes: Corregir movimiento en caja validada (Admin)

- **Página del manual:** `resources/manual/admin/corregir-movimiento-caja-validada.md`
- **Rol:** Admin
- **Pantalla:** `/cashflow`
- **Fecha de verificación:** 28/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y casos de salida

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Fundamento contable** | Una caja validada representa el dinero físico real entregado en mostrador. No se borra ni edita; se asienta un *ajuste directo en Cashflow* con motivo (DU-27). | `app/Http/Controllers/CajaWebController.php:601`<br>`app/Services/CashflowService.php:120`<br>`docs/02-contratos/DU-27` | Comprobado en código: `CajaWebController::cancelarMovimiento` exige `$caja->estado` en `['ABIERTA', 'RECHAZADA']`. En `VALIDADA`, el controlador aborta cualquier intento de modificación. |
| **Paso 1: Entrar a Cashflow** | En menú lateral, entrar a **Cashflow** y revisar saldo de medio de pago (**Efectivo**, **Banco**) y período del error. | `resources/views/layouts/ds-app.blade.php:147`<br>`resources/views/cashflow/index.blade.php:20, 85` | Ejecutado con usuario Admin (Carlos Freccero): accede a `/cashflow`, visualiza la tarjeta de saldos por tipo de caja y la tabla de movimientos. |
| **Paso 2: Abrir formulario** | En barra superior de Cashflow, tocar botón **Nuevo**. Se abre pantalla **Nuevo movimiento**. | `resources/views/cashflow/index.blade.php:147`<br>`resources/views/cashflow/movimiento.blade.php:14` | Ejecutado: el botón se llama exactamente **Nuevo**, tiene estilo primario y navega a `/cashflow/movimiento`. |
| **Paso 3: Cargar ajuste** | Completar **Tipo de movimiento** (**EGRESO** o **INGRESO**), **Fecha**, **Medio de pago**, **Rubro**, **Subrubro**, **Monto** y **Observaciones**. | `resources/views/cashflow/movimiento.blade.php:38, 47, 62, 75, 95, 110, 126, 140` | Ejecutado: se cargó un egreso compensatorio de $15.000 en Efectivo bajo el rubro de ajustes, con motivo explicativo detallado. Todos los selectores y botones coinciden textualmente. |
| **Paso 4: Guardar y verificar** | Tocar botón **Registrar**, Wings asienta el movimiento en **Cashflow** y actualiza saldos del club. | `resources/views/cashflow/movimiento.blade.php:155`<br>`app/Http/Controllers/CashflowWebController.php:262` | Ejecutado: al tocar **Registrar**, `CashflowWebController::store` guarda el egreso de $15.000 con `monto = -15000` y el balance de Efectivo en Cashflow disminuye en $15.000 de forma exacta. |
| **Qué hago si: caja no validada** | Si la caja está cerrada pero sin validar, en **Caja** tocar **Rechazar** con motivo. La operativa toca **Cancelar** en su caja rechazada y vuelve a cerrar. | `app/Http/Controllers/CajaWebController.php:480, 601`<br>`resources/views/caja/detalle.blade.php:130` | Ejecutado: el botón **Rechazar** en `/cajas/{id}` pasa el estado a `RECHAZADA`. La operativa ingresa a su caja rechazada, toca **Cancelar** en el cobro y la cuota vuelve a quedar impaga. |
| **Qué hago si: afectó deuda alumno (Hueco)** | Hoy Wings no permite devolver la deuda al alumno tras validar la caja: cobro por caja no se anula desde ficha (`PagoCuotaService:1266`), caja validada no permite cancelar (`CajaWebController:601`) y reactivar solo acepta cuotas condonadas (`PagoCuotaService:380`). El ajuste en Cashflow compensa el club, pero la cuota sigue saldada. | `app/Services/PagoCuotaService.php:380, 1266`<br>`app/Http/Controllers/CajaWebController.php:601` | Comprobado exhaustivamente en el código. `PagoCuotaService::anularCobro` lanza «Este cobro se hizo por caja: se anula desde el detalle de esa caja». `PagoCuotaService::reactivarCuota` lanza «Solo se pueden reactivar cuotas CONDONADAS». Se documenta como limitación del sistema para decisión de Carlos. |
---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Esta fecha corresponde a un mes anterior ya cerrado.` | `resources/views/cashflow/movimiento.blade.php:40`<br>`app/Http/Controllers/CashflowWebController.php:240` | No | En Paso 3 punto 2 se describe la advertencia de fecha de mes anterior y el cambio a botón Confirmar sin citar el texto literal exacto. |
| `Este cobro se hizo por caja: se anula desde el detalle de esa caja.` | `app/Services/PagoCuotaService.php:1266` | No | Excepción técnica del servicio al intentar anular desde la ficha de alumno un cobro originado en caja; explicada en la limitación contable. |
| `Solo se pueden reactivar cuotas CONDONADAS.` | `app/Services/PagoCuotaService.php:380` | No | Excepción técnica al intentar reactivar una cuota con saldo saldado. |
| `The monto field must be at least 0.01.` | `app/Http/Controllers/CashflowWebController.php:235` | No | Validación estándar de formulario para monto numérico positivo. |
| `Movimiento registrado en Cashflow.` | `app/Http/Controllers/CashflowWebController.php:264` | No | Mensaje flash del backend tras redirección; en Paso 4 se describe la actualización de saldos. |
