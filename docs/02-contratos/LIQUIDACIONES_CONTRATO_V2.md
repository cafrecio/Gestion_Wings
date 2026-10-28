# Contrato Funcional - Sistema de Liquidaciones v2

## Enmienda 28/10/2026 — T45, vigencia mensual de la forma de liquidar

DU-42 / CAT-002: ADMIN puede programar otro tipo solo si el deporte no tiene liquidaciones ABIERTAS. El rechazo identifica número, profesora y período de cada una. Cambiar solamente el nombre no modifica la forma ni queda bloqueado por una abierta.

El cambio rige desde el primer día del mes siguiente. Cada programación conserva tipo, fecha de vigencia, autor y fecha de registro. El tipo se resuelve por el período que se liquida o previsualiza; no por el día en que se pulsa Generar. Se conserva el historial de meses anteriores y el tipo/importe ya congelados en cerradas o pagadas. Modificar otra vez una programación pendiente exige el mismo control de abiertas; reenviar la misma forma no la duplica ni posterga su vigencia.

Si la forma es COMISION y falta porcentaje válido, se avisa al programar y en Nueva liquidación para el mes elegido; el servidor impide generar/previsualizar hasta cargarlo. No se supone un porcentaje. El formulario de profesores permite completar la próxima forma antes de su vigencia y conserva la tarifa y el porcentaje anteriores. Los estimados de Sueldos aplican la misma vigencia; las liquidaciones existentes conservan su foto.

Implementada localmente por Codex CyE; verificación independiente de Claude y aspecto de Carlos pendientes. [Entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T45.md) · [Capturas](../06-pruebas/PRU-04/APROBAR-T45.html). Sin despliegue.

> Enmienda funcional 13/09/2026, pendiente de implementación: ver [Clases particulares V1](Wings-Contrato-Clases-Particulares-V1.md). Sus reglas específicas prevalecen para particulares sobre las reglas generales de este documento. Para todas las clases, el cierre bloquea asistencia a todos los perfiles; solo ADMIN puede cancelar una cerrada no pagada para revisarla. Una pagada permanece intacta y se corrige económicamente en la próxima liquidación. Las descripciones anteriores de comportamiento no certifican la implementación de esta enmienda.

## Enmienda 20/10/2026 — T42, mes efectivo de la comisión

Decisión DU-19: cada parte de una cuota cobrada se liquida en el mayor entre el mes real del cobro y el mes que paga. Noviembre cobrado en octubre corresponde a noviembre; septiembre cobrado en octubre sigue en octubre. Se exige al menos una asistencia presente con ese profesor en el mes de la liquidación. Un cobro que paga varios meses se desglosa por `pago_deuda_cuota.monto_aplicado` y `deuda_cuotas.periodo`.

Sin imputaciones se conserva la regla histórica de `Pago.mes/anio` y `monto_cuota` (fallback a `monto_final` si es NULL). Se excluyen anulados y se conserva el porcentaje congelado. Los centavos de un pago asignados a un mismo mes se suman antes de redondear su comisión a dos decimales, como el cálculo anterior por pago. No se recalculan liquidaciones cerradas ni pagadas. No cambia el registro de dinero de Caja/Cashflow.

T42 implementada y publicada en desarrollo, a verificar por Claude; completa 997 aprobadas/6 omitidas, corte 1003. Carlos resolvió conservar el mes original solamente cuando no pueda probarse el pagado. Sin despliegue. [Entrega y pruebas](../06-pruebas/PRU-04/IMPLEMENTACION-T42.md).

## Enmienda 20/10/2026 — T43, revisión de cuotas atrasadas

DU-36: una cuota atrasada cobrada en el mes corresponde a esa liquidación, con aviso al dueño. Cada parte registra pago, deuda, período, base, fecha de cobro y origen (`atrasado`, `del_mes` o `adelantado`). Las atrasadas nacen PENDIENTES; solo ADMIN activo puede validarlas o rechazarlas, con responsable y fecha. No se puede cerrar ni pagar mientras haya decisiones pendientes.

El cálculo original se conserva. El confirmado resta las comisiones pendientes o rechazadas; ese es el monto a pagar por defecto. Las cuotas del mes y adelantadas se incluyen automáticamente según DU-19. El anexo del recibo incluye solamente las atrasadas validadas y las partes automáticas: no muestra las rechazadas. Las decisiones por sí solas no mueven Caja/Cashflow; el pago registra el importe efectivo confirmado.

