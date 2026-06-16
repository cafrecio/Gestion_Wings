# Wings-Contrato-Alumno-Grupo-Deporte-Deuda-V3.md

**Caso de uso (Index):** 2) Alumno–Grupo–Deporte–Deuda  
**Versión:** V3  
**Estado:** CERRADO  
**Alcance:** Modelo base de identidad deportiva + estructura comercial (planes) + existencia de deuda por período.  
**No incluye:** Pagos, generación automática de deuda, estados dinámicos.

---

## 2.a Deporte

Representa una disciplina (Patín, Fútbol, Vóley, etc.).

**Reglas:**
- El deporte define reglas globales (por ejemplo liquidación de profesores).
- El deporte no define precios de alumnos.
- Un deporte puede tener múltiples grupos.

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
- El monto original debe derivar del plan activo del alumno al momento de generar la deuda.
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

🔒 CONTRATO CERRADO.
Cualquier modificación futura requiere versión V4 explícita.

