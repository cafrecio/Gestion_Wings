# Dataset del seeder de prueba - V1

Este documento **define exactamente** qué filas debe crear el seeder de prueba.
No es una guía ni una sugerencia: es la lista de datos a transcribir.

Quien implemente el seeder **no decide qué datos cargar**. Si algo acá no está
definido, se pregunta; no se inventa.

Motivo: el seeder anterior (`DemoSeeder`) cargó una operación por día, todas
iguales, todas en efectivo, todas las clases sin asistencia, y además escribió
referencias corruptas en cashflow. Con esos datos no se puede probar nada.

## Convenciones de fecha

| Símbolo | Significado |
|---|---|
| `HOY` | Fecha de ejecución del seeder |
| `M0` | Mes en curso (`YYYY-MM` de HOY) |
| `M1` | Mes anterior |
| `M2` | Dos meses atrás |

Todas las fechas son **relativas a HOY**. No hardcodear fechas absolutas: el
seeder tiene que servir igual dentro de seis meses.

## Restricciones de implementación

1. **Aborta si `app()->environment()` no es `local` ni `testing`.** Sin excepción.
2. Los catálogos (deportes, niveles, rubros, subrubros, tipos de caja,
   configuraciones) van en un seeder **separado**, porque producción los necesita
   y estos datos de prueba no.
3. Invariantes que deben cumplirse al terminar:
   - `deuda_cuotas.monto_pagado` = suma de `pago_deuda_cuota.monto_aplicado` de esa deuda.
   - `cashflow_movimientos.referencia_id` guarda el ID de la **caja operativa**,
     nunca el del movimiento.
   - `movimientos_operativos.monto` siempre **positivo**; el signo lo da `rubro.tipo`.
   - Ninguna caja `ABIERTA` o `CERRADA` tiene reflejo en cashflow.
4. Determinístico: misma corrida, mismo resultado. Sin `rand()` ni `faker` para
   datos que el documento define.

## Limitación conocida: EN_PLAZO vs MOROSO

Los dos estados dependen de si ya pasaron los días de gracia del mes en curso, y
la gracia es **un único valor** (`dias_gracia_cobranza`) para todos los alumnos.
En cualquier fecha dada, todos los que deben `M0` están en el mismo estado.

**No se debe fabricar un dato que simule los dos a la vez.** Para probar ambos:
cargar el dataset, mirar el listado, cambiar `dias_gracia_cobranza` y recargar.

---

# 1. Catálogos

## Deportes

| # | Nombre | tipo_liquidacion | activo |
|---|---|---|---|
| DEP1 | Patín | HORA | sí |
| DEP2 | Gimnasia | COMISION | sí |

## Niveles

Principiantes · Intermedias · Avanzadas

## Grupos

| # | Deporte | Nivel | activo |
|---|---|---|---|
| G1 | Patín | Principiantes | sí |
| G2 | Patín | Intermedias | sí |
| G3 | Gimnasia | Principiantes | sí |
| G4 | Gimnasia | Avanzadas | **no** |

`G4` inactivo existe para verificar que no aparezca en selectores ni listados.

## Planes (`grupo_planes`)

| # | Grupo | clases_por_semana | precio_mensual | activo |
|---|---|---|---|---|
| P1 | G1 | 2 | 28.000 | sí |
| P2 | G1 | 3 | 35.000 | sí |
| P3 | G2 | 2 | 33.000 | sí |
| P4 | G3 | 2 | 30.000 | sí |
| P5 | G3 | 3 | 38.000 | sí |

## Tipos de caja

| # | Nombre | Abrev. | permite_descubierto | Saldo inicial esperado |
|---|---|---|---|---|
| TC1 | Efectivo | EFE | no | 0 |
| TC2 | Mercado Pago | MP | no | 0 |
| TC3 | Banco Nación | BNA | **sí** | 0 |
| TC4 | Banco Galicia | BGAL | sí | 0 |

