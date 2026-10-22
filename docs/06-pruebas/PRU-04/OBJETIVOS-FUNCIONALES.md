# Objetivos Funcionales de la Prueba por Jornadas — PRU-04

Documento funcional de referencia para el diseño, seguimiento y cobertura de la prueba por días en el club Wings.  
Conecta cada situación cotidiana del club con los casos del catálogo de pruebas, las comprobaciones humanas observables por pantalla y la continuidad entre jornadas.

> Revisión documental de Codex CyE, 22/10/2026: las filas describen propuestas, no resultados.
> Referencias al catálogo depuradas por alcance; variantes pendientes no acreditan el caso completo.
> El catálogo de Carlos y Claude permanece en solo lectura.

---

## 1. Metodología de prueba funcional

- **Caminos felices primero:** Con un horizonte de dos meses simulados, primero las jornadas reproducen el funcionamiento normal y armónico del club (altas, cobros, gastos, clases, asistencias, cierres).
- **Alimentación y volumen de datos:** Cada día suma registros reales y coherentes al sistema para dar densidad histórica a los reportes, cierres mensuales y futuras mediciones de rendimiento.
- **Comprobación humana cruzada entre roles:** Cuando un usuario realiza una acción en su módulo, se verifica cómo impacta en las pantallas de los demás roles (operativa, profesores y administrador).
- **Capturas para el Manual de Uso:** Cada paso del camino feliz es capturado de forma ordenada para servir de base gráfica en la futura redacción del manual de capacitación del personal.
- **Casos de mal uso y errores para etapas posteriores:** Los intentos con datos incompletos, rechazos de permisos y excepciones se programarán progresivamente en días posteriores.

---

## 2. Matriz Funcional — Día 4 (Martes 13/10/2026)

