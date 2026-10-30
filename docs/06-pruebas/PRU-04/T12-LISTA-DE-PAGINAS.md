# T12 — Lista oficial y depurada de páginas del manual del usuario

Nómina de páginas del manual del usuario para Wings, organizada por rol y por módulo de trabajo, depurada contra el código fuente en el **Paso 0** (29/10/2026).
Cada página cubre una tarea real de Wings, con capturas de pantalla tomadas de la aplicación y apartado «Qué hago si…».

---

## 1. Rol OPERATIVO (Atención y mostrador) — Etapa A (Páginas 2 a 20)

Las operativas acceden a las guías de atención, cobranzas, manejo de caja diaria, movimientos y asistencia.

### Día a día: Alumnos y Cobranza
1. **Cobrar una cuota**: Cómo buscar a un alumno, revisar lo que debe y cobrarle una o más cuotas en el mostrador. *(Muestra implementada)*
2. **Cobrar un pago parcial de cuota**: Cómo ingresar un importe menor a la cuota y cómo se computa el saldo pendiente en el sistema.
3. **Buscar alumnos en el listado**: Cómo buscar por nombre, apellido o DNI, y filtrar por deporte, grupo y condición en Alumnos.
4. **Dar de alta a un alumno nuevo**: Carga de datos personales, teléfono, fecha de nacimiento y asignación a un grupo deportivo.
5. **Modificar datos de un alumno**: Edición de teléfono, dirección, notas y datos personales desde su ficha.
6. **Cambiar a un alumno de grupo o deporte**: Reasignación de grupo deportivo y selección del plan arancelario correspondiente.
7. **Pasar un alumno a inactivo (o reactivarlo)**: Cómo cambiar el estado del alumno con el selector Activo/Inactivo y qué cuota de reingreso se genera al reactivarlo (T38/T70). *(Depurado en Paso 0: dar de baja definitiva es eliminación de Admin T65)*
8. **Consultar el estado de cobranza**: Uso de filtros por deporte, grupo y estados (al día, en plazo, moroso, deudor) en Cobranza.
9. **Consultar movimientos operativos**: Visualización del registro cronológico de ingresos y egresos del mostrador en Movimientos. *(Depurado en Paso 0: condonar es potestad exclusiva de Admin)*

### Plata: Caja y Mostrador
10. **Abrir el turno de caja**: Carga del saldo en Efectivo recibido y confirmación obligatoria al comenzar la jornada.
11. **Registrar un ingreso directo en caja**: Carga de entradas de dinero al turno (venta de indumentaria, cantina, eventos) en Movimiento de caja.
12. **Registrar un gasto menor o salida del cajón**: Cómo registrar salidas de efectivo para compras menores o cuando el dueño retira dinero del mostrador (asentado siempre por la operativa).
13. **Cancelar un cobro de cuota en caja**: Cancelación con motivo obligatorio de un cobro de cuota en Detalle de caja abierta o rechazada.
14. **Cerrar el turno de caja y hacer el arqueo**: Conteo de Efectivo contado, cambio retenido y pase al estado Cerrada.
15. **Rectificar la apertura de caja**: Cómo corregir el saldo inicial en Efectivo recibido si la caja está abierta y se cometió un error al abrir el turno.
16. **Consultar el detalle e historial de cajas**: Revisión del arqueo de la caja activa y consulta de turnos cerrados anteriores en Historial.

### Día a día: Clases, Asistencia y Catálogo
17. **Consultar clases del día**: Visualización de la grilla de actividades, profesores y estados de clase en Clases.
18. **Tomar lista desde el mostrador**: Registro de presentes y ausentes de una clase cuando la profesora no tiene dispositivo a mano.
19. **Resolver casos en Revisión de cobranza**: Decidir si a un alumno con inasistencias se le genera la cuota o continúa en espera.
20. **Consultar grupos y planes deportivos**: Consulta de disciplinas, niveles, cantidad de alumnos activos y planes arancelarios vigentes en Grupos. *(Depurado en Paso 0: Grupo no tiene cupos ni edades)*

---

## 2. Rol ADMIN (Dueño del club) — Etapa B (Páginas 21 a 36) y Etapa C (Páginas 37 a 49)

El dueño tiene acceso a **todas las páginas de la operativa** y a los módulos exclusivos de finanzas, liquidaciones, parámetros y catálogos.

### Capítulo especial: Primera Carga masiva [PRIMERA CARGA] (Etapa B)
21. **Descargar la plantilla de primera carga**: Obtención de la planilla Excel modelo para migración inicial.
22. **Subir y revisar el archivo de primera carga**: Validación sintáctica y control de inconsistencias antes de escribir en la base.
23. **Confirmar la importación masiva**: Ejecución de la primera carga con verificación del resumen de registros importados.
24. **Deshacer la primera carga**: Reversión atómica y limpia de la carga inicial ante errores en la planilla fuente.

### Plata: Validación de Cajas y Arqueos (Etapa B)
25. **Validar o rechazar el cierre de caja de un turno**: Control del arqueo rendido, aprobación del pase a Cashflow o rechazo con observación.
26. **Auditar el detalle y arqueo de una caja**: Revisión minuciosa de cobros, anulaciones, gastos y verificación de movimientos.

