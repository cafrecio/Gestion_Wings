# T52 — Verificación independiente de lógica

## Reverificación de e1e7c499 — Codex CyE, 20/10/2026

**LÓGICA APROBADA en la revisión independiente.** Se leyó el cuerpo actual de `Alumno::scopeEnGrupoElDia`: exige siempre ingreso ocurrido y, cuando existe cambio, llegada al grupo ocurrida. La fecha del cambio ya no puede adelantar un ingreso futuro. Sin modificación de producto por Codex.

Nueve pruebas propias, conservadas sin cambiar su criterio, más las doce regresiones de T52: **21 aprobadas, 139 aserciones, 19,79 s, salida 0**, `wings_testing_codex`, sin otra suite activa al iniciar. [Salida](evidencia-t52-reverificacion-codex/revision-h5-e1.txt) · [Filas del nuevo corte](evidencia-t52-reverificacion-codex/observaciones-h5-e1.jsonl) · [Fuentes verificadas](evidencia-t52-reverificacion-codex/fuentes-h5-e1.json).

H5 web repetido: ingreso 01/11 y cambio de grupo 20/10; clase del 20/10 ya no lo lista, POST devuelve **422 y no guarda marca**. H1–H4, reactivación por los tres roles, plan diferido y protección de cerradas/pagadas HORA/COMISIÓN siguen aprobados. Consultas: **12 con uno y 12 con doce alumnos**.

Visual de Carlos conservado: «Las vistas están OK». La migración de cambios antiguos con plan diferido sigue siendo una aproximación conservadora declarada, no una reconstrucción de la fecha real. No se despliega ni se migra el club. T52 cerrada por script con hizo=Claude y verifica=Codex. Completa compartida con T42, corte 1003: **997 aprobadas, 6 omitidas, 8652 aserciones, 1242,09 s, salida 0** ([salida](evidencia-t42/suite-completa-corte1003.txt)). No hubo cambios de producto T52 por Codex.

## Antecedente: reverificación de d148a241 — Codex CyE, 20/10/2026

**DEVUELTA A CLAUDE POR H5. H1–H4 corregidos en los escenarios originalmente reproducidos.**
Las cuatro fuentes principales de T52 (modelo, controlador de clases, servicio y migración) no tienen diferencias entre d148a241 y HEAD al verificar.

Nueve pruebas independientes: **8 aprobadas, 1 fallida, 59 aserciones, 21,50 s, salida 1**, exclusivamente `wings_testing_codex`, después de terminar la suite T61. [Pruebas](evidencia-t52-reverificacion-codex/T52ReverificacionTest.php) · [Salida final](evidencia-t52-reverificacion-codex/resultado-final.txt) · [Filas observadas](evidencia-t52-reverificacion-codex/observaciones.jsonl) · [Fuentes](evidencia-t52-reverificacion-codex/fuentes.json).

- H1: POST del inactivo sin marca devuelve 422 sin escribir.
- H2: corregir ingreso tras cambiar de grupo el mismo día conserva la fecha del cambio.
- H3 original: migración del ingreso futuro con primer plan coincidente conserva `grupo_desde=NULL`; la fecha efectiva de ingreso excluye clases anteriores.
- H4: detalle con 1 y 12 alumnos hace **12 consultas en ambos casos**. No crece por alumno.
- Reactivación por tres roles, cambio inmediato con plan diferido y bloqueo de cerradas/pagadas HORA/COMISIÓN siguen aprobados.

### H5 — Cambiar de grupo antes del ingreso futuro habilita asistencia anticipada

Caso alcanzable por web, con reloj en **20/10/2026**:

1. Alumno activo con ingreso y plan desde **01/11/2026**, fecha permitida por T50.
2. ADMIN envía PUT `/alumnos/{id}` cambiando A→B, con plan válido y sin alterar el ingreso. El guardado confirma éxito.
3. Persisten ingreso **01/11** y `grupo_desde=20/10`.
4. GET de una clase de B del **20/10** incluye al alumno; POST de asistencia devuelve **200 y guarda un presente** antes del ingreso.

