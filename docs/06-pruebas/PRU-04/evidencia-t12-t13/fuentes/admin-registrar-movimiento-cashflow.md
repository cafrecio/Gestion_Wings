# Ficha de fuentes: Registrar un movimiento directo de administración

- **Página del manual:** `resources/manual/admin/registrar-movimiento-cashflow.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/cashflow/movimiento`
- **Controlador / Servicio:** `app/Http/Controllers/CashflowWebController.php:create`, `app/Http/Controllers/CashflowWebController.php:store`
- **Vista Blade:** `resources/views/cashflow/movimiento.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/cashflow/movimiento` | Muestra formulario de movimiento directo con pestañas INGRESO y EGRESO | `registrar-movimiento-cashflow-paso1.png` |
| 2 | Seleccionar tipo EGRESO, completar campos y guardar | Valida campos, registra en `cashflow_movimientos` y actualiza saldos | `registrar-movimiento-cashflow-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Fecha de un mes anterior seleccionada | Muestra advertencia visual y cambia botón a Confirmar | `Atención: fecha de un mes anterior` | Sí | Paso 2. Completar los datos y guardar | — |
| Monto menor o igual a cero | Validación de monto numérico positivo | `El campo monto debe ser al menos 0.01.` | No | — | Validación estándar de formulario requerida |
| Medio de pago o subrubro sin seleccionar | Exige selección válida | `El campo subrubro es obligatorio.` | No | — | Validación estándar de formulario |
