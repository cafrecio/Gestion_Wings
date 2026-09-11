# Wings-Contrato-Recibos-PDF-V2.md

**Caso de uso (Index):** 7) Recibos PDF (cuotas + liquidaciones)
**Versión:** V2 — 11/09/2026, autorizada por Carlos
**Estado:** CERRADO
**Origen:** `docs/05-pendientes/CUESTIONARIO-I3-CONTRATOS.md` (I3.0) — módulo sin ningún contrato previo. Reglas definidas junto al usuario el 2026-07-26 a partir de auditoría completa del código real (`ReciboService`, `ReciboController`, vistas `pdfs/*`).
**Alcance:** Recibo de pago de cuota y recibo de liquidación — generación, contenido, permisos, comportamiento ante anulación.
**No incluye:** Numeración fiscal (estos PDFs son explícitamente "no válidos como factura" / "comprobante interno", sin CUIT ni razón social — decisión de producto ya tomada antes de este contrato, no se toca).

---

## 7.a Generación y caché

- Librería: **dompdf** (`Barryvdh\DomPDF`). Formato A5 vertical.
- El PDF se genera **una vez y se persiste** en `storage/app/recibos/recibo-{cuota|liquidacion}-{id}.pdf` (ruta determinística por el ID del `Pago`/`Liquidacion`, no un correlativo independiente).
- Pedidos posteriores devuelven el archivo cacheado tal cual, salvo `?regenerar=1` (fuerza regeneración) o que el pago esté **anulado** (ver 7.c, siempre regenera).
- `numero_recibo` es literalmente `"CUOTA-{pagoId}"` / `"LIQ-{liquidacionId}"` — no hay tabla de numeración de recibos ni control de correlativo independiente. Aceptado así: no es un comprobante fiscal.

---

## 7.b Permisos

| Endpoint | ADMIN | OPERATIVO | PROFESOR |
|---|---|---|---|
| `GET /recibos/cuota/{pagoId}` | Sí | Sí (cualquier cobro, no solo el propio — dominio Cuotas = ambos) | No (403) |
| `GET /recibos/liquidacion/{liquidacionId}` | Sí | No (`ensure.admin.web`) | No |

Sin cambios respecto de lo ya definido en `PERMISOS-ROLES.md` — este contrato solo lo confirma para este caso de uso puntual.

---

## 7.c Pago anulado — conserva el detalle (FIN-03)

Decision de Carlos, 11/09: conservar periodos, importes y motivo al anular.

- Antes de retirar las imputaciones activas, se guarda en pagos.detalle_anulacion
  una copia de cada periodo e importe aplicado y el motivo de cancelacion.
  Ocurre en la misma transaccion que la reversion del cobro.
- La copia es documental: no suma pagos ni participa en deudas o FIFO.
  El total del recibo sigue saliendo de monto_final, que no se modifica.
- El PDF anulado toma esa copia y agrega el motivo a las observaciones existentes.
  Cobros posteriores no sustituyen el detalle del recibo anterior.
- La migracion no inventa ni reconstruye detalles de anulaciones anteriores:
  si sus imputaciones ya fueron borradas, este cambio no las recupera.
- No cambia plantilla, formato ni permisos.


**Decisión:** si el `Pago` asociado está `estado = 'ANULADO'`, el recibo **sigue siendo descargable** pero se genera/regenera con un sello visible: *"✗ RECIBO ANULADO — el pago fue cancelado"*.

- `ReciboService::generarReciboCuota()` siempre regenera (ignora el caché) cuando el pago está anulado, para garantizar que nunca se sirva una versión vieja sin el sello.
- No se agregó ningún bloqueo a nivel `ReciboController::cuota()` — la ruta sigue devolviendo 200 con el PDF, ahora marcado.
- Este caso no aplica a Liquidación: no existe un estado "ANULADA" en el modelo (`Liquidacion` solo tiene `ABIERTA`/`CERRADA` + `PENDIENTE`/`PAGADA`), y una vez `PAGADA` la liquidación queda estructuralmente protegida (no se puede reabrir ni borrar — ver 7.e).

---

## 7.d Medio de cobro mostrado — vinculo exacto (FIN-02)

- Operativo: MovimientoOperativo con pago_id igual al pago del recibo.
- Se elimino la busqueda por observaciones, importe y fecha.
- Admin: sin cambios, CashflowMovimiento por referencia_tipo/referencia_id.
- Si no hay vinculo que identifique el medio, muestra "N/D", sin adivinar.
- Antes, dos cobros iguales del mismo alumno el mismo dia, o cancelar y volver
  a cobrar por otro medio, podian mostrar el medio del primer movimiento.
  FIN-02 corrige el medio: el importe y los periodos del cobro vigente no
  estaban afectados. La perdida de periodos al cancelar es FIN-03.


---

## 7.e Verificado, sin bug: liquidación pagada no se puede borrar ni reabrir

Se investigó si una `Liquidacion` con `estado_pago = PAGADA` podía quedar en estado `ABIERTA` (lo que dejaría un recibo emitido sin respaldo). **No es posible**:
- `LiquidacionPagoService::marcarComoPagada()` exige `estaCerrada()` antes de aceptar el pago.
- `Liquidacion::boot()` bloquea modificar el campo `estado` de una liquidación `CERRADA` (excepto los campos de pago) y bloquea `deleting()` si está `CERRADA`.

No se requirió ningún cambio de código para este punto.

---

## 7.f Reglas Freeze

- Un recibo de un pago anulado nunca se sirve sin el sello de "ANULADO".
- Un recibo de liquidación solo existe si la liquidación está `PAGADA` (y por 7.e, eso implica `CERRADA` e inmutable).
- PROFESOR nunca accede a ningún recibo.

Cualquier cambio futuro requiere una nueva version explicita.

---

## 7.g Antecedente y verificacion

Antecedente V1 (historico): Implementado y probado contra datos reales el 2026-07-26: verificado que la plantilla muestra el sello cuando corresponde, y que el servicio regenera el PDF (bytes y mtime distintos) para un pago anulado sin pedir `forceRegenerate`. El chequeo de liquidación (7.e) se verificó por lectura de código, sin necesidad de cambios.


V2: regresiones en ReciboMedioDePagoTest; ejecucion y limites en LOG-CODEX.md.
No implica despliegue. V1 queda conservada como antecedente.
