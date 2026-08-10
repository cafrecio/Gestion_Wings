# Resultado de prueba funcional cerrada - 260809

## Resumen ejecutivo

Prueba ejecutada el **9 de agosto de 2026** sobre la aplicación local `http://gestion-wings`, usando recorridos web reales y peticiones HTTP autenticadas contra los mismos controladores web. No se llamaron services directamente y **no se corrigió ningún fallo durante la prueba**.

| Caso | Resultado | Síntesis |
|---|---|---|
| 1. Cobro de cuota | **PASA** | El cobro impactó correctamente en deuda, pago, imputación, caja, pantallas y recibo; no llegó prematuramente a cashflow. |
| 2. Validar caja | **FALLA** | La caja quedó VALIDADA, pero el ingreso no se creó en cashflow ni aumentó el saldo de Efectivo. |
| 3. Cancelar cobro | **PASA** | La deuda se revirtió, el pago y movimiento se anularon, la imputación desapareció y el recibo quedó marcado. |
| 4. Tomar asistencia | **FALLA** | Base, contador de presentes, estado y permisos pasaron; el badge “Sin asistencia” permaneció en 156. |
| 5. Solapamiento | **PASA** | Se rechazó con el nombre del profesor y no se creó una segunda clase. |

Resultado global: **3 casos pasan y 2 fallan**.

## Método y datos utilizados

- Roles: operativo, administrador y profesor de las credenciales provistas en `docs/06-pruebas/Credenciales Wings 9-8-26.txt`.
- Cobro exitoso: Candela Delgado, deuda `#105`, período 2026-05, **$28.000 en Efectivo**.
- Cobro cancelado: Ámbar Gutiérrez, deuda `#110`, período 2026-05, **$33.000 en Efectivo**.
- Caja del cobro exitoso: `#83`.
- Caja del cobro cancelado: `#84`.
- Clase temporal: `#679`, Patín - Principiantes, 09/08/2026 de 19:00 a 20:00, profesor Jorge Mitre, ocho alumnos.

Los identificadores se incluyen solo para correlacionar la evidencia. Todos los registros temporales fueron eliminados al terminar.

## Caso 1 - Cobro de cuota como operativo

**Resultado: PASA**

### Acción real

Se ingresó como operativo, se seleccionó a Candela Delgado desde `/caja/cobrar`, se marcó su única cuota pendiente y se cobró el total de $28.000 en Efectivo.

### Resultado real en base

- `deuda_cuotas #105`: `monto_pagado` pasó de `0.00` a `28000.00` y `estado` a `PAGADA`.
- `pagos #149`: creado con `estado = COMPLETADO` y `monto_final = 28000.00`.
- `pago_deuda_cuota #149`: creado con `monto_aplicado = 28000.00`.
- `movimientos_operativos #150`: creado en caja `#83`, Efectivo, $28.000, `estado = ACTIVO`.
- `cashflow_movimientos`: no se creó ninguna fila nueva antes de validar.

### Resultado real en pantallas

- `/caja/83/detalle`: mostró a Candela Delgado, período 05/26 y $28.000.
- `/movimientos`: mostró el ingreso como primera fila.
- `/caja/historial`: mostró “Cuota Mensual - Delgado, Candela - Efectivo - +$28.000”.
- `/alumnos/23`: el estado de cobranza cambió a **Al día** y el pago apareció en el historial.
- Recibo `CUOTA-149`: descargable, visualmente correcto, con período Mayo 2026, total $28.000 y medio Efectivo.
- Dashboard de admin: no incorporó el ingreso antes de validar. Además, la base confirmó que cashflow seguía sin filas nuevas.

## Caso 2 - Cerrar y validar la caja

**Resultado: FALLA**

### Acción real

La caja `#83` se cerró como operativo y se validó como administrador. La petición de validación se repitió inmediatamente una segunda vez para comprobar idempotencia.

### Resultado real en base

- La caja quedó `VALIDADA`.
- `usuario_admin_validacion_id = 1`.
- `validada_at = 2026-08-09 20:25:23` durante la prueba.
- La segunda validación no duplicó filas.
- **No se creó ninguna fila nueva en `cashflow_movimientos`.**
- El saldo acumulado de Efectivo permaneció en **$4.230.000**, exactamente igual al valor inicial; debía subir a $4.258.000.

### Resultado real en pantallas

- La caja apareció como VALIDADA.
- `/cashflow` no mostró el ingreso del 09/08/2026 por $28.000.
- El medio Efectivo no aumentó por el monto cobrado.

### Causa localizada

La falla se produce en `app/Services/CashflowIntegracionCajaService.php`, líneas **31 a 38**.

La idempotencia consulta `referencia_tipo = CAJA_OPERATIVA` y `referencia_id = caja_id`. Para la caja nueva `#83` ya existía una fila histórica con `referencia_id = 83`, pero esa fila representa el antiguo criterio “Integración caja - mov #83”, no la caja `#83`. El servicio interpretó esa colisión histórica como si la caja nueva ya estuviera reflejada y salió sin crear el ingreso.

No se modificó el código ni la información histórica.

## Caso 3 - Cancelar un cobro

**Resultado: PASA**

### Acción real

Se cobró $33.000 en Efectivo a Ámbar Gutiérrez y, antes de cerrar la caja `#84`, se canceló el cobro con el motivo “Prueba funcional cerrada 260809”. Luego se cerró y validó la caja.

### Resultado real en base

