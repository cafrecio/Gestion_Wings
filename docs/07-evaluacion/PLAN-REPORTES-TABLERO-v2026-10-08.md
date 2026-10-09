# B12 + A23 — Reportes e inicio de ADMIN

**09/10/2026 · Codex CyE · PLAN VALIDADO, desarrollo autorizado.**
Carlos validó por preguntas cortas los [24 acuerdos](ACUERDOS-B12-A23-2026-10-09.md)
y pidió implementar antes de la prueba manual. Aportes/retiros quedan fuera del
resultado del negocio; disponible por tipo de caja y total; gráficos en Reportes;
inicio con avisos breves y acceso al listado. El aspecto se elige con imágenes reales.
El relevamiento del 08/10 y las entregas de abajo conservan su fecha y alcance.

**Revisión posterior 09/10:** Inicio aprobado por Carlos en capturas, aplicado y
verificado por HTTP independiente. Reportes original rechazado; separar Alumnos,
Ingresos/egresos y análisis Sueldos. Sueldos compara costo clases del mes con cuotas
reales del mismo mes, netas de bonificaciones/condonación, y calcula costo por cada
asistencia presente. Atribución proporcional aprobada, historia desde ahora y comisión
sobre cuotas cobradas; ADMIN edita monto final de liquidación, también cerrado sin pagar.
Historial analítico, ajuste final y motor Sueldos implementados y probados en base descartable.
Sin activar en el club; aspecto Sueldos aprobado por Carlos («Esta OK»), ubicación
de Reportes en el acceso Plata → Reportes aplicado, aspecto por revisar y ajuste final por revisar. Estimados/reparto usan horarios
y asistencias actuales; liquidaciones cerradas conservan importe. Carlos pidió
más visual y menos lectura, con tres referencias: [V3](../06-pruebas/B12-A23/PROPUESTA-VISUAL-2026-10-09.md)
con tarjetas, gráficos y detalle plegado aprobada: «Si, mucho mejor». Aplicación financiera con filtros y detalle histórico en [esta entrega](../06-pruebas/B12-A23/IMPLEMENTACION-REPORTES-2026-10-09.md). No aplicar
Reportes original. [Entrega y capturas vigentes](../06-pruebas/B12-A23/IMPLEMENTACION-INICIO-2026-10-09.md).
Ruta financiera conectada a Ver/indicadores de Inicio; clasificación de antecedentes pendiente.
Alumnos aprobado («Esta OK»), integración habitual comprobada; [entrega](../06-pruebas/B12-A23/IMPLEMENTACION-ALUMNOS-2026-10-09.md).
Este agregado amplía entrega 4; no cambia reglas de pago ni las fechas reales del cashflow.

## 1. Una implementación, dos usos

- **B12 / Reportes:** consultar el negocio, elegir el mes y recorrer el detalle.
- **A23 / Inicio ADMIN:** resumen del mes actual, pendientes y accesos a acciones.
- Ambos consumen los mismos cálculos. Pulsar un indicador abre su detalle con
  el mismo período, filtro y estado de confirmación; la suma debe coincidir.

El inicio responde: **cuánto entró, cuánto salió, cuánto me deben y cuánto debo**.
Agrega dinero disponible y resultado, con nombres distintos para no confundirlos.

## 2. Decisiones recuperadas de la encuesta

| Tema | Regla que debe conservarse |
|---|---|
| Período principal | Mes actual; se puede elegir un mes pasado |
| Evolución | Hasta seis meses cerrados con datos; mes actual separado |
| Cuotas cobradas | Fecha real del pago, aunque cubra otra mensualidad |
| Por cobrar | Cuota impaga del mes y deuda anterior, importe y alumnos en cada grupo |
| Por pagar | Liquidaciones de profesores cerradas no pagadas; abiertas solo como aviso |
| Disponible | Dinero acumulado al corte, incluido lo anterior; sin sumar por cobrar |
| Confirmación | Importes confirmados y sin confirmar separados, sin repetir movimientos |
| Resultados | Negocio y global separados; nunca sumar el saldo inicial al resultado |
| Deportes | Filtro por deporte; gastos generales aparte como Gastos del club |
| Gastos del club | Se descuentan una vez del total del negocio, no de cada deporte |
| Alumnos | Activos por deporte/nivel; evaluación por deporte, incluidos inactivos |
| Historia | Admite carga tardía y confirmación posterior por fecha real del hecho |
| Proyecciones | Solo deudas ya generadas; separadas del dinero y resultado reales |
| Accesos | Agregados económicos solo ADMIN; Cobranza y Revisión conservan sus roles |

Un alumno que deba este mes y meses anteriores figura en ambos grupos.
No sumar esos contadores como si fueran personas distintas.

## 3. Qué comprobé para armar el plan

Relevamiento de archivos actuales, sin consultar la base real ni ejecutar pruebas:

