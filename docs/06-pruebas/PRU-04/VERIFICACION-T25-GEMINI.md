# Verificación T25 — Cobro a un alumno inactivo pregunta si pasa a activo

**Fecha:** 28/10/2026  
**Verificador:** Gemini (CAB)  
**Commit verificado:** `847a35df` (Claude CyE)  
**Base de pruebas:** `wings_testing_gemini`  
**Resultado de lógica:** APROBADA  
**Resultado visual:** Capturas preparadas en `docs/06-pruebas/PRU-04/APROBAR-T25.html` a la espera de Carlos  

---

## 1. Regla de negocio (Carlos, 11/10 y 28/10)

Al cobrarle a un alumno inactivo:
1. La pantalla de Cobrar pregunta *«¿Pasa a activo?»* en un panel hermano de confirmación (no usa ventana/alert del navegador).
2. **Sí:** se reactiva como hasta ahora (T38): se le genera la cuota del mes en curso si el cobro no la incluía, con fecha de reingreso y descuento si corresponde.
3. **No:** se cobra lo elegido y el alumno permanece inactivo. No se genera la cuota del mes ni se le aplica descuento de reingreso.
4. **«No» solo se ofrece con deuda vieja:** si el cobro incluye el mes en curso o uno futuro, pagar el mes es volver; solo aparecen **Sí** y **Cancelar**.
5. Sin plan para el mes actual no se pregunta (el cobro no lo reactivaría).

---

## 2. Verificaciones realizadas en código y comportamiento

### A. Salidas individuales (Admin y Operativa)
- **Sí:** probado como Admin y como Operativa. Alumno pasa a `activo = true`, la deuda vieja se cobra y se genera la cuota del mes en curso con `fecha_reingreso` (T38).
- **No:** probado como Admin y como Operativa. Se cobra la deuda vieja seleccionada, alumno permanece en `activo = false`, no se crea cuota del mes en curso. En caja operativa se genera el `MovimientoOperativo` y en admin el `CashflowMovimiento`.
- **Cancelar:** cierra el modal en pantalla, no envía la petición y no se escribe nada en la base de datos.
- **«No» forzado por fuera de pantalla con el mes en curso o futuro:**
  - El controlador `CajaWebController::pagar` lo rechaza con `422` y mensaje: *«El cobro incluye el mes en curso o uno futuro: para cobrarlo, el alumno tiene que volver a activo.»*
  - El servicio `PagoCuotaService::exigirActivoParaCobrarElMes` lanza `RuntimeException` con el mismo mensaje si se invoca directamente.
- **Alumno activo:** no dispara el panel ni la confirmación 409; redirige y cobra directamente.
- **Alumno inactivo sin plan para el mes:** no dispara la pregunta (el cobro no lo reactivaría).

### B. Combinación con otras confirmaciones
Se verificó que los datos y banderas de confirmación persistan a través de los modales sin cobrar doble:
1. **Con fecha de mes cerrado (`fecha_pago` anterior al mes actual):**
   - 1ª petición: salta 409 `requiere_confirmacion_fecha_vieja`.
   - 2ª petición (con `confirmar_fecha_vieja = 1`): salta 409 `requiere_reactivacion`.
   - 3ª petición (con `reactivar = 0` y `confirmar_fecha_vieja = 1`): se registra el cobro, alumno sigue inactivo y se genera el aviso correspondiente.
2. **Con mes adelantado (ej. noviembre `2026-11`):**
   - 1ª petición: salta 409 `requiere_confirmacion_adelantado`.
   - 2ª petición (con `confirmar_pago_adelantado = 1`): salta 409 `requiere_reactivacion` con `permite_no = false`.
   - Si se intenta forzar `reactivar = 0`: devuelve 422.
   - Con `reactivar = 1`: cobra, reactiva al alumno y congela el precio del mes adelantado.
3. **Con cobro parcial que pide motivo (B3):**
   - 1ª petición: salta 409 `requiere_reactivacion`.
   - 2ª petición (con `reactivar = 0`): salta 409 B3 (`requiere_confirmacion = true`, `requiere_motivo = true`).
   - 3ª petición (con `reactivar = 0`, `confirmar_deuda_anterior = 1` y motivo): se registra el pago parcial, se guarda la excepción en `excepciones_cobranza` y el alumno sigue inactivo.