### Plata: Cashflow y Finanzas (Etapa B)
27. **Consultar el libro Cashflow y saldos por medio**: Interpretación del disponible por tipo de caja y resultado del período.
28. **Registrar un movimiento directo de administración**: Carga de ingresos extraordinarios, equipamiento o gastos generales en Cashflow.
29. **Corregir un movimiento mal cargado en una caja ya validada [DU-27]**: Fundamento contable y carga de ajuste directo compensatorio en Cashflow. *(Muestra implementada)*
30. **Transferir entre medios propios**: Transferencias internas (ej. pasar efectivo a banco) con Cuenta de salida y Cuenta de destino sin alterar resultado contable.
31. **Retiros y aportes personales del dueño**: Asentamiento en caja como gasto o ingreso bajo las clasificaciones contables de retiro y aporte de los dueños.

### Plata: Liquidaciones de Profesores (Etapa B)
32. **Generar la liquidación mensual de profesores**: Cálculo automático de honorarios por horas y comisiones por alumno del período.
33. **Revisar el desglose de una liquidación**: Auditoría de clases computadas, tarifas congeladas y comisiones calculadas.
34. **Recalcular o ajustar una liquidación abierta**: Actualización de importes ante cambios de asistencia o carga de ajustes manuales. *(Depurado en Paso 0: no existen adelantos en liquidación)*
35. **Cerrar y pagar una liquidación**: Cierre de la liquidación, registro del pago y salida de fondos en Cashflow.
36. **Descargar el recibo oficial de liquidación**: Generación del comprobante oficial DomPDF para firma y archivo.

### Reportes e Indicadores (Etapa C)
37. **Analizar el reporte visual de ingresos**: Gráficos de recaudación por deporte, evolución mensual y medios de cobro en Reportes.
38. **Analizar el reporte de alumnos y morosidad**: Estado de la matrícula activa, inactivos y alumnos con deuda en Reportes de alumnos.
39. **Consultar el reporte de sueldos de profesores**: Auditoría consolidada de honorarios y comisiones liquidadas en Reporte de sueldos. *(Depurado en Paso 0: reemplaza a exportación a Excel/CSV inexistente)*

### El Club: Catálogos y Estructura (Etapa C)
40. **Administrar deportes y formas de liquidar**: Creación de disciplinas y asignación de liquidación por hora o comisión. *(Depurado en Paso 0: deporte no tiene color)*
41. **Administrar niveles deportivos**: Creación y ordenamiento de categorías y niveles de destreza.
42. **Crear y administrar grupos deportivos**: Vinculación de deporte y nivel, y configuración de planes arancelarios por clases semanales. *(Depurado en Paso 0: grupo no tiene cupo ni edades)*
43. **Gestionar la nómina de profesores**: Altas de docentes, tarifa por hora, comisión, subrubro contable y datos de contacto.
44. **Administrar rubros y subrubros contables**: Clasificación de conceptos para Cashflow y configuración de afectación de caja.
45. **Administrar tipos de caja y cuentas**: Habilitación de efectivo, cuentas bancarias y billeteras virtuales.
46. **Condonar cuotas o recargos de un alumno**: Condonación autorizada con motivo obligatorio desde la ficha del alumno. *(Movido desde Operativo: es exclusivo de Admin)*
47. **Eliminar un alumno cargado por error (T65)**: Eliminación permanente y atómica de alumnos sin pagos registrados. *(Incorporado en Paso 0)*

### Sistema y Parámetros (Etapa C)
48. **Configurar parámetros generales del club**: Día de vencimiento, recargos punitorios por mora y plazos de pago.
49. **Crear y administrar usuarios del sistema**: Asignación de roles (ADMIN, OPERATIVO, PROFESOR), cuentas activas y reseteo de claves.

---

## 3. Rol PROFESOR (Docentes) — Etapa C (Páginas 50 a 54)

Los profesores tienen una vista simplificada en su celular o tablet, centrada en sus clases y sus alumnos.

### Clases y Asistencia
50. **Consultar mis clases programadas**: Visualización de horarios, deportes y grupos de hoy y de la semana. *(Depurado en Paso 0: Wings no tiene sedes)*
51. **Tomar asistencia en una clase**: Marcado de Presente y Ausente en la ficha de la clase al iniciar la actividad. *(Muestra implementada)*
52. **Modificar la asistencia de una clase anterior**: Corrección de marcas con motivo obligatorio antes del cierre de liquidación.
53. **Consultar clases pendientes sin asistencia**: Acceso directo desde el menú a clases finalizadas que traban la liquidación.
54. **Ver los alumnos inscriptos en mi clase**: Consulta de deportistas asignados al grupo en la ficha de clase.

---

## Resumen de Etapas de Trabajo

| Etapa | Alcance | Tareas adicionales | Estado |
|---|---|---|---|
| **Muestras iniciales** | Páginas 1 (Cobrar), 29 (Ajuste Cashflow DU-27) y 51 (Tomar asistencia) | Estructura base de Ayuda, ruta de imágenes, botón ? y botón Imprimir | **APROBADO POR CLAUDE Y CARLOS** |
| **Etapa A** | **Operativa: Páginas 2 a 20** (19 páginas) | **Buscador en /ayuda** y botón **?** en las pantallas de esas páginas | **EN CURSO** |
| **Etapa B** | Dueño: Páginas 21 a 36 (15 páginas, excluyendo la 29) | Botón **?** en las pantallas de esas páginas | Pendiente |
| **Etapa C** | Dueño: Páginas 37 a 49 y Profesora: Páginas 50, 52, 53 y 54 | Botón **?** en todas las pantallas restantes de Wings | Pendiente |