Si existe un monto final ajustado por Administración, se conserva como total absoluto autorizado según la enmienda09/10: el recibo separa el subtotal incluido y su ajuste hasta ese total. No se transforma silenciosamente el ajuste en una diferencia ni se borra su auditoría.

Al validar/rechazar desde la pantalla, se actualizan en el lugar la decisión y los importes confirmados, sin recargar la página completa. El pie de comisión muestra Total confirmado; el cálculo original permanece separado. La respuesta parcial conserva las mismas validaciones de servidor que el formulario normal.

Decisiones y totales se guardan de forma atómica con bloqueo de cabecera; un formulario viejo no pisa otra decisión y un reenvío idéntico no duplica auditoría. Recalcular una abierta conserva una decisión solo si coinciden pago, deuda, período, base y comisión. Si cambian, la parte requiere nueva revisión. La auditoría anterior permanece incluso al recalcular o eliminar una abierta.

No hay reconstrucción automática de origen/decisión en documentos históricos: metadatos NULL y exclusión cero. Cerradas y pagadas conservadas. La migración preserva evidencia financiera al retroceder código; su `down()` no elimina el esquema ni las decisiones.

Implementación local con pruebas dirigidas y capturas reales; completa posterior al guardado JSON 1017 aprobadas, 6 omitidas, 8791 aserciones, 844,65 s, salida 0; corrección visual e interacción aprobadas por Carlos («Ahora esta OK»), pendiente control independiente de lógica por Claude. Sin despliegue. [Entrega T43](../06-pruebas/PRU-04/IMPLEMENTACION-T43.md).

## Enmienda 09/10/2026 — monto final de comisión, implementado en desarrollo

En Desarrollo III, Carlos eligió comisión sobre **cuotas cobradas** y aclaró que
ADMIN edita el **«Valor final de la liquidacion»**, no el porcentaje ni la cuota.
Permite hacerlo **«También cerradas sin pagar»**. Es una excepción económica al
bloqueo, sin editar detalles del cálculo ni asistencia de la liquidación cerrada.
Las pagadas conservan el criterio de corrección posterior de la enmienda13/09.
Conservar cálculo original y registro de cada ajuste final, con responsable y fecha.
Implementado y probado en base descartable: POST ADMIN activo, monto final separado,
auditoría de anterior/nuevo/cálculo/motivo/responsable/fecha y bloqueo de fila.
Pago, recibo, resúmenes y pendientes usan el final; cálculo y detalles quedan intactos.
No se permite ajuste directo sin auditoría ni ajustes pagados/cancelados/HORA.
Un formulario viejo se rechaza tras cambio de importe. Sin desplegar ni migrar la base del club.

El reporte separará **Alumnos con pagos registrados** y **Asistencias sin pago de
cuota**. Atribuir cuotas proporcionalmente a asistencias es solo análisis; no
cambia la comisión completa vigente para cada profesor.
[Acuerdos literales](../07-evaluacion/ACUERDOS-B12-A23-2026-10-09.md) ·
[Historia probada, sin activar en el club](../06-pruebas/B12-A23/HISTORIAL-SUELDOS-2026-10-09.md).

## Aclaración visual — 10/10/2026

Campo **Monto ajustado**: se ingresa el total a pagar, cero o positivo; no la diferencia.
La diferencia entre ese total y el cálculo original puede ser negativa y se muestra automáticamente.
Carlos aprobó texto, ayuda e íconos: «Sí, así». No cambia la regla monetaria ni la auditoría.
[Capturas y control](../06-pruebas/B12-A23/ETIQUETAS-AJUSTE-2026-10-10.md).

## 1. Modelo de Dominio

### 1.1 Entidad Central: Deporte

El **Deporte** es el eje del modelo. Define:

- `nombre`: Nombre del deporte
- `tipo_liquidacion`: Enum (`HORA` | `COMISION`)
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
| valor_hora | Decimal | Condicional | Para períodos por HORA; se conserva al programar COMISION |
| porcentaje_comision | Decimal | Condicional | Para períodos por COMISION; puede cargarse antes de su vigencia |

**Regla:** El profesor hereda la forma de liquidación del deporte vigente para el período. Se conservan ambos valores para liquidar períodos anteriores.

### 2.3 Clases

| Estado | Liquida? | Condición |
|--------|----------|-----------|
| cancelada = true | ❌ NO | Nunca liquida |
| Sin asistencias, sin validación | ❌ NO | No liquida |
| Con al menos 1 asistencia (presente=true) | ✅ SÍ | Liquida |
| validada_para_liquidacion = true | ✅ SÍ | Liquida aunque no tenga asistencias |

