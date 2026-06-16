# Contrato Funcional - Sistema de Liquidaciones v2

## 1. Modelo de Dominio

### 1.1 Entidad Central: Deporte

El **Deporte** es el eje del modelo. Define:

- `nombre`: Nombre del deporte
- `tipo_liquidacion`: Enum (`POR_HORA` | `POR_COMISION`)
- `activo`: Boolean

```
┌─────────────────────────────────────┐
│              DEPORTE                │
├─────────────────────────────────────┤
│ id                                  │
│ nombre                              │
│ tipo_liquidacion (HORA|COMISION)    │
│ activo                              │
└─────────────────────────────────────┘
         │
         │ 1:N
         ▼
┌─────────────────┐    ┌─────────────────┐
│    PROFESOR     │    │      GRUPO      │
└─────────────────┘    └─────────────────┘
         │                     │
         │ N:M                 │ 1:N
         ▼                     ▼
┌─────────────────────────────────────┐
│               CLASE                 │
├─────────────────────────────────────┤
│ grupo_id                            │
│ fecha                               │
│ profesores[] (N:M)                  │
│ validada_para_liquidacion           │
│ cancelada                           │
└─────────────────────────────────────┘
         │
         │ 1:N
         ▼
┌─────────────────────────────────────┐
│            ASISTENCIA               │
├─────────────────────────────────────┤
│ clase_id                            │
│ alumno_id                           │
│ presente                            │
└─────────────────────────────────────┘
```

---

## 2. Reglas de Negocio Innegociables

### 2.1 Relación Alumno-Profesor

```
❌ PROHIBIDO: Alumno → Profesor (relación directa)
✅ CORRECTO:  Alumno → Asistencia → Clase → Profesor(es)
```

**Justificación:**
- Un alumno puede asistir a clases dictadas por distintos profesores
- Una misma clase puede tener más de un profesor
- Un mismo nivel puede ser dictado por distintos profesores en días distintos

### 2.2 Profesor

| Campo | Tipo | Obligatorio | Condición |
|-------|------|-------------|-----------|
| deporte_id | FK | Sí | Siempre |
| valor_hora | Decimal | Condicional | Solo si deporte.tipo_liquidacion = HORA |
| porcentaje_comision | Decimal | Condicional | Solo si deporte.tipo_liquidacion = COMISION |

**Regla:** El profesor hereda la forma de liquidación del deporte.

### 2.3 Clases

| Estado | Liquida? | Condición |
|--------|----------|-----------|
| cancelada = true | ❌ NO | Nunca liquida |
| Sin asistencias, sin validación | ❌ NO | No liquida |
| Con al menos 1 asistencia (presente=true) | ✅ SÍ | Liquida |
| validada_para_liquidacion = true | ✅ SÍ | Liquida aunque no tenga asistencias |

### 2.4 Liquidaciones

- **Generación:** Automática por el sistema, nunca manual
- **Modificación:** Solo liquidaciones ABIERTAS
- **Inmutabilidad:** Liquidaciones CERRADAS no pueden modificarse ni eliminarse
- **Permisos:** Solo Administrador puede crear/gestionar liquidaciones

---

## 3. Lógica de Cálculo por Tipo

### 3.1 Liquidación POR_HORA

**Premisa:** El profesor cobra por cada clase válida que dictó.

**Cálculo:**
```
total = Σ (clases_validas_del_profesor × valor_hora)
```

**Clase válida:**
- Pertenece al período (mes/año)
- NO está cancelada
- Tiene al menos 1 asistencia con presente=true O está validada_para_liquidacion

**Regla multi-profesor:**
Si una clase tiene 2+ profesores, CADA profesor cobra su valor_hora completo.
NO se divide el monto.

**Ejemplo:**
```
Clase: Natación 15/01 10:00
Profesores: Juan (valor_hora=1000), María (valor_hora=1200)
Asistencias: 5 alumnos presentes

→ Liquidación Juan: +$1000 por esta clase
→ Liquidación María: +$1200 por esta clase
```