Los saldos arrancan en cero y se construyen con los movimientos definidos abajo.
No inyectar saldos a mano.

## Usuarios

| # | Rol | Nombre | Activo |
|---|---|---|---|
| U1 | ADMIN | Admin Prueba | sí |
| U2 | OPERATIVO | Operativo Uno | sí |
| U3 | OPERATIVO | Operativo Dos | sí |
| U4 | PROFESOR | Jorge Mitre | sí |
| U5 | OPERATIVO | Operativo Baja | **no** |

`U5` inactivo existe para verificar que no puede iniciar sesión.

## Profesores

| # | Nombre | Deporte | valor_hora | % comisión | activo |
|---|---|---|---|---|---|
| PR1 | Jorge Mitre | Patín | 10.000 | — | sí |
| PR2 | Silvia Roca | Gimnasia | — | 40 | sí |
| PR3 | Tomás Vidal | Patín | 12.000 | — | **no** |

`PR1` es el profesor vinculado al usuario `U4`.

---

# 2. Alumnos

Cada alumno existe **para probar una cosa concreta**, indicada en la última
columna. Todos con `celular` y `email` cargados (son obligatorios).

## 2.1 Estados de cobranza

| # | Alumno | Grupo | Plan | fecha_alta | Deudas | Estado esperado |
|---|---|---|---|---|---|---|
| A1 | Ana Ruiz | G1 | P1 | HOY − 8 meses | M1 PAGADA, M0 PAGADA | **AL_DIA** |
| A2 | Bruno Sosa | G1 | P1 | HOY − 8 meses | M1 PAGADA, M0 PENDIENTE | **EN_PLAZO** o **MOROSO** según el día (ver limitación) |
| A3 | Carla Vera | G2 | P3 | HOY − 12 meses | M1 PENDIENTE, M0 PENDIENTE | **DEUDOR** |
| A4 | Diego Paz | G1 | P1 | HOY − 3 meses | M2, M1, M0 PENDIENTES | **DEUDOR** (3 períodos) |

`A4` es el caso de deuda acumulada: sirve para probar imputación FIFO cobrando
un monto que no alcanza a cubrir todo.

## 2.2 Alumno nuevo (corte por cantidad de clases)

Ninguno tiene pagos ni deudas. Todos son **alumno nuevo** hasta que paguen.

| # | Alumno | Grupo | fecha_alta | Clases asistidas | Qué prueba |
|---|---|---|---|---|---|
| B1 | Facundo Ríos | G1 | HOY − 3 días | 1 | Primera clase: prueba, entra sin objeción |
| B2 | Gala Núñez | G1 | HOY − 6 días | 2 | Segunda clase: permitida |
| B3 | Hugo Lema | G2 | HOY − 10 días | 3 | Tercera clase: requiere justificación |

## 2.3 Generación de la deuda mensual

Estos alumnos **no deben tener deuda de `M0` cargada por el seeder**. La deuda de
`M0` la tiene que generar el proceso mensual al ejecutarse. El seeder crea las
condiciones previas.

| # | Alumno | Grupo | Plan | Condición previa | Resultado esperado del proceso |
|---|---|---|---|---|---|
| C1 | Irina Paz | G1 | P1 | Asistió a 4 clases en M1 | **Genera** deuda M0 de $28.000 |
| C2 | Jorge Sena | G1 | P1 | Sin asistencias en M1, sin pagos | **Cola de revisión**, no genera |
| C3 | Karen Ortiz | G3 | P4 | Alta HOY − 10 días, pagó HOY − 5 días, sin asistencias | **Genera** deuda M0 de $30.000 |

## 2.4 Cambio de plan

