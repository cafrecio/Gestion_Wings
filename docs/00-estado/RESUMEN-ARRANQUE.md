# Resumen de arranque — Wings

> Cierre A6–A10: 08/10/2026, Codex CyE; aspecto aprobado por Carlos y lógica A10 verificada por Claude. A32 cerrado: candado y nombres/rol aprobados por Carlos. Los cortes históricos conservan su fecha.
> Orienta a los tres agentes; no reemplaza verificar código, base o servidor.
> [Protocolo común](PROTOCOLO-CONTINUIDAD.md) · [Plan compacto](../07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md)

## Lectura y coordinación

- Leer este resumen y los tres logs activos; después, solo la tarea elegida.
- [Codex](LOG-CODEX.md) · [Claude](LOG-CLAUDE.md) · [Gemini](LOG-GEMINI.md).
- Cada agente escribe su bitácora. Actualizar este resumen sin pisar aportes simultáneos.
- Las evaluaciones del 8/9 son históricas: no tratarlas como defectos actuales.
- No leer todo el histórico para ponerse al día; buscar ID y fragmento relevante.
- No ejecutar tareas por aparecer aquí: respetar el pedido actual de Carlos.
- **Desde el 17/09, para analizar o buscar en el repo usar primero `codebase-memory-mcp`** (AGENTS.md §6e): pista, no hecho; reindexar tras pull; se instala por máquina. CyE: sí.

## Últimos resultados documentados (fecha y alcance por fila)

A6–A10 cerrados 08/10: Carlos verifica aspecto A6–A9/A10; Claude verifica lógica A10 en segunda revisión. Limpiar corregido y pulsado en los cuatro modos. Suite final Codex 508/2, 4077 aserciones, 423,14 s; 510 pruebas. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md) · [Verificación independiente](../06-pruebas/PRU-02/VERIFICACION-A10.md). A32 cerrado, candado y retoque aprobados; [entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A32.md). Sin despliegue.

| Tema | Corte y alcance |
|---|---|
| A12/A24 | IMPLEMENTADAS 08/10, Gemini CyE ([informe](../06-pruebas/PRU-02/IMPLEMENTACION-A12-A24.md)). Carlos autorizó Opción 2 Variante B. Vista y controlador actualizados: estado del cajón primero, botón Abrir a /caja/apertura, reconocimiento de turno compartido, tareas diarias en 2 columnas con scroll interno de clases a 165px para nivelar alturas, recaudación al pie. Suite verde (Cobranza, Revision, CajaCambioInicial, CSP). Pasadas a_verificar en tablero |
| A15/A16 | CERRADOS 07/10, verificado Gemini y contrastado por Claude ([informe](../06-pruebas/PRU-02/VERIFICACION-A15-A16.md)). Aviso/confirmación y horarios por día; 76 clases con seis cargas, rollback completo. Diseño aprobado por Carlos con capturas reales. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A15-A16.md); otro agente verifica, sin deploy |
| COB-05, COB-09, FIN-02 | Verificadas el 11/09 sobre e921e5d; 15 cobros en navegador. [Evidencia](../06-pruebas/COB-05-CIERRE-2026-09-11.md) |
| FIN-03 | Implementada en c1bef8a: detalle documental de nuevas anulaciones; contrato Recibos V2. No reconstruye imputaciones antiguas borradas. Pendiente revisión cruzada y despliegue según pase de Codex |
| Suite | **613 pruebas**: 611 aprobadas/2 omitidas; Claude CyE, 10/10, wings_testing_claude. T15 y T17 cerrados; T16, T18 y T19 hechos. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-T15.md). Sin deploy |
| FDS-04 | Roles de Cobranza y protección de catálogos verificadas 11/09. [Evidencia](../06-pruebas/FDS-04-2026-09-11.md) |
| FIN-01 | No aplica por decisión de Carlos 11/09; no ejecutar seeders contra datos existentes |
| FIN-05 | Claude registra corrección de pago concurrente y prueba con dos conexiones; consultar criterio antes de dar por cerrada toda la concurrencia |
| SEG-01 | Retiro de Axios y audit/build verificados al 11/09; no afirma estado actual de dependencias locales |
| FDS-02 / alertas | Cierre documentado 09/09 con recepción de email y Telegram; no revalidado hoy |
| Servidor | **Sitio de prueba:** T16 consultado en Chrome el 10/10: Inicio/Reportes $164.000, sin aviso de sin clasificar; [verificación](../06-pruebas/PRU-04/VERIFICACION-T16.md). Revisión desplegada no consultada por consola en esta tarea. Día 1 conservado: 100 activos, cuatro pagos por $164.000, deuda de Cobranza $2.010.000 y caja 1 validada sin diferencia; hallazgos históricos en T14. **Producción:** `314e485` (22/09), leído por consola el 06/10, con 7 migraciones sin correr; no se tocó. Nada posterior está en producción |
| Base del club | Carlos informó el 05/10: cero alumnos, deudas y pagos; usuarios/catálogos existentes se conservan. No inspeccionada ni modificada por Codex en P1. No limpiar ni cargar datos reales sin autorización |

