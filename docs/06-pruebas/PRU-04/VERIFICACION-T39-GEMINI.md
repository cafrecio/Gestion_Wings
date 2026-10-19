# Verificación independiente T39 — Anulación de cobro del dueño con fecha original

**Fecha:** 18 de octubre de 2026  
**Verificador:** Gemini CAB  
**Autor del cambio:** Claude (commit `687262f`)  
**Base de datos:** `wings_testing_gemini`  
**Veredicto:** **APROBADO** (`estado=cerrado`, `verifica=Gemini`)

---

## 1. Contexto y decisiones vigentes

- **Decisión de Carlos DU-21 (13/10/2026):**
  > *«Los dos con la fecha del original (como definiste para Cashflow)»*.
- **Decisión de Carlos (18/10/2026):**
  > La deuda del cobro anulado (cuota, inscripción y recargo) vuelve en el mismo mes del cobro.

---

## 2. Verificación del código (revisión estática y puertas)

### 2.1. `app/Services/PagoCuotaService.php` (`anularCobroAdmin`)
- Se verificó la implementación del método en las líneas 646–748:
  - Extrae `$fechaOriginal = Carbon::parse($pago->fecha_pago)->toDateString();`.
  - Reversión en Cashflow: para cada movimiento positivo original asociado al pago (`referencia_tipo = REF_PAGO_CUOTA`, `referencia_id = $pago->id`), crea el contraasiento negativo especificando `'fecha' => $original->fecha->toDateString()`.
  - Reversión de deuda: para cada `DeudaCuota` involucrada, asigna `$deuda->fechaHistorial = $fechaOriginal;` antes de guardar el cambio de estado, garantizando que el historial de deuda registre la restauración en la fecha exacta del pago original y no con `today()`.
  - Para cuotas canceladas (`monto_pagado == 0`), restaura `ESTADO_PENDIENTE`.
  - Restaura la deuda de inscripciones y punitorios con su período original.

### 2.2. `app/Services/ReporteMensualService.php` (`punitoriosPendientes`)
- En `punitoriosPendientes`, se retiró la reconstrucción sintética `$anulados` que marcaba deudas en meses equivocados.
- Al anularse el cobro, el punitorio vuelve al estado pendiente sin condonación en el período de origen, reflejándose directamente en la deuda de punitorios del mes original y no en períodos posteriores.

### 2.3. Búsqueda de otras puertas de anulación o reversión
- Se verificó si existe alguna otra puerta que revierta cobros del dueño utilizando la fecha de hoy:
  - **Devoluciones manuales de Cashflow:** `CashflowCorreccionService` ya utilizaba `$mov->fecha->toDateString()`.
  - **Cancelación de cobros operativos:** `CajaService::cancelarCobroOperativo` cancela cobros realizados por operativas dentro de una sesión de caja abierta. Es por diseño un circuito separado (afecta el arqueo de la caja abierta del turno).
  - No existen rutas adicionales ni controladores activos para anulación de cobros del dueño fuera de `PagoCuotaService::anularCobroAdmin`.

---

## 3. Revisión de las pruebas modificadas y nuevas

### 3.1. Pruebas modificadas
1. **`ReporteMensualTest::test_reporte_mensual_deuda_incluye_punitorios_pendientes`:**
   - Anteriormente esperaba que un cobro del 25/09 anulado el 10/10 no registrara deuda en septiembre.
   - Con la regla nueva, afirma `$this->assertSame(50000, $reporte['deuda']['punitorios']);` en el reporte de septiembre. Controla con precisión la restitución retroactiva de la deuda por punitorios.
2. **`PunitoriosCobroTest::test_anulacion_de_cobro_restaura_el_punitorio`:**
   - Comprueba que la anulación del cobro restaura el punitorio a su estado impago en el período original sin alterar otros meses.