- `WebController::adminDashboard()` calcula alumnos y deuda; su vista muestra cuatro
  indicadores y tres accesos. No calcula el resultado económico del negocio.
- `CashflowIntegracionCajaService::reflejarCajaEnCashflow()` copia movimientos activos
  cuando la caja está VALIDADA, conservando la fecha del movimiento. Su referencia
  identifica la caja, no cada movimiento original.
- `CashflowSaldoService::obtenerSaldosPorTipoCaja()` usa Cashflow y saldo inicial;
  no recibe corte temporal ni incluye por sí mismo movimientos operativos sin validar.
- `LiquidacionPagoService::marcarComoPagada()` exige liquidación cerrada y registra
  el egreso en Cashflow con fecha del pago. No sumar otra vez ese sueldo como gasto.
- `AvisoAdminService::resumenDiario()` ya consulta cajas por validar, Revisión,
  liquidaciones abiertas/cerradas pendientes y clases sin lista. Compartir consultas
  de pendientes; abrir el inicio no debe invocar el envío de avisos.
- `AlumnoPlan` guarda vigencias, pero eso no acredita un historial completo del
  estado activo, deporte y nivel. Los modelos/migraciones consultados no permiten
  prometer todavía todos los totales históricos solicitados.
- `PagoCuotaService::anularCobroAdmin()` conserva el asiento y genera uno contrario
  con fecha de la anulación; retira imputaciones y conserva detalle del pago anulado.
  Un reporte no puede descartar el pago original sin estudiar ese movimiento inverso.
- El generador de deuda consulta asistencias del mes calendario anterior y contempla
  altas con pago reciente. El reporte enlaza la cola de Revisión existente;
  no vuelve a calcular otra lista con un criterio distinto.

## 4. Orden propuesto de implementación

| Entrega | Trabajo | Condición para avanzar |
|---|---|---|
| 0. Especificación | Consolidar encuesta, FIN-04 y contrato; inventario de fechas, orígenes e historia | Resolver los puntos de §5 y aprobar este alcance |
| 1. Cálculos comunes | Movimientos, confirmación, saldos al corte, deuda y liquidaciones pendientes | Conciliación y casos de §6 correctos antes de mostrar cifras |
| 2. B12, consulta mensual | Ingresos/egresos por rubro/subrubro, resultados, saldos, por cobrar/pagar y detalle | Cada total coincide con las filas que lo componen |
| 3. A23, inicio útil | Resumen actual alimentado por B12, avisos y acciones rápidas | Misma cifra en inicio y Reportes; capturas y recorrido aprobados |
| 4. Gestión y evolución | Deporte/nivel/grupo, alumnos, comparación anterior y seis meses cerrados | Atribución e historia acreditadas; sin reconstrucción inventada |
| 5. Cierre conjunto | Control independiente de lógica/permisos, capturas, documentos y tablero | B12 completo según alcance acordado y A23 verificado; despliegue separado |

**Entrega 0:** elaborar la versión consolidada del contrato, sin borrar la encuesta
hasta incorporar cada decisión. Definir fecha del hecho, carga, rendición y corte.
Auditar unas filas concretas de cada origen cuando se habilite el desarrollo.

**Entrega 1:** un servicio de consultas para ambos módulos, con importes decimales,
procedencia y clasificación explícitas. Leer los movimientos confirmados y los
operativos activos no validados; evitar repetir las cajas que ya están en Cashflow.
No volver a crear asientos por consultar ni usar el nombre de un subrubro para
deducir deporte/profesor. Separar inscripción de cuota y estudiar anulaciones,
condonaciones, ajustes y saldos iniciales importados.

Para saldos: saldo inicial una sola vez + movimientos firmados hasta el corte.
Mostrar saldo confirmado, variación sin confirmar y disponible incluyendo esa
variación. Acordar etiquetas claras para que no parezcan dos saldos iniciales.
El cambio para vuelto, la entrega y las diferencias de arqueo A25 no son nuevos
ingresos o egresos; son custodia física y no se suman al saldo financiero.

Si hacen falta registros históricos nuevos, diseñarlos y comenzar a guardarlos
desde esta entrega, antes de postergar los gráficos. Preferir hechos con vigencia
que permitan correcciones tardías; no congelar cifras incompatibles con la encuesta.

**Entrega 2:** mes y filtro de deporte; cada indicador lleva a las filas de origen.
Cobranza y Revisión se reutilizan para las acciones actuales. Si hace falta detalle
de deuda a un corte pasado, no presentarlo mediante la lista actual como si fueran
los mismos datos. La consulta de movimientos conserva el alcance Día/Semana/Mes/Año
aprobado en A10; no se agrega por arrastre esa selección a todos los indicadores.