| Situación cotidiana y objetivo | Caso de referencia (catálogo fuente) | Jornada y persona | Comprobación humana por pantalla (1366 / 375, §8-bis) | Continuidad entre días | Cobertura funcional |
|---|---|---|---|---|---|
| **Apertura de caja heredando cambio:** El sistema debe sugerir el efectivo retenido en el último cierre para garantizar la continuidad del dinero en mostrador. | `CAJ-005` (solo antecedente validado; cobertura parcial) | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | En el modal/pantalla de apertura, comprobar que Efectivo muestre sugerido `$10.000` (retenidos del sábado). Al confirmar, verificar mensaje de éxito y cambio a estado `ABIERTA`. | El saldo físico basal queda disponible para operar cobros y vueltos en la jornada. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Inscripción de alumna menor con tutor:** Dar de alta un alumno nuevo vinculándolo a un deporte, grupo y plan tarifario. | `ALU-002` (alta completa con tutor; validaciones negativas pendientes) | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | Completar formulario de Mora Benítez. Al presionar Guardar, verificar la redirección al listado general de Alumnos con mensaje verde «Alumno creado correctamente.». Abrir su ficha desde la tabla: comprobar datos, grupo Patín Principiantes, deuda de matrícula ($5.000), cuota octubre ($40.000) y badge `En plazo` con vencimiento al 20/10/2026 (7 días de plazo por alta). | Mora se incorpora a la nómina del grupo para la profesora y genera plazo propio de 7 días. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Comprobación cruzada en Cobranza:** La operativa comprueba que la nueva alumna figure inmediatamente en el padrón de cobranzas. | Cruce Alumnos/Cobranza; referencia específica pendiente | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | Buscar a Mora en el buscador reactivo de Cobranza. Verificar que aparezca con `$45.000` de deuda y estado `En plazo`. Comprobar que el padrón de alumnos activos suba a 101. | Se valida la consistencia inmediata entre el módulo de Alumnos y Cobranza. | **Planificado para Día 4; ejecución pendiente** (Comprobación cruzada). |
| **Cobro de cuota del mes en mostrador:** Cobrar cuota completa del mes en curso y emitir comprobante oficial. | `COB-003` · `REC-001` (recibo desde confirmación; cobertura parcial) | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | Cobrar cuota octubre de Renata Cabrera ($43.000 en Efectivo). Verificar que se abra el Recibo oficial en PDF con número correlativo y datos claros. Comprobar que Renata pase de `Moroso` a `Al día` con deuda $0. | Renata queda al día para todo el mes de octubre. En caja suma efectivo físico. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Cobro de deuda atrasada por medio digital:** Cobrar un período anterior pendiente mediante billetera virtual sin sumar al efectivo de cajón. | `COB-004` (Mercado Pago; variante de cuota atrasada) | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | Cobrar cuota septiembre de Emma Fernández ($45.000 en Mercado Pago). Verificar que septiembre pase a pagada y octubre siga pendiente. Comprobar que el estado de Emma cambie de `Deudor` a `Moroso`. | Emma regulariza sus meses anteriores; la plata ingresa en la cuenta digital de MP. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Pago parcial con imputación FIFO:** El sistema debe aplicar pagos parciales cancelando primero la deuda más antigua. | `COB-008` (variante de pago parcial de octubre luego de saldar septiembre) | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | Cobrar pago parcial de $30.000 en Efectivo a Dante Ramos con observación «Pago a cuenta». Verificar que cancele los $20.000 de septiembre y aplique $10.000 a octubre (saldo restante $38.000). Comprobar pase de `Deudor` a `Moroso`. | Dante reduce su deuda total y cancela su pasivo histórico. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Cobro por transferencia bancaria:** Clasificar cobros bancarios directos según la entidad financiera correspondiente. | `COB-004` (variante transferencia bancaria) | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | Cobrar cuota octubre de Mateo Rodríguez ($38.000) seleccionando «Transferencia Banco Galicia». Verificar recibo y pase a `Al día`. En caja no afecta efectivo físico pero suma a la recaudación total. | Saldo financiero asentado en Banco Galicia en Cashflow. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Cobro adelantado de mes futuro (T42):** Permitir que un alumno al día pague por anticipado cuotas de meses venideros. | `COB-012` · `REP-003` (variantes e importes del relato; cobertura parcial) | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | Cobrar cuota noviembre 2026 ($30.000) a Sofía Gómez en Efectivo. Verificar recibo con período 11/2026 y que Sofía conserve su estado `Al día`. Comprobar que suma al efectivo de caja de hoy. | El dinero ingresa a caja hoy; el ingreso económico corresponde a noviembre. La cobertura de comisión de un docente por porcentaje requiere un caso específico posterior. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Ingreso operativo habitual de mostrador:** Registrar ventas de productos del club (remeras, indumentaria institucional). | `MOV-002` (solo efectivo; variante Mercado Pago pendiente) | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | En Menú Caja → Nuevo Movimiento → Ingreso: seleccionar «Indumentaria institucional» (clasificado como `APORTE` en el catálogo) por $15.000 en Efectivo. Comprobar que aparece en la tabla de la caja y suma al arqueo físico. | Registrado con comprobante y detalle para rendición y clasificación contable. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Gasto operativo menor con comprobante:** Registrar egresos corrientes de mostrador respaldados con ticket. | `MOV-001` | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | En Menú Caja → Nuevo Movimiento → Egreso: seleccionar «Limpieza» por $8.500 en Efectivo con detalle del ticket. Comprobar que resta del efectivo esperado en cajón. | Disminuye el efectivo físico a rendir en el cierre de turno. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Comprobación de nómina y toma de lista del día:** La profesora debe verificar la nómina de su grupo y registrar asistencia de la clase dictada. | `ASI-001` (variante 10 presentes y 2 ausentes; sin suplencia) | Día 4 (13/10) <br>Lucía Gaitán (`PROFESOR`) | 1) Lucía abre su grupo Patín Principiantes y comprueba que Mora Benítez ya figure en la nómina para la clase de mañana miércoles. <br>2) Lucía entra a la clase 28 de hoy (Patín Avanzadas, 12 alumnas): marca 10 presentes y 2 ausentes (Emma Fernández y Martina Herrera). Guarda y comprueba mensaje de confirmación. | Las asistencias alimentan el historial pedagógico y las liquidaciones según la modalidad de cada docente; este recorrido no acredita una comisión por el pago adelantado de Sofía. | **Planificado para Día 4; ejecución pendiente** (Comprobación cruzada y asistencia). |
| **Regularización de asistencia histórica:** Permitir asentar listas de clases pasadas olvidadas reduciendo el pasivo docente. | Asistencia histórica reciente; `CLA-018` sobre antigüedad extrema queda pendiente | Día 4 (13/10) <br>Hernán Quintana (`PROFESOR`) | Entrar a Menú Clases → enlace «Sin asistencia». Abrir clase de fútbol del sábado 10/10. Marcar 12 presentes y 2 ausentes y guardar. Anotar el contador de Sin asistencia antes y después de esta carga con el mismo usuario y filtros; comprobar que baje en uno. | Regulariza el historial pedagógico antes del cierre de liquidación mensual. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Arqueo y cierre de turno en cero:** Controlar que el dinero físico en billetes coincida exactamente con los registros del sistema. | `CAJ-018` (variante con los importes del relato; sin faltante ni cambio aumentado) | Día 4 (13/10) <br>Sandra Vidal (`OPERATIVO`) | Menú Caja → Cerrar. Contar billetes físicos ($119.500), declarar $119.500, retener $10.000 para cambio y retirar $109.500. Verificar diferencia $0 y cambio a estado `CERRADA`. | Caja cerrada lista para revisión y custodia del dinero por el dueño. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Validación de caja por el dueño:** El administrador audita cada comprobante tildando las líneas y validando el turno. | `CAJ-025` (cobertura parcial; sin inscripción cobrada ni cobro cancelado) | Día 4 (13/10) <br>Admin Prueba (`ADMIN`) | Menú Caja → Ver detalle caja 2. Tildar cada movimiento verificado y presionar Validar. Comprobar redirección al listado principal de Caja con mensaje verde «Caja validada y reflejada en cashflow.», mostrando la caja 2 con badge `VALIDADA`. | La recaudación se transfiere al disponible global de Cashflow del club. | **Planificado para Día 4; ejecución pendiente** (Camino feliz). |
| **Comprobación del dueño sobre matrícula actual:** El dueño verifica en Alumnos y en Reportes la incorporación de la nueva inscripta a la matrícula del club. | Cruce Alumnos/Reportes de matrícula actual; referencia específica pendiente | Día 4 (13/10) <br>Admin Prueba (`ADMIN`) | Buscar a Mora en Alumnos para revisar su ficha. Luego abrir Menú Reportes → Alumnos: comprobar que la **matrícula actual activa** sube a 101 alumnos, con su reflejo en Patín y en Principiantes. | Trazabilidad administrativa de la matrícula del club. | **Planificado para Día 4; ejecución pendiente** (Comprobación cruzada). |
| **Cierre integral y consistencia visual:** El dueño verifica que la información sea armónica en Inicio, Cobranza, Cashflow y Reportes. | `CBZ-018` · `REP-001` · `REP-003` (controles parciales del cierre del relato) | Día 4 (13/10) <br>Admin Prueba (`ADMIN`) | Recorrer Inicio, Cobranza, Cashflow y Reportes. Comprobar que la actividad de la tarde se refleje claramente: cobros imputados, disponibles por medio, deuda actualizada y la venta de remera ($15.000) expuesta en el renglón propio de **Aportes**. | Concluye la jornada laboral garantizando consistencia en todo el club. | **Planificado para Día 4; ejecución pendiente** (Cierre de jornada). |