Causa leída en `Alumno::scopeEnGrupoElDia`: al existir `grupo_desde`, la rama `orWhereDate('grupo_desde', '<=', $dia)` deja de exigir que la fecha de ingreso haya llegado. El mismo scope alimenta GET y POST. No es una inferencia de agregados: se inspeccionaron los campos concretos y la asistencia persistida en base descartable.

Se devuelve a Claude para conservar la exclusión anterior al ingreso incluso después de cambiar de grupo. Codex no cambia producto ni toma decisiones nuevas sobre datos históricos. El criterio de migración de cambios antiguos con plan diferido sigue siendo una aproximación declarada; no se convierte en fecha real por pasar este ensayo.

La suite permanente conjunta reciente dio **976 aprobadas/6 omitidas, 8527 aserciones, 804,11 s, salida 0**, corte 982, durante T61. No cubría H5: el caso independiente nuevo lo reproduce en rojo. No se presenta ese verde como aprobación de T52. Visual de Carlos conservado; sin cambios de clases ni despliegue.

## Antecedente de la verificación anterior


Codex CyE, 20/10/2026. Entrega de Claude: `2dfbf243` y `dd45ce0e`.

**CON HALLAZGOS.** Tres incumplimientos funcionales reproducidos y un N+1 previo.
Suite completa: 962 aprobadas, 5 omitidas y 2 fallidas en T33; T33 aislada: 10 aprobadas, sin causa atribuida.

## Alcance

Solo lógica: elegibilidad por fecha y grupo, conservación de marcas, edición del ingreso,
migración de existentes, permisos y protección de liquidaciones. Visual aprobado por Carlos
el 20/10 («Las vistas están OK»), conservado sin nueva revisión ni cambios de pantalla.
No se corrige producto. T50 pertenece a Gemini y se preserva.

Base descartable: `wings_testing_codex`. Se esperó a que terminara la suite ajena iniciada
a las 09:02:27 (PID 46620 / PHPUnit 4708) antes de ejecutar pruebas propias.

## Hallazgos reproducidos

### H1 — El guardado acepta a un inactivo sin marca que la pantalla excluye

`app/Http/Controllers/ClaseWebController.php:303` exige `activo=true` para el grupo actual;
en `:402` el POST usa fecha/grupo y marcas previas, pero omite ese filtro.
Alumno inactivo desde el fixture, del grupo y con ingreso previo a la clase, sin asistencia:
GET no lo incluye; POST como OPERATIVO devuelve **200 y crea el presente**. Debía devolver
422 sin escribir, conforme al criterio de misma elegibilidad entre show y store.
No depende de la API deshabilitada: se reprodujo por `/clases/{id}/asistencias`.
La falta de filtro activo en el POST ya existía antes de T52; la entrega no la resuelve.

### H2 — Cambiar de grupo el mismo día del ingreso permite retrotraer ese cambio

`app/Models/Alumno.php:68`: que `grupo_desde` coincida con el ingreso anterior no demuestra
que el alumno nunca cambió de grupo.
Se crearon ingreso y cambio A→B el 20/10, usando el **PUT web real con un plan válido**.
Un segundo PUT corrigió el ingreso al 1/9, con motivo válido y sin inscripción cobrada.
Resultado persistido: `grupo_desde=2026-09-01`, y el alumno aparece en B para una clase del
10/10, aunque llegó a B el 20/10. Debía conservar `2026-10-20` y quedar fuera de esa lista.
El caso es alcanzable en edición; las siete pruebas originales no cubren esa coincidencia.

### H3 — El backfill adelanta un ingreso futuro y permite listar antes del ingreso

