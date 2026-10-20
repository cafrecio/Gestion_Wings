# Wings-Contrato-Clases-Asistencias-V1.md

> Enmienda de creación aprobada el 06/10/2026: [V2, A15/A16](Wings-Contrato-Clases-Asistencias-V2.md). Aviso con confirmación para bloques de cancha y horarios distintos por día en una sola carga recurrente. Las demás reglas de V1 se conservan; implementación local pendiente de verificación ajena y despliegue.

> Enmienda funcional 13/09/2026, pendiente de implementación: ver [Clases particulares V1](Wings-Contrato-Clases-Particulares-V1.md). Sus reglas específicas prevalecen para particulares sobre las reglas generales de este documento. Para todas las clases, el cierre bloquea asistencia a todos los perfiles; solo ADMIN puede cancelar una cerrada no pagada para revisarla. Una pagada permanece intacta y se corrige económicamente en la próxima liquidación. Las descripciones anteriores de comportamiento no certifican la implementación de esta enmienda.

**Caso de uso (Index):** 4) Clases + Asistencias
**Versión:** V1
**Estado:** CERRADO
**Origen:** `docs/05-pendientes/CUESTIONARIO-I3-CONTRATOS.md` (I3.0) — módulo sin ningún contrato previo, primera vez que se escribe. Reglas definidas junto al usuario el 2026-07-26 a partir de una auditoría completa del código real (modelos, migraciones, rutas, controllers, vistas).
**Alcance:** Clase (única y recurrente), asignación de profesores, toma y corrección de asistencia, cancelación, solapamiento de horarios.
**No incluye:** Cálculo de liquidaciones (ver `LIQUIDACIONES_CONTRATO_V2.md`), motor de estados de cobranza (Caso 10, pendiente).

---

## 4.a Roles

| Acción | ADMIN | OPERATIVO | PROFESOR |
|---|---|---|---|
| Crear clase (única o serie) | Sí | No | No |
| Editar clase (fecha/hora/grupo) | Sí | No | No |
| Validar para liquidación | Sí | No | No |
| Cambiar profesores — clase de hoy o futura | Sí | Sí | No |
| Cambiar profesores — clase de fecha pasada | Sí | **No** | No |
| Cancelar / reactivar clase | Sí (ambas) | Solo cancelar, no reactivar | No |
| Ver clase y tomar/corregir asistencia | Sí | Sí | Sí — de **cualquier** clase, no solo las propias (ver 4.f) |

Regla general: crear/editar/validar clases queda exclusivo de ADMIN — es una excepción deliberada a la regla de "operativo = dominio completo" de `PERMISOS-ROLES.md`, específica de este caso de uso.

---

## 4.b Solapamiento de horarios

**Precisión A5 aprobada por Carlos el 05/10/2026:** al asignar profesores se ofrecen
solo los **activos del deporte del grupo de la clase**. Se controla también en el
servidor al crear (única/serie), editar hoy/futuro y reasignar desde la ficha; un
formulario adulterado se rechaza sin cambios parciales. Los roles, motivos retroactivos
y bloqueos por liquidación permanecen como estaban. No se borran asignaciones históricas
por desactivar posteriormente un profesor.

- Un profesor no puede quedar asignado a dos clases que se solapen en fecha+horario.
- Un alumno no puede quedar con `presente=true` en dos clases que se solapen en fecha+horario.
- Se valida en **todos** los puntos de entrada del flujo web real: crear clase (única y cada clase de una serie recurrente), reasignar profesores, guardar asistencias.
- Comparación de solape: `A.inicio < B.fin && B.inicio < A.fin` (estrictamente exclusivo en los bordes — una clase que termina 10:00 y otra que empieza 10:00 NO se consideran solapadas).
- Al guardar asistencias, la validación es **todo o nada**: si algún alumno de la tanda tiene conflicto, no se guarda nada de esa tanda; el mensaje identifica el conflicto para que se corrija y se reintente.
- Al crear una serie recurrente, si cualquier clase de la serie tiene un conflicto de horario para algún profesor, **no se crea ninguna clase de la serie** (transacción atómica).

---

## 4.c Fecha pasada — crear vs editar

