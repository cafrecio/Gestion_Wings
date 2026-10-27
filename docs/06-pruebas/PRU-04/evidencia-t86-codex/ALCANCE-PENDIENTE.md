# T86 — Diferencia pendiente de alcance

Comprobación de Codex CAB, 27/10/2026, después de la entrega `13c4ee9d`.

El objetivo pide comprobar que el sueldo operativo aparezca en Reportes de Sueldos e Inicio, y ordena «No toques vistas ni CSS». El pago por Cashflow, Ingresos/egresos e Inicio ya está implementado y probado. La parte de Reportes de Sueldos no está satisfecha.

## Por qué no alcanza con agregar una fila desde el servidor

La vista real `resources/views/reportes/sueldos.blade.php` dice:

- Línea 27: «Costo de profesores» y «Aunque se pague después».
- Línea 29: «Parte de las cuotas destinada a profesores».
- Línea 36: «Por profesor».
- Líneas 40–41: costo por asistencia y alumnos con pagos/asistencias.
- Línea 46: las cuotas se reparten entre profesores por asistencias.

`ReporteSueldosService::obtener`, líneas 154–168, construye costos por profesor y deporte y devuelve `profesores` y `deportes`. Inyectar una operativa en esas filas mostraría su sueldo bajo rótulos de profesor, clases y asistencia. Cambiar solo el total tampoco identificaría el pago y lo mezclaría con el costo docente. Ninguna de esas variantes cumple correctamente el pedido.

El [diagnóstico por rutas reales](observaciones.json) registró primero un egreso de $48.000,75 y luego consultó el reporte: la lista de profesores está vacía y su costo es cero en el escenario sin docentes. No se infiere la ausencia a partir de un agregado: también se leyó la lista devuelta, el servicio y los rótulos de la vista. Cobertura MCP no disponible/metadata_changed: se completó con lectura directa.

## Decisión que falta

1. Considerar la inclusión de operativas en este análisis como trabajo separado; T86 conserva su entrega de pago por Cashflow y su efecto financiero en Inicio e Ingresos/egresos.
2. Mantener esa inclusión dentro de T86 y resolver cómo presentarla, compatible con la prohibición actual de editar vistas. No se modificó la pantalla ni se trató a una operativa como profesora para evitar esa decisión.

Se consultó el alcance al usuario. La selección no se presupone por falta de respuesta. Los cambios de rol con vínculo antiguo siguen documentados aparte, sin adoptar una política nueva. T86 permanece `a_verificar` para Claude; este documento no la cierra ni cambia sus pruebas.
