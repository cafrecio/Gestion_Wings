# Ficha de fuentes: Retiros y aportes personales del dueño

- **Página del manual:** `resources/manual/admin/dinero-del-dueno-retiros-aportes.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/cashflow/movimiento` y `/caja/movimiento`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Clasificación contable especial** | En Wings los retiros y aportes personales del dueño se clasifican como «Retiro de los dueños» y «Aporte de los dueños» para no distorsionar gastos ni ingresos deportivos. | `app/Support/ClasificacionSubrubros.php:23-24`<br>`app/Models/Subrubro.php:35` | `self::APORTE => 'Aporte de los dueños'`<br>`self::RETIRO => 'Retiro de los dueños'` | Si se selecciona un subrubro común no clasificado como retiro/aporte de dueños, Wings lo computa en el resultado operativo del club alterando los márgenes de la actividad. |
| **Canal 1: Movimiento directo en Cashflow** | El dueño asienta sus retiros (**EGRESO**) o aportes (**INGRESO**) directamente en Cashflow, seleccionando la cuenta bancaria o Efectivo. | `resources/views/cashflow/movimiento.blade.php:25-155`<br>`app/Http/Controllers/CashflowWebController.php:230-264` | `if ($saldoResultante < 0 && !$tipoCaja->permite_descubierto)` | Si el dueño intenta registrar un retiro en una cuenta bancaria sin descubierto cuyo saldo disponible sea inferior al monto solicitado, el sistema rechaza la operación informando `Saldo insuficiente en...`. |
| **Canal 2: Efectivo físico en la caja de la operativa** | Si se retira o aporta efectivo físico del cajón durante el turno, lo asienta la operativa en su caja. El dueño no puede operar en la caja del turno. | `app/Http/Controllers/CajaWebController.php:321, 350`<br>`resources/views/caja/movimiento.blade.php:20` | `if (auth()->user()->isAdmin()) { return redirect()->route('web.cashflow.movimiento')->with('error', 'Tus movimientos se cargan en Cashflow; la caja es de la operativa. No se registró nada.'); }` | Al intentar ingresar a `/caja/movimiento` o enviar POST con el usuario administrador, Wings bloquea la acción y redirige a Cashflow con el mensaje textual: «Tus movimientos se cargan en Cashflow; la caja es de la operativa. No se registró nada.». |
| **Qué hago si: retiro supera efectivo en cajón** | La operativa no puede registrar un retiro que supere el efectivo físico real disponible en el cajón de su turno. | `app/Services/CajaService.php:614-615` | `if ($montoCentavos > $disponibleCentavos) { throw new \DomainException('El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.'); }` | Al cargar un gasto de retiro por un importe mayor al saldo de efectivo acumulado en la caja abierta, Wings interrumpe la transacción y muestra el mensaje: «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.». |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Tus movimientos se cargan en Cashflow; la caja es de la operativa. No se registró nada.` | `app/Http/Controllers/CajaWebController.php:321` | Sí | Qué hago si: ¿Qué pasa si intento cargar un movimiento en la caja del turno con mi usuario de administrador? |
| `El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.` | `app/Services/CajaService.php:614` | Sí | Qué hago si: ¿Qué pasa si el retiro de efectivo supera lo disponible en el cajón? |
| `Saldo insuficiente en :cuenta. Saldo disponible: :disponible` | `app/Http/Controllers/CashflowWebController.php:248` | No | Validación general de disponibilidad de fondos en cuentas sin sobregiro. |
| `Atención: fecha de un mes anterior` | `resources/views/cashflow/movimiento.blade.php:40` | No | Advertencia de fecha retroactiva aplicable a movimientos directos de administración. |
