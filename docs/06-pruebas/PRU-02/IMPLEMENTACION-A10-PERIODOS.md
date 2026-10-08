# A10 — Períodos y acciones de Cashflow

08/10/2026 · Codex CyE. **CERRADO, verificado Claude.** Diseño aprobado por Carlos: «Me gusta, A10 Aprobado».

## Aplicado y comprobado

- Día por fecha; Semana lunes–domingo que la contiene; Mes/Año completos. Fechas inclusivas, cruces de mes/año sin recorte.
- Filas y totales usan el mismo intervalo y caja; Tipo afecta las filas y conserva ambos totales.
- Enlaces anteriores Año/Mes compatibles; sin parámetros, año actual completo.
- Fecha conservada al alternar modos; día limitado al último válido del mes/año.
- Resultado del período = ingresos − egresos, sin saldo inicial. Saldo disponible de Caja/Liquidaciones intacto; saldo acumulado de Reportes fuera de esta tarea.
- Nuevo junto al contador; destino Nuevo movimiento. Limpiar elimina Caja/Tipo conservando período y fecha.

## Corrección tras revisión independiente

Claude detectó que el enlace de Limpiar se escapaba dos veces y volvía a hoy. Se corrigió el parámetro ligado :href="route(...)", sin cambio visual. La nueva regresión DOM lee el href como Chrome: falló antes con amp;anio/amp;fecha/amp;mes y pasó después en los cuatro modos. Las pruebas HTTP solas ocultaban el defecto al decodificar otra vez.

Claude volvió a pulsar Limpiar en los cuatro modos con fechas lejanas y caja/tipo: período, fecha, filas y totales conservados; filtros vacíos. **45 comprobaciones, 0 fallos**. [Verificación independiente y antecedente negativo](VERIFICACION-A10.md).

## Evidencia final

Diez A10: **10 aprobadas / 110 aserciones**, 7,78 s. Suite propia completa: **508 aprobadas / 2 omitidas**, 4077 aserciones, 423,14 s; 510 pruebas. [Salida](evidencia/a6-a10/suite-a10-limpiar-2026-10-08.txt).

Claude: matriz propia de 432 combinaciones/442 pedidos; segunda corrida 19 aprobadas/6819 aserciones, 46,74 s, y 18 capturas del navegador. Fuente del controlador sin cambios entre ambas revisiones; negativo archivado con huellas.

24 capturas actuales de Laravel real después de corregir: cuatro modos, cruces de mes/año, selección Día → Semana y Nuevo hasta el formulario completo. Mismos filtros/rangos/filas que la propuesta; seis íconos y acciones visibles; login dentro de marco375. [Visor aplicado](evidencia/a6-a10/visor-a10-aplicado.html) · [Huellas](evidencia/a6-a10/control-a10-aplicado.json).

Sintaxis y Blade correctos; build final 38,28 s. Escenario ficticio Codex 4/15 verde, 8,72 s. Controles documentales 6/28 verdes. Servidores y Chrome detenidos al entregar; base del club no usada. Sin despliegue.

[Entrega del paquete](IMPLEMENTACION-A6-A10.md) · [Antecedente íntegro](../../99-archivo/pruebas/2026-10-08/IMPLEMENTACION-A10-PERIODOS-ANTES-CIERRE.md.txt).
