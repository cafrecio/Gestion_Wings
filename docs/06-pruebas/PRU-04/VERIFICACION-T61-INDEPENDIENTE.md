# T61 — Verificación independiente de código

20/10/2026 · Codex CyE, subagente revisor independiente de la integración.

**Dictamen: revisión de código aprobada, sin hallazgos bloqueantes en el alcance revisado.**
No certifica ejecución de pruebas, recorrido de navegador, aprobación visual de Carlos,
publicación ni despliegue. Esos controles los lleva el agente integrador por separado.

## Corte y método

- Checkout `C:/xampp/htdocs/Gestion_Wings`, máquina `VENTAS_CYE`, rama `main`.
- HEAD observado `c2721d8d`; integración de `origin/codex/t61-traspasos` en curso.
- Se comparó el diff de T61 contra main y se leyeron los archivos actuales completos
  de clasificación, migración, controladores Cashflow/Subrubro, servicio mensual,
  servicio de corrección, observador de movimientos y las 12 pruebas T61.
- El agente integrador aportó la exploración del índice `C-xampp-htdocs-Gestion_Wings`,
  generación `2026-10-20T10:40:40Z`, con `coverage_unavailable / metadata_changed`.
  Esta revisión usó archivos reales y búsquedas de texto; no dedujo ausencia de fallos
  ni exhaustividad del índice. No se ejecutaron suites para evitar solapamiento.

## Comprobaciones

| Requisito | Evidencia directa y resultado |
|---|---|
| Dos cargas independientes | `CashflowService::registrarMovimientoAdmin` crea un movimiento común; el signo sale del tipo del rubro. T61 no añade entidad, vínculo o validación de igualdad. Las pruebas incluyen pareja 300.000/300.000, meses distintos y 300.000/290.000. |
| Clasificación en ambos tipos | `ClasificacionSubrubros::permitidas` admite TRASPASO en INGRESO/EGRESO y conserva APORTE solo en ingreso y RETIRO solo en egreso. Subrubro y Rubro validan mediante esa misma función. |
| Catálogo e idempotencia | `aplicarTraspasos` usa transacción, crea los dos nombres convenidos con ADMIN/activo/afecta caja, evita duplicar un subrubro existente y no pisa una clasificación previa. Completa NULL solo con tipo compatible. Rechaza rubro existente con tipo incompatible. La migración y el seeder llaman al mismo método; down conserva historia. |
| Permisos operativos | Rutas Cashflow dentro de `ensure.admin.web`; `CajaWebController::cargarRubros` filtra OPERATIVO y `CajaService::validarSubrubroManual` lo vuelve a exigir en servidor. Los dos conceptos nuevos ADMIN no pasan ese control. La prueba cubre GET y POST directos de ambos conceptos. |
| Resultado del club | `ReporteMensualService::obtener` usa igualdad exacta NEGOCIO para ingresos/egresos y gastos; APORTE/RETIRO tienen filtros propios y desconocido exige NULL. TRASPASO no entra en esos indicadores. `reportes/index` desglosa solo NEGOCIO. Inicio y Reportes reciben ese mismo servicio. |
| Global y saldos | Global suma todos los centavos del mes; cajas suma todos los movimientos firmados hasta el corte y agrega iniciales. La prueba verifica septiembre negativo/octubre positivo y conservación del resultado económico. Cashflow conserva totales de entradas/salidas y etiqueta Traspaso. |
| Saldos ajenos a filtros | Cashflow pide `obtener(today()->format('Y-m'))['cajas']` sin pasar filtros del listado. La vista separa saldo actual del bloque de totales del período y muestra confirmado/pendiente cuando este último es distinto de cero. La prueba consulta septiembre con movimientos de octubre. |
| Foto histórica | `MovimientoReporteObserver::creating` copia clasificación y respeta valores explícitos. Updating solo reclasifica al cambiar subrubro. La edición del mismo concepto conserva la foto; `CashflowCorreccionService` anula con fecha/medio/clasificación/tipo del original. Se leyeron pruebas de edición/anulación y de cambio posterior del catálogo. |
| Saldo inicial | La modificación de `SaldoInicialTipoCajaTest` mantiene la exclusión del capital inicial del bloque de resultado del período mediante consulta DOM; permite el saldo inicial en su tarjeta nueva. No se suprime la comprobación económica anterior. |
| Ayuda diferida a T13 | V5 explicita ese límite; `IMPLEMENTACION-T61.md`, sección «Para la ayuda», contiene los dos pasos y ejemplo. La prueba valida esa sección, sin certificar una pantalla de ayuda que todavía no existe. |
| T52 conservada | HEAD contiene `2dfbf243`. `ClaseWebController::show` conserva alumnos con marca previa; `storeAsistencias` acepta esa marca para corregir. Controlador, vista de clase y archivo de cinco pruebas T52 no presentan diff respecto de HEAD durante esta revisión. No se certifica su ejecución aquí. |

## Cobertura leída de las pruebas

Los 12 métodos de `TraspasosEntreMediosT61Test.php` verifican catálogo, caso económico
con valores no nulos, Inicio/Reportes/Sueldos/Alumnos, cruce de meses, cargas desparejas,
permisos, validación de clasificación, repetición de migración, vacío compatible,
edición/anulación, reclasificación posterior y saldo actual con operativos pendientes.
Se inspeccionaron sus aserciones; el resultado de ejecución conjunta debe adjuntarse
desde la corrida del integrador y no se hereda de la entrega CAB.

La revisión de código queda entregada. T61 conserva pendientes el control conjunto,
recorrido real y aprobación visual; este informe no cierra el tablero.