### 3.2 Liquidación POR_COMISION

**Premisa:** El profesor cobra comisión por alumnos que:
1. Pagaron en el período
2. Asistieron a al menos 1 clase dictada por ESE profesor en el período

**Cálculo:**
```
Para cada alumno del deporte:
  SI pagó en el período
  Y asistió a al menos 1 clase del profesor en el período
  ENTONCES:
    comision = monto_pagado × (porcentaje_comision / 100)
```

**Regla multi-profesor (CRÍTICA):**
Si un alumno asistió a clases de 2+ profesores distintos en el período:
- **Opción A (Implementada):** Cada profesor cobra comisión completa
- ~~Opción B: Se divide la comisión proporcionalmente~~
- ~~Opción C: Solo cobra el profesor con más clases~~

**Ejemplo:**
```
Alumno: Pedro (pagó $5000 en enero)
Asistencias enero:
  - 3 clases con Prof. Juan (comisión 10%)
  - 2 clases con Prof. María (comisión 15%)

→ Liquidación Juan: +$500 (5000 × 10%)
→ Liquidación María: +$750 (5000 × 15%)
```

**Importante:** Las clases en comisión sirven como CONTROL, no determinan el monto.
El monto viene del PAGO del alumno.

---

## 4. Diagrama Entidad-Relación Completo

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│   DEPORTE    │       │    GRUPO     │       │   ALUMNO     │
├──────────────┤       ├──────────────┤       ├──────────────┤
│ id           │◄──┐   │ id           │       │ id           │
│ nombre       │   │   │ deporte_id   │───────│ deporte_id   │
│ tipo_liquid. │   │   │ nombre       │       │ grupo_id     │
│ activo       │   │   │ activo       │       │ nombre       │
└──────────────┘   │   └──────────────┘       │ activo       │
       │           │          │               └──────────────┘
       │ 1:N       │          │ 1:N                  │
       ▼           │          ▼                      │ 1:N
┌──────────────┐   │   ┌──────────────┐              │
│   PROFESOR   │   │   │    CLASE     │              │
├──────────────┤   │   ├──────────────┤              │
│ id           │   │   │ id           │              │
│ deporte_id   │───┘   │ grupo_id     │◄─────────────┤
│ nombre       │       │ fecha        │              │
│ valor_hora   │       │ hora_inicio  │              │
│ porcentaje_c │       │ hora_fin     │              │
│ activo       │       │ validada_liq │              │
└──────────────┘       │ cancelada    │              │
       │               └──────────────┘              │
       │                     │                       │
       │ N:M                 │ 1:N                   │
       ▼                     ▼                       │
┌──────────────────────────────────┐                 │
│         CLASE_PROFESOR           │                 │
├──────────────────────────────────┤                 │
│ clase_id                         │                 │
│ profesor_id                      │                 │
└──────────────────────────────────┘                 │
                                                     │
                    ┌──────────────┐                 │
                    │  ASISTENCIA  │                 │
                    ├──────────────┤                 │
                    │ id           │                 │
                    │ clase_id     │◄────────────────┤
                    │ alumno_id    │─────────────────┘
                    │ presente     │
                    └──────────────┘

┌──────────────┐       ┌──────────────────────┐
│ LIQUIDACION  │       │ LIQUIDACION_DETALLE  │
├──────────────┤       ├──────────────────────┤
│ id           │◄──────│ liquidacion_id       │
│ profesor_id  │       │ tipo_referencia      │
│ mes          │       │ referencia_id        │
│ anio         │       │ monto                │
│ tipo         │       │ descripcion          │
│ total_calc.  │       └──────────────────────┘
│ estado       │
└──────────────┘