### C. Estado posterior al «No»
- **Generador de cuotas del día 1 (`cobranza:generar-deudas`):** simulado el avance al 1 de noviembre (`2026-11-01`). Al estar inactivo, el generador lo saltea y no crea cuota para el nuevo mes.
- **Ficha y listados:** Alumnos y Cobranza muestran el estado inactivo.
- **Recibo de pago:** la descarga de PDF (`web.recibos.cuota`) funciona con 200 OK.

---

## 3. Análisis del punto dudoso (marcado por Claude)

**Pregunta planteada:**  
Con «No» igual se ejecuta `AlumnoRevisionCobranza::reactivarAuto($data['alumno_id'], $item['periodo'])` para los meses cobrados. Si el alumno tenía una revisión pendiente de ese mes, ¿queda resuelta como «reactivado automático» aunque siga inactivo? ¿Se puede llegar a esa situación desde la pantalla?

**Análisis detallado del flujo:**
1. Para que la pantalla ofrezca el botón **«No»**, el cobro debe incluir **únicamente deuda vieja** (`$pagaElMes = false`).
2. Una deuda vieja solo aparece listada en la pantalla si ya existe un registro en `deuda_cuotas`.
3. Cuando `cobranza:generar-deudas` corre, si un alumno ya tiene `deuda_cuotas` para ese período, saltea y **nunca crea una revisión**. Si no cumple condiciones, crea la revisión pero **no crea deuda**.
4. Si la revisión fue resuelta manualmente por el admin como `CONTINUA`, se creó la deuda pero la revisión pasó a `ESTADO_RESUELTO` (ya no está `PENDIENTE`).
5. Si la revisión fue resuelta como `INACTIVO`, no se creó deuda y la revisión pasó a `ESTADO_RESUELTO`.
6. Si la revisión quedó sin resolver (`PENDIENTE`), **no existe deuda** para ese período. Y al ser un período pasado, **no aparece en `mesesAdelantables`** (que solo ofrece el mes en curso y meses futuros). Por lo tanto, no hay forma de seleccionarlo ni de cobrarlo desde la pantalla de Cobrar.
7. Y si se cobra el mes en curso (que sí podría haber estado en revisión), **no se ofrece «No»**; pagar el mes exige reactivar, con lo cual la resolución `REACTIVADO_AUTO` es correcta.

**Conclusión:**  
No es posible llegar a esa situación desde la interfaz de usuario. No constituye un defecto bloqueante.

---

## 4. Diseño y Seguridad CSP

- Botones del panel `modal-reactivar`: son de una sola palabra (`Cancelar`, `No`, `Sí`).
- Botón primario (`Sí`): posicionado a la derecha tanto en escritorio como en celular (`acciones-celular-derecha`).
- Cero scripts o manejadores de eventos inline en la vista Blade (`caja/cobrar.blade.php`).
- Prueba `CspSinCodigoIncrustadoTest`: 2/2 PASAN.

---

## 5. Pruebas ejecutadas

Base: `wings_testing_gemini`

1. `CobroInactivoT25Test`: 9/9 PASAN.
2. `CobroInactivoT25VerificacionGeminiTest` (nuevas pruebas de combinaciones, roles y generador): 7/7 PASAN.
3. `ReingresoCuotaT38Test`: 18/18 PASAN.
4. `CambioPlanCobroTest`: 10/10 PASAN.
5. `T88B3DineroTest`: 18/18 PASAN.
6. `CspSinCodigoIncrustadoTest`: 2/2 PASAN.

---

## 6. Distinción entre Verificado e Inferido (AGENTS.md §6c)

- **Verificado:**
  - Código en `CajaWebController.php`, `PagoCuotaService.php`, `cobrar.js`, `cobrar.blade.php`.
  - Pasaron 64 pruebas automatizadas específicas.
  - Comportamiento de combinaciones con fecha vieja, cobro adelantado y B3.
  - Capturas generadas con Chrome real headless a 1366 px y dentro de marco iframe de 375 px.
  - Compilación Vite con `npm run build`.
- **Inferido:**
  - Nada fue inferido; todas las afirmaciones provienen de corridas de pruebas y código fuente inspeccionado.
