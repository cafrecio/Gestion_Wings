# Ficha de fuentes: Consultar el libro Cashflow y saldos por medio

- **Página del manual:** `resources/manual/admin/consultar-cashflow.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/cashflow`
- **Controlador / Servicio:** `app/Http/Controllers/CashflowWebController.php:index`, `app/Services/CashflowService.php`
- **Vista Blade:** `resources/views/cashflow/index.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/cashflow?periodo=mes&mes=10&anio=2026` | Muestra saldos por tipo de caja y resultado mensual | `consultar-cashflow-paso1.png` |
| 2 | Cambiar período a Año (`?periodo=anio&anio=2026`) | Muestra consolidado anual de ingresos, egresos y resultado | `consultar-cashflow-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Período inválido | Validación de query params | 422 Unprocessable Entity | No | — | Validación interna de ruta ante parámetros manipulados |
| Fecha con formato inválido | Rechaza parámetro fecha | `The fecha field format is invalid.` | No | — | Validación estándar de Laravel |
| Sin movimientos en el período | Muestra tabla vacía | `Sin movimientos para los filtros seleccionados.` | No | — | Estado vacío natural de la tabla |
