# T12 — Lista propuesta de páginas del manual del usuario

Esta es la nómina completa de páginas del manual del usuario propuesta para Wings, organizada por rol y por módulo de trabajo. Cada página cubre una tarea concreta de la operatoria diaria y se redacta en formato de guía paso a paso, con capturas de pantalla reales y su correspondiente sección de «Qué hago si…».

---

## 1. Rol OPERATIVO (Atención y mostrador)

Las operativas acceden a las guías de atención, cobranzas, manejo de caja diaria y asistencia.

### Día a día: Alumnos y Cobranza
1. **Cobrar una cuota**: Cómo buscar a un alumno, revisar lo que debe y cobrarle una o más cuotas en el mostrador. *(Muestra implementada en Etapa 1)*
2. **Registrar un pago parcial**: Cómo cobrar un monto menor a la cuota total y cómo se computa el saldo pendiente restante.
3. **Buscar alumnos por cualquier dato**: Cómo usar el buscador predictivo por nombre, apellido o DNI sin importar acentos ni orden.
4. **Dar de alta a un alumno nuevo**: Carga de datos personales, teléfono, contacto de emergencia y asignación a un grupo deportivo.
5. **Modificar datos de un alumno**: Corrección de teléfono, dirección, responsable a cargo o notas de salud.
6. **Cambiar a un alumno de grupo o deporte**: Asignación de un nuevo horario y ajuste automático del plan de cuota.
7. **Dar de baja a un alumno**: Cómo asentar la baja de un alumno que deja la actividad conservando su historial de deudas.
8. **Consultar el estado de cobranza**: Uso de filtros por deporte, grupo y período para identificar cuotas al día o adeudadas.
9. **Condonar una cuota o intereses**: Registro de condonación autorizada con motivo obligatorio para no reclamar el cobro.

### Plata: Caja y Movimientos
10. **Abrir el turno de caja**: Carga del saldo inicial en efectivo al comenzar la jornada en el mostrador.
11. **Registrar un cobro manual en caja**: Carga de un cobro directo con recibo cuando no se imputa por la ficha del alumno.
12. **Registrar un gasto menor o salida del cajón [DU-20]**: Cómo registrar cuando sale plata física del cajón para compras menores o cuando el dueño retira efectivo del mostrador (el dueño nunca toca la caja directamente; la operativa asienta el egreso en su turno).
13. **Anular un cobro o gasto mal cargado**: Anulación con motivo obligatorio de un movimiento erróneo mientras la caja sigue abierta.
14. **Cerrar el turno de caja y hacer el arqueo**: Conteo físico de billetes, cupones y comprobantes, carga del arqueo y pase al estado Cerrada.
15. **Rectificar un cierre de caja observado**: Cómo corregir las diferencias señaladas por el dueño antes de la validación definitiva.
16. **Consultar el historial de cajas**: Revisión de turnos anteriores cerrados y exportación del resumen diario.

### Día a día: Clases y Asistencia
17. **Consultar clases del día**: Visualización de la grilla de actividades, horarios y canchas asignadas.
18. **Tomar lista en el mostrador**: Carga de asistencia de una clase cuando la profesora no tiene celular o dispositivo a mano.
19. **Revisar casos en Revisión de cobranza**: Gestión de alumnos observados por inasistencias prolongadas o cambios de condición.

### El Club
20. **Consultar grupos y cupos**: Verificación de vacantes, edades y niveles para responder consultas de familias interesadas.

---

## 2. Rol ADMIN (Dueño del club)

El dueño tiene acceso a **todas las páginas de la operativa** y además a los módulos exclusivos de finanzas, liquidaciones, parámetros y catálogos.

### Capítulo especial: Primera Carga masiva [PRIMERA CARGA]
21. **Descargar la plantilla de primera carga**: Obtención del formato Excel oficial con las columnas requeridas para migrar datos históricos.
22. **Subir y revisar el archivo de primera carga**: Validación previa de alumnos, documentos, deportes y deudas iniciales sin escribir en la base.
23. **Confirmar la importación masiva**: Ejecución de la primera carga con verificación del informe de altas y deudas registradas.
24. **Deshacer la primera carga**: Reversión limpia de la carga inicial en caso de haberse detectado errores en la planilla fuente.

### Plata: Validación de Cajas y Arqueos
25. **Validar o rechazar el cierre de caja de un turno**: Control del arqueo rendido por la operativa, aprobación del pase a Cashflow o rechazo con observación.
26. **Consultar el detalle de una caja validada**: Auditoría de cobros, anulaciones, egresos y comprobantes de cualquier fecha.

