# Resumen de arranque — Wings

> Actualización de verificación A13/B1/A54/A55 y preparación A25: 06/10/2026, Codex CAB; los cortes históricos conservan su fecha.
> Orienta a los tres agentes; no reemplaza verificar código, base o servidor.
> [Protocolo común](PROTOCOLO-CONTINUIDAD.md) · [Plan compacto](../07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md)

## Lectura y coordinación

- Leer este resumen y los tres logs activos; después, solo la tarea elegida.
- [Codex](LOG-CODEX.md) · [Claude](LOG-CLAUDE.md) · [Gemini](LOG-GEMINI.md).
- Cada agente escribe su bitácora. Actualizar este resumen sin pisar aportes simultáneos.
- Las evaluaciones del 8/9 son históricas: no tratarlas como defectos actuales.
- No leer todo el histórico para ponerse al día; buscar ID y fragmento relevante.
- No ejecutar tareas por aparecer aquí: respetar el pedido actual de Carlos.
- **Desde el 17/09, para analizar o buscar en el repo usar primero `codebase-memory-mcp`**
  (AGENTS.md §6e): pista, no hecho; reindexar tras pull; se instala por máquina. CyE: sí.

## Últimos resultados documentados (fecha y alcance por fila)

| Tema | Corte y alcance |
|---|---|
| COB-05, COB-09, FIN-02 | Verificadas el 11/09 sobre e921e5d; 15 cobros en navegador. [Evidencia](../06-pruebas/COB-05-CIERRE-2026-09-11.md) |
| FIN-03 | Implementada en c1bef8a: detalle documental de nuevas anulaciones; contrato Recibos V2. No reconstruye imputaciones antiguas borradas. Pendiente revisión cruzada y despliegue según pase de Codex |
| Suite | **444 pruebas: 438 aprobadas y 1 omitida, 2968 aserciones** el 05/10 en wings_testing_codex, con A4/A5 y A37 de Gemini (3062f95). [Entrega y alcance](../06-pruebas/PRU-02/IMPLEMENTACION-A4-A5.md). Sin despliegue |
| FDS-04 | Roles de Cobranza y protección de catálogos verificadas 11/09. [Evidencia](../06-pruebas/FDS-04-2026-09-11.md) |
| FIN-01 | No aplica por decisión de Carlos 11/09; no ejecutar seeders contra datos existentes |
| FIN-05 | Claude registra corrección de pago concurrente y prueba con dos conexiones; consultar criterio antes de dar por cerrada toda la concurrencia |
| SEG-01 | Retiro de Axios y audit/build verificados al 11/09; no afirma estado actual de dependencias locales |
| FDS-02 / alertas | Cierre documentado 09/09 con recepción de email y Telegram; no revalidado hoy |
| Servidor | Último despliegue documentado 81f27ef. Las correcciones posteriores no se dan por desplegadas |
| Base del club | Carlos informó el 05/10: cero alumnos, deudas y pagos; usuarios/catálogos existentes se conservan. No inspeccionada ni modificada por Codex en P1. No limpiar ni cargar datos reales sin autorización |

## Trabajo que continúa

- **A4/A5 entregados por Codex CAB el 05/10:** aviso superior en alta/edición, conservación de datos y advertencia al salir; profesores activos del deporte de la clase con rechazo servidor. Carlos aprobó las capturas y pidió **Hecho (Cx), a revisar**, no cerrado. [Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-A4-A5.md). Otro agente verifica; sin despliegue.
  Retoque pedido después sobre el motivo de ingreso: alineado dentro de la grilla y con ícono existente, sin CSS; capturas nuevas listas, Carlos aprobó las capturas con ícono el 05/10; autorizado el commit/push.
  Control posterior registrado 06/10: **A54 cerrado por Codex; A13/B1/A55 abiertos**.
  A55: dos deportes muestran distinta inscripción entre selector y ficha. A13/B1:
  reversión financiera correcta, historial anulado y signo del contraasiento en pantalla fallan.
  Cierre operativo comprobado por servicio, no por pantalla. Claude corrige.
  [Informe y evidencia](../06-pruebas/PRU-02/VERIFICACION-A13-A54.md). Ambos seguimientos:
  26 cerrados de 71; sin suite completa nueva ni deploy. Publicado en `ddefe00`:
  push recibido en main y pull al día, comprobados el 06/10; cambios ajenos quedaron fuera.

- **A25, preparado 06/10, no implementado:** inicio y arqueo inexistentes. Carlos definió
  cajón compartido, herencia confirmada, primer importe declarado, corrección con motivo,
  un turno abierto, cambio retenido elegible y cierre con diferencia para revisión ADMIN.
  ADMIN configura el medio físico una vez, guarda aparte sus cobros y cuenta/cierra antes de validar.
  [Decisiones](../05-pendientes/A25-CAMBIO-INICIAL-CAJA.md). Ambos seguimientos: Falta; siguiente implementación y capturas reales.

