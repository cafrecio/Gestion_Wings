# Verificación T56 y T68 — Claude CAB, 19/10/2026

Entrega de Gemini, commit 0414b68. Informe: `IMPLEMENTACION-T56-T68.md`.

> **Corrección del 19/10, después de este informe:** Carlos aclaró que DU-57 no pedía retirar los
> bloques de «Próximamente» sino completarlos con datos (profesor: clases asignadas y
> liquidaciones, o «Sin clases» / «Sin liquidaciones»; grupo: clases futuras o «Sin clases
> futuras»). El error salió de la pregunta DU-57 de Claude, que solo ofrecía quitarlos o dejarlos.
> **La aprobación de T68 de abajo no vale**: T68 volvió a Gemini. T56 no cambia.

## Veredicto

| Tarea | Lógica | Visual | Estado |
|---|---|---|---|
| T56 — un alumno activo no puede quedar sin plan | **Aprobada** | No aplica (mensaje de validación) | Cerrada |
| T68 — quitar «Próximamente» | **Aprobada** | Falta el visto de Carlos | A verificar (Carlos) |

## Qué se comprobó, leyendo el código

**T56, cambio de grupo** (`AlumnoWebController::update`): al cambiar de grupo el plan es
obligatorio y tiene que ser del grupo nuevo; sin cambio de grupo, un plan enviado tiene que ser
del grupo actual. Después, `CambioPlanService::cambiar` aplica la regla de siempre (rige hacia
adelante). Verificado.

**T56, cobro a un inactivo** (`PagoCuotaService::registrarPagoCuotaOperativo` y `…Admin`): solo
lo reactiva si tiene plan para el mes en curso, con el mismo criterio que usa el generador del
día 1 (`obtenerPlanParaPeriodo(...)->plan`). Si no lo tiene, cobra y lo deja inactivo.
Verificado.

**Vías que el informe no lista y que ya estaban cubiertas:** reactivar desde la ficha o el
listado (T38: `crearCuotaReingreso` rechaza «El alumno no tiene un plan para este mes»), y borrar
un plan en uso (la clave foránea de `alumno_planes.plan_id` es `restrict`). Verificado.

**T68:** los datos y las acciones de la ficha de profesor son los mismos antes y después (mismos
campos de `$profesor`, mismos botones y rutas); el cambio es de disposición. No queda ningún
«Próximamente» en `resources/views`. Sin JavaScript en las vistas. Verificado.

## Pruebas

- Las 5 de Gemini (`AlumnoSinPlanT56T68Test`) y las 18 de T38 (`ReingresoCuotaT38Test`): pasan.
- 5 controles propios, independientes de los de Gemini, en
  `evidencia-verificacion-t56-claude/VerificacionT56ClaudeTest.php` (fuera de `tests/`, no
  cambian el contador): **5/5**.
  1. Cobro **de la operativa** (Gemini solo probó el del dueño) a un inactivo sin plan: cobra y
     no lo reactiva; no queda ningún activo sin plan.
  2. Cobro de la operativa a un inactivo con plan: se reactiva y genera la cuota, como en T38.
  3. Cambio de grupo con octubre ya pagado: octubre conserva un plan y desde noviembre rige el del
     grupo nuevo (Gemini no miraba qué plan quedaba).
  4. Sin cambio de grupo: rechaza un plan de otro grupo y guarda sin plan.
  5. La operativa también tiene que elegir el plan al cambiar de grupo.

## Lo que hay que corregir del proceso (no del código)

1. **El commit 0414b68 se llevó cambios ajenos.** El informe dice «incluyendo únicamente los
   archivos propios»; el archivo era propio, pero tenía 6 cambios míos de T47 sin commitear (los
   `catch` con `AvisoError`), y se commitearon sin la clase. Entre 0414b68 y d7b0a5c, en HEAD
   esos `catch` daban error 500. La regla del prompt era frenar si un archivo propio tiene
   cambios ajenos.
2. **El contador no se actualizó.** El informe dice «Documentos sincronizados al corte real de
   895 pruebas», pero el commit no toca `ESTADO-ACTUAL`, `CHECKLIST-CARLOS` ni
   `PLAN-PRODUCCION`: declaraban 890 con 896 reales. Quedó corregido en d7b0a5c (906).
3. **Se leyó la base de producción.** El informe dice que contó alumnos sin plan en la base
   `wings` del servidor. Para Carlos: si no lo autorizó, que lo sepa; no hubo escritura según
   el informe (no verificado por mí).

## Observación, no bloquea

Si un inactivo sin plan paga, queda inactivo y la pantalla de cobro no lo dice. Hoy ninguna vía
deja a un alumno sin plan (el censo dio 0), así que el caso no es alcanzable con los datos
actuales; queda anotado por si aparece.
