# Ficha de fuentes: Registrar un movimiento directo de administración

- **Página del manual:** `resources/manual/admin/registrar-movimiento-cashflow.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/cashflow/movimiento`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Acceso exclusivo admin** | El registro de movimientos directos en Cashflow (ingresos o gastos de administración) es exclusivo del dueño. | `routes/web.php:140`<br>`app/Http/Middleware/EnsureUserIsAdmin.php:17` | `if (!auth()->user()->isAdmin()) abort(403);` | Al solicitar `/cashflow/movimiento` con un usuario operativo o profesor, Wings aborta con HTTP 403 Forbidden. |
| **Paso 1: Abrir formulario y elegir tipo** | En Cashflow, tocar **Nuevo**. Elegir pestaña **INGRESO** (entradas extraordinarias) o **EGRESO** (gastos generales). | `resources/views/cashflow/index.blade.php:147`<br>`resources/views/cashflow/movimiento.blade.php:25-35` | `if (!in_array($tipo, ['INGRESO', 'EGRESO']))` | Al enviar un tipo no reconocido, la validación de formulario rechaza con error de selección inválida. |
| **Paso 2: Completar campos y fecha retroactiva** | Seleccionar fecha, medio de pago, rubro, subrubro, monto y observación. Si la fecha es de un mes anterior, muestra advertencia y botón Confirmar. | `resources/views/cashflow/movimiento.blade.php:40, 155`<br>`app/Http/Controllers/CashflowWebController.php:240-245` | `if ($esFechaVieja && !$request->boolean('confirmar_fecha_vieja'))` | Al ingresar una fecha de un mes previo sin tildar la ratificación, la pantalla muestra el aviso «Atención: fecha de un mes anterior» y el botón cambia a **Confirmar**. Al tocar Confirmar con la casilla enviada, el movimiento se guarda con esa fecha real. |
| **Disponibilidad de fondos en egresos** | Si el egreso supera el saldo de una cuenta que no permite descubierto, Wings bloquea el registro. | `app/Http/Controllers/CashflowWebController.php:247-251` | `if ($saldoResultante < 0 && !$tipoCaja->permite_descubierto)` | Al cargar un gasto por un monto mayor al disponible en una cuenta sin sobregiro, Wings no guarda la operación y recarga el formulario con el error: `Saldo insuficiente en :cuenta. Saldo disponible: :disponible`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Atención: fecha de un mes anterior` | `resources/views/cashflow/movimiento.blade.php:40` | Sí | Paso 2: Completar los datos y guardar |
| `Saldo insuficiente en :cuenta. Saldo disponible: :disponible` | `app/Http/Controllers/CashflowWebController.php:248` | No | Validación general de saldo disponible en cuentas sin descubierto. |
| `El campo monto debe ser al menos 0.01.` | `app/Http/Controllers/CashflowWebController.php:235` | No | Validación estándar de formulario para monto mínimo. |
| `El campo subrubro es obligatorio.` | `app/Http/Controllers/CashflowWebController.php:234` | No | Validación estándar de formulario cuando no se selecciona subrubro. |
