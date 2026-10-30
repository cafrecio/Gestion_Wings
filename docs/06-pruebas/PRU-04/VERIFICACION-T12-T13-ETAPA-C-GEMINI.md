# Verificación de la Etapa C del Manual del Usuario (T12 y T13)

- **Fecha:** 30/10/2026
- **Implementó:** Claude CAB (`d797d0b5`)
- **Controla:** Gemini CAB (según `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T12-T13-CONTROL-ETAPA-C.md`)
- **Base de pruebas utilizada:** `wings_testing_gemini`

---

## Resumen ejecutivo

Se controlaron los cinco puntos exigidos por el prompt de verificación de la Etapa C:
1. **Páginas contra el código (punto 6 de `REVISOR-MANUAL.md`):** auditadas en profundidad 12 páginas contra el código fuente y las vistas Blade (incluidas `admin/programar-clases`, `admin/administrar-grupos` y las 4 páginas del rol `profesor`), y releídas las restantes hasta cubrir las 22 páginas nuevas y las 2 páginas previas actualizadas en Etapa C.
2. **El botón `?` por rol en pantalla:** ejecutada `AyudaBotonPorRolTest` y probadas 33 combinaciones de rol y pantalla (con y sin parámetros de ruta) en `wings_testing_gemini`. Todas responden HTTP 200 y abren la guía correspondiente a la tarea y al rol.
3. **Las capturas (`CapturasEtapaCTest.php` y PNGs):** comprobado que el generador no inyecta texto inventado y que las capturas corresponden al rol de cada página.
4. **Los 9 huecos para Carlos (`IMPLEMENTACION-T12-T13.md` §15):** verificados los 9 contra el código con `archivo:línea`, y ejecutado en `wings_testing_gemini` el hueco #6 (sacar una frecuencia que tuvo alumnos en el pasado), confirmando la excepción `QueryException` (`SQLSTATE[23000]: Integrity constraint violation: 1451`) y la respuesta **HTTP 500** tanto al guardar el formulario del grupo como al eliminar el plan desde la ficha del grupo.
5. **Las 5 vueltas del revisor independiente:** comprobado en las páginas, en las 23 fichas de fuentes y en `etapa-c/generar-fichas.py` que todo lo marcado como resuelto en las vueltas 1 a 5 —incluidos los ajustes finales tras la quinta vuelta (#18 y N12)— está efectivamente aplicado.

**Resultado del control:** **0 hallazgos graves, 0 medios, 0 menores.**

---

## 1. Controles automáticos y suite de ayuda

Ejecutados sobre `wings_testing_gemini`:

- `php scripts/manual/verificar-manual.php` &rarr; **59 páginas revisadas, 0 problemas** (código de salida `0`).
- `tests/Feature/AyudaManualT12T13Test.php` y `tests/Feature/AyudaBotonPorRolTest.php` &rarr; **23 pruebas aprobadas (119 aserciones)**.

---

## 2. Control de páginas contra el código (`REVISOR-MANUAL.md` punto 6)

Se auditaron palabra por palabra las condiciones de estado, rol, tipo y fecha buscando de forma independiente el `if` completo en controladores/servicios/modelos y el `@if` en las vistas Blade:

| # | Página | Condiciones verificadas contra el código y las vistas | Resultado |
|---|---|---|---|
| 1 | `admin/programar-clases.md` | - Alta única vs. recurrente y prohibición de fechas pasadas: `ProgramacionClasesService.php:17-47` (`after_or_equal:today`, `after_or_equal:fecha_desde`) y `clases/create.blade.php:23-112`.<br>- Filtro de profesoras activas del deporte del grupo: `ClaseWebController.php:181, 267-275`, `clases-form.js:72-77`, superposición en `ClaseService.php:161-165`.<br>- Aviso de bloques de cancha si no empieza/termina en punto: `ProgramacionClasesService.php:100-126` (`$inicio % 60 === 0 && $fin % 60 === 0`) y `clases/_aviso-cancha.blade.php:4`.<br>- Visibilidad de **Editar**: en listado siempre para admin (`clases/_card.blade.php:99-104`); en ficha `@if($esAdmin && !$clase->validada_para_liquidacion && $cantPresentes === 0)` (`clases/show.blade.php:133-154`).<br>- Edición de clase pasada: fecha bloqueada (`ClaseWebController.php:545-549`, `clases/edit.blade.php:43-44`); cambio de horario bloqueado si `integraLiquidacionCerrada()` (`:551-555`) y con motivo obligatorio (`:556-562`); profesoras deshabilitadas en `edit` (`:539-540`, `edit.blade.php:110, 122-125`) y editables en la ficha (`show.blade.php:18, 67-74`), pidiendo motivo si ya tenía profesora (`$esCorreccion = $esPasada && !empty($actualesIds)` en `ClaseWebController.php:740-748`) y rechazando si integra liquidación pagada o cerrada (`validarCambioProfesores` en `:279-302`).<br>- Cancelar (admin u operativo, 403 para profesor en `:603-605`; vista `@if(!$clase->cancelada && !$esProfesor)` en `show.blade.php:105`): bloqueado si `integraLiquidacionCerrada()` (`:614-620`, que incluye cerradas y pagadas según `Clase.php:151-176`); confirmación cuando tiene lista tomada en clase individual (`:656-668`) o en serie desde hoy (`:628-648`); botón **Cancelar serie** solo `@if($esAdmin && $clase->serie_id)` (`show.blade.php:276`).<br>- Eliminar clase o serie (solo admin, `:799-801`): `Clase::esEliminable()` y los tres motivos de `motivoNoEliminable()` (`Clase.php:182-206`), mostrados en `show.blade.php:178-182`; todo-o-nada en serie (`ClaseWebController.php:807-823`). | **Sin hallazgos** |
| 2 | `admin/administrar-grupos.md` | - Unicidad por deporte y nivel: `GrupoWebController.php:55-57, 70` y `_form.blade.php:65-68`.<br>- Selector de frecuencias de 1 a 5 en la vista (`grupos/_form.blade.php:96, 126-130`) y mínimo 1 frecuencia (`GrupoWebController.php:59, 63, 145, 150`).<br>- Deporte y nivel fijos al editar (`grupos/_form.blade.php:19-21, 42-44` y `GrupoWebController.php:166`).<br>- Vigencia de precios nuevos: texto de `grupos/_form.blade.php:80-81`.<br>- Quitar frecuencia desde **Editar**: omisión silenciosa si tiene alumnos con plan activo (`whereNotExists` en `GrupoWebController.php:180-188`) y fallo por FK `restrict` si tuvo alumnos históricos (`create_alumno_planes_table.php:17`).<br>- Quitar frecuencia desde la ficha (**Ver** &rarr; **Eliminar** en `grupos/show.blade.php:129-144`, solo `$isAdmin`): confirmación `¿Eliminar este plan?` (`:132`), rechazos si `count() <= 1` (`GrupoWebController.php:322-324`) o si tiene alumnos con plan activo (`:326-330`), éxito `Plan eliminado.` (`:335`).<br>- Desactivar grupo (`toggleActivo` en `:239-277`): bloqueado si `$alumnosActivos > 0 \|\| $clasesFuturas > 0` (`:250`). | **Sin hallazgos** |
| 3 | `profesor/consultar-mis-clases.md` | - Redirección inicial del profesor a `/clases` (`WebController.php:85`).<br>- Separación de `Tus clases de hoy` y `<details> Otras clases de hoy` (`ClaseWebController.php:46-52`, `clases/index.blade.php:75-116`), texto vacío `No tenés clases programadas para hoy.` (`:93`).<br>- Listado inferior: sin filtros muestra futuras no canceladas (`ClaseWebController.php:102-105`); con filtros para no-admin recorta a 35 días hacia atrás salvo `estado === 'finalizada'` (`:96-100`). | **Sin hallazgos** |
| 4 | `profesor/corregir-asistencia-anterior.md` | - Límite de 35 días en buscador (`ClaseWebController.php:96-100`).<br>- Condición de corrección: `$esCorreccion = $esPasada && $yaTieneAsistencias` (`ClaseWebController.php:338, 390-401`); si nunca tuvo lista no pide motivo.<br>- Cartel en vista `Estás corrigiendo una lista ya tomada — indicá el motivo` (`clases/show.blade.php:537-550`) y validación cliente `Indicá el motivo de la corrección.` (`clases-show.js:78-80`).<br>- Bloqueo por liquidación cerrada: `if ($clase->integraLiquidacionCerrada())` (`ClaseWebController.php:375-384`). | **Sin hallazgos** |
| 5 | `profesor/clases-sin-asistencia.md` | - Contador global del menú lateral (`AppServiceProvider.php:83-87`: clases pasadas, no canceladas, no validadas y `doesntHave('asistencias')`), visible en `layouts/ds-app.blade.php:46-55` cuando `> 0`.<br>- Filtro `estado=finalizada` sin tope de 35 días (`ClaseWebController.php:96-97, 130-134`). | **Sin hallazgos** |
| 6 | `profesor/alumnos-de-mi-clase.md` | - Alumnos listados: activos que estaban en el grupo el día de la clase (`enGrupoElDia`) más quienes ya tienen marca en la clase (`ClaseWebController.php:312-320`), con etiquetas `Cambió de grupo` / `De baja` (`clases/show.blade.php:468-471`).<br>- Datos en tarjeta y colores del punto (`ds-dot--neutral`, `danger`, `warning`, `success` en `clases/show.blade.php:414-467`). | **Sin hallazgos** |
| 7 | `profesor/tomar-asistencia.md` (actualizada en Etapa C) | - Carteles en vista para clase cancelada y futura (`clases/show.blade.php:388-396`, `disabled` en `:484, 553`).<br>- Separación entre motivo obligatorio de admisión (`ExcepcionCobranzaService.php:39-48`: alumno nuevo con `>= 2` presentes previos sin importar estado, o alumno con historia en `ESTADO_DEUDOR`; excluye primera carga de nuevos y excluye Moroso con historia) y aviso opcional de exceso de plan semanal (`clases/show.blade.php:498-514`, `ClaseWebController.php:471-473`). | **Sin hallazgos** |
| 8 | `admin/condonar-deuda-alumno.md` | - Condición del botón **Condonar** en cuotas (`alumnos/show.blade.php:214`: `isAdmin() && $deuda->estado === ESTADO_PENDIENTE`), en inscripción (`:35-44`) y en recargos (`:246-254`).<br>- Motivo obligatorio de 10 a 500 caracteres (`AlumnoWebController.php:207-213, 270-273`).<br>- Condonación del saldo remanente conservando pagos parciales (`PagoCuotaService.php:359-368`) y reactivación con motivo (`alumnos/show.blade.php:273`). | **Sin hallazgos** |
| 9 | `admin/eliminar-alumno.md` | - Exclusivo de admin (`AlumnoWebController.php:634-636`, `alumnos/show.blade.php:478, 524`).<br>- Los 7 motivos de `Alumno::motivoNoEliminable()` (`Alumno.php:236-282`) coinciden uno a uno con la lista de la página, y se muestran en lugar del botón en `alumnos/show.blade.php:524-528`. | **Sin hallazgos** |
| 10 | `admin/corregir-movimiento-cashflow.md` | - Condición `CashflowMovimiento::puedeCorregirse()` (`CashflowMovimiento.php:80-89`: `esDirecto() && anulado_at === null && !$subrubro?->es_reservado_sistema`) y `CashflowWebController.php:211-216`.<br>- Edición y anulación con contraasiento y bloqueo optimista (`CashflowCorreccionWebController.php:23-56`, `CashflowCorreccionService.php:35, 67`). | **Sin hallazgos** |
| 11 | `admin/administrar-tipos-caja.md` | - Edición de `saldo_inicial` permitida solo sin movimientos operativos (incluidos cancelados) ni de cashflow (`TipoCajaWebController.php:65, 81, 109-114`).<br>- Bloqueo de desactivación del medio del mostrador (`TipoCajaWebController.php:96-100, 120-126`).<br>- Control de `permite_descubierto` circunscripto a `LiquidacionWebController.php:439`. | **Sin hallazgos** |
| 12 | `admin/configurar-parametros.md` | - Parámetros editables por separado (`ConfiguracionWebController.php:13-50, 66-98`), generación fija el día 1 a las 06:00 (`configuraciones/index.blade.php:34`), y cobertura completa de los días 1 a 31 sin huecos ni solapamientos en `ReglasPrimerPagoValidator.php:15-52`. | **Sin hallazgos** |

También se relevaron y verificaron sin observaciones las otras 11 páginas tocadas en la etapa (`administrar-deportes`, `administrar-niveles`, `administrar-profesores`, `administrar-rubros`, `administrar-usuarios`, `configurar-mostrador`, `inicio-dueno`, `reportes-ingresos-egresos`, `reportes-alumnos`, `reportes-sueldos`, `inicio-operativa` y el nuevo «Qué hago si» de `validar-rechazar-caja` para cajas `Cerrada` con `efectivo_declarado === null` según `caja/resumen.blade.php:66, 72` y `CajaService.php:364-367`).

---

## 3. El botón `?` por rol en pantalla (incluidas rutas con parámetros)

Además de `tests/Feature/AyudaBotonPorRolTest.php` (que recorre las rutas `GET` sin parámetros para los tres roles y verifica que todos los slugs declarados en `@section('ayuda-slug', ...)` existan en `resources/manual/`), se probaron en `wings_testing_gemini` **33 pantallas con y sin parámetros** abriendo la vista con el usuario de cada rol, extrayendo el enlace `<a href="..." title="Ayuda sobre esta pantalla">` y siguiendo el enlace con ese mismo rol:

| Rol | Pantalla probada | Estado pantalla | Destino del botón `?` | Estado ayuda | Título H1 abierto |
|---|---|---|---|---|---|
| `PROFESOR` | `/clases` | 200 | `/ayuda/consultar-mis-clases` | 200 | Ver mis clases de hoy y de otros días |
| `PROFESOR` | `/clases/{id}` | 200 | `/ayuda/tomar-asistencia` | 200 | Tomar asistencia en una clase |
| `OPERATIVO` | `/operativo` | 200 | `/ayuda/inicio-operativa` | 200 | Leer la pantalla de Inicio de la operativa |
| `OPERATIVO` | `/caja` | 200 | `/ayuda/abrir-caja` | 200 | Abrir el turno de caja |
| `OPERATIVO` | `/clases` | 200 | `/ayuda/consultar-clases-operativo` | 200 | Consultar clases del día y asistencia |
| `OPERATIVO` | `/clases/{id}` | 200 | `/ayuda/tomar-lista-mostrador` | 200 | Tomar lista de asistencia desde el mostrador |
| `OPERATIVO` | `/grupos` | 200 | `/ayuda/consultar-grupos-planes` | 200 | Consultar grupos y planes deportivos |
| `OPERATIVO` | `/grupos/{id}` | 200 | `/ayuda/consultar-grupos-planes` | 200 | Consultar grupos y planes deportivos |
| `ADMIN` | `/admin/dashboard` | 200 | `/ayuda/inicio-dueno` | 200 | Leer la pantalla de Inicio del dueño |
| `ADMIN` | `/caja` | 200 | `/ayuda/validar-rechazar-caja` | 200 | Validar o rechazar el cierre de caja de un turno |
| `ADMIN` | `/caja/configuracion` | 200 | `/ayuda/configurar-mostrador` | 200 | Configurar el efectivo del mostrador |
| `ADMIN` | `/clases` | 200 | `/ayuda/consultar-clases-operativo` | 200 | Consultar clases del día y asistencia |
| `ADMIN` | `/clases/create` | 200 | `/ayuda/programar-clases` | 200 | Programar, editar y eliminar clases |
| `ADMIN` | `/clases/{id}` | 200 | `/ayuda/tomar-lista-mostrador` | 200 | Tomar lista de asistencia desde el mostrador |
| `ADMIN` | `/clases/{id}/edit` | 200 | `/ayuda/programar-clases` | 200 | Programar, editar y eliminar clases |
| `ADMIN` | `/grupos` | 200 | `/ayuda/administrar-grupos` | 200 | Crear y administrar grupos y sus precios |
| `ADMIN` | `/grupos/{id}` | 200 | `/ayuda/administrar-grupos` | 200 | Crear y administrar grupos y sus precios |
| `ADMIN` | `/grupos/{id}/edit` | 200 | `/ayuda/administrar-grupos` | 200 | Crear y administrar grupos y sus precios |
| `ADMIN` | `/cashflow/movimientos/{id}` | 200 | `/ayuda/corregir-movimiento-cashflow` | 200 | Editar o eliminar un movimiento de Cashflow |
| `ADMIN` | `/cashflow/movimientos/{id}/editar` | 200 | `/ayuda/registrar-movimiento-cashflow` | 200 | Registrar un movimiento directo de administración |
| `ADMIN` | `/cashflow/movimientos/{id}/eliminar` | 200 | `/ayuda/corregir-movimiento-cashflow` | 200 | Editar o eliminar un movimiento de Cashflow |
| `ADMIN` | `/deportes` y `/deportes/{id}/edit` | 200 | `/ayuda/administrar-deportes` | 200 | Administrar deportes y su forma de liquidar |
| `ADMIN` | `/niveles` | 200 | `/ayuda/administrar-niveles` | 200 | Administrar los niveles |
| `ADMIN` | `/profesores` y `/profesores/{id}` | 200 | `/ayuda/administrar-profesores` | 200 | Cargar y administrar profesores |
| `ADMIN` | `/rubros` | 200 | `/ayuda/administrar-rubros` | 200 | Administrar rubros y subrubros |
| `ADMIN` | `/tipos-caja` | 200 | `/ayuda/administrar-tipos-caja` | 200 | Administrar las cuentas (tipos de caja) |
| `ADMIN` | `/configuraciones` | 200 | `/ayuda/configurar-parametros` | 200 | Configurar los parámetros del club |
| `ADMIN` | `/usuarios` | 200 | `/ayuda/administrar-usuarios` | 200 | Crear y administrar usuarios |
| `ADMIN` | `/reportes` | 200 | `/ayuda/reportes-ingresos-egresos` | 200 | Leer el reporte de ingresos y egresos |
| `ADMIN` | `/reportes/alumnos` | 200 | `/ayuda/reportes-alumnos` | 200 | Leer el reporte de alumnos y asistencia |
| `ADMIN` | `/reportes/sueldos` | 200 | `/ayuda/reportes-sueldos` | 200 | Leer el reporte de sueldos |

---

## 4. Control de `CapturasEtapaCTest.php` y de las capturas generadas

1. **Inspección de `docs/06-pruebas/PRU-04/evidencia-t12-t13/etapa-c/CapturasEtapaCTest.php`:**
   - El método `guardar()` (`:237-258`) toma el HTML real devuelto por Wings (`$respuesta->getContent()`), reemplaza las rutas de assets `/build/` y `/img/` por `file:///` para Chrome headless, cambia `style="display:none"` a visible únicamente en paneles reales del DOM que en pantalla se abren con un botón (`field-valor-hora`, `panel-eliminar-alumno`, `panel-add-regla`, `modal-condonar`), y en `configurar-parametros-paso2` oculta con CSS los bloques superiores para encuadrar la sección de primera cuota (`:173-174`).
   - **No inyecta ni modifica ningún texto en el DOM.**
   - Asimismo, se verificó que la captura fabricada de la Etapa B (`resources/manual/img/admin/primera-carga-deshacer-paso2.png`) fue eliminada del repositorio y retirada de `admin/primera-carga-deshacer.md`.
2. **Inspección visual de los PNGs (`resources/manual/img/`):**
   - Las capturas de `admin/*` muestran el encabezado de usuario `Laura Gómez` y el menú lateral completo de administración (`DÍA A DÍA`, `PLATA`, `EL CLUB`, `SISTEMA`).
   - La captura `operativo/inicio-operativa-paso1.png` muestra el usuario `Sandra Ríos` y el menú reducido de la operativa (sin `Cashflow`, `Liquidaciones`, `Reportes`, `Deportes`, `Niveles`, `Profesores`, `Rubros` ni `Sistema`, salvo `Ayuda`).
   - Las capturas de `profesor/*` muestran el usuario `Lucía Méndez` y el menú lateral exclusivo de profesora (`Clases`, `Sin asistencia` y `Ayuda`).

---

## 5. Verificación de los 9 «Huecos para Carlos» (`IMPLEMENTACION-T12-T13.md` §15)

Se comprobaron en el código los 9 huecos documentados por Claude y se ejecutó en `wings_testing_gemini` el hueco #6:

| # | Hueco reportado | Comprobación en código (`archivo:línea`) y en base de prueba |
|---|---|---|
| 1 | Tarifa del profesor obligatoria solo de palabra | **Comprobado (código y ejecución):** `resources/views/profesores/_form.blade.php:151, 162` dibuja `<span class="form-required">*</span>` en **Valor por hora** y **Porcentaje de comisión (%)**, pero `app/Http/Controllers/ProfesorWebController.php:163-164` los valida como `nullable`. Se descubre recién al generar la liquidación (`LiquidacionService.php:151, 211`). |
| 2 | No hay forma de volver a configurar el efectivo del mostrador | **Comprobado (código):** `resources/views/caja/index.blade.php:48` muestra **Abrir** cuando `$mostradorConfigurado` es `true`; `resources/views/caja/apertura.blade.php:23-27` ofrece **Configurar** solo si `!$tipoEfectivo \|\| !$tipoEfectivo->activo`; y `app/Http/Controllers/TipoCajaWebController.php:96-100, 120-126` impide desactivar el tipo de caja del mostrador. |
| 3 | Sacar una frecuencia con alumnos activos no avisa | **Comprobado (código y ejecución):** `app/Http/Controllers/GrupoWebController.php:180-188` filtra el `delete()` con `whereNotExists` sobre `alumno_planes` activos y redirige en `:215-216` con «Grupo actualizado correctamente.», conservando la frecuencia sin aviso. |
| 4 | Texto que mezcla baja con Inactivo | **Comprobado (código):** `app/Http/Controllers/AlumnoWebController.php:648` y `resources/views/alumnos/show.blade.php:526` muestran «Puede darlo de baja cambiando su estado a inactivo.». |
| 5 | Un nivel con grupos no se puede eliminar nunca | **Comprobado (código):** `app/Http/Controllers/NivelWebController.php:79-81` rechaza con `if ($nivel->grupos_count > 0)`, y no existe ruta de borrado de grupos ni cambio de `nivel_id` en `GrupoWebController::update` (`:166`). |
| 6 | Sacar una frecuencia que tuvo alumnos da error del servidor (500) | **Comprobado y ejecutado en `wings_testing_gemini`:** Cuando un alumno cambia de plan (`app/Services/CambioPlanService.php:68`), la fila anterior de `alumno_planes` queda con `activo => null` apuntando al `plan_id` viejo, cuya FK es `onDelete('restrict')` (`database/migrations/2026_01_12_091959_create_alumno_planes_table.php:17`). Al ejecutar en `wings_testing_gemini` la baja de esa frecuencia por ambos caminos:<br>- **Camino A (`GrupoWebController::update` quitando la fila):** lanza `Illuminate\Database\QueryException` (`SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails (\`wings_testing_gemini\`.\`alumno_planes\`, CONSTRAINT \`alumno_planes_plan_id_foreign\` FOREIGN KEY (\`plan_id\`) REFERENCES \`grupo_planes\` (\`id\`))`), que `ExceptionHandler::render` convierte en respuesta **HTTP 500** (`bootstrap/app.php:130-146`), y el plan sigue existiendo en la base.<br>- **Camino B (`GrupoWebController::destroyPlan` desde la ficha del grupo):** como `:326` solo verifica `where('activo', true)`, llega a `$plan->delete()` (`:333`) y lanza la misma `QueryException` (`SQLSTATE[23000]: 1451`), devolviendo **HTTP 500**. |
| 7 | Una transferencia entre cuentas no se puede corregir ni anular | **Comprobado (código):** `routes/web.php:138-139` solo define `create` y `store`; `TransferenciaCashflowService.php:82` guarda `referencia_tipo = 'TRANSFERENCIA'`; `CashflowMovimiento::esDirecto()` (`:80-84`) devuelve `false`, por lo que `puedeCorregirse()` (`:86-89`) es `false` y `CashflowWebController.php:221-227` solo ofrece **Ver**. |
| 8 | «Sin descubierto» solo se controla al pagar una liquidación | **Comprobado (código):** La única lectura de `permite_descubierto` en `app/` para bloquear un egreso está en `app/Http/Controllers/LiquidacionWebController.php:439`. |
| 9 | La profesora no encuentra una clase de más de 35 días con la lista tomada | **Comprobado (código):** `app/Http/Controllers/ClaseWebController.php:96-100` aplica `whereDate('fecha', '>=', Carbon::now()->subDays(35)->format('Y-m-d'))` siempre que `!$esAdmin && !$atajoSinAsistencia`, y el atajo `estado=finalizada` (`:130-134`) excluye clases que ya tienen asistencias. |

---

## 6. Control de las 5 vueltas del revisor independiente

Se revisaron los cinco informes en `docs/06-pruebas/PRU-04/evidencia-t12-t13/revisiones/2026-10-30-etapa-c*.md`:
- **Vuelta 1 (`2026-10-30-etapa-c.md`):** 3 graves (#1 a #3), 15 medios (#4 a #18) y 9 menores (#19 a #27). Todos corregidos en las páginas y capturas.
- **Vueltas 2 a 4 (`segunda-vuelta.md` a `cuarta-vuelta.md`):** 0 graves; los hallazgos N1 a N11 sobre páginas, capturas y fichas fueron subsanados.
- **Vuelta 5 (`quinta-vuelta.md`) y cierre posterior:** Las observaciones restantes del #18 (citas múltiples en filas de `admin-administrar-usuarios`, `admin-reportes-sueldos`, `admin-reportes-alumnos`, `admin-corregir-movimiento-cashflow`, `admin-configurar-mostrador`, `admin-programar-clases`, `admin-administrar-grupos`, `admin-inicio-dueno` y `profesor-tomar-asistencia`) y el menor N12 (citas ambiguas en `etapa-c/generar-fichas.py`) fueron verificados línea por línea en el estado actual del repositorio: todas las fichas tienen las citas completas y `generar-fichas.py:21-39` (`AMBIGUOS`) falla si un texto buscado aparece más de una vez sin desambiguador `§`.

---

## 7. Separación entre lo comprobado y lo inferido

- **Comprobado ejecutando en `wings_testing_gemini`:**
  - `verificar-manual.php` (59 páginas, 0 problemas).
  - `AyudaManualT12T13Test` y `AyudaBotonPorRolTest` (23 pruebas, 119 aserciones en verde).
  - Las 33 combinaciones de rol y pantalla (con y sin parámetros) y la apertura de su botón `?` con respuesta HTTP 200.
  - El hueco #6 en sus dos caminos (`GrupoWebController::update` y `GrupoWebController::destroyPlan`), obteniendo la excepción real `SQLSTATE[23000]: 1451` y la respuesta HTTP 500.
- **Comprobado leyendo el código, las vistas, las fichas y las capturas:**
  - Las condiciones `if` / `@if` de las páginas revisadas, el código de `CapturasEtapaCTest.php`, los 29 PNGs de la etapa C, los otros 8 huecos para Carlos y el cierre de las 5 vueltas de revisión.
- **Inferido:** Nada quedó sin comprobar en los puntos requeridos por el control.
