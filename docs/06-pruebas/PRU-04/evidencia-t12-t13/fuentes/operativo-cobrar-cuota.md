# Ficha de fuentes: Cobrar una cuota (Operativo)

- **Página del manual:** `resources/manual/operativo/cobrar-cuota.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/cobrar/{alumnoId}`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Requisito: caja abierta** | Para cobrar necesitás tener turno de caja abierto del día. Si no tenés caja abierta, Wings muestra «Debés abrir la caja y confirmar el efectivo antes de operar.». | `app/Services/CajaService.php:278`<br>`app/Http/Controllers/CajaWebController.php:33, 713, 866` | `if ($this->aperturaNecesaria())`<br>`if (!$caja \|\| $caja->estado !== CajaOperativa::ESTADO_ABIERTA)` | Al ingresar a `/caja/cobrar` sin caja abierta, redirige a `/caja/apertura`. Si se intenta enviar el cobro por POST, responde HTTP 422: `{"success":false,"message":"Abrí la caja y confirmá el efectivo antes de cobrar."}`. |
| **Caja de día anterior** | Wings no permite operar si quedó un turno pendiente de un día previo («Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.»). | `app/Services/CajaService.php:245, 270`<br>`app/Http/Controllers/CajaWebController.php:79` | `if ($cajaAbierta && !$cajaAbierta->apertura_at->isToday())` | Lanza excepción con mensaje textual: `Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.`. |
| **Paso 1: Buscar alumno** | Entrar a **Caja**, tocar **Cobrar**. Escribir nombre/DNI y tocar **Buscar**. En la tarjeta tocar **Cobrar**. Desde Cobranza va directo al cobro del alumno. | `resources/views/caja/cobrar-cuota.blade.php:22, 53`<br>`resources/views/cobranza/index.blade.php:226` | `if ($request->filled('search')) { $query->buscar(...); }` | Al no ingresar búsqueda en `/caja/cobrar`, lista únicamente a los alumnos con deuda pendiente. Con búsqueda filtra por nombre o DNI. |
| **Paso 2: Elegir cuotas** | Se visualizan datos y **Total pendiente**. Casillas de períodos, matrícula o recargos. Permite cuota parcial. | `resources/views/caja/cobrar.blade.php:48, 86`<br>`app/Services/PagoCuotaService.php:272` | `if ($cuota->saldo_pendiente_centavos === 0) { $cuota->estado = DeudaCuota::ESTADO_PAGADA; }` | Si el pago no cubre el total, la cuota permanece en `PENDIENTE` con el saldo restante reducido. |
| **Paso 3: Medio de pago** | Seleccionar **Medio de pago** entre los habilitados. **Fecha del pago** y **Observaciones**. | `resources/views/caja/cobrar.blade.php:154, 185`<br>`app/Http/Controllers/CajaWebController.php:874` | `'tipo_caja_id' => 'required\|exists:tipos_caja,id'` | Al omitir el medio de pago, la validación rechaza con error de campo requerido. |
| **Paso 4: Confirmar y recibo** | Tocar **Cobrar**. Emite confirmación y botón **Recibo** (`/recibos/cuota/{pagoId}`). | `resources/views/caja/cobrar.blade.php:235`<br>`app/Http/Controllers/CajaWebController.php:970` | `if ($pago->id) { return response()->json(['success' => true, 'recibo_url' => ...]); }` | Al confirmar cobro válido, registra pago, descuenta deuda y entrega URL directa del recibo oficial. |
| **Qué hago si: deuda vieja salteada** | Si se cobra dejando meses viejos impagos, pide motivo con: «¿Cobrás igual solo los meses elegidos? Indicá el motivo...». | `app/Services/ExcepcionCobranzaService.php:96-100`<br>`resources/views/caja/cobrar.blade.php:305-323` | `if ($anteriores) $tipos['COBRO_DEUDA_ANTERIOR'] = true;`<br>`if (trim((string) $motivo) === '') throw new ExcepcionCobranzaRequerida($detalle);` | Abre panel modal de excepción. Si no se indica motivo, la confirmación no avanza; con motivo, registra el cobro. |
| **Qué hago si: cobro adelantado** | Cobrar un mes que no empezó pide confirmación: «Estás cobrando por adelantado... El importe queda fijo aunque después cambie la cuota.». | `app/Http/Controllers/CajaWebController.php:923-930`<br>`resources/views/caja/cobrar.blade.php:305-323` | `if (!$request->boolean('confirmar_pago_adelantado'))` | Abre panel de confirmación advirtiendo que el valor queda congelado y que no habilita asistencia previa al 1° del mes. |
| **Qué hago si: fecha mes cerrado** | Fecha en mes anterior cerrado pide confirmación: «Esta fecha de pago corresponde a un mes anterior ya cerrado...». | `app/Http/Controllers/CajaWebController.php:896-902`<br>`resources/views/caja/cobrar.blade.php:305-323` | `if ($esFechaVieja && !$request->boolean('confirmar_fecha_vieja'))` | Abre panel de confirmación alertando que entra en la caja actual pero altera el reporte del mes cerrado. |
| **Qué hago si: alumno inactivo** | Cobrar a un alumno inactivo pregunta: «¿Pasa a activo?». Si incluye mes actual/futuro, pagar el mes es volver y no se ofrece «No». | `app/Http/Controllers/CajaWebController.php:936-954`<br>`resources/views/caja/cobrar.blade.php:325-338` | `if ($reactivaria)`<br>`if ($request->has('reactivar') && !$request->boolean('reactivar') && $pagaElMes)` | Abre panel `Alumno inactivo`. Si el cobro incluye mes en curso/futuro, la opción «No» se oculta y forzarla vía API devuelve HTTP 422. |
| **Qué hago si: anulación con caja cerrada/validada** | Si la caja está cerrada esperando validación o ya fue validada, la operativa no puede cancelar el cobro. | `app/Http/Controllers/CajaWebController.php:601`<br>`resources/views/caja/detalle.blade.php:201` | `if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA']))` | En caja cerrada o validada, el botón rojo **Cancelar** no se muestra. Si se invoca la ruta por GET o POST, redirige con flash error: `Solo se puede cancelar un cobro con la caja abierta o rechazada.`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Comentario / Motivo |
|---|---|---|---|
| `Debés abrir la caja y confirmar el efectivo antes de operar.` | `app/Services/CajaService.php:278` | Sí | Explica en el requisito inicial que sin caja abierta no se puede operar. |
| `Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.` | `app/Http/Controllers/CajaWebController.php:79` | Sí | Explica que no se puede operar si quedó un turno previo abierto. |
| `Esta fecha de pago corresponde a un mes anterior ya cerrado. El cobro quedará registrado con esa fecha real, pero entrará en la caja de hoy y modificará el reporte de ese mes.` | `app/Http/Controllers/CajaWebController.php:900` | Sí | Explica en «Qué hago si» la confirmación requerida ante fecha de pago en mes anterior cerrado. |
| `Estás cobrando por adelantado :meses, que todavía no empezó. El importe queda fijo aunque después cambie la cuota.` | `app/Http/Controllers/CajaWebController.php:928` | Sí | Explica en «Qué hago si» la confirmación requerida ante cobro de meses futuros no iniciados. |
| `Se puede cobrar por adelantado hasta 6 meses.` | `app/Http/Controllers/CajaWebController.php:914` | Sí | Explica el tope legal de meses futuros que permite cobrar el sistema. |
| `Indicá el importe del mes que se cobra por adelantado.` | `app/Http/Controllers/CajaWebController.php:920` | Sí | Explica que para un mes adelantado sin cuota generada debe consignarse el monto a cobrar. |
| `:apellido, :nombre está inactivo. ¿Pasa a activo?` | `app/Http/Controllers/CajaWebController.php:951` | Sí | Explica en «Qué hago si» la pregunta del panel y remite a la guía de reactivación. |
| `El cobro incluye el mes en curso o uno futuro: para cobrarlo, el alumno tiene que volver a activo.` | `app/Http/Controllers/CajaWebController.php:942` | Sí | Explica que no se ofrece la opción «No» si se cobra el mes en curso o futuro. |
| `¿Cobrás igual solo los meses elegidos? Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.` | `app/Services/ExcepcionCobranzaService.php:96-100` | Sí | Explica el panel que solicita motivo obligatorio ante deuda anterior no saldada. |
| `Solo se puede cancelar un cobro con la caja abierta o rechazada.` | `app/Http/Controllers/CajaWebController.php:601` | Sí | Explica que con caja cerrada o validada no se puede cancelar directamente el cobro. |
| `Solo se pueden cancelar cobros de cuota.` | `app/Http/Controllers/CajaWebController.php:608` | Sí | Explica que la anulación directa de cobros aplica exclusivamente a cobros de cuota. |
| `La caja no está en estado editable.` | `app/Services/CajaCorreccionService.php:24` | Sí | Explica que las cajas validadas no admiten modificaciones contables directas. |