- **Crear** una clase única o una serie recurrente con fecha (o `fecha_desde`) anterior a hoy: **bloqueado**. No tiene sentido cargar una clase para ayer.
- **Editar** una clase ya pasada (cambiar profesor, corregir asistencia): **permitido**. En la operación real las correcciones llegan después, no todo se carga en el momento.
- Toda edición retroactiva deja un motivo obligatorio (ver 4.d y 4.e) — es la forma en que "queda asentado el cambio".

### Enmienda 17/10/2026 — la asistencia se toma desde el día de la clase (T35)

Decisión de Carlos del 13/10 (DU-35): **no se toma asistencia de una clase que todavía no
ocurrió.** Un presente cargado de antemano cuenta como clase dictada, para la comisión y para
la cuota del mes siguiente.

- Vale para todos los perfiles y para la primera carga y la corrección. El límite es la fecha,
  no la hora: desde las 00:00 del día de la clase, hora de Wings, se puede tomar.
- La regla vive en `Clase::todaviaNoOcurrio()` y la aplican `ClaseWebController::storeAsistencias`
  y `ClaseService::registrarAsistencia`. La pantalla de una clase futura avisa, muestra los
  presentes deshabilitados y no ofrece Guardar.
- Lo ya cargado antes de esta regla no se toca.
- Cubierto por `tests/Feature/AsistenciaClaseFuturaTest.php`.

### Enmienda 13/09/2026 — mover la fecha y el horario

Lo de arriba nombraba *cambiar profesor* y *corregir asistencia*, y **no decía nada de
mover la fecha ni el horario**. La laguna la encontró Codex CyE al abrir FIN-10. Carlos
la resolvió el 13/09, y **fecha y horario quedan separados**:

| Estado de la clase | Fecha | Horario |
|---|---|---|
| **Futura o de hoy** | Se cambia libremente, nunca hacia el pasado | Se cambia libremente |
| **Pasada** | **Bloqueada** | **Con motivo**, validando que ningún alumno presente quede superpuesto con otra clase suya |
| **Pasada y ya liquidada** | **Bloqueada** | **Bloqueado** |

**Por qué la fecha no se mueve nunca hacia atrás.** Una clase pasó el día que pasó:
cambiarle la fecha después no corrige la realidad, corrige un error de carga. Y si la
clase no se dictó, eso no es moverla de día — es cancelar la del martes y crear la del
jueves, que el sistema ya sabe hacer. Criterio de Carlos: *"no tiene sentido movernos en
el pasado, es hacer muy complejo el sistema sin uso práctico"*. Por la misma razón sigue
en pie §4.c: tampoco se crea una clase con fecha pasada.

**Por qué el horario sí.** Ahí el caso real existe y es frecuente: la clase fue de 19 a
20:30 y se cargó de 18 a 19:30. El día es correcto, la hora no. Corregirla preserva el registro de lo ocurrido. Pide motivo porque es una corrección de un dato ya registrado,
igual que §4.e.

**Por qué revalidar el solapamiento al mover el horario.** §4.b prohíbe que un alumno
quede en dos clases solapadas y lo valida al tomar lista — pero si el horario se mueve
*después*, esa validación ya pasó y nadie la vuelve a correr. Mover la clase puede dejar
a los presentes en la situación que el contrato prohíbe.

**Por qué la clase liquidada se bloquea.** La clase figura en el detalle de una liquidación de un mes cerrado;
su horario queda protegido como parte de ese registro. El plan vigente dice que
*"las liquidaciones cerradas no se reabren; las correcciones son compensatorias"*:
permitir esto sería la puerta de atrás a esa regla.

**Consecuencia conocida y aceptada.** Si una clase se carga con la fecha equivocada y se
descubre al día siguiente, no se puede corregir ni recrear en el día correcto. Queda una
clase cancelada en el día que no fue. Se acepta: es raro, y el costo de habilitar la
edición retroactiva de fechas para cubrirlo es mayor que el problema.

Sigue rigiendo §4.a: **editar fecha, hora o grupo es exclusivo de ADMIN.** Esta enmienda
no le da al OPERATIVO ninguna facultad nueva.

