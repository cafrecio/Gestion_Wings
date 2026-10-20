# T42 — Preparación y consulta previa

Codex CyE, 20/10/2026. **Tomada y publicada en c0bbe3aa; producto todavía sin modificar.** T61 entregada en f089768b con suite completa verde y aviso aprobado por Carlos. T52 devuelta a Claude por H5.

## Regla solicitada

Comisión de cada imputación en `max(mes del cobro, mes pagado)`, con asistencia en el mes de liquidación. Sin pivotes, conservar la regla anterior. Los ingresos adelantados de Reportes/Inicio pasan al mes pagado; la fecha del dinero y los saldos de Caja/Cashflow se conservan. T43 va después.

## Diferencia encontrada antes de implementar

`CashflowIntegracionCajaService::reflejarCajaEnCashflow` guarda hoy `referencia_id = caja_id` y `movimiento_operativo_id = movimiento.id`. El vínculo permite llegar desde el asiento al pago y sus imputaciones. La migración `2026_10_12_120000_add_movimiento_operativo_id_to_cashflow_movimientos.php` agrega una columna nullable, sin reconstruir los vínculos anteriores.

Consulta local de solo lectura en `gestion_wings`: los asientos 1 a 5 son Cuota Mensual, fechas 06/01 a 06/05/2026, importe $25.000 y referencia a las cajas 1 a 5. Esa base todavía no tiene la columna `movimiento_operativo_id`; no se ejecutaron migraciones ni escrituras. Esto acredita la existencia del formato anterior; no acredita que esos cinco pagos fueran adelantados ni que sus vínculos sean ambiguos. No se inspeccionó ni modificó ninguna base remota.

La orden permite conservar la regla anterior para pagos sin imputaciones, pero no define qué hacer con un asiento sin vínculo identificable al pago. No corresponde adivinarlo por importe, fecha o posición. Consulta enviada a Carlos: conservar el mes original únicamente en los no identificables, o reconstruir primero los vínculos verificables y consultar los ambiguos. Implementación dependiente detenida hasta su respuesta, según AGENTS.md §6b. No se toma el tiempo transcurrido como respuesta.

## Caminos de creación de pagos revisados

| Camino | Importe de cuota | Imputaciones |
|---|---|---|
| `PagoCuotaService::crearPago`, usado por cobro ADMIN y OPERATIVO | `monto_cuota` explícito; no incluye inscripción ni punitorio | Ambos caminos llaman `relacionarPagoConDeudas`, que crea `PagoDeudaCuota` con el importe aplicado |
| `PagoService::registrarPago` | No escribe `monto_cuota`; el accessor de Pago usa `monto_final` | No crea pivotes. Caller encontrado: PagoController de API; la carga de API está comentada en bootstrap/app.php |
| `DemoSeeder` | Inserta `monto_final` por SQL; accessor conserva el fallback | Inserta también `pago_deuda_cuota`, con deuda y monto aplicado |
| `ReportesEscenarioSeeder` | Usa el servicio real de cobro | Hereda las imputaciones de PagoCuotaService |

Se leyeron cuerpos reales, no solo firmas. `search_graph` y `check_index_coverage` siguen devolviendo `Transport closed`; búsqueda de texto y lectura directa como alternativa, sin certificar cobertura exhaustiva del grafo.

## Otros puntos para la implementación autorizada

- Separar filas económicas del reporte de las financieras: mover solo el ingreso del período, sin alterar disponible, cuentas ni resultado global del dinero.
- El detalle de ingresos de Reportes usa las mismas filas del servicio; no basta cambiar únicamente el total.
- `HistorialAnaliticoReportesService` captura Pago antes de que el servicio adjunte los pivotes, y hoy no guarda el desglose. Capturar las imputaciones completas sin reescribir estados históricos.
- La anulación guarda los períodos en `detalle_anulacion` antes de retirar los pivotes. Conservar el desglose para el contraasiento y excluir ANULADO de comisión.
- Previsualización y generación de comisión, estimación en ReporteSueldos e Inicio deben coincidir. Liquidaciones cerradas/pagadas conservan sus importes.
- Actualizar COB-012 e/h en copia y tabla viva; el artifact se pudo leer en Chrome en esta sesión, todavía sin modificar.

Sin código T42, sin pruebas nuevas ni cierre de tarea. Próximo paso: resolver el alcance histórico consultado y después implementar comisión, reportes y pruebas de borde.
