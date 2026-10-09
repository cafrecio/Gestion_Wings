# Ajuste final — mapa previo y continuación implementada

09/10/2026, Codex CyE. Carlos: **«El monto final»** y **«También cerradas sin pagar»**.
Alcance acordado: liquidaciones a comisión, ADMIN; abiertas y cerradas pendientes
de pago. Las pagadas conservan su importe. El mapa siguiente conserva el diagnóstico ANTERIOR al cambio.
Implementación posterior: [entrega y verificación](IMPLEMENTACION-SUELDOS-2026-10-09.md).

## Comprobación de fuente

Corte inicial antes de implementar: leídos los cuerpos; entonces no se ejecutaron operaciones ni se consultó la base.

| Punto | Comprobado | Cambio necesario antes de habilitar el ajuste |
|---|---|---|
| [Modelo](../../../app/Models/Liquidacion.php) | Cerradas permiten campos de pago o cancelación pendiente; no permiten editar el importe | Excepción limitada al monto final, con auditoría; cálculo y detalles congelados |
| [Cálculo](../../../app/Services/LiquidacionService.php) | Comisión sobre pagos de cuota del período, completos o parciales, con asistencia al profesor; `total_calculado` guarda el cálculo | Conservar cálculo original separado del monto final; el reparto analítico entre profesores no cambia esta comisión |
| [Pago](../../../app/Services/LiquidacionPagoService.php) | Bloquea la fila de liquidación antes de pagar; crea el egreso usando `total_calculado` | Ajustar y pagar bajo el mismo bloqueo; egreso por el importe final leído dentro de la transacción |
| [Controlador](../../../app/Http/Controllers/LiquidacionWebController.php) | Advertencia de saldo y aviso de fecha anterior usan `total_calculado` | Usar importe final y el resultado efectivo del pago para no anunciar un importe anterior |
| [Recibo](../../../app/Services/ReciboService.php) | Total y total liquidado toman `total_calculado`; detalle conserva el cálculo | Mostrar monto pagado y explicar ajuste sin falsear la suma del detalle original |
| [Pendientes](../../../app/Observers/HistorialReporteObserver.php) | Saldo pendiente cerrado se registra a partir de `total_calculado` | Registrar diferencia del monto final desde el momento del ajuste, sin alterar cortes anteriores |
| [Historial analítico](../../../app/Services/HistorialAnaliticoReportesService.php) | Preparado para guardar `calculado_centavos`; sin pruebas ejecutadas | Guardar cálculo y monto final por separado; observer debe detectar ajustes |
| [Resumen](../../../app/Services/LiquidacionService.php) y [aviso diario](../../../app/Services/AvisoAdminService.php) | Sumas por período y pendientes usan `total_calculado` | Unificar importe final con pago, recibo y pantallas |

No alcanza con desbloquear el campo del modelo: dejar esos consumidores con el
cálculo anterior produciría importes distintos para una misma liquidación.
En ese corte no se había habilitado excepción ni ruta nueva de ajustes.

Revisión independiente de fuente y enlaces conforme, 09/10, agente
`verificar_continuidad`. Sin ejecución, base ni certificación funcional.

## Entrega necesaria

1. Monto final separado; registro de cada cambio con importe anterior/nuevo,
   administrador y fecha. Ajuste y registro en una única transacción.
2. Validación ADMIN activo, estado pendiente y modalidad comisión en servidor;
   liquidación pagada/cancelada rechazada. Probar ajuste contra pago concurrente.
3. Pago, pendientes, recibo, resumen e historial coherentes. Los cortes anteriores
   al ajuste conservan el estado conocido; los meses sin respaldo, Sin historial.
4. Pantalla real con cálculo original, monto final y textos claros. Recuento de
   alumnos únicos con pagos separado de asistencias sin pago de cuota; un pago
   parcial no afirma que se canceló toda la cuota.
5. Pruebas en `wings_testing_codex`, capturas reales escritorio/marco375,
   control independiente de lógica y publicación autorizada en GitHub.

## Interrupción anterior — resuelta

La revisión automática rechazó el último comando: **workspace sin créditos**.
No se ejecutó; el rechazo no determinó que la acción fuese insegura. Ya quedó
preparado el historial con nueve pruebas pendientes. No se modificó en este corte
el circuito financiero sin poder probarlo. Carlos restableció los créditos; ejecución y pruebas retomadas. Ajuste implementado
y probado en base descartable. Capturas, suite y publicación se registran en la entrega.
B12/A23 abiertos; no despliegue.