`database/migrations/2026_10_20_150000_add_grupo_desde_to_alumnos.php:34` toma el máximo
del ingreso y primer plan; luego `:35` lo recorta a hoy.
Se ejecutó `down/up` de esa migración sobre un alumno existente con ingreso y plan el
1/11, con reloj en 20/10. Persistió **grupo_desde=20/10 y apareció en la clase del 20/10**.
El ingreso 1/11 está dentro del límite permitido por T50.

El criterio «nunca antes del ingreso ni después del día de migración» es imposible para
ese dato. Se devuelve la contradicción sin elegir una nueva regla ni corregir producto:
hay que definir cómo conservar la exclusión hasta el ingreso cuando este sea futuro.
No se probó con datos del club ni se afirma que exista allí un alumno de ese tipo.

### H4 — N+1 en el detalle, anterior a T52

`app/Http/Controllers/ClaseWebController.php:315` llama por cada alumno a
`ClaseService::contarAsistenciasSemana` (`app/Services/ClaseService.php:217`).
Con planes asignados: **12 consultas para 1 alumno y 45 para 12**, tres por cada alumno
adicional (asistencias, asignación y plan). SQL guardado en la evidencia.
Se contrastó con `git show 2dfbf243^:app/Http/Controllers/ClaseWebController.php`:
el bucle y la llamada ya existían. El criterio de ausencia de N+1 no pasa; no se atribuye
una regresión de rendimiento nueva a T52.

## Backfill histórico: límite confirmado

Pasan los casos de primer plan igual al ingreso, alumno sin plan y plan anterior al ingreso
(se conserva el ingreso como mínimo).
También se reprodujo un cambio a B del 5/10 con plan B diferido al 1/11. Tras el backfill
del 20/10, `grupo_desde` queda en 20/10 y el alumno no aparece en la clase B del 10/10 sin
marca previa. La fecha histórica real del cambio no se puede recuperar de esas dos fechas.
Es una limitación de la aproximación declarada; no se certifica reconstrucción histórica
exacta ni se inventa una fecha para corregirla. Las marcas previas tienen su excepción
independiente y se conservan por `orWhereIn`.

## Fuentes y límites

Se consultó codebase-memory primero; `check_index_coverage` devolvió
`coverage_unavailable / metadata_changed` para las fuentes utilizadas. Las conclusiones
se contrastan con los cuerpos de los archivos reales, no con la cobertura del índice.
La consulta adicional de cobertura de rutas, arranque y controladores API devolvió
`Transport closed`; se verificaron directamente esos archivos.
Las [huellas iniciales](evidencia-t52-codex/fuentes-antes.json) permiten comprobar cambios
durante la revisión de la carpeta compartida.

### Caminos de escritura examinados

- Alta web y edición: `AlumnoWebController`, creación y actualización por modelo.
- Primera carga Excel: `PrimeraCargaExcelService::confirmar`, `Alumno::create` seguido de
  `forceFill(...)->save()`. Dispara el evento de creación de `grupo_desde`.
- Reactivación manual: `AlumnoWebController::toggleActivo`, actualiza `activo`, sin cambiar grupo.
- Reactivación al cobrar: `PagoCuotaService` usa actualizaciones masivas de `activo`, sin
  modificar `grupo_id` ni `fecha_alta`; no necesita disparar el evento de grupo por ese cambio.
- `CambioPlanService::cambiar` modifica asignaciones de plan; el grupo se cambia en la edición
  web antes de aplicar el plan. El plan puede comenzar el mes siguiente.
- Los controladores API usan modelos; la API continúa deshabilitada en `bootstrap/app.php`.
- Búsqueda literal de `grupo_id` y escrituras de alumnos en `app/`: no se encontró una escritura
  masiva de grupo que eluda el evento. Esta conclusión no abarca SQL externo al repositorio.

### Pruebas originales

Se leyeron completas las siete pruebas de `ClaseListaHaciaAdelanteT52Test`.
Cubren marcas presentes y ausentes conservadas, cambio/baja, corrección con motivo,
rechazo de otro grupo o llegada posterior y lectura por profesora.
La prueba del modelo cambia de grupo en un día distinto del ingreso; no cubre la coincidencia
de ambas fechas. No ejecuta el backfill sobre alumnos existentes ni mide consultas.