### 2.4 Liquidaciones

- **Generación:** Automática por el sistema, nunca manual
- **Cierre:** Solo a partir del día 1 del mes siguiente al período liquidado (enmienda 18/10/2026, DU-37 / T41).
- **Modificación general:** Liquidaciones ABIERTAS; ajuste final de comisión según
  enmienda09/10 también en CERRADAS sin pagar, implementado en desarrollo.
- **Inmutabilidad:** Liquidaciones CERRADAS no pueden modificarse ni eliminarse —
  **con las excepciones de cancelar13/09 y ajuste final09/10 documentadas aquí**.
- **Permisos:** Solo Administrador puede crear/gestionar liquidaciones

#### Enmienda 18/10/2026 — no se puede cerrar una liquidación antes de que termine el mes (T41, DU-37)

Decisión de Carlos: **«Impedir cerrar antes de que termine el mes»** / **«A partir del 1ro de cada mes»**.

- Una liquidación cerrada y luego pagada deja afuera las clases que restan del mes, porque no se admite una segunda liquidación activa para el mismo profesor y período.
- Una liquidación del mes `M` y año `A` solo se puede cerrar cuando la fecha actual del sistema en la zona horaria de Wings (`America/Argentina/Buenos_Aires`, respetando el reloj simulado del sitio de prueba) es mayor o igual al día `01` del mes siguiente a las `00:00`.
- Mientras el mes no haya terminado, `LiquidacionService::cerrarLiquidacion` rechaza el cierre con un aviso en castellano, y la pantalla de la liquidación (`resources/views/liquidaciones/show.blade.php`) oculta el botón `Cerrar` y muestra el texto `Se puede cerrar desde el DD/MM/AAAA`. El resto de las acciones de una liquidación abierta (`Recalcular`, `Eliminar`, `Volver`) permanece sin cambios.

#### Enmienda 13/09/2026 — cancelar una cerrada que todavía no se pagó (FIN-12)

Hasta hoy «cerrada» y «pagada» se trataban igual, y una liquidación mal armada
quedaba clavada para siempre aunque el profesor todavía no hubiera cobrado un peso.

Carlos lo resolvió el 13/09 con el caso real a la vista: **Vanina va a liquidar mal
alguna vez, sobre todo en los primeros meses**, y mientras no haya pagado tiene que
poder volver atrás. Obligarla a arrastrar el error hasta la liquidación siguiente no
protege nada: la plata todavía no salió.

**Son dos situaciones distintas y el contrato ya no las llama igual:**

| Estado | Qué se puede hacer |
|---|---|
| `CERRADA` + `estado_pago = PENDIENTE` | **Solo ADMIN la cancela**, para revisar las asistencias y rehacerla. La liquidación cancelada y su detalle **se conservan**, con responsable, fecha y motivo |
| `CERRADA` + `estado_pago = PAGADA` | **Intocable, para todos.** La diferencia se corrige en la liquidación siguiente |

**«No pagada» se refiere al sueldo del profesor**, no a cuotas pendientes de los
alumnos. Son dos plata distintas y es fácil confundirlas leyendo rápido.

**Lo que esta enmienda NO cambia:**

- Cancelar no borra: la liquidación cancelada queda como documento, con su detalle
  y el vínculo a la que la reemplace. Sin eso no hay forma de explicar después por
  qué un mes se liquidó dos veces.
- **Mientras la liquidación siga cerrada, ningún perfil toca las asistencias
  vinculadas — tampoco el ADMIN.** Cancelarla no le da permisos nuevos al profesor
  ni le reinicia el plazo de corrección.
- No cambia el cálculo de sueldos ni habilita tocar pagos, recibos o egresos.
- La regla general **«las liquidaciones cerradas no se reabren; las correcciones son
  compensatorias» sigue vigente para las pagadas**, que es donde siempre aplicó.

Alcance y criterios de aceptación:
[FIN-12](../05-pendientes/FIN-12-CANCELAR-LIQUIDACION-CERRADA.md).

---

## 3. Lógica de Cálculo por Tipo

### 3.1 Liquidación HORA

**Premisa:** El profesor cobra cada clase válida que dictó según su duración
(decisión de Carlos, 17/09/2026; antes se pagaba la tarifa entera por clase).

