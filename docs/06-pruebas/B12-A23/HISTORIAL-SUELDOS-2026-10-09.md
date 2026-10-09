# Sueldos — historia monetaria implementada y probada

09/10/2026, Codex CyE. Carlos: **«Sí, guardar desde ahora»**.
Migración y nueve pruebas ejecutadas únicamente en wings_testing_codex: **9/71**,108,70s.
No se migró ni activó en la base del club. La interrupción por créditos quedó resuelta.

## Alcance

- Estados conocidos de CUOTA neta, TARIFA, PAGO y LIQUIDACION; cálculo/final separados.
- Cobrar no reduce cuota devengada; condonar conserva lo ya pagado.
- Cuota/deporte congelados desde primera observación; cambio de tarifa/anulación/baja
  conserva estados anteriores. JSON ordenado evita duplicados equivalentes.
- Último estado por objeto al corte; fechas no retroceden por cambios del reloj.
- Escritura/baja y registro atómicos; Deshacer P1 registra cuotas antes del CASCADE.
- No nombres, DNI ni contactos en la nueva tabla; tampoco CASCADE del historial.
- Captura inicial desde activar: antes, Sin historial. No reconstruye pasado real.
- Clases/asistencias no tienen snapshots: estimados y reparto usan registros actuales.
  Corregir horarios/presencias puede cambiar esos indicadores. Liquidación cerrada
  y estados monetarios conservan su importe conocido al corte.

## Activación

Primera activación exige Wings en mantenimiento y detener CLI, workers y escrituras
externas; la bandera de mantenimiento sola no los detiene. Guardia previa al DDL y
al iniciar, salvo cuatro bases descartables. No hubo activación ni despliegue.

## Evidencia

[Pruebas](../../../tests/Feature/HistorialAnaliticoReportesTest.php): neto/condonación,
tarifas/modalidad, anulación, liquidación, bajas/cobertura, rollback, captura inicial,
ficha vieja/reloj y JSON reordenado. Sintaxis y vistas compiladas correctamente.
[Servicio](../../../app/Services/HistorialAnaliticoReportesService.php) ·
[Observer](../../../app/Observers/HistorialAnaliticoReportesObserver.php) ·
[Migración](../../../database/migrations/2026_10_09_180000_create_historial_analitico_reportes.php).

[Entrega Sueldos/ajuste y controles](IMPLEMENTACION-SUELDOS-2026-10-09.md).
Capturas con historia ficticia desde abril solo en la base descartable; no certifican
historia del club ni habilitan rellenar datos anteriores sin respaldo. B12/A23 abiertos.
