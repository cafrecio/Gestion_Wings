# Día 4 — revisión de la historia publicada en 7cf48ece

22/10/2026 — Codex CyE, chat Casos prueba humana I.

**Dictamen vigente tras revisar d34dee7a: guion apto para el recorrido humano del Día 4.**
Preparación técnica previa y ejecución pendientes; no equivale a resultados aprobados del sistema.
El detalle de la revisión inicial de 7cf48ece se conserva a continuación. La resolución figura en §6.

**Resultado inicial sobre 7cf48ece: enfoque humano correcto; ajustes documentales antes de ejecutar.**
Se revisaron los tres entregables reales. El Día 4 no se ejecutó y no se certifica cobertura aprobada.
Se conservan las 16 situaciones y el avance por camino feliz, sin exigir más operaciones ni errores.

## 1. Ajustes del relato y las comprobaciones humanas

| Situación | Contraste comprobado | Corrección necesaria |
|---|---|---|
| 11: Lucía toma lista de Principiantes el martes 13, con Mora y Sofía presentes | En prueba, la clase 28 del 13/10 de 17 a 18 es del grupo 3, Patín Avanzadas. Emma pertenece al grupo 3; Sofía al 1, Principiantes. El grupo 1 tiene su próxima clase el 14/10 de 16 a 17, clase 6. Mora se propone para el grupo 1. | No pedir marcar a Mora y Sofía en la lista de Avanzadas. Alternativas para el relato: conservar su inscripción y comprobar su grupo por separado, dejando la asistencia de Principiantes para el 14; o proponer otro alta/grupo compatible. La lista del martes debe corresponder a los alumnos de su clase real. No se eligió ni modificó grupo por Carlos. |
| 15: Reportes contabiliza a Mora como alta nueva de octubre | Reportes → Alumnos muestra matrícula actual, distribución por deporte/nivel y asistencias del mes. El servicio y la vista no construyen un indicador de altas mensuales. | Comprobar la ficha, el total de activos y la distribución actual por deporte/nivel. No exigir una métrica de altas del mes que esa pantalla no ofrece. |
| 16d: indumentaria suma a ingresos económicos de octubre | En prueba, Indumentaria institucional es INGRESO / APORTE / OPERATIVO. ReporteMensualService separa aportes de ingresos NEGOCIO. | Buscar los $15.000 en el disponible y en Aportes. Los cobros de cuotas de octubre aportan $156.000 al ingreso económico de octubre; el adelanto de $30.000 corresponde a noviembre. No reclasificar el catálogo para acomodar la venta. |
| 2 y 14: apertura automática de ficha y validación sin recarga | AlumnoWebController::store vuelve al listado al guardar. CajaWebController::validar vuelve al listado de caja; verificar un movimiento sí devuelve JSON. | Tras el alta, abrir la ficha desde el listado. Separar la revisión de tildes en el lugar de la navegación al validar toda la caja; registrar la experiencia visual real. Si se pretende otro comportamiento, queda como discrepancia para Carlos, sin modificar producto durante la prueba. |

El reloj inicial de 18:15 también sitúa el alta después de la clase de 17 a 18. No atribuir asistencia
previa a una alumna recién inscripta sin que el relato explique esa situación. La preparación del reloj
indicada en las reglas del cuento corresponde a este chat; el ejecutor humano recibe el entorno preparado.

## 2. Lo comprobado que permite conservar el resto de la jornada

- Caja 1 VALIDADA con $10.000 retenidos: la apertura propuesta tiene antecedente real.
- Hay 100 registros activos; el DNI propuesto para Mora no apareció en la consulta de alumnos.
- Principiantes ofrece 2 clases semanales a $40.000. Sofía tiene plan de 1 clase semanal a $30.000.
- Renata: octubre pendiente $43.000. Emma: septiembre y octubre $45.000 cada uno.
- Dante: septiembre pendiente $20.000 y octubre $48.000. Mateo: octubre pendiente $38.000.
- Clase histórica 74 del 10/10, grupo 6 Fútbol Avanzadas, profesor Hernán, sin marcas guardadas:
  14 alumnos activos actuales en ese grupo, entre ellos Dante y Leon. La elegibilidad histórica se
  contrasta con la nómina de pantalla al ejecutar; la matrícula actual no la certifica por sí sola.