**Cálculo:**
```
importe_clase = round(valor_hora × minutos_de_la_clase / 60, 2)
total         = Σ importe_clase
```

- Los minutos salen de `hora_fin - hora_inicio`. Una clase sin duración válida
  frena la liquidación con un mensaje que la identifica; no se inventa una hora.
- La tarifa queda congelada en `liquidaciones.valor_hora_aplicado` y los minutos en
  `liquidacion_detalles.minutos`. Recalcular una abierta usa la tarifa congelada;
  cambiar después la tarifa del profesor o el horario de la clase no reescribe lo
  liquidado. La vista previa usa el mismo cálculo con la tarifa vigente.
- Las liquidaciones anteriores al 17/09 no se recalculan: la migración toma como
  tarifa el importe que se pagó por clase y registra los minutos que ese importe
  representa.

**Clase válida:**
- Pertenece al período (mes/año)
- NO está cancelada
- Tiene al menos 1 asistencia con presente=true O está validada_para_liquidacion

**Regla multi-profesor:**
Si una clase tiene 2+ profesores, CADA profesor cobra su importe completo de la clase.
NO se divide el monto.

**Ejemplo:**
```
Clase: Natación 15/01 10:00 a 11:30 (90 minutos)
Profesores: Juan (valor_hora=1000), María (valor_hora=1200)
Asistencias: 5 alumnos presentes

→ Liquidación Juan: +$1500 por esta clase
→ Liquidación María: +$1800 por esta clase
```

### 3.2 Liquidación COMISION

**Premisa:** El profesor cobra comisión por alumnos que:
1. Tienen importes de cuota asignados a la liquidación del período según DU-19 (enmienda20/10)
2. Asistieron a al menos 1 clase dictada por ESE profesor en el período

**Cálculo:**
```
Para cada alumno del deporte:
  SI tiene cuota imputada a la liquidación del período según DU-19
  Y asistió a al menos 1 clase del profesor en el período
  ENTONCES:
    comision_original = monto_asignado_al_periodo × (porcentaje_comision / 100)
    comision_confirmada = comision_original - atrasadas_pendientes_o_rechazadas
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
3. Sistema calcula según la vigencia del deporte para el mes y año elegidos (T45):

   SI tipo = HORA:
     - Obtener clases del profesor en el período
     - Filtrar clases liquidables (con asistencia o validadas)
     - Calcular: Σ(valor_hora × minutos / 60) por clase

   SI tipo = COMISION:
     - Obtener pagos válidos y sus partes asignadas al período según DU-19
     - Filtrar los que asistieron a clases del profesor
     - Calcular: Σ(monto_del_pago_asignado_al_periodo × porcentaje_comision)

4. Sistema crea liquidación con detalles
5. Liquidación queda en estado ABIERTA
```

### 5.2 Cerrar Liquidación

```
1. Admin solicita cerrar liquidación
2. Sistema valida que esté ABIERTA y que el mes liquidado ya haya terminado
   (hoy >= día 1 del mes siguiente en America/Argentina/Buenos_Aires, T41 / DU-37)
3. Sistema cambia estado a CERRADA
4. Documento/asistencia bloqueados; ajuste final de comisión según enmienda09/10
   implementado para cerradas sin pagar
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

### Caso 1: Natación (HORA)
```
Deporte: Natación (tipo=HORA)
Profesor: Laura (valor_hora=1500)
Enero 2026: 12 clases de una hora, 10 con asistencia, 2 sin asistencia

Liquidación:
- 10 clases × 60 min × $1500 / 60 = $15.000
- Si una de esas 10 durara 90 minutos: $13.500 + $2.250 = $15.750
```

### Caso 2: Fútbol (COMISION)
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

### Al cerrar Liquidación:
- Estado actual debe ser `ABIERTA`
- El mes liquidado debe haber terminado: `today() >= 01/(mes+1)` en hora de Wings (`T41` / `DU-37`)

### Al modificar Liquidación:
- Regla general: estado = ABIERTA.
- Cerrada: documento/asistencia bloqueados; excepción de importe final de comisión
  aprobada09/10 para CERRADA + PENDIENTE, implementado en desarrollo.

---

## 9. Escalabilidad

Para agregar un nuevo tipo de liquidación:

1. Agregar valor al enum `tipo_liquidacion` en deportes
2. Crear método `calcularLiquidacion{NuevoTipo}()` en LiquidacionService
3. Agregar case en `generarLiquidacionMensual()`

No requiere cambios en el modelo de datos base.
