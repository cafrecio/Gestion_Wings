# Resumen de arranque — Wings

> Corte documental: 13/09/2026. Actualizado por Codex CAB para FIN-11.
> Orienta a los tres agentes; no reemplaza verificar código, base o servidor.
> [Protocolo común](PROTOCOLO-CONTINUIDAD.md) · [Plan compacto](../07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md)

## Lectura y coordinación

- Leer este resumen y los tres logs activos; después, solo la tarea elegida.
- [Codex](LOG-CODEX.md) · [Claude](LOG-CLAUDE.md) · [Gemini](LOG-GEMINI.md).
- Cada agente escribe su bitácora. Actualizar este resumen sin pisar aportes simultáneos.
- Las evaluaciones del 8/9 son históricas: no tratarlas como defectos actuales.
- No leer todo el histórico para ponerse al día; buscar ID y fragmento relevante.
- No ejecutar tareas por aparecer aquí: respetar el pedido actual de Carlos.

## Últimos resultados documentados (fecha y alcance por fila)

| Tema | Corte y alcance |
|---|---|
| COB-05, COB-09, FIN-02 | Verificadas el 11/09 sobre e921e5d; 15 cobros en navegador. [Evidencia](../06-pruebas/COB-05-CIERRE-2026-09-11.md) |
| FIN-03 | Implementada en c1bef8a: detalle documental de nuevas anulaciones; contrato Recibos V2. No reconstruye imputaciones antiguas borradas. Pendiente revisión cruzada y despliegue según pase de Codex |
| Suite | Reejecutada por Codex el 13/09 en wings_testing: 187 pasan / 1206 aserciones; incluye FIN-11 y SEG |
| FDS-04 | Roles de Cobranza y protección de catálogos verificadas 11/09. [Evidencia](../06-pruebas/FDS-04-2026-09-11.md) |
| FIN-01 | No aplica por decisión de Carlos 11/09; no ejecutar seeders contra datos existentes |
| FIN-05 | Claude registra corrección de pago concurrente y prueba con dos conexiones; consultar criterio antes de dar por cerrada toda la concurrencia |
| SEG-01 | Retiro de Axios y audit/build verificados al 11/09; no afirma estado actual de dependencias locales |
| FDS-02 / alertas | Cierre documentado 09/09 con recepción de email y Telegram; no revalidado hoy |
| Servidor | Último despliegue documentado 81f27ef. Las correcciones posteriores no se dan por desplegadas |
| Base del club | Carga humana en curso según Carlos; no suponer base vacía ni limpiar. Datos no inspeccionados hoy |

## Trabajo que continúa

- FIN-11: pausa levantada el 13/09; seis cruces corregidos y probados, suite completa
  verde (187/1206). Sin deploy. Recálculo/cierre de liquidaciones sigue pendiente separado.

- FIN-07 implementada y probada 12/09: solo ADMIN condona saldo pendiente, sin borrar
  pago ni imputaciones; concurrencia real en ambos órdenes. Sin despliegue.

- FIN-03: verificar trabajo y criterio cruzado; no rehacer la implementación.
- FIN-04: significado de Balance pendiente de Carlos.
- FIN-06 a FIN-11: consultar estado/criterios individuales del plan; no asumir resueltos.
- SEG: sesiones, política de clave, preflight, errores, restore integral, CI y CSP según plan.
- PRU: recorrido integral, cambio de mes y gate aún requieren aceptación.
- FDS-03 está PAUSADA por Carlos: el estado mínimo de entrega es histórico.
- ENT-02: Gemini dejó diseño de recibos; Claude registra aprobación visual de Vanina.
  [Instructivo](../03-diseno-ui/INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md).
  La aprobación visual no resuelve reglas de liquidación ni autoriza cambios contables.
- ENT-03: CERRADO 12/09. Recurso de patín artístico con alas zoom 95% aprobado por Carlos e implementado en public/ y ds-app.blade.php.
- Antes de cambiar de máquina, comprobar qué está realmente versionado/subido.

## Decisiones pendientes que deben viajar entre sesiones

- Profesores llamados “por hora”: definir por clase o duración antes de implementar el recibo.
- Significado de pagos.monto_base con seña o varios períodos.
- Red de unicidad para egresos de liquidación: propuesta documentada, no decisión ejecutada.
- Balance filtrado; revisiones con parciales/historia; límites de fechas manuales.
- DEUDOR sin pagos y sin saldo; descuento de primer pago para importados.
- Tratamiento contable de inscripción; alcance de arqueo.

## Decisiones que se conservan

- API apagada; recibos no fiscales; exportables fuera de la versión inicial.
- OPERATIVO trabaja sobre todo su dominio, no solo registros propios.
- Liquidaciones cerradas no se reabren; su corrección es compensatoria.
- Sueldos por persona/deporte; no unificar subrubros por “limpieza”.
- Carga del club humana; no seeders ni pruebas destructivas en base real.
- Diseño protegido; autorización concreta y lecturas de diseño antes de tocar vistas.
- CSP gradual: nunca pasar de reporte a bloqueo en un solo paso.
- Toda importación real requiere archivo definitivo y alcance expresamente autorizado.

## Diferencias documentales que no se resuelven por inferencia

- El cierre de Claude conserva COB-09/FIN-02 pendientes, pero hay verificación posterior
  en el reporte COB-05 del 11/09 y el estado. Leer ese reporte, no repetir la pausa.
- ESTADO-ACTUAL conserva un párrafo histórico de COB-03 “no mergeada” que contradice
  su resumen de integración. No usar ese párrafo para ordenar una corrección nueva.
- El plan ENT-02 menciona logo/paleta por recibir; el pase de Gemini y Claude registra
  diseño aprobado. La implementación sigue condicionada por hora/clase y datos del recibo.
- Estas diferencias se señalan para no perderlas; esta tarea no decide reglas de negocio.

## Al terminar

- Entrada breve en log propio y enlace a evidencia.
- Actualizar aquí solo cambios de estado, decisiones o bloqueos relevantes.
- Mantener fecha y alcance de toda verificación. No copiar mensajes enteros.
- [Histórico íntegro e índice](../99-archivo/bitacoras/2026-09-12/INDICE.md).