### Plata: Cashflow y Finanzas
27. **Consultar el libro Cashflow y saldos por medio**: Interpretación del disponible en Efectivo, Bancos y Tarjetas, y resultado operativo del período.
28. **Registrar un movimiento directo de administración**: Carga de cobros extraordinarios, subsidios, compras de equipamiento o pagos de servicios generales.
29. **Corregir un movimiento mal cargado en una caja ya validada [DU-27]**: Por qué lo validado nunca se toca ni se borra, y cómo cargar un ajuste directo compensatorio en Cashflow con motivo obligatorio. *(Muestra implementada en Etapa 1)*
30. **Llevar efectivo al banco o transferir entre medios propios [DU-29]**: Uso del botón **Transferir** en Cashflow con cuenta de salida Efectivo y destino Banco sin alterar el resultado contable del club.
31. **Regla de uso de dinero del dueño [DU-20]**: Pautas claras sobre por qué los gastos personales o retiros del dueño no se toman del cajón operativo sin registro de la empleada.

### Plata: Liquidaciones de Profesores
32. **Generar la liquidación mensual de profesores**: Cálculo automático de honorarios fijos y comisiones por alumno presente del período.
33. **Revisar el desglose de una liquidación**: Auditoría de clases computadas, ausencias justificadas y comisiones calculadas.
34. **Agregar un ajuste o adelanto en la liquidación**: Carga de adicionales o deducciones de sueldo con concepto descriptivo.
35. **Cerrar y pagar una liquidación**: Registro del pago (efectivo o transferencia), salida de dinero en Cashflow y emisión del recibo de sueldo.
36. **Reimprimir recibos de liquidación**: Generación de duplicados en formato oficial DomPDF para archivo o firma del profesor.

### Reportes e Indicadores
37. **Analizar el reporte visual de ingresos**: Gráficos de recaudación por deporte, evolución mensual y comparativa de medios de cobro.
38. **Analizar la morosidad y deudas del club**: Identificación de mora histórica, porcentaje de cobrabilidad y alumnos con mayor atraso.
39. **Exportar reportes a Excel o CSV**: Descarga de tablas consolidadas para el contador o la administración externa.

### El Club: Catálogos y Estructura
40. **Administrar deportes y colores**: Creación de disciplinas y asignación del riel cromático del design system.
41. **Administrar niveles y planes de cuota**: Configuración de precios por categoría y cantidad de clases semanales.
42. **Crear y programar grupos**: Definición de días, horarios, edades, cupo máximo y profesor asignado.
43. **Gestionar la nómina de profesores**: Altas de docentes, esquema de honorarios por hora o por comisión y datos de contacto.
44. **Administrar rubros y subrubros contables**: Clasificación de conceptos de ingresos y egresos para el seguimiento en Cashflow.
45. **Administrar tipos de caja y medios de cobro**: Habilitación de cuentas bancarias, billeteras virtuales y terminales de cobro.

### Sistema y Seguridad
46. **Configurar parámetros generales del club**: Día de vencimiento mensual, recargos por mora y textos de encabezado de recibos.
47. **Configurar reglas del primer pago**: Escala de cobro proporcional para alumnos que ingresan a mitad o fin de mes.
48. **Crear y administrar usuarios del sistema**: Asignación de roles (ADMIN, OPERATIVO, PROFESOR) y blanqueo de contraseñas.
49. **Desactivar un usuario sin borrar su historial**: Cómo impedir el acceso a un empleado que ya no trabaja sin alterar los registros pasados.

---

## 3. Rol PROFESOR (Docentes)

Los profesores tienen una vista simplificada en su celular o tablet, centrada en sus clases y sus alumnos.

### Clases y Asistencia
50. **Consultar mis clases programadas**: Visualización de horarios, grupos y sedes donde le toca dar clase hoy y en la semana.
51. **Tomar lista de asistencia**: Marcado rápido de Presente y Ausente en la ficha de la clase al iniciar la actividad. *(Muestra implementada en Etapa 1)*
52. **Modificar la asistencia de una clase anterior**: Corrección de marcas antes del cierre de liquidación del mes.
53. **Consultar clases pendientes sin asistencia**: Detección de clases finalizadas que aún no tienen lista tomada para evitar demoras en la liquidación.
54. **Ver la nómina de alumnos de mi grupo**: Consulta de nombres, edades y datos básicos de los deportistas a cargo.

---

## Resumen de cobertura requerida

| Requisito de Carlos | Página asignada | Estado en Etapa 1 |
|---|---|---|
| **DU-20** (Dueño no usa el cajón; si lo usa, lo registra la operativa) | Páginas 12 y 31 | Listadas en índice y contempladas en el modelo |
| **DU-27** (Corregir movimiento de caja validada con ajuste en Cashflow) | Página 29 | **MUESTRA COMPLETA IMPLEMENTADA Y CAPTURADA** |
| **DU-29** (Llevar efectivo al banco mediante transferencia Cashflow) | Página 30 | Detallada en texto y contemplada en el modelo |
| **Capítulo Primera Carga** (Módulo exclusivo del dueño) | Páginas 21 a 24 | Grupo exclusivo en la nómina de Admin |
| **Cobrar una cuota** (Tarea más utilizada por la operativa) | Página 1 | **MUESTRA COMPLETA IMPLEMENTADA Y CAPTURADA** |
| **Tomar asistencia** (Tarea central del profesor) | Página 51 | Estructura y archivo base implementados |
