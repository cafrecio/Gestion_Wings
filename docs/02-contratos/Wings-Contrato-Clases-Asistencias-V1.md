# Wings-Contrato-Clases-Asistencias-V1.md

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

---

## 4.d Cambio de profesores

**Clase de hoy o futura:** ADMIN u OPERATIVO reasignan libremente (`PATCH /clases/{id}/profesores`), sin motivo, validando solapamiento para los profesores que se agregan.

**Clase de fecha pasada:**
- Solo ADMIN puede tocar los profesores.
- El campo `motivo` es obligatorio (se guarda en `clases.motivo_cambio_profesor`).
- Si el profesor que se saca/reemplaza **ya cobró esa clase** en una liquidación con `estado_pago = PAGADA` (se busca en `liquidacion_detalles` con `tipo_referencia='clase'` y `referencia_id` = la clase), el sistema **no bloquea**, pero **avisa** al admin con el número de liquidación, el profesor y la fecha de pago, y pide confirmación explícita antes de aplicar el cambio (`HTTP 409` con `requiere_confirmacion: true`; el admin reenvía con `confirmar: true` para forzarlo).
- Este chequeo solo aplica a deportes con liquidación `tipo=HORA` (donde `liquidacion_detalles` referencia la clase directamente). En deportes `COMISION` la liquidación se calcula por alumno/pago, no por clase, así que no hay vínculo directo clase→liquidación para avisar.

---

## 4.e Corrección de asistencia

- Guardar asistencia de una clase de **hoy**: sin motivo, como siempre.
- Guardar/corregir asistencia de una clase **pasada**: el campo `motivo` es obligatorio para toda la tanda que se guarda en esa llamada (ej. "se nos pasó cargar a Josefa", "se marcó por error"). Se persiste en `asistencias.motivo_correccion` en cada fila tocada por esa llamada.
- Re-guardar una asistencia ya existente (ej. confirmar de nuevo sin cambios) no se autobloquea por "solapamiento consigo misma" — la validación de solapamiento excluye la fila de asistencia que se está actualizando.

---

## 4.f Ownership — profesor y clases ajenas

Decisión explícita: **un PROFESOR puede ver y tomar asistencia de cualquier clase**, no solo las suyas — cubre casos como suplencias o clases con múltiples profesores. No se agrega ningún filtro de "¿sos el profesor asignado?" en `show()` ni en `storeAsistencias()`. Lo que el profesor **no puede hacer nunca** es crear, editar, cancelar/reactivar, ni reasignar profesores (bloqueado explícitamente contra `isProfesor()`).

---

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
- No existe reasignación de profesores sin motivo en una clase pasada.
- No existe corrección de asistencia sin motivo en una clase pasada.
- Un profesor nunca queda con dos clases solapadas asignadas; un alumno nunca queda `presente=true` en dos clases solapadas.
- Cambiar de profesor en una clase pasada y ya pagada en liquidación PAGADA requiere confirmación explícita del admin.

Cualquier cambio futuro requiere versión V2 explícita.

---

## 4.j Estado

🔒 CONTRATO CERRADO. Implementado y probado contra datos reales (transacción + rollback) el 2026-07-26: 19 aserciones sobre creación con fecha pasada, solapamiento en creación/reasignación/asistencia, permisos por rol, motivo obligatorio, aviso de liquidación pagada con confirmación, y no-autobloqueo al re-guardar.
