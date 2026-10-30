# Ficha de fuentes: Transferir entre medios propios

- **Página del manual:** `resources/manual/admin/transferir-entre-cuentas.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/cashflow/transferencia`
- **Controlador / Servicio:** `app/Http/Controllers/TransferenciaCashflowWebController.php:create`, `app/Http/Controllers/TransferenciaCashflowWebController.php:store`, `app/Services/TransferenciaCashflowService.php`
- **Vista Blade:** `resources/views/cashflow/transferencia.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/cashflow/transferencia` | Muestra formulario de transferencia con selector de salida y destino | `transferir-entre-cuentas-paso1.png` |
| 2 | Completar origen, destino, monto y tocar `Transferir` | Registra salida y entrada enlazadas en `cashflow_transferencias` y `cashflow_movimientos` | `transferir-entre-cuentas-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Cuenta de origen igual a cuenta de destino | Valida `destino_id.different` | `Elegí una cuenta de destino distinta de la salida.` | Sí | Qué hago si: ¿Qué hago si elijo la misma cuenta en salida y destino? | — |
| Fecha de un mes anterior seleccionada | Exige confirmación explícita | `La transferencia quedará con esa fecha y cambiará los saldos de ambas cuentas desde ese mes. El resultado del club no cambia. Confirmá para guardar la salida y la entrada.` | Sí | Qué hago si: ¿Qué hago si la fecha es de un mes anterior? | — |
| Transferencia guardada con éxito | Redirige a Cashflow con mensaje de éxito | `Transferencia #... registrada: salida y entrada guardadas.` | Sí | Paso 2: Indicar salida, destino e importe | — |
| Monto menor o igual a cero | Validación de monto | `El campo monto debe ser al menos 0.01.` | No | — | Validación estándar de formulario requerida |
