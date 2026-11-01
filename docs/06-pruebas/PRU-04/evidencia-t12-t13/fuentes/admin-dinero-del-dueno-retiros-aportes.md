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
| **Canal 1: Movimiento directo en Cashflow** | El dueño asienta sus retiros (**EGRESO**) o aportes (**INGRESO**) directamente en Cashflow, seleccionando la cuenta bancaria o Efectivo. | `resources/views/cashflow/movimiento.blade.php:25-155`<br>`app/Http/Controllers/CashflowWebController.php` | Sin control de saldo: «Sin descubierto» solo se controla al pagar una liquidación (`LiquidacionWebController.php:441`). | Ejecutado 01/11: con Efectivo en $0 y sin descubierto, el retiro entra y la cuenta queda en −$2.000 (`RetiroDuenoCajonRealidadTest`). |
| **Canal 2: Plata del cajón durante el turno** | Un retiro no se puede cargar en el turno: va por Cashflow y el turno cierra con faltante. Un aporte entra con Agregar solo si su subrubro está en Ambos. | `app/Services/CajaService.php:673`<br>`app/Http/Controllers/CajaWebController.php:490` | `if ($subrubro->permitido_para !== 'OPERATIVO')` | Ejecutado 01/11, hallazgo de Gemini: el dueño recibe «El subrubro seleccionado no está permitido para usuarios operativos.» y no se registra nada (`RetiroDuenoCajonRealidadTest`). |
| **Qué hago si: gasto supera efectivo en cajón** | Un gasto en el turno no puede superar el efectivo disponible en el cajón. | `app/Services/CajaService.php:657` | `throw new \DomainException('El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.')` | Se rechaza el gasto. Rige para gastos en Ambos: un retiro no llega a este control. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Tus movimientos se cargan en Cashflow; la caja es de la operativa. No se registró nada.` | `app/Http/Controllers/CajaWebController.php:1110` | Sí | Qué hago si: ¿Qué pasa si intento cargar un movimiento en la caja del turno con mi usuario de administrador? |
| `El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.` | `app/Services/CajaService.php:657` | Sí | Qué hago si: ¿Qué pasa si el retiro de efectivo supera lo disponible en el cajón? |
| `Saldo insuficiente en :cuenta. Saldo disponible: :disponible` | `app/Http/Controllers/CashflowWebController.php:248` | No | Validación general de disponibilidad de fondos en cuentas sin sobregiro. |
| `Atención: fecha de un mes anterior` | `resources/views/cashflow/movimiento.blade.php:192` | No | Advertencia de fecha retroactiva aplicable a movimientos directos de administración. |

---

## Actualización por T94 a T109 (Claude CAB, 01/11/2026)

| Qué afirma el manual | Archivo y línea | Tarea |
|---|---|---|
| El dueño tiene Agregar en la tarjeta del turno | `resources/views/caja/index.blade.php:161` | T101 |
| Agregar lo deja a nombre de quien lo carga | `app/Http/Controllers/CajaWebController.php:496` | T101 |
| La operativa no ve los subrubros del dueño | `app/Http/Controllers/CajaWebController.php:227` | T101 |
| Retiro de dueños es solo del dueño, y por eso Agregar lo rechaza | `database/seeders/CatalogosSeeder.php:133`, `app/Services/CajaService.php:673` | T101 y control de Gemini |
| Agregar ofrece todos los subrubros al dueño | `app/Http/Controllers/CajaWebController.php:1149` | T116 |
| El retiro por Cashflow baja la cuenta una sola vez; el faltante no vuelve a descontar | `app/Services/CajaService.php:431`, ejecutado en `RetiroDuenoCajonRealidadTest` | Control de Gemini |
| El formulario general rechaza al dueño | `app/Http/Controllers/CajaWebController.php:1110` | T59 |
