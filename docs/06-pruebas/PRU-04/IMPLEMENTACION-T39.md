# T39 — Al anular un cobro del dueño, la resta va con la fecha del original

18/10/2026 · Claude CAB.

**Decisiones de Carlos:**
- DU-21, 13/10: «Los dos con la fecha del original (como definiste para Cashflow)».
- 18/10, al implementar: la deuda (cuota, inscripción y recargo de ese cobro) también vuelve
  en el mes del cobro. Ese mes queda como si el cobro no hubiera existido.

## Qué pasaba

Anular un cobro del dueño creaba el asiento en contra con la fecha de hoy. Un cobro del 25/09
anulado el 10/10 restaba en octubre, y septiembre conservaba el ingreso. Eliminar un movimiento
directo en Cashflow, en cambio, ya restaba en el mes original (T27).

La verificación de A13 (05/10) dejó escrito que llevarlo al mes del cobro «requeriría una
decisión explícita». DU-21 es esa decisión.

## Qué cambió

| Dónde | Cambio |
|---|---|
| `PagoCuotaService::anularCobroAdmin` | El asiento en contra lleva la fecha del asiento original. La deuda que vuelve a pendiente se registra en el historial de reportes con la fecha del cobro (`fechaHistorial`). |
| `ReporteMensualService::punitoriosPendientes` | Se quitó la reconstrucción que, para un corte anterior a la anulación, seguía mostrando el recargo como pagado. Las inscripciones ya se calculaban así. |
| `ReporteMensualTest` y `PunitoriosCobroTest` | Las dos pruebas que controlaban la regla vieja ahora controlan la nueva, con un comentario que lo explica. |
| Contratos | Caja y Cashflow V5, Permisos y roles, Punitorios (D4: el perdón posterior conserva el corte; la anulación, no). |
| Tabla de pruebas | COB-021, líneas e y f, en la copia del repositorio y en la tabla viva. |

**El mes en que se anula** no cambia lo cobrado. La deuda del reporte es un saldo a la fecha
de corte, así que la cuota anulada, mientras siga impaga, también figura en los meses siguientes.

## Pruebas

`tests/Feature/AnulacionFechaOriginalT39Test.php`, 5 pruebas:
1. Anulado en octubre por la ficha un cobro del 25/09: el asiento en contra es del 25/09, con
   el mismo medio y subrubro; el original no se toca.
2. Cobro cargado con fecha anterior (28/09, cargado el 02/10): se anula con el 28/09.
3. Reporte de septiembre: pierde lo cobrado y recupera la deuda; octubre no cambia lo cobrado.
4. Inscripción cobrada en ese pago: vuelve a deberse en septiembre; todos los asientos en
   contra son del 25/09.
5. El saldo del medio de pago queda en cero entre original y contra.

Más las de anulación, reportes, Cashflow y recibos existentes: ver la bitácora.

## Fuera de alcance

- **Cancelar un cobro hecho por caja** (operativa) es otro camino (`cancelarCobroOperativo`) y
  no cambió.
- **El recibo anulado** sigue igual: guarda qué períodos cubría el cobro.