**Entrega 3:** mes actual como contexto explícito. Bloque económico resumido;
pendientes de validar, revisar, cerrar o pagar con acceso directo. Proponer botones
Cobrar, Registrar, Nuevo y Ver donde correspondan, respetando el flujo de ADMIN.
Probar la distribución con el sistema Wings funcionando, escritorio y marco 375.
Mostrar capturas antes de aplicar el cambio visual definitivo.

**Entrega 4:** ingresos atribuibles por deporte/grupo, costos atribuibles de profesor
y margen identificado por su alcance. No llamar rentabilidad completa al margen
que excluya gastos generales o costos de canchas todavía no modelados.
Comparar activos con su estado real al cierre anterior. Cuando un dato histórico
no pueda acreditarse, indicar desde cuándo está disponible; ausencia no equivale a
cero. Mostrar hasta seis meses cerrados con información y permitir abrir cada mes.

## 5. Puntos que deben quedar resueltos antes del código dependiente

1. **Aportes/retiros, RESUELTO 09/10:** Carlos confirmó que solo cambian el
   disponible; no entran al resultado del negocio. Identificarlos explícitamente
   en datos, sin confundirlos con ingresos/egresos económicos del mes.
2. **Historia disponible:** comprobar qué se reconstruye con fechas y relaciones
   conservadas y qué exige registrar cambios nuevos. El estado actual de un alumno
   no demuestra su estado pasado. Definir cobertura inicial de cada indicador.
3. **Puerta de desarrollo, RESUELTO 09/10:** Carlos: «Vamos a implementarlo antes
   de la prueba manual. Avancemos». El cambio de orden autoriza desarrollo;
   no sustituye la prueba manual ni autoriza despliegue.

Estos puntos no impiden discutir el plan. Impiden dar por resueltas reglas o cifras
que todavía no quedaron comprobadas o conciliadas.

## 6. Casos mínimos de aceptación

- Saldo previo 100, ingresos 80, egresos 50: resultado global 30, disponible 130.
- Cuota de agosto pagada realmente en septiembre: ingreso septiembre; deuda al
  cierre de agosto. Pago real agosto cargado septiembre: corrige agosto.
- Caja de agosto validada septiembre: cambia confirmación en agosto; importe total
  y dinero no se duplican. Probar ABIERTA, CERRADA, RECHAZADA y VALIDADA.
- Anulación posterior: conservar la cronología del asiento y su contramovimiento;
  deuda histórica e importe neto coherentes en los dos períodos.
- Inscripción y cuota en el mismo cobro: cuotas cobradas no incluyen inscripción.
- Pago parcial, varios períodos, condonación, ajuste y deuda inicial importada:
  comprobar saldo al corte, sin tomar solo el estado actual de la deuda.
- Liquidación abierta: aviso; cerrada pendiente: por pagar; pagada: egreso una vez.
- Un gasto general se descuenta una vez del negocio y no de cada deporte.
- Cambio de grupo/nivel/actividad: no trasladar automáticamente el dato actual a
  meses anteriores. Carga tardía: misma fecha del hecho en resumen y detalle.
- OPERATIVO conserva todo su dominio de Cobranza/Revisión y no accede a agregados
  ADMIN ni mediante URL directa. PROFESOR no obtiene reportes económicos.
- Inicio y Reportes coinciden al mismo corte; móviles sin desbordes ni controles
  desalineados. Verificación lógica por otro agente y visual por Carlos.

## 7. Alcance separado y continuidad

POS-06 particulares y POS-07 canchas/clubes conservan sus planes propios: preparar
extensión por origen, sin esperar su implementación para la primera entrega útil.
No se incluyen exportación Excel/PDF, sueldo configurable del operativo, cuentas
a pagar generales ni un segundo módulo de Revisión.

No cerrar B12 cuando solo exista el resumen inicial: faltan los detalles y la
evolución acordados. Aprobar A23 no certifica toda la lógica económica de B12.
Actualizar contrato, estado, checklist, bitácora y tablero a medida que se entreguen
fases; distinguir propuesto, implementado, verificado y desplegado.

## Fuentes

- [Encuesta, decisiones 13–22/09](../05-pendientes/ENCUESTA-REPORTES-PROVISIONAL.md).
- [Reportes V1 y enmienda FIN-04](../02-contratos/Wings-Contrato-Reportes-V1.md).
- [Caja V4, reglas financieras](../02-contratos/Wings-Contrato-Caja-Cashflow-V4.md)
  y [V5, custodia física A25](../02-contratos/Wings-Contrato-Caja-Cashflow-V5.md).
- [Permisos](../02-contratos/PERMISOS-ROLES.md).
- [B12 y A23](../06-pruebas/PRU-02/DEFECTOS.md).
- [Plan vigente, POS-01](PLAN-TRABAJO-IA-v2026-09-08.md).
- [POS-07 separado](PLAN-CANCHAS-LIQUIDACIONES-v2026-09-21.md).