## Trabajo que continúa

- **T16, segunda vuelta devuelta a Claude por Codex CyE, 10/10:** `fcdc8f2` corrige las dos fallas originales: 12 controles/225 aserciones aprobados; otros 8/269 aprobados. Un ensayo con dos pedidos simultáneos deja EGRESO + APORTE (1 fallo/10 aserciones): Claude corrige ese cruce y Codex verifica otra vez. Suite de copia fija `d4f18d6`: 611 aprobadas/2 omitidas, 5.087 aserciones, 785,65 s, base propia. Nombres nuevos en INGRESO quedan pendientes para el admin; DemoSeeder NULL, observación separada. Sin arreglo, visita al sitio ni despliegue en esta vuelta; aspecto aprobado por Carlos. [Informe](../06-pruebas/PRU-04/VERIFICACION-T16.md).

- **T14 Día 1, 10/10:** recorrido en test entregado por Codex, a verificar por Claude. Cuatro pagos por $164.000, caja 1 validada sin diferencia y asistencia de Mariela 6/2. Inicio no concilia (deuda $1.960.000 frente a $2.010.000 en Cobranza; ingresos $0 frente a $164.000 en Cashflow); Mariela abrió una clase de Lucía. No se investigó causa ni se arregló nada. [Informe único](../06-pruebas/PRU-04/DIA-01.md). Conservar los 100 alumnos y estos cobros; no repetir ni deshacer carga.