- **A43 y A29/A30/A31 verificados y cerrados por Gemini el 05/10:** autorización literal de Carlos y «A43 solo la cuota».
  Ingreso cerrado elige cuota corriente completa o sin cuota; inscripción independiente.
  Acceso restringido usa aviso común y Volver al inicio del rol sin exponer datos ni redirigir al login.
  Código, navegador normal/375 px y base de datos comprobados; suite 380/2242 verde.
  [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A43-PERMISOS.md). Sin despliegue.

- **P2 Entrega 2 implementada por Gemini el 05/10 (A17, A34, A3):**
  Ficha del alumno con botón principal Cobrar y botón de fila en cuotas pendientes hacia `/caja/cobrar/{id}`;
  historial con enlace directo a Recibo (PDF con descarga e impresión); cobro adelantado de períodos futuros
  al precio vigente del plan con badge «Adelantado», omitido sin duplicar por `cobranza:generar-deudas` el día 1;
  búsqueda en caja para cualquier alumno activo. 8 pruebas / 40 aserciones verdes en `wings_testing_gemini`.
  Pendiente de control cruzado (§6a). Sin despliegue.

- **P2 Entrega 1 aprobada por Codex CAB, segunda vuelta 05/10:** `abc346a`
  corrige A51/A52; deuda por registro, ayuda y estado solo por cuotas comprobados.
  Barras de filtros a 375 revisadas; suite propia **380/2242**. A53–A55 surgieron
  fuera de esta entrega; al 06/10 A54 cerrado y A53/A55 abiertos. Sin despliegue; Gemini puede
  continuar Entrega 2.
  [Verificación](../06-pruebas/PRU-02/VERIFICACION-ENTREGA1.md).
  **A11: Configuración entregada**, con maqueta y línea Diseno-autorizado escritas
  por Carlos. Nombres humanos, grupos, validación y errores persistentes;
  generación mensual fija. [Evidencia](../06-pruebas/PRU-02/IMPLEMENTACION-A11.md).
  Gemini registró A11 aprobada el 04/10 en [su informe](../06-pruebas/PRU-02/VERIFICACION-A11.md); sin despliegue.

- **P0 del 04/10 implementado en `ad24769`:** cuota del mes real de ingreso; A43 verificada por Gemini añade elección para mes cerrado. Inscripción manual por DNI, sin corte; cargos/pagos preservados. **P1 aprobada e implementada el 05/10:** Alumnos con 12 pares, plantilla vacía y catálogos reales, revisión sin escritura con informe Excel, carga transaccional y Deshacer protegido. Estado persistente, entrada ADMIN y bloqueo servidor. [Entrega y capturas](../06-pruebas/PRU-02/P1-IMPLEMENTACION-2026-10-05.md). Gemini verifica; no cerrar ni desplegar. Importadores antiguos conservados hasta aceptar P1; retirar en commit aparte. No limpiar el sitio de prueba ni producción en esta entrega.

- **ENT-01:** inscripción primero, sin comisión, desglose caja/recibo; importe obligatorio. Editar ingreso sin pagos conserva cargo con auditoría; con pagos rechaza. [Regla y ENT-10 pendiente](../05-pendientes/ENT-01-INSCRIPCION-Y-PRIMERA-CARGA.md). Sin despliegue en esta tarea.

- Reportes: gastos generales separados como «Gastos del club», sin reparto ni descuento del resultado por deporte; sí del total del negocio (22/09). [Decisiones de la encuesta](../05-pendientes/ENCUESTA-REPORTES-PROVISIONAL.md). La entrevista derivó en POS-07: [plan de canchas y liquidaciones v2026-09-21](../07-evaluacion/PLAN-CANCHAS-LIQUIDACIONES-v2026-09-21.md), ocho etapas y pruebas. Costos solo ADMIN; mes o fechas elegidas, únicamente clases dictadas y pendientes de liquidar, sin duplicarlas. Plan documentado; sin implementación ni despliegue. SaaS futuro fuera del alcance; particulares en rama separada.

- FIN-12: Carlos prioriza para hoy (13/09) cancelar cerradas no pagadas, solo ADMIN. [Instrucciones](../05-pendientes/FIN-12-CANCELAR-LIQUIDACION-CERRADA.md). PENDIENTE; se adelanta de POS-06 sin implementar particulares.

- POS-06: [contrato de particulares](../02-contratos/Wings-Contrato-Clases-Particulares-V1.md) documentado el 13/09; implementación PENDIENTE. [Ficha y siguiente paso: Reportes](../05-pendientes/CLASES-PARTICULARES.md).
- FIN-10: revisión visual completada en Chrome el 13/09 sobre base descartable;
  error de motivo y guardado comprobados. Sin deploy.

