# Wings-ER-Alumno-Grupo-Deporte-Deuda-V2.md

**Caso de uso (Index):** 2) Alumno–Grupo–Deporte–Deuda  
**Versión:** V2  
**Estado:** CANDIDATO A CIERRE  
**Fuente de verdad:** repo (migraciones + models)

---

## 2.2.b.1 Deporte
**Tabla:** `deportes`
- id (PK)
- nombre
- tipo_liquidacion (HORA | COMISION)
- activo
- created_at, updated_at

Relaciones:
- Deporte 1 ── N Grupos
- Deporte 1 ── N Alumnos

---

## 2.2.b.2 Grupo
**Tabla:** `grupos`
- id (PK)
- deporte_id (FK → deportes.id)
- nombre
- activo
- created_at, updated_at

Relaciones:
- Grupo N ── 1 Deporte
- Grupo 1 ── N Alumnos
- Grupo 1 ── N GrupoPlanes

---

## 2.2.b.3 GrupoPlan
**Tabla:** `grupo_planes`
- id (PK)
- grupo_id (FK → grupos.id)
- clases_por_semana (int)
- precio_mensual (decimal 10,2)
- activo (bool)
- created_at, updated_at

Restricción:
- UNIQUE(grupo_id, clases_por_semana, activo)

Relaciones:
- GrupoPlan N ── 1 Grupo
- GrupoPlan 1 ── N AlumnoPlanes

---

## 2.2.b.4 Alumno
**Tabla:** `alumnos`
- id (PK)
- dni
- nombre
- apellido
- deporte_id (FK → deportes.id)
- grupo_id (FK → grupos.id)
- activo
- created_at, updated_at

Restricción de identidad:
- UNIQUE(dni, deporte_id)

Relaciones:
- Alumno N ── 1 Deporte
- Alumno N ── 1 Grupo
- Alumno 1 ── N AlumnoPlanes
- Alumno 1 ── N DeudaCuotas

---

## 2.2.b.5 AlumnoPlan
**Tabla:** `alumno_planes`
- id (PK)
- alumno_id (FK → alumnos.id)
- plan_id (FK → grupo_planes.id)
- fecha_desde (date)
- fecha_hasta (date, nullable)
- activo (bool)
- created_at, updated_at

Restricción:
- UNIQUE(alumno_id, activo)  (solo 1 plan activo por alumno)

Relaciones:
- AlumnoPlan N ── 1 Alumno
- AlumnoPlan N ── 1 GrupoPlan

---

## 2.2.b.6 DeudaCuota
**Tabla:** `deuda_cuotas`
- id (PK)
- alumno_id (FK → alumnos.id)
- periodo (YYYY-MM)
- monto_original
- monto_pagado
- estado (PENDIENTE | PAGADA | CONDONADA | AJUSTADA)
- created_at, updated_at

Restricción:
- UNIQUE(alumno_id, periodo)

Relaciones:
- DeudaCuota N ── 1 Alumno

Nota (importante, pero se define en Caso 3):
- `monto_original` debe derivar del plan activo del alumno (GrupoPlan.precio_mensual) al momento de generar la deuda.
- Las reglas de “primera cuota” y automatismos de generación pertenecen al Caso 3.