| # | Alumno | Plan actual | Asistencias en M0 | Cambio a probar | Resultado esperado |
|---|---|---|---|---|---|
| D1 | Lucas Díaz | P2 ($35.000) | 3 | Bajar a P1 | Aplica **desde M1 siguiente** |
| D2 | Mara Gil | P2 ($35.000) | 0 | Bajar a P1 | Aplica **en M0** |
| D3 | Nico Ferro | P1 ($28.000) | 2 | Subir a P2 | Aplica **en M0** |

Cada uno necesita su fila en `alumno_planes` con `fecha_desde`, `activo = 1` y
`fecha_hasta = NULL`. `D1` y `D2` además deben tener una fila histórica cerrada
(plan anterior con `fecha_hasta` y `activo = 0`) para que se vea el historial.

## 2.5 Alumno dado de baja

| # | Alumno | Grupo | activo | Qué prueba |
|---|---|---|---|---|
| E1 | Olga Sanz | G1 | **no** | No aparece en listados activos ni en generación de deuda |

**Total: 15 alumnos.**

---

# 3. Clases y asistencias

## 3.1 Clases del mes anterior (M1) — base de la liquidación

Grupo G1, profesor PR1, lunes y miércoles 18:00–19:00. **8 clases** en M1.

| Estado | Cantidad | Detalle |
|---|---|---|
| Con asistencia completa | 6 | Todos los alumnos de G1, mezcla de presentes y ausentes |
| Cancelada con motivo | 1 | `motivo_cancelacion = "Corte de luz en el club"` |
| Sin lista cargada | 1 | Para que aparezca en el badge "Sin asistencia" |

Grupo G3, profesor PR2, martes y jueves 17:00–18:00. **8 clases** en M1, todas
con asistencia cargada (base de la liquidación por comisión).

## 3.2 Clases del mes en curso (M0)

| # | Grupo | Profesor | Cuándo | Estado |
|---|---|---|---|---|
| CL-A | G1 | PR1 | HOY − 2 días | Con asistencia cargada |
| CL-B | G1 | PR1 | HOY − 1 día | **Sin lista** (alimenta el badge) |
| CL-C | G2 | PR1 | HOY, 19:00–20:00 | Sin lista |
| CL-D | G1 | PR1 | HOY + 2 días | Futura, sin lista |
| CL-E | G3 | PR2 | HOY + 3 días | Futura, sin lista |

`CL-C` existe específicamente para probar el solapamiento: intentar crear otra
clase de PR1 el mismo día entre 19:00 y 20:00 debe ser rechazado.

## 3.3 Serie recurrente

Una serie (`serie_id` compartido) de **6 clases** de G2 con PR1, viernes
20:00–21:00, arrancando HOY + 7 días. Sirve para probar edición y borrado de
serie completa.

## 3.4 Clase validada para liquidación

Una de las 6 clases con asistencia de G1 en M1 debe tener
`validada_para_liquidacion = 1`, para verificar que no se puede modificar.

## 3.5 Asistencias

Las listas cargadas **no pueden ser todas iguales**. Distribución obligatoria:

- Al menos una clase con **todos presentes**.
- Al menos una con **todos ausentes**.
- El resto, mezcla parcial.
- Al menos dos filas con `motivo_correccion` cargado, simulando una corrección
  posterior sobre una clase ya pasada.

---

# 4. Cajas y movimientos

Cinco cajas, en los cuatro estados. **Ninguna con un solo movimiento.**

| # | Operativo | Estado | Apertura | Movimientos |
|---|---|---|---|---|
| CJ1 | U2 | ABIERTA | HOY | 4 (ver 4.1) |
| CJ2 | U2 | CERRADA | HOY − 1 día | 5 |
| CJ3 | U3 | VALIDADA | HOY − 2 días | 6 |
| CJ4 | U3 | RECHAZADA | HOY − 3 días | 3 |
| CJ5 | U2 | ABIERTA | HOY − 4 días | 2, uno **CANCELADO** |

`CJ4` con `motivo_rechazo = "Diferencia de $2.000 en el arqueo de efectivo"`.

