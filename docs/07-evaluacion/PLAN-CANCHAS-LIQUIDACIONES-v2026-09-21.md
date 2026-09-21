# POS-07 — Ubicaciones, canchas y liquidaciones de clubes

**Versión: 2026-09-21.v1. Autor: Codex CyE. Estado: PLAN; aplicación pendiente.**

Fuente de negocio: entrevista con Carlos del 21/09, incluida su última precisión:
liquidar un mes o fechas elegidas, solamente clases dictadas hasta ese día y todavía
pendientes de liquidación. Este documento reemplaza la propuesta inicial «mejora
futura sin fórmula»; no afirma que el módulo exista ni autoriza un despliegue.

Base técnica consultada: `main`, hasta `49f66e9`. Se leyeron cuerpos de los servicios
y controladores citados en §12; no se ejecutaron pruebas ni consultas a datos reales
para redactar este plan. Revalidar ese corte antes de implementar.

[Índice IA](PLAN-TRABAJO-IA-v2026-09-08.md) ·
[Índice de Carlos](PLAN-TRABAJO-CARLOS-v2026-09-08.html#plan-canchas) ·
[Entrevista de Reportes](../05-pendientes/ENCUESTA-REPORTES-PROVISIONAL.md)

## 1. Resultado buscado y límites

ADMIN carga dónde se da una clase, elige una tarifa y obtiene su costo de alquiler.
Después revisa las clases de un club, liquida las pendientes y registra el pago.
El reporte permite recorrer **Club → Deporte → Nivel → Clases**.

Implementar para Wings ahora, con la forma de contratación definida abajo. No
construir ahora un motor universal de alquileres ni una plataforma multicliente.
Visión futura de Carlos: producto deportivo autocontratable por web, precio mensual
publicado, alta y carga sin asistencia humana, capacidad ampliable y respaldos.
Se conserva como visión, no como alcance, nombre definitivo ni política comercial.

Fuera de esta entrega: tarifas automáticas por horario, precios por minutos,
abonos de alquiler, impuestos, pagos parciales a clubes, anticipos, transferencias
bancarias automáticas, varios clubes cobrando una misma clase, compartir un alquiler
entre grupos, facturación fiscal y migración automática de gastos históricos.
Tampoco se implementan particulares ni el tablero general de Reportes por arrastre.

§2–7 desarrolla las decisiones de Carlos y sus salvaguardas operativas propuestas;
§8–11 define la implementación técnica. No atribuirle a Carlos cada elección de
tablas, estados internos o bloqueos: son el diseño propuesto para cumplir su pedido.
Nombres de tablas/campos pueden ajustarse, no las reglas confirmadas ni sus garantías.
Una necesidad real fuera de este alcance se consulta según AGENTS §6b, no se improvisa.

## 2. Ubicaciones, canchas y tarifas

- **Ubicación = club a liquidar en esta versión.** Nombre del club y dirección.
  Una liquidación agrupa las canchas de esa ubicación. No agrupar por nombre textual.
- **Cancha/pista = espacio físico**, con nombre, ubicación y estado activo.
  La cantidad de canchas de la ubicación se obtiene de sus registros; no guardar
  un segundo contador editable. Mostrar cuántas están activas.
- **Tarifa = opción de precio por hora de una cancha**, con nombre libre y valor.
  «Cancha 1 día» y «Cancha 1 noche» son dos opciones de la misma cancha física,
  no dos espacios que se puedan reservar simultáneamente.
- ADMIN selecciona ubicación, cancha y tarifa al crear una clase, también en serie.
  Validar las relaciones en servidor, no confiar en los IDs enviados.
- Una clase tiene una sola cancha y una sola tarifa. La tarifa elegida se aplica
  a todos sus bloques. No inferir cambio de tarifa a las 18 ni por la palabra «noche».
- ABM solo ADMIN. Baja lógica: un catálogo usado no se borra ni pierde referencias.
  Inactivo no se ofrece para nuevas selecciones; las clases e historia existentes
  se conservan. Reactivar no cambia precios. No trasladar una cancha usada a otro club
  ni una tarifa usada a otra cancha: crear el registro correcto, preservando historia.
- Nombre obligatorio; sin duplicados de cancha dentro de una ubicación ni de tarifa
  dentro de una cancha. Respetar normalización habitual de formularios; no agregar
  otro `trim` donde ya actúa el middleware.

## 3. Cálculo y corrección manual

### 3.1 Bloques completos del reloj

La unidad es **cada bloque horario del reloj ocupado**, aunque se use parcialmente.
No es `ceil(duración / 60)`: eso cobraría una hora donde Carlos confirmó dos.
Horario de fin exclusivo: terminar a las 18:00 no ocupa el bloque 18:00–19:00.

Para horarios del mismo día, expresados en minutos desde medianoche:

`bloques = ceil(fin / 60) - floor(inicio / 60)`

`costo_calculado = bloques × precio_hora_aplicado`

Validar fin posterior a inicio. Mantener la restricción actual de clases dentro del
mismo día; no incorporar cruces de medianoche en esta tarea.

| Horario | Bloques | Tarifa elegida | Costo automático |
|---|---:|---:|---:|
| 17:00–18:00 | 1 | $29.000 | $29.000 |
| 16:30–17:30 | 2 | $29.000 | $58.000 |
| 17:30–18:30 | 2 | $29.000 | $58.000 |
| 17:00–19:00 | 2 | $29.000 | $58.000 |
| 18:00–19:00 | 1 | $40.000 | $40.000 |
| 17:00–17:15 | 1 | $29.000 | $29.000 |

El ejemplo 17:30–18:30 **no suma dos tarifas diferentes automáticamente**. Carlos
aclaró que no hay configuración para saber que la segunda hora es más cara. Si el
acuerdo real exige otro total, ADMIN lo corrige para esa clase.

### 3.2 Valor particular de la clase

- ADMIN puede reemplazar el **total de alquiler de esa clase**, no el precio por hora
  de todas las clases. No exigir motivo para esta corrección monetaria.
- Ejemplo: costo calculado $58.000, total acordado $50.000. Guardar ambos y marcar
  el segundo como manual. Conservar usuario y fecha del cambio sin pedir texto.
- No inferir «manual» comparando importes: puede coincidir con el automático.
- Permitir volver explícitamente al cálculo automático; mostrar el importe resultante
  antes de guardar. No borrar un valor manual por cambiar otro campo silenciosamente.
- Tarifas y totales no negativos, dos decimales. Cero explícito es distinto de falta
  de costo: esta última bloquea liquidar, no se convierte en gratuidad.
- Aritmética en centavos enteros; persistencia `DECIMAL`, nunca suma binaria de floats
  como fuente de verdad de los nuevos importes. Rechazar desborde y exceso de decimales.
- El servidor calcula; la vista presenta ese resultado. Vista previa y guardado usan
  el mismo servicio. El navegador no mantiene otra fórmula ni manda un total confiable.
  Si el presupuesto cambió entre consulta y guardado, informar y reconfirmar, no guardar
  un total diferente del anunciado.

### 3.3 Aumentos y conservación histórica

- Un cambio de tarifa actualiza clases que **todavía no empezaron**, con esa tarifa,
  no liquidadas y con costo automático. El instante del cambio se toma una vez en
  la zona horaria de la aplicación. Inicio igual al instante: ya no es futura.
- No cambia clases pasadas, iniciadas, costos manuales ni liquidaciones históricas.
- Clase futura con manual $50.000 sigue en $50.000 aunque su automático pase de
  $58.000 a $64.000. Conservar el nuevo automático como referencia, sin reemplazar manual.
  Esa actualización de referencia usa el mismo límite temporal y no toca el total manual.
- Fecha/horario/tarifa modificados por ADMIN recalculan el automático. Si había manual,
  conservarlo, advertir que sigue vigente y permitir restablecer automático explícitamente.
- Una corrección del costo de una clase pasada pendiente es individual; no es una
  aplicación retroactiva del precio vigente. No reconstruir su precio desde el catálogo actual.
- Conservar snapshots y cambios auditables; renombrar catálogos o modificar grupos no
  debe reescribir club, deporte, nivel e importes de una liquidación cerrada.

## 4. Agenda y clases

- No permitir dos clases no canceladas superpuestas en la **misma cancha física**,
  aunque seleccionen distintas tarifas, grupos, deportes o profesores.
- Solape real: mismo día, `inicio_A < fin_B` y `fin_A > inicio_B`. Clases que se tocan
  en un extremo son compatibles. No confundir bloques facturables con ocupación real.
- Conservar controles existentes de profesores y alumnos. Cancha agrega un control,
  no reemplaza los de FIN-10 ni los permisos/validaciones históricas.
- Crear una serie es atómico: un conflicto en una fecha rechaza toda la serie y
  muestra qué clase entra en conflicto. No dejar clases ni costos parciales.
- Una clase no cancelada cuyo horario ya terminó se considera dictada para **alquiler**,
  aun sin asistencia cargada. No exige `validada_para_liquidacion`: Carlos confirmó
  que el costo existe igual. No cambiar por esto el cálculo de profesores.
- Clase en curso o futura no entra en una liquidación de club. Comparar fecha y hora
  de fin con un único instante de corte; no seleccionar todas las de hoy a las 00:00.

## 5. Cancelación, revisión y privacidad

### 5.1 Quién decide

| Acción/dato | ADMIN | OPERATIVO | PROFESOR |
|---|---|---|---|
| Ver ubicación/cancha de una clase accesible | Sí | Sí | Sí, dentro de su acceso actual |
| Ver o modificar tarifas, costos, pago de cancha y liquidaciones | Sí | No | No |
| Cancelar clase | Conserva permiso actual | Conserva permiso actual, sin datos financieros | No |
| Resolver si una cancelada se paga al club | Sí | No | No |

Esto **no quita al OPERATIVO su cancelación actual** ni le concede acceso a costos.
No ocultar con CSS: datos financieros ausentes de HTML, atributos `data-*`, scripts,
JSON, respuestas de error, PDFs y endpoints accesibles a no administradores. Usar
listas explícitas de campos; no serializar el modelo financiero completo.

### 5.2 Tratamiento económico de la cancelación

- ADMIN, al cancelar, debe elegir si corresponde pagar el alquiler. Sin respuesta,
  no completar esa cancelación administrativa; el motivo de cancelación existente
  sigue siendo obligatorio, distinto del motivo de corrección de precio que no se pide.
- OPERATIVO cancela con su motivo habitual. El costo queda **pendiente de decisión
  del ADMIN**, sin mostrarle la pregunta ni el importe. Nunca asumir «se paga» o «gratis».
- Estados separados para la decisión económica: `NO_APLICA` (no cancelada),
  `PENDIENTE_ADMIN`, `PAGAR`, `NO_PAGAR`. No usar el importe cero como sustituto del estado.
- ADMIN puede revisar la decisión y el total antes del cierre. El resumen destaca
  canceladas a pagar con fecha, cancha, motivo y monto; muestra también las excluidas
  y las pendientes. No esconder esas filas por filtrar `cancelada = false`.
- Una cancelada a pagar se considera candidata cuando llegó el fin de su horario
  original, no por haberla cancelado anticipadamente. Hasta entonces queda fuera del corte.
- Si queda una decisión pendiente dentro del período y corte solicitado, se puede
  mostrar el borrador pero **no cerrarlo**. Las futuras no bloquean ese cierre.
- Cancelar una serie aplica la misma regla a cada clase afectada; ADMIN puede dar una
  decisión común al lote y corregir casos individuales antes de liquidar. OPERATIVO
  deja cada decisión pendiente. Operación atómica, no saltear bloqueos mediante update masivo.
- Reactivar, solo ADMIN: revalidar cancha/profesores, limpiar decisión económica de
  cancelación y conservar la historia del cambio. Respetar los bloqueos de liquidación.

## 6. Liquidar por club sin repetir clases

### 6.1 Selección exacta

Dos entradas de pantalla, **un solo selector de clases en servidor**:

1. **Mes:** desde el primer día hasta el último día real del mes elegido.
2. **Período:** desde/hasta elegidos, ambos inclusive. Validar desde <= hasta;
   puede cruzar meses, sin convertirlo artificialmente en varias liquidaciones.

Ambas incluyen únicamente clases de ese club, dentro de las fechas solicitadas,
con horario terminado al corte, con costo definido y **no incluidas en otra
liquidación vigente**. No canceladas: costo final. Canceladas: solo decisión `PAGAR`.
`NO_PAGAR` se muestra excluida, no genera importe. `PENDIENTE_ADMIN` bloquea el cierre.

**Liquidada no significa pagada.** Una clase incluida en una liquidación abierta
queda reservada allí; en una cerrada, queda liquidada aunque el club no haya cobrado.
Solo liberar por descarte/cancelación de esa liquidación, nunca por falta de pago.
Identificar clases individualmente; NO crear una unicidad «club/mes» que impediría
liquidaciones parciales y complementarias, ni descontar un total previo sin mirar sus clases.

Ejemplo obligatorio: se liquida 1–13. Después se elige el mes entero: aparecen
solamente pendientes hasta el nuevo corte, normalmente del 14 en adelante. Una clase
del día 10 que no quedó incluida y ahora sí es elegible también debe aparecer.
Si se vuelve a pedir el mes y no hay candidatas, mostrar «Sin clases pendientes»;
no crear otra liquidación vacía ni volver a cobrar lo ya liquidado.

El borrador registra rango solicitado y corte efectivo. Recalcular es explícito y
actualiza corte/candidatas/importes; revalidar al cerrar. Si cambió lo que ADMIN revisó,
mostrar el detalle actualizado y pedir que cierre de nuevo: no cerrar silenciosamente
con más clases o con otros montos. Usar versión de revisión del borrador.

### 6.2 Estados y correcciones

Aplicar la lógica de revisión/cierre/pago de profesores, con entidades separadas:

- `ABIERTA`: detalle revisable, reservas de clases, ninguna salida de dinero.
- `CERRADA` + pago pendiente: detalle e importe congelados, deuda con el club.
- `CERRADA` + pagada: un pago total y su egreso, ambos trazables.
- `CANCELADA`: preserva detalle, usuario, fecha y motivo; excluida de totales vigentes.

ADMIN puede descartar un borrador o cancelar una cerrada no pagada, conservando
auditoría y liberando sus clases en la misma transacción, conforme al principio de
FIN-12. Una regeneración puede tomar un conjunto diferente: guardar relación de
procedencia por detalle/clase, no inventar un reemplazo único si hay varios nuevos períodos.

Una liquidación pagada no se borra, reabre ni recalcula. Si surge una corrección de
plata posterior, parar y pedir el alcance del ajuste compensatorio: este plan no
autoriza devolver fondos ni inventa un motor de notas de crédito.

Los cambios de clase que alteren costo, pertenencia al club, período, inclusión o
clasificación quedan bloqueados mientras pertenezca a una liquidación cerrada.
Esto protege el dato histórico de esa clase; no impide editar el nombre o configuración
general de un grupo para las clases futuras ni reescribe sus snapshots anteriores.
Primero cancelar la cerrada no pagada; si está pagada no hay edición retroactiva.
Al OPERATIVO se le informa que requiere revisión del ADMIN, sin revelar datos financieros.
En borrador, una edición autorizada invalida su revisión y exige recalcular antes de cerrar.
El cierre del alquiler **no bloquea asistencias por sí mismo**, porque no determinan
ese costo; siguen rigiendo los bloqueos independientes de liquidaciones de profesores.

## 7. Pago, saldo y reporte

- ADMIN registra un pago total de la liquidación cerrada, elige tipo de caja,
  fecha efectiva y observación opcional. No enviar dinero al banco: registrar lo pagado.
- Club vinculado por FK a su subrubro de alquileres, de egreso y uso ADMIN.
  Configurar/vincular expresamente; no deducir por nombre ni elegir «el primero».
  No reutilizar rubro Sueldos ni generar un subrubro por clase.
- Al pagar: un solo egreso negativo de cashflow por liquidación, con referencia
  inequívoca **de club**, diferente de `LIQUIDACION` de profesor. No crear además
  movimiento operativo ni egreso al generar/cerrar la liquidación.
- Saldo disponible incluye saldo inicial del tipo de caja; respetar descubierto.
  Validar el importe autoritativo dentro de la operación de pago, no el enviado por cliente.
- Adoptar FIN-09 vigente: fecha futura rechazada; mes actual directo; anterior requiere
  confirmación y aviso ADMIN. Conservar fecha real, no modificar cajas ya controladas.
  Aviso/PDF después del commit; su falla no anula ni repite el pago.
- Pago repetido devuelve el pago existente sin segundo egreso. Si pago y asiento
  discrepan, detener y reportar: no «reparar» asignando el medio del segundo request.
- Total cero: mostrar saldo cero/sin desembolso; no crear egreso ficticio ni ofrecer
  un pago de importe positivo. Conservar la liquidación y su detalle a cero.
- Comprobante no fiscal ADMIN: club, período solicitado, corte, clases efectivamente
  incluidas, canceladas abonadas, desglose por deporte/nivel, total, fecha/medio de pago
  del registro exacto. PDF reproducible desde snapshots; fallo regenerable sin otro pago.

**Tres lecturas, sin sumarlas entre sí:** costo de clases por fecha de clase;
liquidaciones cerradas pendientes por pagar; egresos efectivos por fecha del pago.
Reporte del módulo por Club → Deporte → Nivel, con detalle reconciliable. No repartir
por cantidad de clases si los costos son diferentes: sumar costos reales de sus filas.
Excluir liquidaciones canceladas de totales vigentes y no duplicar clases por joins.

El futuro Reportes general mantiene gastos no atribuibles aparte como «Gastos del club».
La incorporación de alquileres cerrados al apartado por pagar queda identificada como
extensión de la encuesta, no como funcionalidad ya existente. Un pago de alquiler
no se suma otra vez al costo de sus clases para calcular un mismo resultado.
No decidir aquí la pregunta pendiente sobre restar gastos generales al filtrar deporte.

## 8. Modelo técnico propuesto

Migraciones nuevas y aditivas; nunca editar migraciones históricas para simular una
base nueva. Todas las relaciones con historia usan restricción de borrado, no cascada
que borre datos de clases o dinero. Los nombres siguientes son orientativos:

| Entidad | Datos e invariantes mínimos |
|---|---|
| `ubicaciones` | ID, nombre, dirección, activo, subrubro de alquiler vinculado; cantidad de pistas calculada |
| `canchas` | ID, ubicación FK, nombre, activo; unicidad ubicación/nombre |
| `cancha_tarifas` | ID, cancha FK, nombre, precio/hora decimal, activo; unicidad cancha/nombre |
| `clase_costos_cancha` | Una fila por clase (FK única), cancha/tarifa, snapshots de club/deporte/nivel y precio/hora, bloques, automático, manual nullable, decisión de cancelación, auditoría y versión |
| `liquidaciones_club` | Club, fechas desde/hasta, corte efectivo, versión revisada, estado, total, auditoría de creación/cierre/cancelación y datos exactos del pago |
| `liquidacion_club_detalles` | Liquidación y clase, snapshot completo de horario/costo/cancelación/clasificación, importe final; única clase dentro de esa liquidación |
| `clases_liquidacion_club_vigente` | Clase FK única → liquidación vigente; reserva activa independiente del detalle histórico; liberar solo al descartar/cancelar |

Guardar en la clase/costo las referencias explícitas; usar snapshots para historia.
Índices de agenda (cancha/fecha), rango por fecha, FK y pendientes; evitar N+1.
Un detalle histórico no se elimina cuando se libera su reserva. No agregar un índice
único global sobre `(referencia_tipo, referencia_id)` de cashflow: rompería otros orígenes,
como documenta FIN-05. Unicidad de pago del nuevo módulo en sus propios datos.

Una ubicación puede representar un club hoy; separar «organización con varias sedes»
es evolución futura. No codificar nombres reales, un cliente tenant ni reglas de las 18 h.
No crear clubes/tarifas de producción con seeders inventados.

## 9. Transacciones, concurrencia y entradas de servidor

Servicios separados propuestos: costo/agenda de cancha, liquidación de club y pago
de club. No generalizar a la fuerza `LiquidacionService` de profesores: su elegibilidad
y prorrateo por minutos son diferentes. Extraer solo utilidades genuinamente comunes.

Todas las rutas nuevas de catálogos, costos, presupuestos, revisión financiera,
liquidaciones, pago y comprobantes llevan autorización ADMIN en servidor. Mantener
solo ubicación/cancha no financiera en las vistas de clases compartidas. En cancelación
operativa, rechazar campos financieros inyectados (403, sin cambios parciales).
API continúa apagada. Sin React, Alpine, Livewire, Redis ni workers nuevos.

Protocolo de bloqueo del módulo: ubicaciones afectadas en orden de ID, canchas y
tarifas en orden de ID, liquidaciones, clases y costos en orden de ID. Todas las
mutaciones que compitan deben respetarlo; releer tras bloquear, no usar modelos precargados.
Para cambios entre clubes bloquear ambos. Las consultas previas sirven para identificar
claves; si cambian al adquirirlas, abortar/reintentar la operación completa.

- Bloquear cancha antes de consultar solapamientos, incluso si no existe ninguna
  clase todavía. Un `exists` seguido de `create` no evita la doble reserva concurrente.
- La reserva única por clase más transacción impide que dos períodos superpuestos
  liquiden la misma clase. Nunca confiar en un botón deshabilitado.
- Cerrar, recalcular, cambiar costos/cancelar clases y cancelar liquidaciones coordinan
  bloqueos y versión revisada; no dejan snapshots mezclados ni reservas huérfanas.
- Pago bloquea/relee liquidación y tipo de caja, comprueba estado y saldo y registra
  todo en una transacción. Dos pagos o pago/cancelación no pueden ganar los dos.
- Bloquear tipo de caja solo protege el saldo frente a escritores que tomen el mismo
  bloqueo. Antes de afirmar protección frente a otros módulos, verificar esos escritores.
  Si exige modificar caja/cobros ajenos al alcance, **frenar y coordinar**, no afirmar
  que un bloqueo local resolvió toda la concurrencia financiera del sistema.
- No efectos externos en la transacción; callbacks post-commit idempotentes.
  Deadlock con reintento acotado no repite egreso, clase, reserva ni notificación.

## 10. Datos existentes y puesta en uso

1. Ensayar migraciones y rollback en MariaDB descartable, no `gestion_wings`.
2. Nueva relación de costo ausente en clases existentes: **no es costo cero** ni
   autoriza asignarles club o tarifa por nombre/grupo. Agenda y asistencia siguen funcionando.
3. Antes de activar carga obligatoria, ADMIN carga catálogos y asigna cancha/tarifa a
   las clases futuras existentes. Listado de faltantes visible solo ADMIN.
4. Para clases pasadas que deba liquidar con el módulo, ADMIN registra ubicación y
   costo histórico explícitos. No multiplicar tarifa actual por clases antiguas.
5. Si un rango contiene clases pasadas sin ubicación/costo, advertir el faltante y
   bloquear el cierre hasta clasificarlas: no omitir silenciosamente porque todavía
   no puede saberse a qué club pertenecen. La preparación inicial incluye esas filas.
6. No importar como pendientes alquileres ya pagados por cashflow manual. Antes de
   usar el módulo con fechas anteriores al arranque, conciliar esos pagos: definir y
   autorizar el lote real. No adivinar equivalencias de fecha/importe ni crear otro egreso.
   La primera activación ordinaria es hacia adelante; incorporación histórica es una
   operación aparte supervisada, no un backfill automático de esta entrega.
7. Nuevas clases requieren ubicación/cancha/tarifa desde la activación. No desplegar
   la obligatoriedad antes de que ADMIN tenga catálogos y acceso a completarlos.
   Si se arranca a mitad de mes sin incorporar el histórico, usar período desde la
   activación. Elegir el mes entero no puede omitir sus faltantes: debe advertirlos.
8. Backup verificado antes de migrar datos reales, smoke test sin cobros reales y
   control de permisos. Después de pagos registrados, rollback no puede borrar tablas:
   corrección hacia adelante o procedimiento de recuperación expresamente autorizado.

## 11. Plan de acción y aceptación por etapa

Una etapa por vez, con su commit; rama aislada `codex/...` si hay otros agentes
trabajando. No tocar sus archivos ni ejecutar pruebas sobre su base. El pedido actual
es el plan; implementar y desplegar requiere la orden posterior de Carlos.

| Paso | Trabajo | Depende de | Termina cuando |
|---|---|---|---|
| 1 | Contrato y mapa de impacto | Este plan | Versionar reglas de clubes, enmendar Clases/Permisos/Reportes donde corresponda; distinguir historia de regla nueva; aprobar alcance concreto de vistas |
| 2 | Catálogos y migraciones | 1 | Ubicaciones, canchas y tarifas funcionan solo ADMIN; baja lógica, relaciones y migración con datos existentes probadas |
| 3 | Costeo y agenda | 2 | Cálculo único, series atómicas, solapamientos, snapshots, manuales y aumentos futuros probados |
| 4 | Cancelación privada | 3 | ADMIN decide; OPERATIVO cancela sin datos económicos; revisión de canceladas y reactivación seguras |
| 5 | Liquidación por fechas | 4 | Mes/período, corte hasta clases dictadas, reservas únicas, revisión, cierre y cancelación sin repetición |
| 6 | Pago y comprobante | 5 | Un egreso por pago, FIN-09, saldo inicial/descubierto, idempotencia y PDF coherentes |
| 7 | Reporte del módulo | 5–6 | Club/deporte/nivel reconcilia con detalle; no duplica devengado/por pagar/pagado ni revela costos al OPERATIVO |
| 8 | Prueba integral y entrega | 1–7 | Matriz §13, navegador, concurrencia, suite y documentos; commit/pull/push verificados; despliegue separado y autorizado |

El cálculo anterior de 4–6 jornadas se refería a ABM y costo de clases, **no** al
alcance ampliado de liquidaciones, pagos y reportes. No es plazo de este plan.
Reestimar al asignar las ocho etapas y revisar integraciones/estado de la rama;
no equiparar tiempo de escribir código con prueba integral y aceptación humana.

## 12. Archivos y contratos que debe revisar la IA

Antes de programar: AGENTS completo, protocolo, logs activos, estado de la rama y
este documento. Usar `codebase-memory` como mapa; confirmar cuerpos reales. La
lista es punto de partida, no certificación de impacto exhaustivo:

- `routes/web.php`: creación/edición ADMIN y cancelación compartida actual.
- `app/Http/Controllers/ClaseWebController.php`: `store`, `update`, `toggleCancelada`,
  creación recurrente y reactivación. El update masivo de cancelación de serie necesita
  integrar la revisión financiera; no puede saltar los servicios nuevos.
- `app/Models/Clase.php`: `seSolapaCon`, `esLiquidable`, `integraLiquidacionCerrada`.
  Los dos últimos se refieren a profesores: no reutilizar su elegibilidad para clubes.
- `app/Services/LiquidacionService.php`: `montoPorClase` usa duración proporcional;
  copiarlo alteraría la regla de bloques. FIN-12 es referencia de conservación histórica.
- `app/Services/LiquidacionPagoService.php`: transacción y bloqueo por liquidación,
  cashflow y PDF posterior; está acoplado a profesores, no pasarle un ID de club.
- `app/Http/Controllers/LiquidacionWebController.php`: FIN-09 y saldo inicial incluidos
  en el pago al corte `49f66e9`. La consulta de saldo del controlador no sustituye
  la protección transaccional del nuevo flujo.
- `app/Models/CashflowMovimiento.php`: referencias de orígenes, monto firmado.
  Buscar todos los consumidores de referencias, reportes y recibos antes de agregar origen.
- Vistas de clases, catálogos, liquidaciones y PDFs: descubrir consumidores antes
  de cambiar formas de respuesta; no filtrar modelos con costos en vistas compartidas.

Contratos a respetar/reconciliar, sin reemplazarlos silenciosamente:

- [Clases y asistencias](../02-contratos/Wings-Contrato-Clases-Asistencias-V1.md).
- [Permisos y roles](../02-contratos/PERMISOS-ROLES.md).
- [Liquidaciones de profesores](../02-contratos/LIQUIDACIONES_CONTRATO_V2.md).
- [Caja y cashflow V4, FIN-09](../02-contratos/Wings-Contrato-Caja-Cashflow-V4.md).
- [Recibos V2](../02-contratos/Wings-Contrato-Recibos-PDF-V2.md).
- [Reportes V1](../02-contratos/Wings-Contrato-Reportes-V1.md): su propuesta de reparto
  por cantidad de clases es antecedente, superada para este nuevo módulo.

Diseño: autorización escrita para las vistas concretas antes de implementarlas;
leer skill Wings, DESIGN-RULES y alumnos/index canónica. Reusar `x-ds.money-input`
y componentes/patrones existentes sin modificar el componente, CSS ni colores.
Botones de un verbo; JavaScript externo con Vite; sin handlers inline nuevos.
Este HTML documental no constituye autorización general sobre `resources/views`.

## 13. Pruebas obligatorias para dar por terminada la implementación

Fixtures sintéticos, reloj controlado y MariaDB descartable. No copiar alumnos,
usuarios, claves ni dumps del club. Anotar commit, base y fecha de cada ejecución.

### A. Cálculo, catálogo e historia

1. Todos los ejemplos §3.1, extremos exactos, 1 minuto dentro de un bloque y horario
   inválido. Tarifa $29.000,25 durante dos bloques = $58.000,50, sin pérdida de centavos.
2. Crear/modificar/inactivar catálogos; FK incorrecta, duplicados, negativos y desbordes
   rechazados. Historia de una cancha inactiva consultable; selección nueva prohibida.
3. Manual $50.000 se conserva al aumentar tarifa y al modificar horario; acción explícita
   de volver a automático aplica exactamente el nuevo resultado anunciado.
4. Aumento afecta futura automática; no pasada, empezada, manual ni liquidada.
   Probar borde exacto del inicio y aumento concurrente con guardar clase.
5. Cambiar nombre de club, tarifa o clasificación de grupo no cambia un detalle cerrado.
6. Misma cancha/tarifas distintas solapadas: rechazo. Extremos contiguos y canchas
   distintas: permitido. Serie con una fecha en conflicto: cero altas parciales.
7. Campo monetario argentino «29.000» viaja/normaliza a 29000, no 29. Probar centavos,
   $1.500.000, formulario inválido que conserva valores y presupuesto cambiado antes de guardar.

### B. Permisos y cancelaciones

8. Tres usuarios reales de prueba: ADMIN, OPERATIVO y PROFESOR. Acceso directo a
   cada URL financiera y PDF denegado a los últimos dos; anónimo requiere login.
   No basta con esconder el menú: inspeccionar respuestas/HTML/Network.
9. OPERATIVO cancela y se genera `PENDIENTE_ADMIN`; no recibe monto ni pregunta de pago.
   Inyectar costo/decisión en su request: rechazo, sin escritura parcial.
10. ADMIN cancela con `PAGAR`/`NO_PAGAR`; falta de elección/motivo rechazada.
    Corregir importe sin motivo adicional permitido. Cancelada pendiente bloquea cierre.
11. Serie cancelada y reactivación: estados individuales, coste preservado, conflicto
    de cancha al reactivar impide la operación; no alterar una clase liquidada cerrada.
12. Canceladas pagables destacadas en pantalla y comprobante; las no pagables fuera
    del total pero visibles en revisión; las futuras no entran ni bloquean.

### C. Fechas, pendientes y estados

13. Preparar un club con clases de $29.000 los días 1, 10, 13, 14 y 30. Reloj día 13
    después del último horario. Liquidar 1–13: 3 filas, $87.000. Dejar sin pagar.
    Reloj día 30 luego de clase: liquidar mes entero da 2 filas, $58.000, NO $145.000.
    Pagar ambas da total $145.000, cada clase una sola vez. Repetir mes: sin pendientes.
14. Con reloj día 13, elegir mes completo: incluye solo las tres terminadas; no 14/30.
    Clase de hoy en curso excluida; al terminar aparece en siguiente liquidación.
    No asistir ningún alumno no elimina su costo. Febrero, bisiesto y cambio de año correctos.
15. Fechas elegidas inclusive, una sola fecha y período entre meses; rango invertido
    rechazado. Clase pendiente antigua dentro del rango no se pierde por otra liquidación previa.
16. Dos borradores solapados nunca reservan la misma clase. Descartar uno libera solo
    sus reservas; cancelar cerrada no pagada conserva historia y habilita reliquidar esas clases.
17. Rango vacío no crea liquidación. Costo faltante no se trata como cero; cero explícito
    sí se conserva sin pago ficticio. Candidatas con decisiones pendientes no cierran.
18. Editar una clase en borrador invalida revisión. Cierre con versión vieja no congela
    datos distintos de los revisados; cerrado bloquea cambios financieros, pagado inmutable.

### D. Pago, reportes y concurrencia

19. Caja con saldo inicial $500.000, sin movimientos, liquidación $100.000: paga una
    vez y saldo $400.000. Insuficiente sin descubierto: nada escrito. Con descubierto permitido,
    se registra el saldo negativo correcto. Egreso exactamente negativo del total cerrado.
20. Repetir pago, incluso con otro medio enviado: conserva el original, sin duplicado.
    Fecha futura rechazada; fecha anterior pide confirmación y aviso conforme FIN-09.
21. PDF con varios deportes/niveles y canceladas: detalle suma total, medio/fecha propios;
    falla del PDF no repite egreso, regeneración conserva datos aun con catálogos cambiados.
22. Reporte por club/deporte/nivel y total: suma detalle, incluye canceladas pagables,
    no duplica por profesores/asistentes. Costo, por pagar y egreso se muestran separados.
23. Dos conexiones MariaDB reales, no mocks: alta/alta solapadas; liquidar mes/período;
    cierre/edición; cierre/cancelación de clase; pago/pago; pago/cancelación de liquidación.
    Probar ambos órdenes relevantes, espera real y resultados sin duplicados ni pérdida.
    Cruces con otras salidas de cashflow: comprobar coordinación de saldo o frenar §9.
24. Migración sobre esquema previo con clases/egresos: sin pérdida ni asignación
    inventada; asistencia/agenda siguen operando; faltantes visibles ADMIN, sin revelar costos.

### E. Navegador y cierre

Recorrer por pantalla una serie, manual, aumento, cancelación por ambos roles,
liquidación parcial y complemento mensual, pago y PDF. Comparar presupuesto visible,
clase, detalle, total, cashflow y reporte. Mirar Network: sin confiar solo en tests PHP.
Comparar antes/después de pantallas compartidas y comprobar que el OPERATIVO sigue
operando sin montos de alquiler en DOM ni respuestas. Capturas sin datos reales.

Ejecutar suite completa y consignar número real de pruebas/aserciones; sintaxis PHP,
build si hay JS, compilación Blade y pruebas documentales. Verificar diff de vistas/CSS:
solo vistas expresamente autorizadas, CSS intacto; declarar `Diseno-autorizado:` con
el pedido real en los commits correspondientes. No copiar un número de suite histórico.

Actualizar estado, índices IA/HTML, contrato del área, checklist afectado y log propio.
Revisión cruzada de otro agente o Carlos. Commit por etapa, pull no destructivo y push;
comprobar SHA de rama remota antes de decir «subido». No incluir cambios ajenos.
«Implementado», «probado» y «desplegado» se registran por separado.
