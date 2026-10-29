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
| **Caja de día anterior** | Wings no permite operar si quedó un turno pendiente de un día previo («Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.»). | `app/Services/CajaService.php:245, 270` | `if ($cajaAbierta && !$cajaAbierta->apertura_at->isToday())` | Lanza excepción con mensaje textual: `Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.`. |
| **Paso 1: Buscar alumno** | Entrar a **Caja**, tocar **Cobrar**. Escribir nombre/DNI y tocar **Buscar**. En la tarjeta tocar **Cobrar**. Desde Cobranza va directo al cobro del alumno. | `resources/views/caja/cobrar-cuota.blade.php:22, 53`<br>`resources/views/cobranza/index.blade.php:226` | `if ($request->filled('search')) { $query->buscar(...); }` | Al no ingresar búsqueda en `/caja/cobrar`, lista únicamente a los alumnos con deuda pendiente. Con búsqueda filtra por nombre o DNI. |
| **Paso 2: Elegir cuotas** | Se visualizan datos y **Total pendiente**. Casillas de períodos, matrícula o recargos. Permite cuota parcial. | `resources/views/caja/cobrar.blade.php:48, 86`<br>`app/Services/PagoCuotaService.php:272` | `if ($cuota->saldo_pendiente_centavos === 0) { $cuota->estado = DeudaCuota::ESTADO_PAGADA; }` | Si el pago no cubre el total, la cuota permanece en `PENDIENTE` con el saldo restante reducido. |
| **Paso 3: Medio de pago** | Seleccionar **Medio de pago** entre los habilitados. **Fecha del pago** y **Observaciones**. | `resources/views/caja/cobrar.blade.php:154, 185`<br>`app/Http/Controllers/CajaWebController.php:874` | `'tipo_caja_id' => 'required\|exists:tipos_caja,id'` | Al omitir el medio de pago, la validación rechaza con error de campo requerido. |
| **Paso 4: Confirmar y recibo** | Tocar **Cobrar**. Emite confirmación y botón **Recibo** (`/recibos/cuota/{pagoId}`). | `resources/views/caja/cobrar.blade.php:235`<br>`app/Http/Controllers/CajaWebController.php:970` | `if ($pago->id) { return response()->json(['success' => true, 'recibo_url' => ...]); }` | Al confirmar cobro válido, registra pago, descuenta deuda y entrega URL directa del recibo oficial. |
| **Qué hago si: anulación con caja cerrada/validada** | Si la caja está cerrada esperando validación o ya fue validada, la operativa no puede cancelar el cobro. | `app/Http/Controllers/CajaWebController.php:601`<br>`resources/views/caja/detalle.blade.php:201` | `if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA']))` | En caja cerrada o validada, el botón rojo **Cancelar** no se muestra. Si se invoca la ruta por GET o POST, redirige con flash error: `Solo se puede cancelar un cobro con la caja abierta o rechazada.`. |
