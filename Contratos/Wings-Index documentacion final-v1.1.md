Wings-Index documentacion final-v1.md

# Wings — Index documentación final (V1)

**Fuente de verdad:** repo `Gestion_Wings` (ZIP “gestion-wings -back100 260207.zip”)  
**Commit:** `6555c05`  
**Regla de oro:** en este index solo marco documentos **que existen en el repo** (✅) y los que **faltan** (⏳) con nombre propuesto.

---

## 0) Convenciones de documentación (para no volvernos locos)

Para cada **Caso de uso** idealmente mantenemos 4 piezas:

- **Contrato (funcional, corto)** → `Wings-Contrato-<tema>-Vx.md`
- **ER (Entidad–Relación)** → `Wings-ER-<tema>-Vx.md`
- **Camino feliz (paso a paso operativo)** → `Wings-CF-<tema>-Vx.md`
- **Decisiones Front (pantallas / UX / permisos operativos)** → `Wings-Contrato-Front-<tema>-Vx.md`

> Donde “tema” sea estable: `Caja-Cashflow`, `Alumnos`, `Clases-Asistencias`, etc.

---

## 1) Caja + Cashflow (operación diaria + cash real)

### 1.1 Contrato
- ✅ `Contratos/Wings-Contrato-Caja-Cashflow-V4.md`

### 1.2 ER
- ✅ `Contratos/Wings-ER-Caja-Cashflow-V4.md`

### 1.3 Camino feliz
- ⏳ `Wings-CF-Caja-Cashflow-V1.md`  
  (Qué hace el OPERATIVO en el día + qué hace el ADMIN para validar + qué pasa si alguien deja caja vieja abierta)

### 1.4 Decisiones Front
- ⏳ `Wings-Contrato-Front-Caja-Cashflow-V1.md`  
  (Pantallas mínimas: “Estado hoy”, “Movimiento”, “Cerrar”, “Pendientes admin”, “Cashflow saldos”)

---

## 2) Alumno–Grupo–Deporte–Deuda (base de identidad + deuda por período)

### 2.1 Contrato
- ✅ `Wings-contrato-alumno-grupo-deporte-deuda-v3.md`

### 2.2 ER
- ✅ `Wings-ER-Alumno-Grupo-Deporte-Deuda-V2.md`

### 2.3 Camino feliz
- ⏳ `Wings-CF-Alumno-Grupo-Deporte-Deuda-V1.md`  
  (Alta alumno → asignación deporte/grupo → generación/gestión deuda por período)

### 2.4 Decisiones Front
- ⏳ `Wings-Contrato-Front-Alumnos-V1.md`  
  (ABM alumnos + reglas de DNI por deporte + cómo se ve deuda/periodos)

---

## 3) Cuotas: pagos + imputación a deuda (operativo/admin) + recibo

> En el repo **no hay documento contractual** específico de “Pagos de cuota / deuda / imputación”, aunque sí hay implementación en backend.

### 3.1 Contrato
- ✅ `Wings-contrato-cuotas-deudas-pagos-V1`  
  (Qué genera un pago operativo vs admin, cómo se imputa, qué significa condonar/ajustar, qué se imprime)

### 3.2 ER
- ✅ `Wings-ER-cuotas-deudas-pagos-V1.md`  
  (DeudaCuota ↔ Pago ↔ (pivot) PagoDeudaCuota, y relación con movimientos/cashflow)

### 3.3 Camino feliz
- ⏳ `Wings-CF-Cuotas-V1.md`  
  (Operativo cobra → impacta deuda → movimiento → cierre del día → validación admin → cashflow)

### 3.4 Decisiones Front
- ⏳ `Wings-Contrato-Front-Cuotas-V1.md`  
  (Pantalla “cobrar cuota” y selección de periodos; búsqueda alumno; reimpresión recibo)

---

## 4) Clases + Asistencias (control operativo)

### 4.1 Contrato
- ⏳ `Wings-Contrato-Clases-Asistencias-V1.md`  
  (Solapamientos, profesores múltiples, alumno no puede estar en dos clases simultáneas)

### 4.2 ER
- ⏳ `Wings-ER-Clases-Asistencias-V1.md`  
  (Clase, ClaseProfesor, Asistencia, relación con Grupo/Profesor/Alumno)

### 4.3 Camino feliz
- ⏳ `Wings-CF-Asistencias-V1.md`  
  (Admin crea clase → operativo registra asistencias)