**Precisión FIN-10 confirmada por Carlos el 13/09.** Ya liquidada significa que existe
un detalle de tipo `clase` vinculado a una liquidación `CERRADA`, aunque siga pendiente
de pago. Una liquidación `ABIERTA` no bloquea esta corrección. El cálculo de importes
no cambia: hoy el servicio asigna `valor_hora` por clase, sin multiplicar duración;
la decisión sobre duración queda separada de FIN-10.

Al cambiar fecha u horario se revalidan todos los profesores que quedan asignados y
los alumnos con `presente=true`, también hoy o en el futuro, excluyendo la propia
clase/asistencia. Si hay conflicto, no se guarda nada (incluidos motivo y profesores).
Los bordes contiguos siguen permitidos. Guardar sin cambios no exige motivo.
El motivo de una corrección horaria pasada se guarda en `clases.motivo_cambio_horario`.
La edición general ignora profesores enviados para clases pasadas; la reasignación
sigue en la ficha con los controles de §4.d. Desde T66, hoy/futuro exige conservar al menos una profesora activa del deporte.

---

## 4.d Cambio de profesores

**Enmienda T66 / DU-55, Carlos 19/10/2026:** «Exigir al menos una profesora». Crear una clase única o una serie, editar su selección de profesoras y reasignar desde la ficha exige al menos una profesora activa del deporte del grupo. Se valida en el servidor; omitir el campo o enviar una lista vacía se rechaza sin escrituras parciales. La serie sigue siendo atómica. No se reparan automáticamente clases históricas sin profesora; Carlos decide sobre ellas. Desactivar a la única profesora queda registrado para T46 y no elimina sus asignaciones históricas.

**Clase de hoy o futura:** ADMIN u OPERATIVO reasignan (`PATCH /clases/{id}/profesores`), sin motivo, conservando al menos una profesora válida y validando solapamiento para las que se agregan.

**Protección FIN-12 confirmada por Carlos el 19/10/2026:** «Bloquear clases en liquidaciones pagadas. En caso de que la liquidacion este cerrada, Hay que cancelar la liquidacion antes de modificar». Una clase cancelada no admite reasignación. Una clase vinculada a liquidación CERRADA/PENDIENTE exige cancelar primero esa liquidación; CERRADA/PAGADA permanece intocable. Se aplica tanto a HORA (detalle de clase) como a COMISIÓN (profesora y período). No existe confirmación que fuerce el cambio. Los importes y detalles históricos se conservan; una liquidación cancelada no impide luego la corrección autorizada.

**Clase de fecha pasada:**
- Solo ADMIN puede tocar los profesores.
- El campo `motivo` es obligatorio **solo si la clase ya tenía profesor asignado** (se guarda en `clases.motivo_cambio_profesor`). Si la clase pasó sin ningún profesor cargado, asignarlo es la **primera carga**, no un cambio: no se pide motivo. Ver 4.e para el criterio general.
- La antigua excepción que permitía reemplazar una profesora ya pagada mediante aviso y confirmación queda retirada por la decisión del 19/10; rige la protección FIN-12 anterior.

---

## 4.e Corrección de asistencia — criterio general del motivo

**El motivo justifica una CORRECCIÓN, nunca una carga.** La regla es la misma para asistencias y para profesores:

| Situación | ¿Pide motivo? |
|---|---|
| Clase de **hoy** | No, nunca |
| Clase **pasada** que nunca tuvo lista tomada / profesor asignado | **No** — es la primera carga (se traspapeló, se cargó tarde). No hay nada que justificar |
| Clase **pasada** que ya tenía lista tomada / profesor asignado | **Sí** — se está cambiando un dato ya registrado |

- Cuando aplica, el `motivo` es obligatorio para toda la tanda que se guarda en esa llamada (ej. "se nos pasó cargar a Josefa", "se marcó por error"). Se persiste en `asistencias.motivo_correccion` en cada fila tocada por esa llamada.
- En la primera carga el campo `motivo_correccion` queda en `NULL` — así se distingue después una lista cargada tarde de una lista corregida.
- El recuadro del motivo **no se muestra en pantalla** cuando no aplica, para no pedir que se justifique algo que no ocurrió.
- Re-guardar una asistencia ya existente (ej. confirmar de nuevo sin cambios) no se autobloquea por "solapamiento consigo misma" — la validación de solapamiento excluye la fila de asistencia que se está actualizando.

