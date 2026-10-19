# Wings-Contrato-Alumno-Grupo-Deporte-Deuda-V5.md

**Caso de uso (Index):** 2) Alumno–Grupo–Deporte–Deuda
**Versión:** V5
**Estado:** VIGENTE; T44 implementada localmente, pendiente verificación de reglas/código por Claude; visual aprobado por Carlos el 19/10/2026 («Ok, pasan»)
**Alcance:** Modelo base de identidad deportiva + estructura comercial (planes) + existencia de deuda por período.
**No incluye:** Pagos, generación automática de deuda, estados dinámicos.

---

## 2.a Deporte

Representa una disciplina (Patín, Fútbol, Vóley, etc.).

**Reglas:**
- El deporte define reglas globales (por ejemplo liquidación de profesores).
- El deporte no define precios de alumnos.
- Un deporte puede tener múltiples grupos.
- **No se puede desactivar un deporte si tiene alumnos activos o clases futuras en cualquiera de sus grupos** (DU-41, T63).

---

## 2.b Grupo

Un grupo es la **unidad operativa y comercial** dentro de un deporte.

Ejemplos:
- Patín Inicial Lunes y Miércoles
- Fútbol Sub-12

**Reglas:**
- Todo grupo pertenece a un único deporte.
- Un grupo no puede mezclar deportes.
- El grupo es el contenedor comercial visible del alumno.
- Un grupo puede tener múltiples planes de cuota (según clases por semana).
- **No se puede desactivar un grupo si tiene alumnos activos o clases futuras** (DU-41, T63).

---

## 2.c GrupoPlan

Define las variantes comerciales de un grupo.

Ejemplo:
- 1 vez por semana → $22.222
- 2 veces por semana → $33.333
- 3 veces por semana → $55.555

**Reglas:**
- Cada plan pertenece a un grupo.
- Un grupo puede tener múltiples planes activos.
- Cada plan define:
  - Cantidad de clases por semana.
  - Precio mensual asociado.

---

## 2.d Alumno

Un alumno representa la **inscripción de una persona a un deporte específico**.

Este sistema NO maneja una entidad separada llamada “Persona”.
Cada inscripción deportiva es un alumno distinto.

**Reglas:**
- Un alumno pertenece a un solo deporte.
- Una misma persona puede tener múltiples alumnos (uno por cada deporte distinto).
- Una persona NO puede existir dos veces en el mismo deporte.
- El DNI es único dentro de cada deporte.
- Cambiar de deporte implica crear un nuevo alumno.
- Cambiar de grupo NO crea un nuevo alumno; solo cambia su grupo.
- Cada alumno debe tener un único plan activo a la vez.

---

## 2.e AlumnoPlan

Relaciona al alumno con el plan comercial que paga.

**Reglas:**
- Un alumno tiene un único plan activo.
- El plan define cuántas clases por semana paga.
- El plan define el precio mensual que servirá de base para la deuda del período.

---

## 2.f Deuda por período

La deuda representa el compromiso mensual del alumno.

**Reglas:**
- Puede existir una deuda por alumno por período.
- La deuda siempre está asociada a un alumno.
- La deuda tiene un período definido (formato YYYY-MM).
- Al generar una cuota normal, el monto original deriva del plan aplicable.
- Excepción explícita P1 (Carlos, 05/10/2026): la primera carga por Excel crea la deuda por el **saldo declarado**. No aplica descuento ni precio actual del plan a ese saldo. El plan existente se asigna para la operación posterior; no se crean catálogos.
- La deuda es la referencia contable del período.

Este contrato no define cómo ni cuándo se genera la deuda.
Eso pertenece a otro caso de uso.

---

## 2.g Reglas Freeze

- No existe alumno sin deporte.
- No existe grupo sin deporte.
- No puede existir el mismo DNI dos veces dentro del mismo deporte.
- Cada alumno tiene un único plan activo.
- Las deudas son independientes entre deportes.
- Este contrato no regula pagos ni estados dinámicos.

---

## 2.h Estado

V5 conserva la identidad deportiva y la excepción P1 de V4, e incorpora el historial y vigencia de precios de T44. Cualquier modificación futura requiere versión V6 explícita.

## 2.i Primera carga P1 — 05/10/2026

Decisión: [Primera carga por Excel](../05-pendientes/PRIMERA-CARGA-EXCEL.md).
Formato y pantalla: [maqueta aprobada](../05-pendientes/maqueta-primera-carga/README.md).