- Limpieza es EGRESO / NEGOCIO / OPERATIVO; inscripción configurada $5.000, plazo de alta 7 y gracia 10.

## 3. Controles técnicos a contrastar con el informe humano

Todo lo siguiente está **pendiente de ejecución y contraste posterior**, aunque el cálculo sea correcto.

| Control | Esperado condicional a realizar exactamente las operaciones propuestas |
|---|---|
| Alta de Mora | Un alta, plan y pertenencia al grupo elegidos; inscripción $5.000 y cuota $40.000, sin duplicación. Activos +1. Comprobar transacción y vínculos después del informe humano. |
| Cobros de cuotas | $186.000: $103.000 en efectivo, $45.000 Mercado Pago y $38.000 Galicia. Revisar pagos, imputaciones, deudas y recibos, sin duplicados ni diferencias de centavos. |
| Movimientos manuales | Ingreso APORTE $15.000 y egreso NEGOCIO $8.500 en efectivo. Cobros más ingreso manual $201.000; movimiento neto financiero +$192.500. |
| Arqueo | $10.000 inicial + $118.000 entradas de efectivo − $8.500 = $119.500 contados. Diferencia $0; cambio retenido $10.000 y retirado $109.500. El arqueo no se obtiene de consultar un saldo global. |
| Reportes económicos | Incremento de ingresos de octubre por cuotas $156.000, aporte $15.000 separado y egreso $8.500. Adelanto noviembre $30.000 en caja hoy y atribución económica a noviembre. Conciliar contra la base vigente, sin imponer acumulados históricos no comprobados. |
| Cobranza | Alta suma $45.000 de deuda; pagos a deuda existente restan $156.000: variación neta −$111.000. El adelanto no reduce otra deuda. Comprobar también el efecto de inscripción y estados individuales con fecha simulada 13/10. |
| Asistencia | Nómina correspondiente al grupo y día de cada clase; una marca por alumno/clase y sin solapamientos. Comparar pendientes antes/después con el mismo usuario y filtros; no fijar 33→32 después de haber cargado otra lista. |
| Validación y trazabilidad | Revisiones persistidas; reflejo único en Cashflow, medios correctos y recibos rastreables. Comprobar efectos reales tras la validación, no presumirlos por el mensaje de éxito. |
| Seguridad y rendimiento | Las pantallas compartidas no acreditan rechazo HTTP directo. Este chat prepara esos controles aparte. Registrar volumen y contexto de medición; acumular datos no prueba por sí solo performance, concurrencia ni seguridad. |

## 4. Seguimiento y referencias: a cargo del control de este chat

`OBJETIVOS-FUNCIONALES.md` dice «Ejercitado en Día 4» en sus 16 filas y «Cobertura alcanzada /
Camino feliz consolidado», pero las columnas del cuento siguen vacías. Cambiar esas etiquetas a
**propuesto o planificado, pendiente de ejecución**; una intención no es un resultado.

El enlace al catálogo sigue teniendo asociaciones equivocadas. Ejemplos comprobados en la copia:
CAJ-005 trata herencia de cambio; ALU-007, fecha futura; CBZ-006, dos deportes;
MOV-001, gasto; MOV-002, ingreso no cuota; MOV-007, editar; MOV-011, visibilidad entre operativas;
CAJ-027, doble validación; CAJ-028, intento de validar de una operativa.
Este chat debe depurar su mapeo técnico y no considerar esas referencias como cobertura acreditada.
No se modifica la tabla de Carlos/Claude ni se agrega una operación solo para justificar un código.

Para el seguimiento de Mora: con alta 13/10 y plazo 7, el límite calculado es 20/10 a las 00:00;
último día EN_PLAZO 19/10 y MOROSO desde el 20 si conserva deuda. El contrato incluye el ejemplo
equivalente 28/09 + 7: EN_PLAZO hasta 04/10 y DEUDOR desde 05/10. No usar el par 20–21 como frontera.
Esto es preparación temporal de este chat; no requiere agregar una prueba de mora al Día 4.

## 5. Evidencia y límites

