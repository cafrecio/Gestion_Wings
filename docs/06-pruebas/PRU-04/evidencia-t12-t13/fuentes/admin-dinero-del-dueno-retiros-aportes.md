# Ficha de fuentes: Retiros y aportes personales del dueño

- **Página del manual:** `resources/manual/admin/dinero-del-dueno-retiros-aportes.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/cashflow/movimiento` y `/caja/movimiento`
- **Controlador / Servicio:** `app/Http/Controllers/CashflowWebController.php:store`, `app/Http/Controllers/CajaWebController.php:movimientoStore`, `app/Support/ClasificacionSubrubros.php`
- **Vista Blade:** `resources/views/cashflow/movimiento.blade.php` y `resources/views/caja/movimiento.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/cashflow/movimiento` como Admin | Formulario de movimiento directo con subrubros de RETIRO y APORTE | `dinero-del-dueno-retiros-aportes-paso1.png` |
| 2 | Entrar a `/caja/movimiento` como Operativo | Formulario de caja para asentar gasto o ingreso con subrubro de dueño | `dinero-del-dueno-retiros-aportes-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Admin intenta registrar en la caja del turno | Redirige a Cashflow bloqueando la carga | `Tus movimientos se cargan en Cashflow; la caja es de la operativa. No se registró nada.` | Sí | Qué hago si: ¿Qué pasa si intento cargar un movimiento en la caja del turno con mi usuario de administrador? | — |
| Retiro en efectivo supera saldo del cajón | `CajaService::registrarGasto` bloquea | `El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.` | Sí | Qué hago si: ¿Qué pasa si el retiro de efectivo supera lo disponible en el cajón? | — |
| Subrubro sin clasificación contable | Permite guardar pero clasifica según catálogo | `ClasificacionSubrubros::clasificar` | No | — | Lógica contable interna de imputación |