`CJ5` abierta hace 4 días existe para probar el middleware `bloqueo.caja.vieja`.

## 4.1 Composición de los movimientos

Regla que el seeder debe respetar en **todas** las cajas:

- Cada caja usa **al menos dos tipos de caja distintos** (nunca todo efectivo).
- Cada caja tiene **al menos un egreso** además de los ingresos.
- Los montos son distintos entre sí (nunca todos iguales).

Distribución sugerida por caja, respetando lo anterior:

| Concepto | Tipo de caja | Signo |
|---|---|---|
| Cobro de cuota | Efectivo | Ingreso |
| Cobro de cuota | Mercado Pago | Ingreso |
| Venta de indumentaria | Efectivo | Ingreso |
| Compra de insumos | Efectivo | Egreso |
| Pago a profesor | Banco Nación | Egreso |

Los cobros de cuota deben estar **vinculados a un pago real** (`pago_id` y
`alumno_id` cargados) y tener su imputación en `pago_deuda_cuota`. Los movimientos
que no son cobro de cuota van sin `alumno_id`.

## 4.2 Reflejo en cashflow

**Solo `CJ3`** (la validada) genera filas en `cashflow_movimientos`.

- Una fila por movimiento activo de esa caja.
- `referencia_tipo = 'CAJA_OPERATIVA'`.
- `referencia_id` = **ID de `CJ3`**, no el del movimiento.
- `monto` **con signo**: negativo para egresos.
- El movimiento cancelado, si lo hubiera, **no se refleja**.

`CJ4` (rechazada) no genera nada.

## 4.3 Saldo negativo

Los egresos por Banco Nación deben dejar ese tipo de caja **en negativo**, para
verificar que `permite_descubierto = 1` lo tolera y que la pantalla lo muestra
correctamente.

---

# 5. Liquidaciones

| # | Profesor | Período | Tipo | estado | estado_pago |
|---|---|---|---|---|---|
| L1 | PR1 | M2 | HORA | CERRADA | **PAGADA** |
| L2 | PR1 | M1 | HORA | CERRADA | PENDIENTE |
| L3 | PR2 | M1 | COMISION | ABIERTA | PENDIENTE |

`L1` pagada existe para probar que **no se puede cambiar el profesor** de una
clase ya liquidada y pagada. Debe tener cargados `pagada_por_admin_id`,
`pagada_fecha`, `pagada_tipo_caja_id` y `pagada_subrubro_id`.

`L2` cerrada sin pagar prueba el flujo de pago de liquidación.

`L3` abierta prueba el cálculo por comisión sobre lo cobrado de los alumnos de G3.

El `total_calculado` de cada una tiene que **coincidir con las clases y
asistencias realmente cargadas**. Si no cierra, el dataset está mal.

---

# 6. Configuraciones

| Clave | Valor |
|---|---|
| `dias_gracia_cobranza` | 10 |

Cualquier otra configuración que el sistema lea debe quedar con su valor por
defecto documentado, no ausente.

---

# 7. Verificación obligatoria al terminar

El seeder debe imprimir y verificar:

1. Conteo por tabla.
2. Estado de cobranza calculado de **cada uno de los 15 alumnos**, comparado
   contra la columna "Estado esperado" de este documento. Si alguno no coincide,
   fallar con el detalle.
3. `deuda_cuotas` cuyo `monto_pagado` no coincida con la suma de sus
   imputaciones: debe dar **cero filas**.
4. `cashflow_movimientos` con `referencia_tipo = 'CAJA_OPERATIVA'` cuyo
   `referencia_id` no exista en `cajas_operativas`: debe dar **cero filas**.
5. Saldo por tipo de caja, con Banco Nación en negativo.
6. Cantidad de clases sin lista cargada, que debe coincidir con el badge
   "Sin asistencia".

Si cualquiera de estas verificaciones falla, el seeder está mal y no se entrega.