HEAD local y GitHub leídos en `7cf48ece947e427f54b950219dd36d30075bed3c`.
Sitio de prueba leído en `56379295a33782858ce9c986e200f13382d61cc4`; `.env` confirmó
DB_DATABASE=wingstest antes de las consultas. Diff de app/routes/resources entre ambos commits: vacío.
Se consultaron filas concretas de alumnos, planes, deudas, clases, docentes, configuración, caja y subrubros.

Fuentes directas: `ClaseWebController::show/storeAsistencias`, `AlumnoWebController::store`,
`CajaWebController::validar/verificarMovimiento`, `CajaService::validarCaja`,
`ReporteMensualService::obtener`, `ReporteAlumnosService::obtener`, `ReporteWebController::alumnos`,
vistas de reportes/alumnos, formulario de alta y detalle de caja, rutas y contrato de cobranza.
El MCP orientó la búsqueda; cobertura informó metadata_changed/coverage_unavailable y un Blade
no parseable. Se usó lectura directa para esas conclusiones; no es una auditoría exhaustiva del sistema.

Una consulta inicial usó `subrubros.clasificacion`, columna inexistente; se corrigió a
`clasificacion_resultado` tras leer el esquema. El error de consulta no es un defecto de Wings.
No se cambiaron producto, catálogos, alumnos, clases, pagos ni reloj. Sin suite por revisión documental.

Siguiente: resolver los ajustes del relato señalados en §1, entregar la versión coherente y preparar
el inicio técnico. El recorrido humano sigue en su otro chat; T14 permanece en curso.

## 6. Segunda revisión: d34dee7a, 22/10/2026 — guion apto

Se leyeron el diff y las situaciones corregidas, no solo el resumen de Gemini.

- Tarea 11 separa la nómina de Principiantes de la lista de Avanzadas. Consulta de prueba confirmó
  las 12 alumnas del grupo 3, incluidas Emma Fernández y Martina Herrera; la clase 28 corresponde
  al martes de 17 a 18. La clase 6 de Principiantes es el miércoles de 16 a 17.
- Un profesor puede consultar Clases y abrir el detalle de la próxima clase. ClaseWebController
  permite listar futuras y show obtiene la nómina; la vista muestra los alumnos aunque la clase
  no haya ocurrido, con controles de asistencia deshabilitados. La comprobación de Mora se hace
  allí sin registrar asistencia del miércoles ni cambiar el reloj.
- Tareas 2 y 14 ajustadas a las redirecciones ya comprobadas. Tarea 15 usa matrícula actual y
  desglose por deporte/nivel; tarea 16 separa Aportes del ingreso económico por cuotas.
- El arqueo conserva $119.500; caja 1 sigue VALIDADA con $10.000 de cambio retenido en consulta.
- Las columnas de ejecución permanecen vacías. Se normaliza el seguimiento funcional a
  PLANIFICADO, se retiran referencias incompatibles y se deja explícita la cobertura parcial.
  El contador histórico se comparará antes/después, sin fijar 33→32 para distintos roles/filtros.
- El plazo futuro de Mora queda como seguimiento técnico 19/10 EN_PLAZO → 20/10 MOROSO si
  conserva deuda, según código y contrato comprobados en la revisión inicial.

La preparación del reloj se separa del trabajo humano. Se aclaró el acceso a la nómina por la clase
del miércoles y se corrigieron etiquetas y referencias documentales, sin sumar situaciones ni
alterar los pedidos de las personas. El relato de Gemini queda revisado; la edición documental de
seguimiento de este chat no constituye ejecución ni cobertura aprobada.

Al comenzar esta revisión HEAD era d34dee7aa790f47d46d25f08687819f3bbcb39b5, carpeta limpia.
Código funcional sin diferencias con el sitio 56379295 ya comprobado. MCP volvió a informar
metadata_changed/coverage_unavailable en las fuentes de clases: se leyeron los fragmentos reales.
Solo SELECT en wingstest y lecturas de código/documentos; sin operar la jornada, cambiar el reloj,
corregir producto ni desplegar. T14 sigue en curso. Siguiente: preparar el entorno técnico para
el martes 13/10 y ejecutar la historia en el chat Prueba humana, con autorización de Carlos.