- **T15,10/10:** pantallas404/500/503/429 en castellano e Inicio en el menú. Carlos confirmó «Sí, conservar permisos y login». Suite594/2 verde. Errores graves sin sesión/base: conexión al puerto1 y mantenimiento real comprobados. Capturas1366/marco360; entrega lista, a_verificar por Claude, sin desplegar. [Informe](../06-pruebas/PRU-02/IMPLEMENTACION-T15.md). B10 ya figura cerrado en el tablero; A42 cerrado, no duplicar consulta inicial.
- **B12/A23, desarrollo 09/10:** [acuerdos y revisión de Carlos](../07-evaluacion/ACUERDOS-B12-A23-2026-10-09.md). Inicio aprobado («Ok, aprobado»), aplicado y verificado por HTTP independiente; cuatro avisos/listados y permisos comprobados. Suite completa 532 aprobadas/2 omitidas, 4286 aserciones; cuatro regresiones posteriores P1 aprobadas y después 16/108 del módulo. [Entrega](../06-pruebas/B12-A23/IMPLEMENTACION-INICIO-2026-10-09.md) · [capturas](../06-pruebas/B12-A23/visor.html). Reportes original rechazado; Carlos pidió más visual y menos lectura, con tres referencias. [V3](../06-pruebas/B12-A23/PROPUESTA-VISUAL-2026-10-09.md) aprobada por Carlos («Si, mucho mejor»). [Aplicación financiera](../06-pruebas/B12-A23/IMPLEMENTACION-REPORTES-2026-10-09.md): ruta mensual, filtros/detalles reales y regresiones; suite completa537/2,4369 aserciones,476,20s correcta. CuatroHTTP200 y ocho capturas reales; revisión independiente final aprobada para esta fase financiera. Alumnos aprobado por Carlos e integrado. Sueldos implementado y aprobado por Carlos, acceso Plata → Reportes aprobado por Carlos el 10/10 («Esta OK el acceso»): costo clases del mes, cuotas reales netas del mismo mes y cada asistencia; atribución proporcional aprobada, historia desde ahora y comisión sobre cuotas cobradas. Ver/indicadores de Inicio conectados al mismo mes y sus detalles. Falta clasificación de antecedentes. Base del club intacta por Codex; B12 abierto, A23 cerrado el 10/10 por Claude.
- **Alumnos/Sueldos, continuación09/10:** Carlos aprobó Alumnos («Esta OK»), integrado a ruta habitual. Sueldos y ajuste final de comisión implementados; ADMIN ajusta abiertas/cerradas sin pagar, con auditoría y bloqueo compartido con pago. Suite573/2,4615aserciones,997,45s; controles finales30/185,14,02s tras fixture portable. Independiente26/166,15,62s, lógica/concurrencia/recibo correctos. Créditos restablecidos;14HTTP200,30 capturas web y2 páginas PDF reales. Sueldos aprobado por Carlos («Esta OK, solo deberiamos ver desde donde accede en el menu»); acceso Plata → Reportes aprobado por Carlos el 10/10 («Esta OK el acceso») y aspecto del ajuste aprobado por Carlos («Sí, así»,10/10); [Monto ajustado e íconos](../06-pruebas/B12-A23/ETIQUETAS-AJUSTE-2026-10-10.md). GitHub main a132bfd comprobado. Acceso: nueva suite573/2,4616aserciones,885,32s; fuente y muestra12PNG verificadas por otro agente. [Entrega del acceso](../06-pruebas/B12-A23/ACCESO-REPORTES-2026-10-09.md). [Entrega Sueldos y ajuste final](../06-pruebas/B12-A23/IMPLEMENTACION-SUELDOS-2026-10-09.md). Antes de activar historia: mantenimiento y detener escrituras externas/CLI/workers. Horarios/asistencias siguen actuales y pueden cambiar estimados/reparto; importes cerrados conservados. B12 abierto; A23 cerrado el 10/10 por Claude ([verificación](../06-pruebas/B12-A23/VERIFICACION-A23-INICIO.md)). Acceso y aspecto del ajuste aprobados por separado; base del club sin cambios por Codex.
- **A6–A10, CERRADOS 08/10:** aspecto verifica Carlos; lógica A10 verifica Claude con 432 combinaciones/442 pedidos y 45 comprobaciones de navegador sin fallos. Regresión nueva de Limpiar, diez A10/110 aserciones; suite completa 508/2, 4077 aserciones, 423,14 s; build 38,28 s. Capturas finales renovadas; [entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md). A32 cerrado; Carlos aprobó también nombres y rol. Sin despliegue.
- **A14/A27/A53, CERRADOS 07/10 (verificado Gemini, contrastado Claude; [informe](../06-pruebas/PRU-02/VERIFICACION-A14-A27-A53.md)):** Carlos abrió el visor ANTES/DESPUÉS y dio el OK. Cartel A14 dinámico con JS externo, barra móvil en cinco formularios y tarjetas completas. 74 capturas reales; tres roles, 20 alumnos y precios grandes. Suite propia **489 aprobadas/2 omitidas, 3924 aserciones, 257,64 s**; build/vistas correctos. [Propuesta aprobada](../06-pruebas/PRU-02/PROPUESTA-A14-A27-A53.md) y [entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A14-A27-A53.md). **Cada defecto en a_verificar, hizo Codex, tiene nadie: falta asignar verificador.** Sin autocierre ni deploy. T1 conserva lo pendiente fuera de estas pantallas.
- **Celular, control independiente Codex CyE 06/10:** A20, A28, A33, A36, A40 y A41: CERRADOS, verificado Codex CyE 06/10. A14, A27 y A53: DEVUELTOS a Gemini; T1 también devuelto por cobertura incompleta y recortes. 84 capturas propias y 102 originales revisadas; roles ADMIN/OPERATIVO/PROFESOR, 20 alumnos, nombres largos y tarifas millonarias. [Informe](../06-pruebas/PRU-02/VERIFICACION-CELULAR-COMPARTIDO.md). Corte versionado 41/72; sin despliegue. Gemini incluyó esta entrega en d91a840 junto con A25; Claude reabrió A25 en abda923. Se conservan los dictámenes de celular y A25 sigue a revisar.
- **A13/B1/A55 CERRADOS por Codex CyE, 06/10:** rama integrada entera en main `6d3f68a`; texto aprobado y capturas reales renovadas. Historial, contraasientos, inscripción única y separación ADMIN/cajón de A25 comprobados por HTTP, navegador y filas. A48/A49 CERRADOS, verificado Claude; búsqueda repetida sobre main coincide. **41/72 cerrados, 31 abiertos, 0 frenan.** [Informe](../06-pruebas/PRU-02/VERIFICACION-A13-B1-A55-CIERRE.md). Sin despliegue.
- **A4/A5 entregados por Codex CAB el 05/10:** aviso superior, conservación y salida; profesores activos del deporte. Gemini registró control y cierre independiente el 06/10 en 94e368e. [Informe](../06-pruebas/PRU-02/VERIFICACION-A4-A5.md). Sin despliegue.
  Retoque pedido después sobre el motivo de ingreso: alineado dentro de la grilla y con ícono existente, sin CSS; capturas nuevas listas, Carlos aprobó las capturas con ícono el 05/10; autorizado el commit/push.
  Primer control: A54 cerrado y A13/B1/A55 devueltos; [antecedente](../06-pruebas/PRU-02/VERIFICACION-A13-A54.md).
  Segundo control sobre main integrado: A13/B1/A55 CERRADOS por Codex; A48/A49 asentados verificado Claude.
  Corte versionado: 41/72 y ningún defecto que frene. A53 fue DEVUELTO a Gemini por Codex CyE 06/10.
- **A25, CERRADO 06/10 (verificado por Gemini, contrastado por Claude):** apertura confirmada, cajón compartido y arqueo;
  [Contrato V5, 06/10/2026.v2](../02-contratos/Wings-Contrato-Caja-Cashflow-V5.md): respuestas de Carlos consolidadas para los tres agentes. Rechazadas conservan
  lo contado/entregado, sin cambiar el turno siguiente. Diseño aprobado en capturas reales.
  26 pruebas permanentes (142 aserciones) pasando en suite compartida; sin CSS ni base real tocados.
  [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A25.md). No desplegado.
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
  fuera de esta entrega; al 06/10 A54/A55 cerrados y A53 DEVUELTO a Gemini por Codex CyE 06/10. Sin despliegue; Gemini puede
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
- A2/B2: Carlos define DEUDOR solo por deuda de mes cerrado y cuota creada en el alta. Implementado localmente con importe congelado, pendiente de verificación independiente
  y despliegue. Descuento sobre deudas históricas importadas conserva su pendiente separado.
- A25: CERRADO 06/10 (verificado por Gemini); decisiones en [Contrato V5.v2](../02-contratos/Wings-Contrato-Caja-Cashflow-V5.md); diseño aprobado. No desplegado.
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
