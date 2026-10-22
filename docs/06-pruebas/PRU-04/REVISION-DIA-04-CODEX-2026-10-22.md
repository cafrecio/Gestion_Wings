# Día 4 — revisión de a86c3509 por Codex CyE

22/10/2026. **DEVUELTA la preparación; no ejecutar todavía.**

Se leyó el contenido de los cinco archivos del commit, no solo el resumen de Gemini. Carpeta limpia al iniciar; HEAD local y GitHub coincidentes en `a86c3509e10486ab6648a2da4cc443aaa836d8c8`.

## Qué se corrigió

- Adelanto: ahora describe cuota de noviembre, pago e imputación. Coincide con los cuerpos de `registrarPagoCuotaOperativo`, `aplicarPagoADeudas`, `obtenerOcrearDeuda` y `relacionarPagoConDeudas` revisados en este ciclo.
- Rutas principales de cobros, movimientos, apertura, cierre y validación corregidas contra `routes/web.php`.
- COB-009 ya no se acredita escribiendo observaciones voluntarias. Se separa visibilidad de menú de rechazo de peticiones directas.
- Se agregó seguimiento temporal y una comprobación inicial; resultados de las 18 tareas siguen vacíos.

## H1 — Los antecedentes no sustituyen comprobar la base inicial

Se reemplazó 62/0/15/23 por 65/0/22/13; esta última distribución está documentada en los días 2–3, pero no es por eso una certificación del estado actual del 13/10 tras cambios de versión. La revisión anterior pedía explicar la diferencia, no escoger la cifra más antigua.

El paso 0 permite «anotar» una discrepancia sin indicar inequívocamente que no se continúa con las 18 operaciones y sus totales fijos. Debe detener el inicio hasta reconciliar versión, reloj, distribución y alumnos representativos; no modificar datos para hacerlos coincidir. Los resultados finales se conservan como condicionales a esa base. El contador de asistencia también se mide al inicio y antes/después de cada lista, sin atribuirle siempre 33→32.

No se calcularon hoy contadores globales en el servidor; no se certifica ninguna de las dos distribuciones como actual.

## H2 — El relato contradice el catálogo financiero real

Consulta **de solo lectura** en `wingstest`, 22/10, código del sitio `56379295a33782858ce9c986e200f13382d61cc4`; reloj consultado: hora real 22/10/2026 13:03, sin fecha simulada. Filas concretas obtenidas:

| Registro | Tipo del rubro | Clasificación | Permiso |
|---|---|---|---|
| Indumentaria institucional / Indumentaria | INGRESO | APORTE | OPERATIVO |
| Limpieza / Gastos Operativos | EGRESO | NEGOCIO | OPERATIVO |
| Rubro Alquileres, id 7 | EGRESO | Según subrubro | — |

La operación 11 crea el subrubro dentro de **Alquileres**, y la 12 quiere usarlo como **Ingreso**. Cambiar el permiso no cambia el tipo del rubro. La preparación debe plantear un ingreso con una clasificación compatible, sin alterar ni reclasificar Alquileres para salvar el cuento.

Además, el esperado de **$347.000 de ingresos económicos** incluye los $15.000 de indumentaria. `ReporteMensualService::obtener` filtra `clasificacion === NEGOCIO` para ingresos/egresos; APORTE se informa aparte. Esos $15.000 sí entran al disponible, pero no al ingreso económico del club.

Recalcular ingresos, egresos, aportes y resultado después de resolver el concepto del alquiler. Si los $12.000 fueran un ingreso NEGOCIO correctamente registrado y se conservara el antecedente de $164.000, el cálculo condicional sería $164.000 + $156.000 de cuotas + $12.000 = **$332.000** de ingresos; no $347.000. No se afirma que esa operación ya exista ni se elige un rubro nuevo por Carlos. La creación del subrubro también debe definir la clasificación exigida por el formulario.

## H3 — El plazo de Mora está corrido un día

Configuración leída en el sitio: `dias_gracia_cobranza=10`, `dias_plazo_alta=7`.

En [CobranzaEstadoService](../../../app/Services/CobranzaEstadoService.php), `limiteVencimientoAlta` suma 7 días al 13/10 a las 00:00: límite **20/10 00:00**. `calcularEstadoDesdeDeudas` y `calcularEstadoInscripcion` mantienen EN_PLAZO solo mientras la fecha sea estrictamente anterior al límite.

Con esos parámetros y las deudas del relato: último día en plazo **19/10 23:59:59**; primer día MOROSO **20/10 00:00**. La matriz propone estar en plazo hasta el final del 20 y pasar el 21: no coincide con el código. Contrastar el contrato antes de cambiar el esperado; si discrepa, presentar la diferencia a Carlos, sin corregir producto por cuenta propia. Revisar todas las menciones en los cuatro documentos.

