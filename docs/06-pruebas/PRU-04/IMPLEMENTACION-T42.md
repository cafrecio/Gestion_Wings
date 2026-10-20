# T42 — Comisión e ingresos por mes pagado

Codex CyE, 20/10/2026. **IMPLEMENTADA; a verificar por Claude. Suite completa verde.** Tomada y publicada antes del código en c0bbe3aa. T61 entregada en f089768b; T52 devuelta a Claude por H5. Sin despliegue ni escritura en bases remotas.

## Regla solicitada

Comisión de cada imputación en `max(mes del cobro, mes pagado)`, con asistencia en el mes de liquidación. Sin pivotes, conservar la regla anterior. Los ingresos adelantados de Reportes/Inicio pasan al mes pagado; la fecha del dinero y los saldos de Caja/Cashflow se conservan. T43 va después.

## Diferencia encontrada antes de implementar

`CashflowIntegracionCajaService::reflejarCajaEnCashflow` guarda hoy `referencia_id = caja_id` y `movimiento_operativo_id = movimiento.id`. El vínculo permite llegar desde el asiento al pago y sus imputaciones. La migración `2026_10_12_120000_add_movimiento_operativo_id_to_cashflow_movimientos.php` agrega una columna nullable, sin reconstruir los vínculos anteriores.

Consulta local de solo lectura en `gestion_wings`: los asientos 1 a 5 son Cuota Mensual, fechas 06/01 a 06/05/2026, importe $25.000 y referencia a las cajas 1 a 5. Esa base todavía no tiene la columna `movimiento_operativo_id`; no se ejecutaron migraciones ni escrituras. Esto acredita la existencia del formato anterior; no acredita que esos cinco pagos fueran adelantados ni que sus vínculos sean ambiguos. No se inspeccionó ni modificó ninguna base remota.

La orden permite conservar la regla anterior para pagos sin imputaciones, pero no define qué hacer con un asiento sin vínculo identificable al pago. No corresponde adivinarlo por importe, fecha o posición. Consulta enviada a Carlos: conservar el mes original únicamente en los no identificables, o reconstruir primero los vínculos verificables y consultar los ambiguos. Carlos pidió un ejemplo; se explicó el 20/10, sin interpretar esa pregunta como elección. Carlos resolvió la consulta: «Sí, conservar el mes original solo en esos casos.» Se aplica la excepción únicamente cuando no puede comprobarse el mes pagado; los identificables siguen DU-19. La implementación de Reportes queda habilitada.

Comprobación adicional de solo lectura: asiento 1 tiene un candidato por caja/fecha/concepto/medio/importe, pago 1 que paga enero. Asiento 56 tiene dos candidatos (movimientos/pagos 56 y 125), ambos pagan enero. Comparar además observaciones no dio coincidencia para los primeros cinco; no se mostró su contenido. No se comprobó ingreso adelantado mal imputado en estos ejemplos. Falta de vínculo y error de mes no son equivalentes.

## Avance independiente — comisión

La regla de comisión está definida y no depende de identificar asientos Cashflow. `PartesPagoCuotaService` obtiene los pagos candidatos con sus imputaciones y calcula en centavos la parte asignada a cada mes. `LiquidacionService` usa esa base tanto en generación como en previsualización; conserva asistencia del mes, filtro de estado, porcentaje congelado y fallback sin pivotes. Se suman las partes del mismo pago/mes antes de redondear, conservando el redondeo anterior por pago. Sin vistas ni cambios de Caja/Cashflow.

Primer corte de comisión, antes de incorporar Reportes: diez nuevas más ocho históricas, **18 aprobadas, 88 aserciones, 10,38 s, salida 0**. [Salida](evidencia-t42/comision-dirigidas.txt). Rápida de ese corte: **872 aprobadas, 6 omitidas, 7015 aserciones, 155,19 s, salida 0**; [salida](evidencia-t42/comision-suite-rapida.txt). No certifican por sí solas el bloque posterior de Reportes.

## Reportes, Inicio e historia

`ReporteMensualService` mantiene las filas financieras originales para dinero y saldos, y desglosa únicamente el ingreso de cuotas por imputación. El detalle conserva la fecha real de cobro y agrega el mes de la cuota al concepto. Las cajas pendientes y su posterior reflejo confirmado usan el mismo reparto, sin duplicarse. Inscripción y otros conceptos no se trasladan por compartir referencia a un Pago.

Para asientos antiguos se admite una correspondencia financiera uno a uno, solo en lectura. La ambigua conserva el mes original, según la respuesta de Carlos. La anulación usa sus períodos conservados para repartir tanto el asiento original como el contraasiento después de retirar los pivotes. Disponible y resultado global siguen el dinero real.

El historial captura las imputaciones completas al terminar de adjuntarlas. En estados anteriores sin ese campo, obtiene las originales disponibles o las conservadas al anular, sin reescribir registros históricos. `ReporteSueldosService` usa esas partes en la estimación de comisión; la base analítica de cuotas devengadas no cambia. Inicio consume el mismo ReporteMensualService, comprobado por HTTP.

