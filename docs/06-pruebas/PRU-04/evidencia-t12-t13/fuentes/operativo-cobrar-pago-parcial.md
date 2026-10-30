# Ficha de fuentes: Cobrar un pago parcial de cuota (Operativo)

- **Página del manual:** `resources/manual/operativo/cobrar-pago-parcial.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/cobrar/{alumnoId}`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Requisito: caja abierta** | Necesitás tener tu turno de caja abierto del día. Si no tenés caja abierta, Wings muestra «Debés abrir la caja y confirmar el efectivo antes de operar.». | `app/Services/CajaService.php:278`<br>`app/Http/Controllers/CajaWebController.php:33, 713, 866` | `if ($this->aperturaNecesaria())`<br>`if (!$caja \|\| $caja->estado !== CajaOperativa::ESTADO_ABIERTA)` | Acceso sin caja abierta redirige a `/caja/apertura`. Enviar el cobro vía API devuelve HTTP 422: `{"success":false,"message":"Abrí la caja y confirmá el efectivo antes de cobrar."}`. |
| **Paso 1: Buscar alumno** | Entrar a **Caja**, tocar **Cobrar**, buscar alumno y tocar **Cobrar** en su tarjeta. | `resources/views/caja/cobrar-cuota.blade.php:22, 53`<br>`resources/views/cobranza/index.blade.php:226` | `if ($request->filled('search')) { $query->buscar(...); }` | Sin búsqueda filtra por deudores; con búsqueda filtra por coincidencia de apellido, nombre o DNI. |
| **Paso 2: Modificar importe** | Marcar casilla de cuota y modificar el valor en el campo de importe (o usar campo de monto entregado). | `resources/views/caja/cobrar.blade.php:108`<br>`app/Services/PagoCuotaService.php:260` | `$saldoRestante = $cuota->saldo_pendiente_centavos - $montoCobradoCentavos;` | Si el monto ingresado excede el saldo pendiente, el validador frontend y backend lo limitan al tope de deuda exigible. |
| **Paso 3: Medio y confirmar** | Seleccionar **Medio de pago**, revisar total y tocar **Cobrar**. Abre panel exigiendo motivo obligatorio. | `resources/views/caja/cobrar.blade.php:305-323`<br>`app/Services/ExcepcionCobranzaService.php:93, 100`<br>`app/Http/Controllers/CajaWebController.php:970` | `if (isset($tipos['COBRO_PARCIAL']))`<br>`if (trim((string) $motivo) === '') throw new ExcepcionCobranzaRequerida($detalle);` | Al tocar Cobrar con importe menor a la cuota, abre el panel `Confirmar cobro` con la advertencia. Si se intenta confirmar sin motivo, el campo no avanza; con motivo, registra el cobro parcial y emite recibo. |
| **Estado de cuota parcial** | En Wings no existe el estado "parcial". La cuota continúa en estado *Pendiente* con su saldo pendiente reducido. | `app/Models/DeudaCuota.php:15-18`<br>`app/Services/PagoCuotaService.php:272` | `if ($cuota->saldo_pendiente_centavos === 0) { $cuota->estado = DeudaCuota::ESTADO_PAGADA; } else { /* conserva PENDIENTE */ }` | Verificado en base de datos: la fila conserva `estado = 'PENDIENTE'` y `saldo_pendiente` refleja la diferencia exacta que resta cobrar. |
| **Qué hago si: cancelación** | Solo se puede cancelar si la caja está abierta o rechazada. Con caja cerrada o validada no permite cancelación directa. | `app/Http/Controllers/CajaWebController.php:601`<br>`resources/views/caja/detalle.blade.php:201` | `if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA']))` | En caja `CERRADA` o `VALIDADA` no aparece el botón Cancelar; invocar la ruta por GET o POST redirige con: `Solo se puede cancelar un cobro con la caja abierta o rechazada.`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Es un cobro parcial: queda parte de la cuota pendiente.` | `app/Services/ExcepcionCobranzaService.php:93` | Sí | Paso 3: punto 5 |
| `Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.` | `app/Services/ExcepcionCobranzaService.php:100` | Sí | Paso 3: punto 5 |
| `Debés indicar un motivo.` | `app/Services/ExcepcionCobranzaService.php:123` | No | En Paso 3 punto 5 se explica que el motivo es obligatorio y que sin motivo no cobra, sin citar el texto de la excepción técnica. |
| `Debés abrir la caja y confirmar el efectivo antes de operar.` | `app/Services/CajaService.php:278` | No | Requisito previo general de caja para operar en mostrador; explicado en cobrar-cuota.md. |
| `Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.` | `app/Http/Controllers/CajaWebController.php:79` | No | Requisito previo general de caja; explicado en abrir-caja.md y cobrar-cuota.md. |
| `Solo se puede cancelar un cobro con la caja abierta o rechazada.` | `app/Http/Controllers/CajaWebController.php:601` | No | En «Qué hago si me equivoqué de importe al ingresar el pago parcial?» explica conceptualmente el flujo sin citar el mensaje de error de URL. |
| `Solo se pueden cancelar cobros de cuota.` | `app/Http/Controllers/CajaWebController.php:608` | No | Validación interna de URL que restringe la cancelación exclusivamente a cuotas con alumno. |
| `La caja no está en estado editable.` | `app/Services/CajaCorreccionService.php:24` | No | Excepción interna del servicio contable si la caja ya fue validada. |