┌──────────────┐
│     PAGO     │
├──────────────┤
│ id           │
│ alumno_id    │
│ mes          │
│ anio         │
│ monto_final  │
│ estado       │
└──────────────┘
```

---

## 5. Flujo de Liquidación

### 5.1 Generar Liquidación

```
1. Admin solicita generar liquidación (profesor_id, mes, año)
2. Sistema valida:
   - Profesor existe y está activo
   - Profesor tiene deporte asignado
   - No existe liquidación previa para el período
   - Profesor tiene valor_hora o porcentaje según tipo
3. Sistema calcula según tipo_liquidacion del deporte:

   SI tipo = HORA:
     - Obtener clases del profesor en el período
     - Filtrar clases liquidables (con asistencia o validadas)
     - Calcular: cantidad × valor_hora

   SI tipo = COMISION:
     - Obtener alumnos del deporte con pago en el período
     - Filtrar los que asistieron a clases del profesor
     - Calcular: Σ(monto_pago × porcentaje_comision)

4. Sistema crea liquidación con detalles
5. Liquidación queda en estado ABIERTA
```

### 5.2 Cerrar Liquidación

```
1. Admin solicita cerrar liquidación
2. Sistema valida que esté ABIERTA
3. Sistema cambia estado a CERRADA
4. Liquidación se vuelve INMUTABLE
```

---

## 6. API Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | /api/liquidaciones | Generar liquidación |
| GET | /api/liquidaciones/{id} | Ver liquidación |
| DELETE | /api/liquidaciones/{id} | Eliminar (solo si ABIERTA) |
| POST | /api/liquidaciones/{id}/cerrar | Cerrar liquidación |
| POST | /api/liquidaciones/{id}/recalcular | Recalcular (solo si ABIERTA) |
| GET | /api/liquidaciones/preview | Vista previa sin guardar |
| GET | /api/liquidaciones/resumen/{mes}/{anio} | Resumen del período |
| GET | /api/profesores/{id}/liquidaciones | Historial de profesor |

---

## 7. Casos de Uso

### Caso 1: Natación (POR_HORA)
```
Deporte: Natación (tipo=HORA)
Profesor: Laura (valor_hora=1500)
Enero 2026: 12 clases, 10 con asistencia, 2 sin asistencia

Liquidación:
- 10 clases × $1500 = $15.000
```

### Caso 2: Fútbol (POR_COMISION)
```
Deporte: Fútbol (tipo=COMISION)
Profesor: Carlos (porcentaje=10%)

Alumnos del deporte con pago en enero:
- Pedro: pagó $8000, asistió 4 clases de Carlos ✓
- Juan: pagó $8000, asistió 2 clases de Carlos ✓
- María: pagó $8000, NO asistió a ninguna clase ✗

Liquidación Carlos:
- Pedro: $8000 × 10% = $800
- Juan: $8000 × 10% = $800
- María: NO (sin asistencia)
- Total: $1.600
```

### Caso 3: Fútbol con múltiples profesores
```
Deporte: Fútbol (tipo=COMISION)
Profesor A: (10%), Profesor B: (12%)

Alumno Pedro: pagó $8000
- Asistió 3 clases con Prof. A
- Asistió 2 clases con Prof. B

Liquidación Prof. A: +$800 (8000 × 10%)
Liquidación Prof. B: +$960 (8000 × 12%)
```

---

## 8. Validaciones de Integridad

### Al crear Profesor:
- Debe tener deporte_id
- Si deporte.tipo = HORA → valor_hora obligatorio
- Si deporte.tipo = COMISION → porcentaje_comision obligatorio

### Al generar Liquidación:
- No puede existir liquidación previa para profesor+mes+año
- Profesor debe tener deporte activo
- Valores de cálculo deben ser > 0

### Al modificar Liquidación:
- Solo si estado = ABIERTA
- Cerrada = INMUTABLE

---

## 9. Escalabilidad

Para agregar un nuevo tipo de liquidación:

1. Agregar valor al enum `tipo_liquidacion` en deportes
2. Crear método `calcularLiquidacion{NuevoTipo}()` en LiquidacionService
3. Agregar case en `generarLiquidacionMensual()`

No requiere cambios en el modelo de datos base.