- ADMIN prepara catálogos, descarga plantilla vacía, revisa y confirma la carga.
- Estado persistente PENDIENTE/TERMINADA; ni el menú ni un alta individual pueden saltearlo. No se deduce contando alumnos.
- Una fila por DNI/deporte; 12 pares mes/monto. Solo Alumnos se completa; Catálogos y Guía no se importan.
- Grupo y plan activos existentes, relacionados al deporte y con precio. Nunca se crean catálogos desde el archivo.
- Revisión completa sin escrituras: todos los errores con fila, columna y esperado. Excel original con AM Errores, conservando datos y tipos.
- Sí/No de inscripción y cuotas son independientes. No en cuotas exige pares vacíos; Sí exige al menos uno completo y no repetir mes.
- Carga atómica: alumno, plan, inscripción si el archivo dice Sí (una por DNI al importe configurado), y solamente las deudas declaradas. Sin cuotas automáticas, descuentos de bienvenida, pagos ni movimientos de caja.
- Monto es saldo pendiente: una cuota de $48.000 con $20.000 pagados antes del sistema entra como deuda de $28.000 y cero pagos locales; no se inventa el pago previo.
- DNI normalizado con el criterio existente; período numérico 92026 y texto 082026 aceptados. Texto 52.000 es $52.000 según el parser compartido; formatos ambiguos se rechazan explícitamente.
- Confirmar revalida archivo, catálogos y resumen en transacción. La revisión queda privada, vinculada a usuario/sesión y hash del archivo.
- Deshacer revierte únicamente esta carga y conserva usuarios/catálogos. Rechaza cualquier pago registrado (incluso anulado) o actividad posterior que pudiera perderse por cascada.
- Los importadores antiguos se retiran **después de verificar P1**, en commit aparte, no como parte del cambio de reglas de este contrato.
- Esta entrega no limpia ni migra la base de trabajo ni despliega a producción.

## 2.j Precios con vigencia — T44, Carlos 18/10/2026

- Cada plan guarda su historial de precios con fecha de cambio. El precio inicial de los
  planes existentes se conserva como vigente desde siempre; no se inventan precios anteriores.
- Para alumnos que ya estaban antes de un cambio, la nueva tarifa rige el mes siguiente.
  El precio del mes se resuelve aunque la cuota se genere o cobre después del aumento.
- Para el mes de alta se usa la tarifa vigente el día de la fecha real de ingreso, con
  su porcentaje. Un reingreso conserva la fecha de alta original para elegir la tarifa.
- Varios cambios en un mes están permitidos: $40.000 → $45.000 el 20/10 → $50.000 el 25/10.
  Los existentes pagan octubre a $40.000 y noviembre a $50.000. Un alta del 22/10 usa
  $45.000 en octubre; un alta del 26/10 usa $50.000. En noviembre ambos usan $50.000.
- Cambiar la tarifa del catálogo no modifica ninguna cuota ya generada, incluida una
  cobrada por adelantado. T18 conserva también el importe elegido al cobrar adelantado.
- Cambiar la frecuencia del alumno es una operación distinta: **bajar siempre rige desde
  el mes siguiente**. Subir rige en este mes si la cuota está pendiente o parcialmente
  pagada: ajusta el importe y resta lo ya pagado. Si el mes está saldado, rige el mes siguiente.
- La pantalla y el guardado comparten esa decisión. La ficha y Cobrar usan el mismo servicio.
  El plan programado no reemplaza al vigente antes de que empiece su fecha.
- Historial en `grupo_plan_precios`; `grupo_planes.precio_mensual` conserva el catálogo editable.
  `GrupoPlan::precioParaPeriodo` es la única resolución de tarifa por período y fecha de alta.
  Cambiar precio e insertar su vigencia se hace en la misma transacción.
- Cuotas de alta, reingreso, meses pasados T58, Revisión, generador y Cobrar consultan esa función.
  La primera carga mantiene su excepción: las deudas importadas valen el saldo declarado.

## 2.k Alumno activo sin plan — T56, Carlos 19/10/2026

Decisión: DU-15 («Impedir que un alumno activo quede sin plan»).
- **Alta por pantalla:** Exige plan activo del grupo (`AlumnoWebController::store`).
- **Primera carga por Excel:** Exige plan activo del grupo (`PrimeraCargaExcelService::validar`).
- **Edición y cambio de grupo:** Si el alumno cambia de grupo (`grupo_id`), la selección de plan (`plan_id`) es obligatoria y debe pertenecer al nuevo grupo. Si no cambia de grupo pero carece de plan vigente, la selección de plan es obligatoria. No se permite actualizar un alumno sin plan aplicable (`AlumnoWebController::update`).
- **Eliminación y cambio de planes de grupo:** Bloqueado si el plan tiene alumnos activos o si es el último plan del grupo (`GrupoWebController::destroyPlan`).
- **Cobro a inactivos (T38):** Cobrarle una deuda vieja a un alumno inactivo solo lo reactiva a `activo = true` si el alumno cuenta con un plan vigente para el mes corriente. Si no tiene plan vigente, la deuda vieja se cobra y cancela normalmente, pero el alumno permanece `activo = false` evitando que ingrese al generador mensual del día 1 sin plan.


[Cuotas, pagos y cambios de plan V2](Wings-contrato-cuotas-deudas-pagos-V2.md) ·
[Implementación y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-T44.md).