## H4 — El adelanto de Sofía no prueba comisión de una docente por hora

Filas leídas en el sitio: Sofía Gómez pertenece a **Patín**, grupo 1; Lucía Gaitán es de tipo **HORA**, tarifa $12.000, sin porcentaje; Hernán Quintana es **COMISION**, 40%.

La matriz manda buscar una comisión por el pago de Sofía en la liquidación de Lucía. Esa relación no acredita LIQ-002. El cálculo HORA usa clases/minutos; COMISION exige pagos y asistencia mensual al profesor correspondiente.

Conservar el adelanto de Sofía para ingreso económico y no duplicación. Marcar la cobertura de comisión adelantada pendiente de un caso de deporte/profesor COMISION, o presentar una sustitución coherente dentro de las 18 filas para revisión de Carlos. No agregar operaciones ni cambiar el deporte del personaje silenciosamente.

La fila conjunta de asistencias también debe separar HORA de COMISION: no todo alumno presente genera comisión, y una ausencia en una clase no descarta por sí sola un presente en otra clase del mismo mes. El control del 1/12 queda fuera del calendario hasta 2/11: registrarlo pendiente, no como cobertura ya prevista dentro de ese ensayo.

## H5 — Persisten referencias que no acreditan el caso citado

Contraste literal con los JSON originales de solo lectura:

| Operación del borrador | Referencia que sigue mal | Qué trata realmente |
|---|---|---|
| Apertura de Sandra heredando cambio | CAJ-004 / CAJ-012 | Primera apertura / el dueño abre para una operativa; heredar cambio está en CAJ-005 |
| Plazo del alta de Mora | CBZ-006 / ALU-007.b | Una persona en dos deportes / rechazo de una fecha futura fuera del límite |
| Parcial de Dante | COB-006.a / COB-007.c como FIFO | Un pago que salda dos meses / sigue DEUDOR al saltar deuda; no describe este parcial ni su transición a MOROSO |
| Ingreso de indumentaria | MOV-001 / MOV-007 | Gasto de caja / corrección de movimiento |
| Gasto de limpieza | MOV-002 / MOV-011 | Ingreso no cuota / visibilidad de cobros entre operativas |
| Crear subrubro | CAT-001 | Crear/editar deportes |
| Menús por rol | ACC-008 | Salir y volver atrás con el navegador |
| Validación del dueño | CAJ-027 / CAJ-028 | Validar dos veces / intento de validar por una operativa |

Revisar cada letra contra su texto; listar solo la parte efectivamente ejercitada y dejar el resto pendiente. No modificar el catálogo para que encaje con el relato.

## H6 — La matriz aún mezcla identificadores técnicos correctos e inventados

Aunque las rutas principales se corrigieron, quedan `CajaService::abrirCaja`, `registrarMovimiento`, `cerrarCaja`, `ReciboPdfService` y `WebController::index` como referencias sin correspondencia con el flujo declarado. Los métodos de Caja encontrados en la fuente son `abrirCajaOperativa`, `registrarMovimientoOperativo` y `cerrarCajaOperativa`; recibos se atienden con `ReciboService`, y el inicio ADMIN con `WebController::adminDashboard`.

También aparece `movimientos_operativos.tipo='INGRESO/EGRESO'`: el registro del servicio guarda subrubro y monto; el tipo se obtiene del rubro. No presentar columnas, IDs de medios/personas o nombres de middleware como comprobados sin contrastarlos. Revisar toda la matriz, no solo los cinco ejemplos de rutas del prompt anterior.

## Alcance y siguiente paso

Se revisaron documentos, código y filas concretas de configuración/catálogos/docentes; no se ejecutó el Día 4, ni se cambió producto, reloj, clasificación ni datos de negocio. Sin suite ni migraciones por revisión documental.

Una primera consulta de solo lectura intentó seleccionar `profesores.tipo_liquidacion`, que es un atributo derivado; MariaDB la rechazó. Se corrigió con JOIN a deportes antes de usar el resultado. No se tomó el error de la consulta como defecto del producto.

El MCP localizó símbolos, pero `check_index_coverage` siguió devolviendo `coverage_unavailable / metadata_changed`; las conclusiones anteriores se apoyan en lectura directa de los cuerpos y catálogos. No se acredita un barrido exhaustivo de todo el sistema.

Gemini corrige la preparación y entrega una nueva revisión. Carlos decide cualquier cambio de relato que requiera elegir concepto/dominio. T14 sigue en curso y el ejecutor no comienza mientras las condiciones iniciales y el esperado financiero sean contradictorios.