---

## 4.f Ownership — profesor y clases ajenas

Decisión explícita: **un PROFESOR puede ver y tomar asistencia de cualquier clase**, no solo las suyas — cubre casos como suplencias o clases con múltiples profesores. No se agrega ningún filtro de "¿sos el profesor asignado?" en `show()` ni en `storeAsistencias()`. Lo que el profesor **no puede hacer nunca** es crear, editar, cancelar/reactivar, ni reasignar profesores (bloqueado explícitamente contra `isProfesor()`).

---

## 4.f-bis Clases que traban el pago — "Sin asistencia"

Una clase pasada **no se puede liquidar** hasta que tenga al menos un presente cargado o el admin la marque `validada_para_liquidacion`. Sin eso, al profesor no se le paga (ver `LIQUIDACIONES_CONTRATO_V2.md` 2.3).

Por eso el menú expone un atajo permanente `Clases › Sin asistencia` con el conteo, que lleva al listado filtrado (`?estado=finalizada`):

- **Se cuentan todas, sin ventana de tiempo.** Una clase de hace seis meses sin lista sigue trabando plata, así que no se la deja de contar por vieja.
- **Se excluyen las `validada_para_liquidacion = true`**: ya están resueltas, se pagan igual.
- El sub-ítem **solo aparece si hay pendientes**; en cero desaparece del menú.
- El conteo usa exactamente la misma consulta que el filtro al que lleva, para que el número anunciado sea el que se ve al hacer click.
- El recorte de 35 días hacia atrás que el listado aplica a OPERATIVO y PROFESOR **no rige en este filtro**: si no, el número no coincidiría y además no podrían abrir la clase vieja que tienen que cargar.

### Enmienda 18/10/2026 — Clase con lista tomada y nadie presente (T34, DU-33)

Con lista tomada la clase sale del contador y del filtro «Sin asistencia» (`finalizada`) y pasa a estar a disposición del dueño para que decida si la paga o no:
- `finalizada` («Sin asistencia»): clase pasada, no cancelada, no validada a la que **nunca se le tomó lista** (`doesntHave('asistencias')`).
- `sin_presentes` («Sin presentes»): clase pasada, no cancelada, no validada con **lista tomada y cero presentes** (`has('asistencias')` y `doesntHave('asistencias', presente=true)`). Visible únicamente para el rol ADMIN en el selector de filtros de Clases.
- La liquidación por hora (`LiquidacionService::clasesLiquidablesHora`) **no cambia**: una clase sin presentes no se paga salvo que esté `validada_para_liquidacion = true`.

| Estado | Significado | Condición |
|---|---|---|
| `finalizada` | Sin asistencia tomada, no se paga | pasada, no cancelada, no validada y sin ningún registro de asistencia |
| `sin_presentes` (filtro dueño) | Lista tomada, todos ausentes | pasada, no cancelada, no validada, con asistencias pero ningún presente |
| `cerrada` | Resuelta para pago | con presentes **o** validada |

### Enmienda 18/10/2026 — DNI recortado en lista de asistencia (T67, DU-56)

En la lista de asistencia de `clases/show`, para resguardo de privacidad y desambiguación de homónimos, se muestran únicamente los tres últimos dígitos del DNI (ej: `DNI …123`). El DNI completo no aparece en el HTML de la vista para ningún rol.

### Enmienda 20/10/2026 — La lista conserva a quien tiene marca (T52, DU-09)

Los cambios de grupo y las bajas rigen hacia adelante. La lista de `clases/show` es la de los alumnos activos que **ya estaban en el grupo el día de la clase** (ver la aclaración de abajo) **más** los que ya tienen una marca guardada en esa clase (presente o ausente), aunque después hayan cambiado de grupo o se hayan dado de baja; esos llevan la aclaración «Cambió de grupo» o «De baja», y el encabezado dice cuántos ya no están. Al corregir esa lista se los acepta (con el motivo de §4.e); a quien no es del grupo ni tiene marca en la clase se lo sigue rechazando.