`CancelarLiquidacionCerradaTest`: el cambio de `dd45ce0e` agrega únicamente el ingreso
2026-08-01 a un alumno cuya clase es 2026-08-10. Conserva las comprobaciones de HTTP 422,
ausencia de escritura, cancelación administrativa y posterior guardado permitido.
No se retiró ninguna protección ni se redujo una aserción.

## Ejecución

Las [reproducciones independientes](evidencia-t52-codex/T52VerificacionTest.php) viven fuera
de `tests/`, para no alterar el contador permanente ni las pruebas originales.
Tienen una guarda que exige entorno `testing` y base `wings_testing_codex`.

Reproducciones: **8 pruebas, 51 aserciones, 4 fallidas, 24,626 s, salida 1**.
[Salida](evidencia-t52-codex/pruebas-independientes.txt) ·
[Código de salida](evidencia-t52-codex/pruebas-independientes.exit) ·
[Filas observadas y SQL](evidencia-t52-codex/observaciones.jsonl).
Hubo una primera ejecución de siete casos (45 aserciones, los mismos cuatro fallos);
se repitió la selección completa al agregar el caso histórico de backfill y al dejar deporte y profesora de COMISIÓN coherentes en el fixture final.

Comprobaciones independientes aprobadas: cambio de grupo inmediato por PUT con mes pagado
y plan nuevo al 1/11; reactivación manual conserva la fecha del grupo y ADMIN/OPERATIVO/
PROFESOR reciben la misma lista; cerradas HORA/COMISIÓN, pendientes/pagadas, rechazan el POST
de ADMIN y OPERATIVO con 422 y conservan exactamente liquidación, detalles y asistencia.
Las pagadas son fixtures sintéticos para comprobar el bloqueo, no cobros realizados.
La reactivación no reconstruye intervalos históricos de inactividad.

Dirigidas: **74 aprobadas, 412 aserciones, 21,52 s, salida 0**.
Selección: T52, CancelarLiquidacionCerrada, T66, T34/T67, AsistenciaClaseFutura,
AsistenciasAtomicidad, ClasesCanceladasHoy y EditarClase.
[Salida](evidencia-t52-codex/dirigidas.txt) · [Código de salida](evidencia-t52-codex/dirigidas.exit).

Suite completa: **962 aprobadas, 5 omitidas, 2 fallidas, 8507 aserciones, 1019,61 s,
salida 2**. Inició a las 09:18:31 sin otra suite activa. Las cinco omitidas son
pruebas de captura condicionadas por su entorno, según la salida del corredor.
[Salida íntegra](evidencia-t52-codex/completa.txt) · [Código de salida](evidencia-t52-codex/completa.exit).

Los dos fallos son `PerdonConcurrenteT33Test`: inscripción excede el timeout de 20 s del
proceso hijo; cuota no observa al segundo proceso esperando en MariaDB. Ese archivo queda
fuera del diff de T52. Repetición aislada: **10 aprobadas, 120 aserciones, 179,90 s, salida 0**. No se atribuye causa ni se declara la completa verde. [Salida aislada](evidencia-t52-codex/t33-aislada.txt) · [Código de salida](evidencia-t52-codex/t33-aislada.exit).

Las nueve fuentes principales tienen [huellas finales](evidencia-t52-codex/fuentes-despues.json)
iguales a las iniciales. La completa se inició sobre `42d99a33`; durante su ejecución
`31823eb5` guardó el avance documental de esta revisión junto con la toma T64 de Gemini,
sin cambiar esas fuentes. Este informe completa aquel avance; no lo confunde con un cierre.

T52 devuelta a Claude con el script del tablero: corregir H1–H3 y resolver el criterio H4;
visual aprobado conservado. Sin cambios de producto ni despliegue.