---

## 3. Cobertura funcional acumulada y casos planificados para próximas jornadas

La estrategia de dos meses ficticios distribuye los objetivos a lo largo del calendario:

### A. Cobertura propuesta para el Día 4 (pendiente de ejecución)
- Apertura de caja con herencia de cambio físico.
- Inscripción de alumna nueva con tutor, grupo y cálculo automático de gracia de 7 días.
- Comprobación cruzada entre roles: visibilidad en Cobranza, nómina de grupo para la profesora y matrícula actual activa en reportes para el dueño.
- Cobros en mostrador con medios múltiples: Efectivo físico, Mercado Pago digital y transferencia bancaria Galicia.
- Cancelación de cuota completa del mes y cancelación de deuda atrasada con transición entre estados (`Moroso` a `Al día`, y `Deudor` a `Moroso`).
- Pago parcial con aplicación automática FIFO priorizando la deuda más antigua.
- Cobro adelantado de mes futuro (noviembre) con emisión de recibo y custodia de fondos.
- Movimientos corrientes de caja: venta de indumentaria institucional (clasificada como Aporte) y compra menor de insumos con ticket.
- Registro de asistencia de clase concluida del día (Patín Avanzadas) y regularización de asistencia histórica pendiente desde el filtro «Sin asistencia» (Fútbol).
- Arqueo físico de billetes en mostrador con diferencia $0 ($119.500 contados), retención de cambio y rendición en sobre ($109.500).
- Auditoría y validación de caja por el dueño con tildes obligatorios, redirección y feedback visual.

### B. Objetivos funcionales planificados para las siguientes jornadas
1. **Clases de Principiantes (Miércoles 14/10):**
   - Primera clase efectiva de Mora Benítez en Patín Principiantes y toma de asistencia en su grupo.
2. **Casos de mal uso y errores deliberados de tipeo (a incorporar gradualmente):**
   - Escribir un DNI inválido o duplicado y corregirlo.
   - Guardar alumno sin datos obligatorios y verificar mensajes descriptivos en castellano.
   - Intentar registrar cobro parcial sin motivo (cuando se evalúe `COB-009` / `T25`).
   - Intentar validar caja con líneas sin tildar (`T36`) o rechazar caja sin motivo explicativo (`T37`).
3. **Ciclo de vida de alumnos:**
   - Cambio de grupo y cambio de plan de un alumno existente.
   - Baja de alumno con deuda y baja de alumno al día.
   - Reactivación de alumno inactivo con generación de cuota (`T33`, `T38`).
4. **Seguimiento de hitos temporales:**
   - **19/10 - 20/10:** Último día en plazo de Mora el 19/10; desde el 20/10 pasa a `Moroso` si conserva deuda. Seguimiento técnico separado del Día 4.
   - **Fines de octubre:** Alta y pago reciente para evaluar la ventana de 15 días de `CBZ-017`.
   - **31/10 (Fin de mes):** Cierre de todas las cajas del mes y balance integral de octubre.
   - **01/11 (Arranque de mes):** Generación automática de deudas de noviembre (`cobranza:generar-deudas`), comprobando la **no duplicación** de la cuota ya abonada por Sofía Gómez (`T40`).
   - **02/11 (Liquidaciones de octubre):** Liquidación a profesoras por hora y por comisión, comprobando que el adelanto de noviembre de Sofía quede excluido de octubre (`T42`), y resolviendo cuotas atrasadas con intervención del dueño (`T43`).