**Aclaración de Carlos, 20/10/2026: «Los cambios son siempre para adelante»** («si yo nací en 1980, ¿puedo haber sido parte de la revolución de mayo?»). Quien entró al grupo **después** de una clase no aparece en ella ni se lo puede marcar. Para saberlo: mientras el alumno nunca cambió de grupo vale su **fecha de ingreso** (aunque se corrija o sea futura); cuando cambia de grupo se guarda en `grupo_desde` el día del cambio, aunque el plan nuevo empiece el mes siguiente, y ese día ya no se mueve aunque después se corrija el ingreso. La pantalla y el guardado usan el mismo criterio, y al guardado solo entran alumnos activos o con marca. Para los alumnos anteriores a este cambio, `grupo_desde` se completó solo si su primer plan del grupo actual empieza después de su ingreso (señal de un cambio de grupo), con esa fecha y nunca en el futuro: es una aproximación.

## 4.g Cancelación de clase

- Cancelar (con `motivo_cancelacion` obligatorio) o reactivar (`motivo_cancelacion` se limpia) no toca las asistencias ya cargadas — quedan tal cual en la base.
- Esas asistencias dejan de contar para **liquidación** (una clase cancelada nunca liquida, sin importar sus asistencias), pero **sí siguen contando** para el cálculo de cupo semanal y déficit/recuperación de otras clases del alumno (`ClaseService::contarAsistenciasSemana()`/`verificarRecuperacion()` no filtran por `cancelada`). Se documenta así porque es el comportamiento real verificado; no se identificó como problema a resolver ahora.
- Cancelar una serie completa (`cancelar_serie=true`) solo cancela las clases de la serie con fecha **hoy o futura** — nunca las pasadas, que ya sucedieron.

---

## 4.h Series recurrentes — límites conocidos (no resueltos en este contrato)

- No existe edición bulk de una serie: cambiar fecha/hora de una clase de la serie no propaga a las demás.
- No existe borrado desde la web (el único `destroy()` vive en la API admin, deshabilitada). Si se habilita en el futuro, no tiene tratamiento especial para huecos en una serie.
- `verificarRecuperacion()` es puramente informativo — nunca bloquea un registro con motivo `RECUPERA` aunque no haya déficit real, y ni siquiera se invoca desde el flujo web (solo desde la API, no usada).

Estos tres puntos quedan fuera de alcance de esta resolución (no se pidió resolverlos); se anotan para que quien retome el módulo sepa que existen.

---

## 4.i Reglas Freeze

- No existe clase creada con fecha pasada.
- No existe clase pasada cuya fecha se haya movido, ni clase de hoy o futura movida al pasado.
- No existe cambio de horario en una clase pasada sin motivo, ni en una clase ya liquidada.
- No se crea una clase única o serie sin al menos una profesora activa del deporte, ni se vacía la selección al editar o reasignar. Las clases históricas no se reparan automáticamente (T66).
- No existe reasignación de profesores sin motivo en una clase pasada que ya tenía profesor; la primera carga conserva la excepción de §4.d.
- No existe corrección de asistencia sin motivo en una clase pasada.
- No existe asistencia tomada antes del día de la clase (enmienda 17/10/2026, T35).
- Un profesor nunca queda con dos clases solapadas asignadas; un alumno nunca queda `presente=true` en dos clases solapadas.
- No se reasignan profesoras en una clase cancelada ni vinculada a una liquidación cerrada o pagada. La cerrada no pagada se cancela primero; la pagada permanece intacta (Carlos, 19/10/2026).

Cualquier cambio futuro requiere versión V2 explícita.

---

## 4.j Estado

🔒 CONTRATO CERRADO, con enmiendas fechadas. El corte histórico del 2026-07-26 probó 19 aserciones e incluía aviso de liquidación pagada con confirmación; esa excepción se retira por la decisión del 19/10/2026 en T66. La entrega local de T66 requiere verificación independiente de lógica; las tres vistas fueron aprobadas por Carlos el 19/10/2026 («Vista aprobadas») y no se vuelve a pedir aprobación visual; ver [informe T66](../06-pruebas/PRU-04/IMPLEMENTACION-T66.md).