### 4.4 Decisiones Front
- ⏳ `Wings-Contrato-Front-Asistencias-V1.md`

---

## 5) Liquidaciones (cálculo + cierre + pago + recibo)

### 5.1 Contrato
- ✅ `docs/LIQUIDACIONES_CONTRATO_V2.md`

### 5.2 ER
- ⏳ `Wings-ER-Liquidaciones-V2.md`

### 5.3 Camino feliz
- ⏳ `Wings-CF-Liquidaciones-V2.md`  
  (Generar → revisar → cerrar → pagar (E2) → recibo (E3))

### 5.4 Decisiones Front
- ⏳ `Wings-Contrato-Front-Liquidaciones-V1.md`

---

## 6) Catálogos contables (Rubros/Subrubros/Tipos de Caja)

### 6.1 Contrato
- ⏳ `Wings-Contrato-Catalogos-Contables-V1.md`  
  (Rubro define naturaleza INGRESO/EGRESO; subrubro hereda; reservado del sistema)

### 6.2 ER
- ⏳ `Wings-ER-Catalogos-Contables-V1.md`

### 6.3 Camino feliz
- ⏳ `Wings-CF-Catalogos-Contables-V1.md`

### 6.4 Decisiones Front
- ⏳ `Wings-Contrato-Front-Catalogos-Contables-V1.md`

---

## 7) Recibos PDF (cuotas + liquidaciones)

### 7.1 Contrato
- ⏳ `Wings-Contrato-Recibos-PDF-V1.md`  
  (Qué debe contener, naming, cuándo se genera, re-generación, permisos operativos “en criollo”)

### 7.2 ER
- ⏳ (no aplica como ER completo; si querés, mini-ER) `Wings-ER-Recibos-PDF-V1.md`

### 7.3 Camino feliz
- ⏳ `Wings-CF-Recibos-PDF-V1.md`

### 7.4 Decisiones Front
- ⏳ `Wings-Contrato-Front-Recibos-PDF-V1.md`

---

## 8) Auth + Roles (ADMIN / OPERATIVO)

### 8.1 Contrato
- ⏳ `Wings-Contrato-Auth-Roles-V1.md`  
  (Qué puede hacer cada rol a nivel operativo, sin entrar en tecnicismos)

### 8.2 ER
- ⏳ `Wings-ER-Auth-Roles-V1.md` (mínimo: User.rol)

### 8.3 Camino feliz
- ⏳ `Wings-CF-Auth-V1.md`  
  (Login → token → llamadas típicas)

### 8.4 Decisiones Front
- ⏳ `Wings-Contrato-Front-Auth-V1.md`

---

## 9) ABM básicos (Admin)

> En backend existen endpoints/Controllers para ABM (deportes, grupos, profesores, alumnos, clases, catálogos).  
> **Pero** no hay documentación “por caso de uso” cerrada aún (más allá de Caja/Cashflow y Alumno–Grupo–Deporte–Deuda).

### 9.1 Contrato general ABM (si querés uno único)
- ⏳ `Wings-Contrato-ABM-Admin-V1.md`  
  (Listar / crear / editar / borrar + restricciones “no borrar si tiene hijos”)

---

## 10) Motor Estados + Cobranza + Control Asistencia/Plan

### 10.1 Contrato
- ⏳ `Wings-contrato-estadosAlum-cobranza-asistencia-V1.md`  
  
### 10.2 ER
- ⏳ `Wings-ER-estadosAlum-cobranza-asistencia-V1.md`

### 10.3 Camino feliz
- ⏳ `Wings-CF-estadosAlum-cobranza-asistencia-V1.md`  
  (Login → token → llamadas típicas)

### 10.4 Decisiones Front
- ⏳ `Wings-Contrato-Front-estadosAlum-cobranza-asistencia-V1.md`

---

## 11) Documentos existentes en repo (inventario real)

✅ Existen estos 4 MD:

1. `Contratos/Wings-Contrato-Caja-Cashflow-V2.md`  
2. `Contratos/Wings-ER-Caja-Cashflow-V2.2.md`  
3. `Contratos/contrato_base_v_1_alumno_grupo_deporte_y_deuda.md`  
4. `docs/LIQUIDACIONES_CONTRATO_V2.md`

Todo lo demás marcado como ⏳ **no existe todavía en el repo**.

---