- FIN-10 implementada y probada 13/09: edición atómica y motivo de horario pasado;
  sin deploy. Falta aplicar migración motivo_cambio_horario y revisión cruzada.

- FIN-11: pausa levantada el 13/09; seis cruces corregidos y probados, suite completa
  verde (187/1206). Sin deploy. Recálculo/cierre de liquidaciones sigue pendiente separado.

- FIN-07 implementada y probada 12/09: solo ADMIN condona saldo pendiente, sin borrar
  pago ni imputaciones; concurrencia real en ambos órdenes. Sin despliegue.

- FIN-03: verificar trabajo y criterio cruzado; no rehacer la implementación.
- FIN-04: definición cerrada 22/09 en el contrato de Reportes. Saldo, resultado y proyecciones separados; gastos del club fuera de cada deporte, dentro del total. Implementación POS-01 pendiente.
- FIN-06 a FIN-11: consultar estado/criterios individuales del plan; no asumir resueltos.
- SEG: sesiones, política de clave, preflight, errores, restore integral, CI y CSP según plan.
- PRU: recorrido integral, cambio de mes y gate aún requieren aceptación.
- FDS-03 está PAUSADA por Carlos: el estado mínimo de entrega es histórico.
- ENT-02: Gemini dejó diseño de recibos; Claude registra aprobación visual de Vanina.
  [Instructivo](../03-diseno-ui/INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md).
  La aprobación visual no resuelve reglas de liquidación ni autoriza cambios contables.
- ENT-03: CERRADO 12/09. Recurso de patín artístico con alas zoom 95% aprobado por Carlos e implementado en public/ y ds-app.blade.php.
- ENT-05: CERRADO 13/09. Recibo accesible a un clic en confirmación de cobro (ds-flash) y en cada fila del historial de pagos en la ficha del alumno.
- SEG-11: 14 onclick migrados a ds-app.js (MANEJADORES_PERMITIDOS bajó a 10). Validador en vivo unificado para niveles y tipos-caja. Scripts de grupos y usuarios migrados a archivos dedicados (resources/js/grupos.js y usuarios.js) compilados por Vite y cargados vía @vite conforme a DESIGN-RULES.md §8 (BLOQUES_SCRIPT_PERMITIDOS bajó a 22). Formateo de precios vía window.initMoneyInput probado; suite verde en 222/1347; diseño intacto.
- FIN-06: CERRADO 13/09. Comisión histórica en liquidaciones; filtros de estado actual (activo, deporte) eliminados en cálculo de comisión; porcentaje congelado en tabla liquidaciones al generar; fallback a comisión actual si null; vista show lo muestra sin tocar Blade. 7 pruebas nuevas pasan. Suite en 229/1372.
- Antes de cambiar de máquina, comprobar qué está realmente versionado/subido.

## Decisiones pendientes que deben viajar entre sesiones

- Profesores “por hora”: CERRADO 17/09, se paga por duración (FIN-13). Pantalla y recibo autorizados muestran "1 h 20 min" y centavos. Sin deploy.
- Significado de pagos.monto_base con seña o varios períodos.
- Red de unicidad para egresos de liquidación: propuesta documentada, no decisión ejecutada.
- Balance filtrado definido 22/09 (FIN-04); implementación de Reportes pendiente. Revisiones y límites de fechas: consultar estados individuales del plan.
- A2/B2: Carlos define DEUDOR solo por deuda de mes cerrado y cuota creada en el alta.
  Implementado localmente con importe congelado, pendiente de verificación independiente
  y despliegue. Descuento sobre deudas históricas importadas conserva su pendiente separado.
- A25: reglas de apertura/cierre respondidas el 06/10. [Detalle](../05-pendientes/A25-CAMBIO-INICIAL-CAJA.md). Pendiente implementación.
- Inscripción: tratamiento aprobado e implementado.

## Decisiones que se conservan

- API apagada; recibos no fiscales; exportables fuera de la versión inicial.
- OPERATIVO trabaja sobre todo su dominio, no solo registros propios.
- Enmienda 13/09 (POS-06), pendiente: ADMIN cancela cerrada no pagada para revisar asistencia; pagada intacta y ajuste posterior. Bloqueo de asistencia para todos.
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
  diseño aprobado. Hora/clase quedó decidida el 17/09 (FIN-13).
- Estas diferencias se señalan para no perderlas; esta tarea no decide reglas de negocio.

## Al terminar

- Entrada breve en log propio y enlace a evidencia.
- Actualizar aquí solo cambios de estado, decisiones o bloqueos relevantes.
- Mantener fecha y alcance de toda verificación. No copiar mensajes enteros.
- [Histórico íntegro e índice](../99-archivo/bitacoras/2026-09-12/INDICE.md).