- `deuda_cuotas #110`: volvió de PAGADA a `PENDIENTE`, con `monto_pagado = 0.00`.
- `pagos #150`: quedó `ANULADO`.
- `movimientos_operativos #151`: quedó `CANCELADO` y conservó el motivo.
- `pago_deuda_cuota`: quedaron **cero filas** para el pago `#150`.
- Después de cerrar y validar la caja no se creó ningún asiento nuevo en cashflow.

### Resultado real en pantallas

- `/caja/84/detalle`: mostró el movimiento como Cancelado.
- El resumen de la caja mostró Ingresos $0, Egresos $0 y Neto $0.
- El recibo `CUOTA-150` siguió descargándose y mostró el sello rojo **“RECIBO ANULADO - el pago fue cancelado”** y el total $33.000.

Observación no bloqueante: al eliminarse correctamente la imputación, el recibo anulado conserva el total pero ya no muestra un período en la tabla de detalle.

## Caso 4 - Tomar asistencia como profesor

**Resultado: FALLA**

### Preparación necesaria

La base no tenía ninguna clase para el 09/08/2026. Se creó por la pantalla administrativa una clase temporal finalizada, con Jorge Mitre y ocho alumnos del grupo Patín - Principiantes. El badge “Sin asistencia” mostraba **156** antes de cargar la lista.

### Acción real

Se ingresó como profesor, se abrió la clase, se marcaron presentes cuatro de ocho alumnos y se guardó.

### Resultado real en base

- Se crearon ocho filas en `asistencias`, una por alumno.
- Cuatro filas quedaron con `presente = 1` y cuatro con `presente = 0`.
- Las ocho filas quedaron con `motivo_correccion = NULL`.

### Resultado real en pantallas

- El detalle mostró **8 alumnos y 4 presentes**.
- El listado cambió la clase de **Finalizada / Pendiente** a **Cerrada / Cargada**.
- El profesor solo vio “Ver”; no tuvo opciones de Editar, Cancelar ni cambiar profesores.
- **El badge “Sin asistencia” permaneció en 156. No bajó en 1.**

### Causa localizada

`app/Providers/AppServiceProvider.php`, línea **32**, cuenta únicamente clases con `fecha < today()`. La clase de hoy no entra en el badge aunque ya haya finalizado, por eso cargar su asistencia no puede reducir el número.

El caso se marca FALLA porque el criterio de aceptación exige que base y todas las pantallas coincidan.

## Caso 5 - Solapamiento de horarios

**Resultado: PASA**

### Acción real

Como administrador se intentó crear otra clase el 09/08/2026 de 19:00 a 20:00, para Patín - Intermedias, asignando nuevamente a Jorge Mitre.

### Resultado real

- La pantalla rechazó la operación con el mensaje: **“El profesor Jorge Mitre ya tiene asignada otra clase que se solapa en fecha 2026-08-09 entre 19:00 y 20:00.”**
- El conteo de clases de ese día era 1 antes y continuó siendo 1 después.
- El ID máximo continuó siendo `679`; no se creó una clase adicional.

## Restauración del sistema

La restauración se verificó después de todos los casos:

- Las deudas `#105` y `#110` volvieron a `PENDIENTE`, con `monto_pagado = 0`, observaciones nulas y fechas originales.
- La caja preexistente `#80` volvió a `ABIERTA`, sin fecha de cierre ni validación y con sus valores originales.
- Los pagos `#149` y `#150`, cajas `#83` y `#84`, movimientos `#150` y `#151`, clase `#679`, pivote de profesor y ocho asistencias temporales ya no existen.
- No quedó ninguna fila de cashflow creada por la prueba.
- Los totales de cashflow volvieron a coincidir con el inicio:
  - Efectivo: $4.230.000, 145 movimientos.
  - Banco Nación: -$110.000, 5 movimientos.
  - Mercado Pago: $14.000, 2 movimientos.
  - Banco Galicia: -$600.000, 5 movimientos.
- No quedaron clases del 09/08/2026.
- Se eliminaron los dos PDF generados, las imágenes temporales, las sesiones de prueba y las pestañas del navegador.
- `database/dump.sql` no fue modificado.
- Los cambios preexistentes `.claude/settings.local.json` y `docs/06-pruebas/Credenciales Wings 9-8-26.txt` no fueron modificados ni incorporados al trabajo.

## Qué quedó sin probar y por qué

- No se simuló una validación verdaderamente simultánea desde dos conexiones; se usó la alternativa autorizada de repetir la validación inmediatamente. La repetición no duplicó filas.
- No se comprobó el incremento visual de Efectivo en el caso 2 porque el asiento de cashflow nunca se creó; esto forma parte de la misma falla, no de una omisión de prueba.
- No se probó una clase de hoy que ya existiera al comenzar porque no había ninguna; se creó una clase temporal por la interfaz y se eliminó al finalizar.
- No se hicieron pruebas fuera de los cinco casos pedidos.

## Próximo paso recomendado

Corregir por separado las dos fallas, sin mezclar cambios:

1. Normalizar la referencia histórica de integración de caja o cambiar la clave de idempotencia para que una caja nueva no colisione con referencias antiguas a movimientos.
2. Definir si el badge debe incluir clases de hoy cuya hora de fin ya pasó; el caso pedido requiere que sí, pero el código actual excluye todo el día corriente.

Después de cada corrección, repetir únicamente el caso correspondiente y luego ejecutar nuevamente esta prueba cerrada completa.