### 3.2. Pruebas nuevas (`tests/Feature/AnulacionFechaOriginalT39Test.php`)
- `test_anulacion_de_cobro_admin_genera_contraasientos_con_fecha_del_cobro_original`: Verifica que ante un cobro del 25/09 con cuota e inscripción anulado el 10/10, los contraasientos de Cashflow lleven fecha `2026-09-25`.
- `test_anulacion_de_cobro_admin_con_recargo_genera_contraasientos_con_fecha_del_cobro_original`: Idem incluyendo recargo de mora ($500), resultando en 3 contraasientos todos fechados el 25/09.
- `test_reporte_mensual_baja_cobrado_y_sube_deuda_en_el_mes_del_cobro_anulado`: Comprueba que en septiembre lo cobrado baja $15.500 y la deuda total sube exactamente $15.500 ($10.000 cuota, $5.000 inscripción y $500 recargo).
- `test_reporte_mensual_del_mes_en_que_se_anula_no_se_ve_afectado`: Comprueba que octubre permanece sin afectación en ingresos ni deuda.
- `test_saldo_del_medio_de_pago_vuelve_al_estado_anterior_al_cobro`: Comprueba que el saldo acumulado del medio de pago queda idéntico al valor previo al cobro.

---

## 4. Verificación empírica en navegador y base de datos (`wings_testing_gemini`)

Se ejecutó un escenario de prueba completo:
1. **Cobro del dueño el 25/09/2026:**
   - Cuota: $10.000,00
   - Inscripción: $5.000,00
   - Recargo por mora: $500,00
   - Total cobrado: $15.500,00
2. **Anulación realizada el 10/10/2026:**
   - Motivo registrado: *«Error de carga en mostrador»*.
3. **Resultados verificados:**
   - **Cashflow:** Los 3 contraasientos (-$10.000, -$5.000, -$500) se registraron con fecha `2026-09-25`. El balance neto del período septiembre quedó en `$0.00`.
   - **Reporte de Septiembre:** Lo cobrado disminuyó en $15.500 (1.550.000 centavos) y la deuda total se incrementó en exactamente $15.500 (desglosado en: +$10.000 cuotas, +$5.000 inscripciones, +$500 punitorios).
   - **Octubre:** En Cashflow figuran 0 movimientos; en el reporte mensual el resultado de octubre no sufrió variación alguna.
   - **Saldo del medio de pago:** El saldo del medio de pago (EFT) retornó exactamente a su balance inicial (variación neta de $0.00).

---

## 5. Evidencia visual

Capturas generadas en Chrome Headless en escritorio (1366 px) y celular (marco `<iframe>` de 360 px), ubicadas en `docs/06-pruebas/PRU-04/evidencia-t39/`:

| Pantalla | Vista Escritorio (1366 px) | Vista Celular (marco 360 px) |
|---|---|---|
| Cashflow Septiembre 2026 | `cashflow-septiembre-despues-anulacion-escritorio-1366.png` | `cashflow-septiembre-despues-anulacion-marco-360.png` |
| Cashflow Octubre 2026 | `cashflow-octubre-despues-anulacion-escritorio-1366.png` | `cashflow-octubre-despues-anulacion-marco-360.png` |
| Reportes Septiembre 2026 | `reporte-septiembre-despues-anulacion-escritorio-1366.png` | `reporte-septiembre-despues-anulacion-marco-360.png` |
| Reportes Octubre 2026 | `reporte-octubre-despues-anulacion-escritorio-1366.png` | `reporte-octubre-despues-anulacion-marco-360.png` |

---

## 6. Resultado de la suite completa de pruebas

La suite se corrió en forma aislada sobre la base de datos `wings_testing_gemini`:
- **Total pruebas:** 860 pruebas (858 aprobadas, 2 omitidas, 0 fallidas).
- **Aserciones:** 7.915 aserciones.
- **Integridad:** Sin errores de sintaxis, vistas compilando sin fallas y pruebas de documentación en verde.
