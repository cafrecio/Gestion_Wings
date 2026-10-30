# Ficha de fuentes: Consultar el libro Cashflow y saldos por medio

- **Página del manual:** `resources/manual/admin/consultar-cashflow.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/cashflow`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Acceso exclusivo admin** | El libro Cashflow consolida todas las entradas y salidas de las cuentas y cajas del club; es exclusivo del dueño. | `routes/web.php:137`<br>`app/Http/Middleware/EnsureUserIsAdmin.php:17` | `if (!$request->user() \|\| !$request->user()->isAdmin()) abort(403);` | Al intentar ingresar a `/cashflow` con usuario del rol operativo o profesor, Wings aborta de inmediato con HTTP 403 Forbidden. |
| **Paso 1: Filtro por Mes o Año** | Permite alternar la vista entre el resultado mensual y el consolidado anual con tarjetas de saldos por medio. | `app/Http/Controllers/CashflowWebController.php:26-55`<br>`resources/views/cashflow/index.blade.php:30-80` | `if (in_array($modo, ['mes', 'anio'], true))` | Si se pasan parámetros manipulados (por ejemplo `periodo=trimestre` o `mes=15`), la validación del controlador rechaza la petición con error 422 Unprocessable Entity. |
| **Paso 2: Detalle de movimientos y orígenes** | Lista ingresos y egresos cronológicos con fecha, medio, rubro, observación y monto. Muestra badges de origen (Caja, Liquidación, Manual). | `resources/views/cashflow/index.blade.php:120-195`<br>`app/Models/CashflowMovimiento.php:45` | `@if($movimiento->origen === 'CAJA')`<br>`<span ...>Caja</span>`<br>`@elseif($movimiento->origen === 'LIQUIDACION')` | Si un movimiento proviene de una caja validada o de una liquidación, no se muestra el botón de eliminación directa en la fila para proteger la consistencia de los módulos de origen. |
| **Qué hago si: diferencias de saldo** | Si el saldo en pantalla no coincide con el dinero físico, se auditan las cajas cerradas del período o se registra un ajuste manual. | `resources/views/cashflow/index.blade.php:65-75`<br>`app/Services/CashflowService.php:80` | `if ($cuenta->saldo_actual !== $cuenta->saldo_calculado)` | Al no existir movimientos en un período filtrado, la tabla muestra el estado vacío: «Sin movimientos para los filtros seleccionados.». |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Sin movimientos para los filtros seleccionados.` | `resources/views/cashflow/index.blade.php:185` | No | Estado vacío natural de la tabla cuando no hubo operaciones en el mes o año consultado. |
| `El campo mes debe estar entre 1 y 12.` | `app/Http/Controllers/CashflowWebController.php:30` | No | Validación estándar de Laravel ante alteración manual de la URL. |
| `El campo anio debe estar entre 1900 y 2100.` | `app/Http/Controllers/CashflowWebController.php:29` | No | Validación estándar de Laravel ante manipulación de query params. |
