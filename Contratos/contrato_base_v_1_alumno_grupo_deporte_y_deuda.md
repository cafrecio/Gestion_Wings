# Contrato Base v1 – Alumno · Grupo · Deporte · Deuda

**Estado:** CERRADO (freeze de modelo)

Este documento define el **núcleo inmutable del sistema** sobre el que se apoyan pagos, caja, cashflow y liquidaciones.  
Todo lo que venga después **no puede contradecir este contrato**.

---

## 1. Deporte

Representa una disciplina (Patín, Fútbol, Vóley, etc.) y define **reglas de negocio globales**.

**deportes**
- id
- nombre
- tipo_liquidacion (HORA | COMISION)
- activo
- timestamps

Reglas:
- El deporte define **cómo se liquidan los profesores**.
- El deporte **no define precios de alumnos** (eso vive en planes/grupos).

Relaciones:
- Deporte 1 ── N Grupos
- Deporte 1 ── N Profesores

---

## 2. Grupo

Un grupo es una **unidad operativa y comercial** dentro de un deporte.

Ejemplos:
- Patín Inicial Lunes y Miércoles
- Fútbol Sub-12

**grupos**
- id
- deporte_id (FK)
- nombre
- activo
- timestamps

Reglas:
- Todo grupo **pertenece a un único deporte**.
- Un grupo no puede mezclar deportes.

Relaciones:
- Grupo N ── 1 Deporte
- Grupo 1 ── N Alumnos
- Grupo 1 ── N Clases

---

## 3. Alumno

Un alumno representa la **inscripción de una persona a un deporte específico**.

⚠️ Una misma persona puede existir **más de una vez** si practica más de un deporte.

**alumnos**
- id
- dni
- nombre
- apellido
- deporte_id (FK)
- grupo_id (FK)
- activo
- timestamps

Reglas:
- **Un alumno pertenece a un solo deporte**.
- DNI **no es único global**, es único por deporte.
- Cambiar de deporte = crear **otro alumno**.

Restricción lógica:
- UNIQUE(dni, deporte_id)

Relaciones:
- Alumno N ── 1 Deporte
- Alumno N ── 1 Grupo
- Alumno 1 ── N Deudas
- Alumno 1 ── N Pagos
- Alumno 1 ── N Asistencias

---

## 4. Deuda de Cuota

La deuda es el **registro contable del compromiso mensual del alumno**.

No es implícita. **Siempre existe**.

**deuda_cuotas**
- id
- alumno_id (FK)
- periodo (YYYY-MM)
- monto_original
- monto_pagado
- estado (PENDIENTE | PAGADA | CONDONADA | AJUSTADA)
- timestamps

Reglas:
- Existe **una deuda por alumno por período**.
- Los pagos **impactan sobre la deuda**, no al revés.
- La deuda es la fuente de verdad.

Estados:
- PENDIENTE: deuda abierta
- PAGADA: monto_pagado >= monto_original
- CONDONADA: deuda cancelada por decisión administrativa
- AJUSTADA: acuerdo especial (quita parcial, negociación)

Relaciones:
- Deuda N ── 1 Alumno
- Deuda 1 ── N Pagos

---

## 5. Pago de Cuota (referencial)

El pago es un **evento inmutable** que impacta deuda y caja.

**pago_cuotas**
- id
- alumno_id (FK)
- deuda_id (FK)
- monto
- fecha_pago
- tipo_caja_id (FK)
- cierre_caja_id (FK)
- timestamps

Reglas:
- Un pago **siempre referencia una deuda**.
- Un pago puede cubrir:
  - una deuda completa
  - parte de una deuda
  - múltiples pagos para una misma deuda

---

## 6. Reglas Clave del Contrato (Freeze)

- ❌ No existe alumno sin deporte
- ❌ No existe grupo sin deporte
- ❌ No existe pago sin deuda
- ❌ No existe deuda sin período
- ✅ Una persona puede tener múltiples alumnos (uno por deporte)
- ✅ Un alumno puede tener múltiples pagos en un mes
- ✅ Las deudas son independientes entre deportes

---

## 7. Impacto Directo en Otros Módulos

Este contrato habilita sin ambigüedades:

- Pagos por deporte
- Cajas diferenciadas
- Cashflow real
- Liquidaciones correctas
- Reportes confiables

---

## 8. Estado Final

🔒 **CONTRATO CERRADO – NO MODIFICAR**  
Cualquier cambio futuro debe:
- extender (no romper)
- o crear un contrato v2 explícito

A partir de acá, se avanza **solo por prompts de implementación**.