Nueve pruebas nuevas de ingresos más diez de comisión y diez de Sueldos: **29 aprobadas, 150 aserciones, 11,08 s, salida 0**, en `wings_testing_codex`, sin otra suite activa. [Dirigidas finales](evidencia-t42/reportes-dirigidas-final.txt). La primera corrida detectó un fallo propio de colección vacía y una URL incorrecta de la prueba, corregidos antes de repetir. [Primera salida](evidencia-t42/reportes-dirigidas.txt). Corte inicial permanente **1002**; final **1003** al incorporarse H5 de T52. Sintaxis de los ocho archivos PHP y compilación/limpieza de vistas correctas. Sin cambios de vistas ni CSS.

Primera completa: **995 aprobadas, 6 omitidas, 1 fallida, 8642 aserciones, 809,46 s, salida 1**. La prueba histórica seleccionaba el primer pago devuelto en vez del pago bajo prueba. Se corrigió por ID y se agregó otro pago del mes al escenario; ahora verifica comisión de octubre y noviembre por separado. Dirigidas repetidas: **29 aprobadas, 150 aserciones, 10,04 s, salida 0** ([salida](evidencia-t42/dirigidas-tras-completa.txt)). Segunda corrida detenida por el contador: durante la ejecución se incorporó H5 de T52 y el total real pasó de 1002 a 1003; 466 aprobadas, 6 omitidas, 1 fallida de contador, 529 pendientes, 3399 aserciones, 202,39 s, salida 1. No se atribuye a esa simultaneidad la falla anterior por selección del primer pago. [Salida](evidencia-t42/suite-completa-final.txt).

## COB-012 — tabla viva y copia

Carlos autorizó: «Sí, enviar esos cambios a Claude.» El editor del artefacto recibió exclusivamente los cambios e/h. Se abrió la operación real de relectura de ArtifactData en pantalla: catálogo COB versión 10; salida de comparación `['COB-012'] {'casos'}` y textos finales iguales a la copia del repo. [Salida real observada](evidencia-t42/tabla-viva-relectura.txt) · [captura de criterios](evidencia-t42/tabla-viva-criterios.jpg).

e distingue dinero en octubre de ingreso económico en noviembre; h asigna comisión a noviembre con asistencia en noviembre. No se modificaron respuestas de dudas ni otros casos. La copia LIQ-002 a/e también se rectificó porque su regla anterior de adelantados quedó falsa; conserva T43 pendiente para el control del dueño. Pasar LIQ-002 a la tabla viva queda junto a T43, sin ampliar el envío autorizado exclusivamente para COB-012. La vista pública del artefacto quedó cargando; no se certifica su renderizado. El cambio de datos se verificó en la relectura del editor, no solo en el relato de Claude.

## Caminos de creación de pagos revisados

| Camino | Importe de cuota | Imputaciones |
|---|---|---|
| `PagoCuotaService::crearPago`, usado por cobro ADMIN y OPERATIVO | `monto_cuota` explícito; no incluye inscripción ni punitorio | Ambos caminos llaman `relacionarPagoConDeudas`, que crea `PagoDeudaCuota` con el importe aplicado |
| `PagoService::registrarPago` | No escribe `monto_cuota`; el accessor de Pago usa `monto_final` | No crea pivotes. Caller encontrado: PagoController de API; la carga de API está comentada en bootstrap/app.php |
| `DemoSeeder` | Inserta `monto_final` por SQL; accessor conserva el fallback | Inserta también `pago_deuda_cuota`, con deuda y monto aplicado |
| `ReportesEscenarioSeeder` | Usa el servicio real de cobro | Hereda las imputaciones de PagoCuotaService |

Se leyeron cuerpos reales, no solo firmas. `search_graph` y `check_index_coverage` siguen devolviendo `Transport closed`; búsqueda de texto y lectura directa como alternativa, sin certificar cobertura exhaustiva del grafo.

## Alcance del código

- Nuevo `PartesPagoCuotaService`; generación/previsualización en `LiquidacionService`.
- Desglose financiero/económico en `ReporteMensualService`; Inicio lo reutiliza.
- Captura completa en `HistorialAnaliticoReportesService` y al adjuntar pivotes en `PagoCuotaService`; estimación en `ReporteSueldosService`.
- Diecinueve regresiones nuevas; contratos de Liquidaciones/Reportes/Cashflow y criterios COB-012 y copia LIQ-002 rectificados.

La implementación se entrega a Claude para verificación; el próximo trabajo de Codex es T43, según la orden de Carlos.

## Cierre de implementación y validación final

**997 aprobadas, 6 omitidas, 8652 aserciones, 1242,09 s, salida 0**, wings_testing_codex, corte 1003. [Suite completa final](evidencia-t42/suite-completa-corte1003.txt). Sin otra prueba activa al arrancar esta corrida; código T42 conservado durante ella. Fuentes en [manifiesto](evidencia-t42/fuentes.json). Compilación/limpieza de vistas y sintaxis correctas; sin vistas/CSS modificados. T42 a verificar por Claude, sin autocierre ni despliegue. La lógica T52 e1e7c499 quedó además aprobada en 21/139 propias y regresiones; su verificación se documenta por separado. Próximo trabajo: T43.
