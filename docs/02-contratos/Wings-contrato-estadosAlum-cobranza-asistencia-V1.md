# Wings-Contrato-EstadosAlum-Cobranza-Asistencia-V1.md

**Caso de uso (Index):** 10) Motor de estados + Cobranza + Control de asistencia/plan
**Versión:** V1
**Estado:** CERRADO como acuerdo de negocio · **NO implementado en su mayor parte** (ver §11)
**Fecha:** 2026-08-09
**Origen:** definido con el usuario en conversación, a partir del hallazgo DOC-01 de la auditoría v02/v03: esta regla gobierna el módulo de Cobranza y el color de estado de cada alumno, pero **no estaba escrita en ningún lado**. Por eso quedó implementada tres veces con criterios divergentes.

> ⚠️ **Advertencia de lectura.** Este documento describe **lo que el negocio necesita**, no lo que el sistema hace hoy. La sección §11 dice, punto por punto, qué está implementado y qué no. No asumir que algo funciona porque está acá.

---

## Inactivos: fecha, cantidades y deuda separada — T70/T71, Carlos 28/10/2026

- **Inactivo y baja no son lo mismo.** Baja es eliminar un alumno sin movimientos (T65).
  El pase a Inactivo conserva el interruptor instantáneo, sin motivo ni aviso (T84/T85).
- Cada cambio de estado conserva su fecha en un historial. `inactivo_desde` representa
  el tramo actual y se limpia al reactivar; los tramos anteriores se conservan.
  Los inactivos anteriores a esta migración quedan sin fecha; no se les inventa un mes.
- Reporte de Alumnos: altas por `fecha_alta` y cantidad de registros distintos que
  pasaron a Inactivo en el mes elegido. Una reactivación no es un alta nueva;
  varios pases del mismo registro en un mes cuentan una vez. Volver a activo no borra
  aquel pase. Dos deportes son dos registros, igual que la matrícula del reporte.
  Los indicadores muestran cantidades, sin listas de nombres.
- **Deuda de alumnos inactivos:** total y lista actuales en Cobranza y Reportes,
  con nombre, saldo y fecha de inactividad (o «Sin fecha registrada»). Se rotula
  con la fecha actual incluso si Reportes tiene seleccionado un mes anterior.
  No suma al por cobrar, a Inicio ni a ningún otro total existente. Incluye cuotas
  de períodos ya empezados, inscripción y punitorios vigentes, por su saldo neto.
  La inscripción compartida por DNI se cuenta una sola vez; si corresponde a un
  registro activo, sigue en su deuda y no se duplica en la de inactivos.
- Solo ADMIN activo puede **dar por perdida** la deuda de un inactivo desde esa lista.
  Quedan autor, fecha, importe y partidas en un registro permanente y visibles en
  su ficha. Se rechazan alumnos activos y montos desactualizados; repetir no duplica.
  La operación es atómica: sin auditoría guardada tampoco hay pérdida aplicada.
- **Perder no es condonar ni cobrar:** `monto_perdido` está separado de cualquier
  condonación. No crea pago, recibo, ingreso, egreso ni movimiento de caja. Conserva
  los importes originales, los pagos anteriores y los devengados analíticos.
  La pérdida reduce únicamente el saldo cobrable y sale de la lista al agotarlo.
- Si vuelve, **lo perdido no se repone automáticamente**. T38 conserva su cuota de
  reingreso cuando corresponde, sin duplicar un período existente; la deuda que no
  se había perdido vuelve al circuito habitual. Los períodos futuros aún no iniciados
  quedan fuera de esta decisión. La auditoría impide eliminar el alumno que la tiene.

Implementación local de Codex CyE; requiere verificación independiente de Claude y
revisión visual de Carlos. Sin despliegue con esta entrega.

## 1. Modelo de pago

El servicio se paga **por adelantado**, entendido así:

- La cuota de un mes se paga **durante ese mes, a partir del día 1**.
- La cuota de agosto se paga en agosto. La de septiembre se paga a partir del 1 de septiembre.
- **No** se paga un mes antes de que empiece.

**Días de gracia:** existe un margen configurable desde el principio del mes. La gente no cobra su sueldo el día 1, y se trata con clientes, no con morosos presuntos.

- El valor es **configuración del sistema**, no una constante en el código.
- Valor actual en configuración: 10 días.

**Pago de varios meses juntos:** fuera de alcance. Si alguna vez ocurre, se registran cobros separados, uno por mes. No se construye funcionalidad para esto.

---

## 2. Generación de la deuda mensual

### Cuándo corre

**El día 1 de cada mes.** Genera la deuda **del mes que empieza**, mirando la actividad **del mes que terminó**.

Ejemplo: el 1 de septiembre se genera la cuota de **septiembre**, evaluando las asistencias de **agosto**.

### Si el día 1 no corre — decisión de Carlos (DU-52, 13/10/2026; T40)

El proceso se programa **todos los días a las 6:00**, pero solo actúa cuando hace falta:

- **El día 1** genera, si ese mes no figura generado.
- **Del día 2 en adelante** genera solo si el mes en curso no figura generado **y el mes anterior sí**. Eso prueba que el sistema venía funcionando y que lo que faltó es la corrida del día 1. En un servidor o una base nuevos, a mitad de mes, no genera nada a destiempo.
- Repetir no duplica: cada alumno con la cuota del mes se saltea. Una corrida cortada a mitad se completa al día siguiente.
- El mes queda anotado como generado (tabla `generaciones_cuotas`) solo cuando la corrida termina, sea la programada o la manual de un técnico.
- **Aviso al dueño** (Telegram y correo): si se generó después del día 1 («las cuotas del mes se generaron con atraso») y si un intento falló («no se pudieron generar las cuotas del mes»). Mientras falte, el resumen diario de las 8:00 muestra «Cuotas del mes sin generar».
- Al instalar este cambio, el mes en curso se anota como ya atendido.

### A quién se le genera

El universo son los **alumnos activos**. Dentro de ese universo, se genera deuda automática si cumple **alguna** de estas dos:

1. **Tuvo al menos una asistencia el mes anterior.** Vino, sigue siendo alumno, se le cobra.
2. **Se dio de alta y pagó dentro de los últimos 15 días.** Es el alumno recién incorporado que todavía no tuvo clases: nadie se anota y paga para no venir.

### La primera cuota nace en el alta — enmienda del 23/09/2026

Decisión de Carlos. **Al dar de alta un alumno con ingreso del mes corriente o futuro
se crea la deuda de la cuota del mes de su fecha real de ingreso**, en la misma operación
que lo crea, además de la inscripción cuando corresponde.

**Enmienda A43 del 04/10/2026, posterior a P0:** si ingresó en un mes ya cerrado,
el alta muestra antes de guardar una decisión obligatoria, sin opción preseleccionada:

- **Sí:** genera solo la cuota del mes corriente, completa, al 100% del plan elegido.
- **No:** no genera cuota. **La inscripción mantiene su regla**, aclarado por Carlos;
  No no evita el cargo de inscripción cuando corresponde por DNI.
- Nunca se crea la cuota de un mes cerrado. No se cambia la fecha real de ingreso.
- La decisión, usuario, fecha, período e importe quedan registrados con el alta,
  en la misma transacción. Un reintento conserva esa decisión y no duplica datos.
- Ingresos del mes corriente o futuro no muestran pregunta y conservan el porcentaje
  del día. No se recalculan alumnos ni deudas ya existentes.

- El importe es el del plan elegido, con el **porcentaje del primer cobro según el día de
  ingreso**, y queda **congelado en el alta**: el cobro posterior no lo vuelve a calcular.
- La generación mensual del día 1 **no vuelve a crear ese período**: ya existe.
- Un alumno que se anota y no aparece nunca **queda debiendo y se ve en la cobranza**. Antes
  era invisible para el club.
- Los alumnos ya existentes no se tocan: esto rige para las altas nuevas.

Con esto, el alumno nuevo entra al circuito normal de cobranza desde el primer día, y por
eso la enmienda de §3 puede sacar la regla de "nunca pagó".

**Implementación local del 23/09, pendiente de verificación independiente:** el alta web
crea alumno, plan, inscripción y cuota en una transacción. P0 del 04/10 corrige la
vigencia del plan a la fecha real de ingreso; A43 incorpora la decisión de cuota
corriente para ingresos en meses cerrados. Entrega pendiente de Gemini. La deuda
conserva el importe y `porcentaje_alta`; el cobro y su vista previa no vuelven a calcular
esa primera cuota. No se generan deudas retroactivas para registros existentes.
Las deudas anteriores, sin esa marca, conservan el tratamiento previo de §4, y el
reingreso posterior conserva su regla. Una modificación explícita de plan sigue regida
por las reglas de cambio de plan de esta sección.

### Si no cumple ninguna: no se genera deuda, se pregunta

**No se asume nada.** El alumno pasa a una **cola de revisión**, con la pregunta: *¿sigue siendo alumno?*

| Respuesta | Qué pasa |
|---|---|
| **Sigue activo** | Se le genera la deuda del período. |
| **Ya no viene** | No se genera deuda. |

### La cola se cierra sola

Si antes de que alguien responda **el alumno registra una asistencia o un pago**, la pregunta se responde sola: está activo. Se le genera la deuda y sale de la cola.

Es la respuesta más confiable de todas, porque es un hecho y no una opinión.

### Cómo se pide la confirmación

**A cualquier operativo o administrador que se conecte**, y se insiste hasta que todos los alumnos pendientes estén respondidos.

No es una pantalla a la que haya que ir a buscar: el sistema la pone adelante. Mientras queden alumnos sin responder, la facturación de ese mes está incompleta, y eso tiene que ser visible.

### Por qué existe esta cola

Es el único mecanismo que detecta al alumno que dejó de venir sin avisar. Sin él, o se le factura a alguien que ya no es cliente, o se deja de facturar a alguien que sí lo es. Las dos cosas se descubren tarde y mal.

> **Nota:** hasta ahora el sistema tenía un tercer criterio de generación automática —*haber pagado el mes anterior*— que se **elimina**. Un alumno que paga pero deja de venir es exactamente el caso que la cola de revisión existe para detectar; darle deuda automática lo volvía invisible.

### El monto sale del plan activo

La deuda se genera **según el plan activo del alumno** al momento de generarla.

**Si el alumno cambia de plan para el mes en curso, la deuda de ese mes se actualiza** al precio del plan nuevo. Rige hacia adelante: las deudas de meses anteriores **no se tocan nunca**.

### Cuándo aplica el cambio de plan

| Cambio | Condición | Desde cuándo aplica |
|---|---|---|
| **Bajar** | Ya asistió a alguna clase este mes | **Mes siguiente.** El corriente se cobra al plan viejo |
| **Bajar** | No tiene ninguna asistencia este mes | **Mes en curso.** Se actualiza la deuda |
| **Subir** | Siempre | **Mes en curso**, cuando quiera |

**La pregunta es una sola: ¿asistió a alguna clase este mes?** No hay que contar clases ni compararlas contra el cupo del plan — alcanza con saber si asistió o no.

**Por qué:** si ya tomó clases bajo el plan alto, ya recibió ese servicio. Bajarle la cuota retroactivamente sería cobrarle menos por algo que ya consumió. Si todavía no vino, no consumió nada y no hay motivo para hacerlo esperar.

**Subir no tiene restricción** porque va a favor del negocio y del alumno: pasa a tener derecho a más clases y la cuota acompaña.

> ✅ **Confirmada por el cliente el 28/08/2026.** Vanina dio el visto bueno a la
> regla tal como está escrita arriba. Deja de estar sujeta a revisión.

---

## 3. Estados de cobranza

### Para qué existen estos estados

**Categorizar a alguien como MOROSO o DEUDOR sirve para activar la cobranza y el seguimiento del pago.** No es una etiqueta descriptiva ni un juicio sobre el cliente: es el disparador de una gestión.

De ahí se desprende todo lo demás. La gente paga cuando cobra, y eso no suele caer el día 1 del mes. Marcar como moroso a alguien el día 2 no tendría ningún sentido: no hay nada que gestionar, todavía no se atrasó nadie. Por eso existen los días de gracia — no son una concesión, son el reconocimiento de cómo cobra la gente.

**Regla para resolver dudas futuras:** ante cualquier caso ambiguo, preguntarse *"¿corresponde salir a reclamar este pago?"*. Si la respuesta es no, el alumno está AL DÍA.

### Los cuatro estados

| Estado | Definición | ¿Hay que reclamar? |
|---|---|---|
| **AL DÍA** | No tiene cuotas pendientes del mes corriente ni de meses anteriores, aunque no tenga pagos registrados en Wings. | No |
| **EN PLAZO** | No debe meses anteriores, debe el corriente, y **todavía está dentro de los días de gracia**. | No, todavía no |
| **MOROSO** | Pasó el día de gracia configurado y debe el **mes corriente**. | Sí |
| **DEUDOR** | Arrastra un mes **ya cerrado** sin pagar. | Sí, con más urgencia |

La escala parte al medio: los dos primeros son *"no hay nada que hacer"*, los dos últimos son *"hay que salir a cobrar"*. Ese corte es el que usa quien trabaja la cobranza.

### Orden de evaluación (importa)

Los estados se evalúan **en este orden**, y el primero que da verdadero gana:

1. **¿Arrastra algún mes cerrado sin pagar?** → DEUDOR
2. **¿Debe el mes corriente y ya pasó el día de gracia?** → MOROSO
3. **¿Debe el mes corriente pero sigue dentro de la gracia?** → EN PLAZO
4. Si no → AL DÍA

**DEUDOR gana sobre todo lo demás.** Alguien que pagó agosto pero debe julio, el 5 de agosto es DEUDOR — no EN PLAZO — porque arrastra un mes cerrado. Sin este orden explícito, es un error casi garantizado al implementar.

### Ejemplos concretos (con gracia configurada en 10 días)

| Fecha | Situación del alumno | Estado |
|---|---|---|
| 5 de agosto | Pagó julio y agosto | **AL DÍA** |
| 5 de agosto | Pagó julio, no pagó agosto | **EN PLAZO** |
| 15 de agosto | Pagó julio, no pagó agosto | **MOROSO** |
| 5 de agosto | No pagó julio | **DEUDOR** (arrastra mes cerrado, sin importar agosto) |
| 1 de septiembre | Pagó agosto, no pagó septiembre | **EN PLAZO** |
| 1 de septiembre | No pagó agosto | **DEUDOR** |
| 15 de septiembre | Pagó agosto, no pagó septiembre | **MOROSO** |
| 15 de septiembre | Debe agosto y septiembre | **DEUDOR** |
| Cualquier fecha | Alumno nuevo que nunca pagó, con su primera cuota vencida y el mes cerrado | **DEUDOR** |
| Cualquier fecha | Alumno que no debe nada, haya pagado por Wings o no | **AL DÍA** |

### Enmienda del 23/09/2026 — deudor es el que debe, no el que nunca pagó

Decisión de Carlos. **Se elimina "o nunca pagó ninguna cuota" como criterio de DEUDOR.** El
estado se calcula sobre lo que el alumno **debe**, no sobre si alguna vez hubo un pago suyo
registrado en Wings.

**Por qué cambia.** La regla original apuntaba al alumno nuevo que se anota y no paga. Pero
marcaba deudor a cualquiera sin pagos, aunque no debiera un peso, y eso se vio el 23/09 en
el sitio de prueba: con el padrón recién declarado, Cobranza mostró **60 deudores** sobre 60
alumnos mientras el tablero mostraba 20 con deuda. Los 40 que arrancaron al día nunca
pagaron **por Wings** —su mes de corte se declaró cerrado en cero, sin inventar un cobro— y
el sistema los trataba como los peores morosos del club. Diagnóstico completo en
`docs/06-pruebas/PRU-02/DIAGNOSTICO-A2.md`.

**Lo que reemplaza a esa regla:** al inscribirse un alumno **se le crea la cuota del mes**,
ver §2. Desde entonces, el alumno nuevo que no paga **debe algo**, y el cálculo normal lo
clasifica solo: EN PLAZO dentro de la gracia, MOROSO pasada la gracia y DEUDOR cuando ese
mes se cierra. No hace falta ninguna regla especial para él.

**Lo que no cambia:** un mes cerrado sin pagar sigue siendo DEUDOR, y DEUDOR sigue ganando
sobre todos los demás estados.

**El paso de MOROSO a DEUDOR es automático:** un moroso de agosto se convierte en deudor el 1 de septiembre, sin que nadie haga nada, porque agosto pasa a ser un mes cerrado.

### Enmienda del 12/10/2026 — el que debe la inscripción también es moroso o deudor (T31)

Decisión de Carlos (12/10/2026):
«Todo el que debe plata es deudor o moroso, según hace cuánto lo debe.»

La inscripción impaga cuenta desde la fecha de ingreso del alumno (`fecha_alta` del registro al que se le imputa):
- **EN PLAZO:** dentro de los días de gracia del mes de ingreso;
- **MOROSO:** pasados esos días, mientras ese mes siga en curso;
- **DEUDOR:** cuando ese mes ya cerró.

Reemplaza la regla anterior, donde el estado salía solo de las cuotas. Deber inscripción sin deber cuota solo puede venir de la primera carga; se acepta suponiendo que esa cuota se pagó afuera.

Reglas operativas de la enmienda:
1. **El estado del alumno es el peor** entre el de sus cuotas y el de su inscripción: `DEUDOR > MOROSO > EN_PLAZO > AL_DIA`.
2. **Días de gracia compartidos:** la inscripción usa la misma configuración `dias_gracia_cobranza` que las cuotas.
3. **Pagos y condonaciones:** inscripción pagada entera o condonada no cambia el estado. Pagada en parte sigue contando por lo que falta.
4. **Persona en dos deportes:** la inscripción afecta a un solo registro, el mismo que hoy carga ese saldo (`inscripcionPendientePorAlumno`). El otro no se vuelve deudor por la inscripción.
5. **Conciliación de listados:** Cobranza y el buscador de Cobrar listan exactamente a los mismos alumnos con deuda.
6. **Antigüedad en Cobranza:** quien debe solo la inscripción se ordena por la antigüedad de esa deuda (`fecha_alta` en formato `Y-m`), no al final con `9999-99`.

### Enmienda del 13/10/2026 — plazo propio del alumno nuevo (`dias_plazo_alta`) (T31 Agregado)

Decisión de Carlos (13/10/2026):
«El alumno nuevo no arranca moroso nunca: tiene sus propios días de gracia desde el alta para pagar lo que debe (inscripción y cuota del mes de ingreso). No corre con el calendario general hasta el mes siguiente.»

Cómo funciona:
1. **Deudas nacidas con el alta:** para la inscripción y la cuota del mes de ingreso, corre un plazo en días corridos desde la fecha de ingreso (`fecha_alta`):
   - dentro de ese plazo: **EN PLAZO**;
   - si el vencimiento general del mes (`dias_gracia_cobranza`) cae después del plazo propio, rige el más favorable al alumno (el más largo);
   - vencido ese plazo:
     - si el mes de ingreso sigue en curso: **MOROSO**;
     - si el mes de ingreso ya cerró: **DEUDOR** (aplica la regla del 12/10).
2. **Plazo que cruza de mes:**
   - Ejemplo: alta el 28 de septiembre, 7 días de plazo propio.
   - 28/09 al 04/10: **EN PLAZO** (el plazo se respeta aunque el mes haya cerrado).
   - A partir del 05/10: **DEUDOR** (el mes de septiembre ya cerró y el plazo venció).
   - Nunca queda moroso en octubre por una deuda de septiembre: una vez vencido el plazo, la deuda de un mes cerrado es DEUDOR.
3. **Cuotas de meses posteriores al de ingreso:**
   - Rigen por el calendario general (`dias_gracia_cobranza` del mes en curso). El plazo propio solo aplica a la cuota del mes de ingreso y a la inscripción, y desde el 17/10/2026 también a la cuota que se genera al reactivar (ver la enmienda siguiente).
4. **Configuración:**
   - Clave nueva: `dias_plazo_alta` (entero, días corridos, valor inicial 7).
   - Tipada a int en el servicio, falla con `\LogicException` si no está o es `<= 0`.
   - `dias_gracia_restantes`: cuando está EN PLAZO por plazo propio, devuelve los días que le quedan de ese plazo (o del general, el que venza más tarde).

> El día de gracia y el plazo de alta son **configuraciones**, nunca números fijos en el código. Los ejemplos usan 10 y 7 porque son los valores por defecto; si mañana cambian, los estados acompañan solos.

### Enmienda del 17/10/2026 — reactivar genera la cuota del mes (T38)

Decisiones de Carlos: DU-12 (13/10) «Generarla al reactivar, con el porcentaje de reingreso
del día. Siempre que no tenga ya generada la cuota del mes. La deuda que pudiese tener se
mantiene.» y, el 17/10, que esa cuota tenga **el mismo plazo que una cuota de alta**.

Antes, un alumno reactivado figuraba Al día y no aparecía en Cobranza hasta que alguien le
cobraba.

1. **Al reactivar** (botón Reactivar de la ficha o interruptor del listado), en la misma
   operación:
   - si no tiene cuota del mes en curso, se crea con el precio de su plan por el porcentaje de
     la regla de primer pago del día (si hay cero o más de una regla para ese día, 100 %);
   - la cuota guarda el porcentaje (`porcentaje_alta`) y el día (`fecha_reingreso`): el cobro
     no la vuelve a calcular;
   - si ya tenía la cuota del mes, no se crea otra ni se toca;
   - lo que debía de meses anteriores queda como estaba;
   - una revisión pendiente del mes se da por respondida («sigue»), como con un pago;
   - sin plan para el mes, **no se reactiva** y se avisa que hay que asignarle uno.
2. **Plazo:** el de una cuota de alta, contado desde el día del reingreso: el más largo entre
   `dias_plazo_alta` y la gracia general de ese mes. Dentro del plazo, EN PLAZO y sin recargo
   por mora; vencido, MOROSO si el mes sigue y DEUDOR si ya cerró. Las demás cuotas del alumno
   siguen con su vencimiento.
3. **Cobrarle a un inactivo** también lo reactiva (ya era así). Si ese cobro no incluyó la
   cuota del mes, se genera igual que en el punto 1. Si la creó ese mismo cobro (con el
   descuento de §4 para quien ya pagó alguna vez), queda marcada con el día del reingreso para
   que el saldo tenga el mismo plazo. Si no hay plan para el mes, el cobro de la deuda vieja se
   hace igual y no se genera la cuota.

Implementado el 17/10/2026 por Claude: `PagoCuotaService::crearCuotaReingreso` y
`completarReingresoPorCobro`, `AlumnoWebController::toggleActivo`,
`CobranzaEstadoService::limiteDeLaCuota` (estados y recargo por mora) y la columna
`deuda_cuotas.fecha_reingreso`. Cubierto por `tests/Feature/ReingresoCuotaT38Test.php`.
Verificado por Gemini el 18/10/2026 ([VERIFICACION-T38-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T38-GEMINI.md)) y cerrado.

### Enmienda del 28/10/2026 — cobrarle a un inactivo pregunta si vuelve (T25)

Decisión de Carlos (11/10, precisada el 28/10). Reemplaza «también lo reactiva» del punto 3:

- Si el cobro lo reactivaría (incluye cuotas y hay plan para el mes), Cobrar pregunta
  «¿Pasa a activo?» con **Sí**, **No** y **Cancelar**, en un panel de la misma página.
- **Sí:** lo de siempre (punto 3).
- **No:** cobra lo elegido y el alumno sigue inactivo; no se genera la cuota del mes ni corre
  el descuento de reingreso.
- Si el cobro incluye el mes en curso o uno futuro, **no se ofrece No**: pagar el mes es
  volver. El servidor rechaza un «No» forzado con el mes.
- Sin plan para el mes no se pregunta: el cobro no lo reactivaría.

Implementado por Claude: `CajaWebController::pagar`, `PagoCuotaService::exigirActivoParaCobrarElMes`,
panel `modal-reactivar` en `caja/cobrar` y `cobrar.js`. Pruebas: `CobroInactivoT25Test`.

### Enmienda del 20/10/2026 — fecha de ingreso futura solo hasta el fin del mes siguiente (T50)

Decisión de Carlos: DU-06 («Aceptar solo hasta el mes siguiente»). Caso: ALU-007.

La fecha de ingreso (`fecha_alta`) puede ser pasada, de hoy o futura solo hasta el **último día del mes siguiente a hoy** (reloj de Wings / `Carbon::now()`, respetando el reloj simulado).

1. **Alta individual y edición:**
   - `AlumnoWebController::store` rechaza fechas que superen el límite con mensaje en castellano: *«La fecha de ingreso no puede ser posterior al dd/mm/aaaa.»* y no persiste alumno ni deudas.
   - `AlumnoWebController::update` aplica el límite únicamente si se está modificando `fecha_alta`. Si se editan otros datos de un alumno con fecha histórica ya guardada, la edición se procesa sin bloquear.
2. **Previsualizaciones:**
   - La previsualización de cuota de alta (`cuotaAltaPreview`, `PagoCuotaService::previsualizarCuotaAlta`) y de inscripción (`inscripcionPreview`) validan la fecha y rechazan fechas fuera del límite (HTTP 422).
3. **Primera carga por Excel:**
   - `PrimeraCargaExcelService::revisar` verifica la fecha en la columna 5 (E) de la hoja `Alumnos`. Las filas que superen el límite se marcan con error explícito y mensaje descriptivo, impidiendo continuar con la carga.
4. **Interfaz y cliente:**
   - El input Blade entrega `data-max` desde `FechaIngresoRegla` y la fecha por defecto desde `now()` de Wings. `alumnos-form.js` usa ese límite para la validación nativa y `alumnos-inscripcion.js` conserva el mensaje específico después de la previsualización. Una fecha histórica original fuera de rango se conserva al editar otros datos; cambiarla exige volver a cumplir el límite. Sin JavaScript incrustado.

Aclaración de Carlos del 21/10/2026: **«Inscripción a partir de hoy»**. Si se da de alta hoy a alguien que ingresará el mes siguiente, la inscripción pendiente se debe desde el alta registral y puede figurar en Cobranza, Cobrar e Inicio; la cuota de un mes que aún no empezó no suma como deuda. No se cambia aquí el cálculo de plazos ni de mora.

Antecedente: la primera entrega de Gemini fue devuelta por fecha mal formada en edición y reloj del navegador. Segunda vuelta `de35ae0` **verificada por Codex CAB el 21/10**: edición inválida responde 422 en castellano sin cambios; límite/default y mensaje asíncrono coinciden con Wings. H1 quedó resuelto por la decisión anterior. Verificación final Codex CyE 21/10 sobre `738de598`: idioma y guardia del auxiliar aprobados, incluida la base efectiva resuelta desde DB_URL antes de migrar/sembrar. Tres rechazos originales comprobados sin escrituras; 13/71 permanentes y 17/214 controles propios verdes, rápida 908 aprobadas/6 omitidas. **T50 CERRADA**, implementó Gemini y verificó Codex. [Informe final](../06-pruebas/PRU-04/VERIFICACION-T50-FINAL-CODEX.md).

### Enmienda del 20/10/2026 — reglas de primer pago sin superposiciones ni días sin cubrir (T64)

Decisión de Carlos: **DU-45 («Impedir reglas superpuestas y días sin cubrir»)**. Caso: **CFG-004**.

El conjunto de reglas de primer pago debe cubrir de manera obligatoria y continua la totalidad de los días del mes, **del día 1 al 31**, sin fisuras ni solapamientos:

1. **Continuidad total sin huecos ni superposiciones:**
   - La primera regla comienza obligatoriamente en el día 1 (`dia_desde == 1`).
   - Cada regla siguiente inicia exactamente al día siguiente del término de la anterior (`siguiente.dia_desde == actual.dia_hasta + 1`).
   - La última regla culmina obligatoriamente en el día 31 (`dia_hasta == 31`).
   - Debe existir al menos una regla activa.
   - Cada día del mes resuelve siempre a exactamente una regla activa. Se elimina el comportamiento previo en que días sin regla o con reglas encimadas no aplicaban descuento sin aviso.
2. **Guardado atómico en lote:**
   - Para no bloquear transiciones de tramos (ej. pasar de «1–15 y 16–31» a «1–10 y 11–31»), la pantalla guarda la colección completa en un solo envío (`PUT /configuraciones/primer-pago`).
   - El servidor valida la integridad del conjunto completo (`App\Support\ReglasPrimerPagoValidator`) y persiste dentro de una transacción atómica (`DB::transaction`).
   - Las reglas preexistentes conservan sus IDs numéricos para preservar la integridad referencial con `pagos.regla_primer_pago_id`.
   - Las operaciones individuales (`store`, `update`, `destroy`) simulan el conjunto resultante contra la base de datos y rechazan cualquier acción que genere huecos o superposiciones.
3. **Mensajes claros en castellano:**
   - Ante huecos: *«Los días X a Y quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.»*
   - Ante superposiciones: *«Los días X a Y se pisan con la regla "Nombre", que va del A al B. Un mismo día no puede tener dos porcentajes.»*

Implementado el 20/10/2026 por Gemini: `App\Support\ReglasPrimerPagoValidator`, `ReglaPrimerPagoWebController` y `resources/js/configuraciones.js`. Cubierto por `tests/Feature/ReglaPrimerPagoSinHuecosT64Test.php` y `ReglaPrimerPagoSinSuperposicionTest.php`.

---

## 4. Alumno nuevo vs alumno con historia

**Un alumno es NUEVO hasta que paga su primera cuota.** Punto. En cuanto registra su primer pago, deja de serlo para siempre.

No se hace ninguna distinción adicional por antigüedad. Un alumno de dos años y uno de dos meses son lo mismo: alumnos con historia. **Fuera de alcance.**

*Nota técnica actualizada27/10/2026: para asistencia, nuevo significa que no tiene
un pago de cuota registrado y no figura en `primera_carga.detalle.alumnos` de una
carga TERMINADA. No hace falta agregar campos. La carga PENDIENTE o sin detalle
no concede la condición de alumno con historia.*

### Enmienda del 06/09 — los alumnos de la primera carga tienen historia

**Planteado por Carlos el 06/09: los alumnos que se cargan en la primera carga no
tienen que reconocerse como nuevos.** Tiene razón, y la definición de arriba tal como
está no lo permite.

**Actualización27/10/2026:** la condición de asistencia usa los ids de la carga
TERMINADA guardados en `primera_carga.detalle.alumnos`. La regla del06/09 ya estaba
decidida; la devolución B3 corrige su implementación. El análisis siguiente
conserva el corte histórico del06/09, no describe el comportamiento actual.

**Observación histórica del06/09.** `CajaWebController.php:598-607` hace
`$tienePagos = Pago::where('alumno_id', $alumnoId)->exists()` y trata como nuevo a
todo alumno sin pagos, sin mirar la fecha de alta. La fecha de alta se usa **después**,
solo para elegir el tramo de descuento.

El club que recibe el sistema ya viene funcionando: sus 60 alumnos entran cargados a
mano, con fecha de alta vieja y **sin ningún pago registrado en Wings**, porque lo que
pagaron lo pagaron antes de que Wings existiera. Para el sistema, los 60 son nuevos.

**Lo que eso provoca:** a cada uno se le ofrece el **descuento de primer pago** según
el día de su fecha de alta (`CajaWebController.php:604`). Con fechas de alta viejas,
el descuento se calcularía con el día del mes de una fecha que ya no tiene nada que
ver con lo que se está cobrando.

### Medido sobre los diez alumnos de la primera carga (06/09)

**Esto ya no es una inferencia.** Con los diez alumnos cargados a mano y las tres
reglas de primer pago en su lugar, se consultó la base:

- Los diez tienen **cero pagos**, así que los diez son "nuevos" para el sistema.
- Cada uno resuelve a **exactamente una** regla: no hay tramos superpuestos.
- **Seis no pierden nada**: su fecha de alta cae entre los días 1 y 15, y ese tramo
  es del 100%.
- **Cuatro sí**, los que se anotaron después del día 15:

| Fecha de alta | Plan | Cobraría | Deja de cobrar |
|---|---:|---:|---:|
| 2026-04-18 | 35.000 | 24.500 (70%) | 10.500 |
| 2026-04-25 | 50.000 | 20.000 (40%) | 30.000 |
| 2026-05-21 | 40.000 | 28.000 (70%) | 12.000 |
| 2026-06-28 | 48.000 | 19.200 (40%) | 28.800 |
| | | **Total** | **81.300** |

**Sobre diez alumnos son $81.300. Sobre los sesenta, del orden de $488.000.**

Y no es plata de una base de prueba: es exactamente lo que va a pasar el día que el
club real empiece a cobrar con Wings, en el primer cobro de cada alumno.

> **Corrección del 06/09, marcada por Carlos:** el margen de dos clases del §5 **no
> son clases gratis** y no entra en esta lista. La cuota se le debe igual desde que se
> registra. Ese margen define hasta cuándo puede entrar sin haber pagado, no cuánto
> paga. Lo había contado como una segunda consecuencia y estaba mal.

**Regla que se adopta, definida el 06/09:**

> El descuento de primer pago se aplica **solo si la cuota que se está cobrando
> corresponde al mismo mes en que el alumno se dio de alta**.

Es lo que el descuento siempre quiso decir: quien se anota el 24 de abril no paga
abril entero, porque no lo usó entero. En septiembre paga lo que corresponde, como
cualquiera.

### Una regla anterior que no servía, y por qué

Primero propuse: *"es nuevo quien no tiene pagos **ni** arrastra deuda de períodos
anteriores"*. **Está mal, y lo aclaró Carlos el 06/09.**

En la carga inicial **solo entra la deuda que Vanina reporta; todos los demás quedan
al día, con todo cobrado**. O sea que la mayoría de los alumnos importados **no va a
tener deuda vieja**, y con esa regla el sistema los habría seguido tratando como
nuevos: no arreglaba nada justamente para el grupo más grande.

La regla del mes de alta no depende de que haya deuda, así que cubre a los sesenta
desde el momento en que se cargan.

### Implementado el 06/09

Nota del 23/09: lo siguiente describe el camino conservado para deudas previas. En
altas nuevas, §2 fija la primera cuota al crearla y no se recalcula al cobrar.

`PagoCuotaService::calcularReglaPrimerPago()` recibe ahora los períodos que se están
cobrando, y el descuento corre **solo si el mes de alta es uno de ellos**. La pantalla
de cobro (`CajaWebController`) aplica la misma condición, para que no anuncie un
descuento que el cobro después no hace.

`PagoService` **no se tocó**: su único consumidor es `PagoController`, que está en la
API deshabilitada desde el fix S2 y no tiene ninguna ruta que llegue. Verificado con
`route:list`.

**Apareció un segundo defecto de la misma clase**, y también quedó cerrado: el
descuento se aplicaba a **todos** los meses de un pago, no solo al de entrada. Alguien
que entraba el 20 de agosto y pagaba agosto y septiembre juntos se llevaba 30% de
descuento en septiembre, un mes que iba a usar entero. Contradecía el §12 de este
mismo contrato, que ya lo tenía escrito: *"aplica solo a la primera cuota; si se pagan
varios meses juntos, los demás van completos"*.

Ese defecto estaba **congelado en una prueba**: `PagoCuotaServiceTest` afirmaba los dos
meses descontados. Se corrigió citando el §12.

Cubierto por `DescuentoPrimerPagoSoloDelMesDeAltaTest`, cuatro casos. Verificado que
tienen dientes: desactivando cada arreglo por separado, cae la prueba que le
corresponde y solo esa.

---

## 5. Acceso a clase — alumno NUEVO

Un alumno nuevo que no pagó tiene un margen de dos clases:

| Clase | Qué pasa |
|---|---|
| **1ª** | Entra. Es la clase de prueba. |
| **2ª** | Entra. El pago puede estar registrándose durante la clase. |
| **3ª en adelante** | **No hay motivo para que entre sin haber pagado.** Si igual se lo deja entrar, hay que justificarlo (ver §8). |

El contador de clases es **interno**: no se muestra en pantalla. Solo se lleva para alumnos nuevos, y deja de importar en cuanto pagan.

**Estas dos clases no son gratis.** Precisado por Carlos el 06/09. El alumno se
registra, y **en ese momento queda definido lo que debe**: la cuota del mes con el
porcentaje que le corresponda según el día en que se registró. El margen de dos
clases no le perdona nada — solo dice **hasta cuándo se lo deja entrar mientras el
pago se registra**. A partir de la tercera, la deuda sigue estando y además ya no
tiene por qué entrar.

---

## 6. Acceso a clase — alumno CON HISTORIA

Un cliente que se atrasa es normal y pasa. No se lo trata como al nuevo.

| Estado | Al tomar asistencia |
|---|---|
| **AL DÍA** | Entra. Nada que mostrar. |
| **EN PLAZO** | Entra normalmente. |
| **MOROSO** | **Entra normalmente, sin justificar.** Quien toma asistencia lo ve, nada más. |
| **DEUDOR** | **Hay que justificar** para dejarlo entrar (ver §8). |

O sea: **el mes corriente impago se ve pero no traba. El mes cerrado impago sí.**

La lógica es la misma que la de los estados: mientras el reclamo todavía no corresponde —o recién empieza— no tiene sentido frenar a nadie. Cuando el alumno arrastra un mes cerrado, ahí sí alguien tiene que hacerse cargo de la decisión de dejarlo pasar.

---

## 7. Qué ve quien toma asistencia

**Siempre**, sin excepción: quien toma asistencia —profesor u operativo— **ve la condición de cada alumno** al lado de su nombre.

Nadie debería tener que ir a otra pantalla a averiguar si el alumno está en condiciones de tomar la clase.

---

## 8. Excepción justificada — mecanismo único

Cuatro situaciones distintas usan **exactamente el mismo mecanismo**. No son cuatro funciones, es una:

| Situación | Cuándo aplica |
|---|---|
| Alumno nuevo entra a la 3ª clase o posterior sin haber pagado | §5 |
| Alumno con historia en estado DEUDOR asiste a clase | §6 |
| Se registra un cobro parcial de una cuota | §9 |
| Se cobra un mes dejando impago uno anterior | §9b |

**En todos los casos:**

1. La acción **no se impide**. Se permite.
2. Quien la ejecuta debe dejar un **motivo obligatorio**.
3. El **administrador es notificado**: al abrir su usuario, o por mail.

La lógica es: el que está en el mostrador o en la cancha sabe lo que está haciendo y tiene contexto que el sistema no tiene. No se le bloquea el trabajo — se deja registro y se avisa a quien tiene que enterarse.

**Aclaración de alcance B3, 27/10/2026:** el exceso del plan no integra estas cuatro
excepciones. Conserva la anotación EXTRA/RECUPERA decidida en DU-34; no requiere
un motivo libre ni un aviso de B3. Las excepciones se consultan en Inicio del dueño
y se incluyen juntas en el resumen diario existente por Telegram y mail, sin
enviar un mensaje individual al registrar cada decisión.

---

## 9. Pago parcial

**Está permitido.** Un cliente puede tener un problema económico y pagar una parte.

- La cuota queda con parte pagada y parte pendiente.
- Requiere **justificación** y **notificación al administrador**, igual que §8.

---

## 9b. Cobro que deja impaga una deuda anterior

**Está permitido.** El operativo elige qué meses cobra y el sistema no lo bloquea.

**Regla:** si al confirmar el cobro queda pendiente **algún mes anterior** al que se está pagando, el sistema avisa antes de guardar, indicando cuántos meses son y por cuánta plata.

> Quedan 2 meses anteriores impagos por $56.000 (marzo, mayo). ¿Cobrás igual solo junio?

A partir de ahí aplica el mecanismo de §8: se permite, se pide **motivo obligatorio** y se **notifica al administrador**.

**Cuándo no avisa:** si se pagan todos los meses anteriores junto con el actual, o si no hay ninguno anterior pendiente.

### Por qué existe

La pantalla de cobro **ya muestra todas las deudas pendientes**, ordenadas de la más vieja a la más nueva. El problema nunca fue de información: es que el sistema no dice nada cuando se deja una atrás.

El caso real es la distracción, no la mala fe. El operativo tilda el mes que el alumno le nombra, no mira el resto de la lista, y la deuda vieja queda arrastrándose sin que nadie se entere.

### Decisión explícita: esto reemplaza al "FIFO fuerte"

El plan de producción proponía que el sistema **rechazara** el cobro de un mes con otro anterior impago (hallazgo B6, "FIFO evadible").

**Se descartó.** Se trata con clientes, no con gente a la que hay que vigilar. Bloquear el cobro en el mostrador crea un problema real para evitar uno hipotético.

La imputación FIFO **dentro de un mismo cobro** sigue vigente: si se pagan varios meses juntos, los viejos se cancelan completos antes de imputar a los nuevos. Lo que se descarta es impedir el cobro de un mes suelto.

> **No "arreglar" esto convirtiéndolo en un bloqueo.** Es una decisión tomada, no un defecto pendiente.

---

## 10. Visibilidad: movimientos vs caja

Son dos cosas distintas y no deben confundirse:

| Qué | Quién lo ve | Por qué |
|---|---|---|
| **Movimientos** (historial de cobros y gastos) | Cualquier operativo, sin restricción de propiedad | Es información operativa. Viene una madre y dice "pagué ayer a otra persona": el operativo de hoy tiene que poder verlo. |
| **La caja** (sus movimientos como conjunto, resumen, si está validada, si se rindió) | Solo quien la abrió. El admin ve todas. | La caja es la rendición de una persona. |

> Esto **corrige** el hallazgo P-04 de la auditoría v02, que trató ambas cosas como una sola y reportó una contradicción que no existía. `PERMISOS-ROLES.md` habla de los **movimientos**, y ahí no hay restricción de propiedad — está bien como está.

---

## 11. Estado de implementación

Las filas conservan su corte histórico salvo actualización fechada. El corte local
del 27/10/2026 de B3 dinero y asistencia está implementado localmente, con pruebas
propias verdes y pendiente de verificación independiente. Sin despliegue; no
certifica el sitio de prueba. Informes por entrega en PRU-04.

### Generación de la deuda (§2)

| Regla | Estado |
|---|---|
| Estructura general: con asistencia → deuda, sin asistencia → cola de revisión | ✅ **Implementado y correcto.** El comando `cobranza:generar-deudas` ya funciona así, y es idempotente (no duplica deuda existente) |
| El proceso corre el día 1 de cada mes | ⚠️ Está programado, pero **nada lo ejecuta**: no hay tarea de sistema que dispare el scheduler. Nunca corrió |
| Genera la cuota del mes que empieza mirando el mes que terminó | ✅ **Implementado y verificado.** El 1 de septiembre genera septiembre evaluando agosto; prueba automatizada confirma además que no genera octubre |
| Criterio 1: asistencia el mes anterior | ✅ Implementado |
| Criterio 2: alta + pago dentro de los últimos 15 días | ✅ **Implementado y verificado** con fechas de alta y pago recientes |
| Criterio a eliminar: "pagó el mes anterior" | ✅ **Implementado y verificado.** Un alumno antiguo que solo pagó el período anterior pasa a revisión si no asistió |
| La cola se cierra sola con una asistencia o un pago | ✅ **Implementado correctamente** en los tres puntos que corresponden |
| Se pide confirmación a operativo o admin que se conecte, hasta completar | ❌ Hoy es una pantalla (`/revision-cobranza`) **restringida a admin**, a la que hay que ir a buscar. No se le pone adelante a nadie |
| El monto sale del plan activo | ✅ Implementado |
| La primera cuota nace en el alta con importe congelado | Implementada localmente el 23/09, con pruebas de atomicidad y no duplicación; pendiente de verificación independiente y despliegue |
| Al cambiar de plan se actualiza la deuda del mes en curso | ✅ **Implementado correctamente** en el flujo de cobro, y solo toca el período corriente: las deudas anteriores no se reescriben |
| Al **bajar** de plan, diferir al mes siguiente si ya asistió | ✅ **Implementado y verificado.** Una prueba de flujo completo confirma que basta una asistencia y que la deuda corriente conserva el plan anterior |
| Al **subir** de plan, aplicar siempre al mes en curso | ✅ **Implementado y verificado**, incluso cuando ya hubo asistencia en el mes |

### Estados, acceso y excepciones (§3 a §10)

| Regla | Estado |
|---|---|
| Los cuatro estados de cobranza | ✅ **Implementado y verificado.** AL_DIA / EN_PLAZO / MOROSO / DEUDOR se calculan en una única implementación consumida por las pantallas de cobranza y alumnos |
| Orden de evaluación con DEUDOR primero | ✅ **Implementado y verificado** con los casos contractuales, incluido mes cerrado impago aunque el corriente esté pagado |
| Días de gracia configurables | ✅ **Implementado y verificado.** Se lee `dias_gracia_cobranza`; una prueba confirma el cambio de umbral de 10 a 15 |
| DEUDOR solo por mes cerrado impago | Enmienda del 23/09 implementada localmente; retirado el criterio de falta de pagos en cálculo individual y masivo. Pendiente de verificación independiente |
| Deudor o moroso por inscripción impaga y plazo propio de alta | ✅ **Implementado y verificado (13/10/2026, T31).** Enmiendas del 12/10 y 13/10: el estado de inscripción y cuota del mes de ingreso usa la fecha de ingreso (`fecha_alta`) con plazo propio (`dias_plazo_alta`, default 7) y días de gracia general; el estado del alumno es el peor entre cuotas e inscripción. Para ingreso futuro, la inscripción se debe desde el alta registral conforme a la aclaración de Carlos del 21/10 en T50. |
| Fecha de ingreso futura solo hasta fin de mes siguiente | **T50 CERRADA el 21/10/2026, verificación final Codex de 738de598.** Límite y mensaje desde Wings; edición inválida 422; idioma y guardia efectiva/DB_URL aprobados. H1 decidido por Carlos: inscripción desde el alta, cuota futura excluida. Ver informe final. |
| Reglas de primer pago sin huecos ni superposiciones | ✅ **Implementado (20/10/2026, T64).** Enmienda del 20/10 (DU-45, CFG-004): cobertura obligatoria continua del 1 al 31 con guardado atómico en lote, preservación de IDs y validación de conjunto. |
| Estado y condición al tomar asistencia | Implementados localmente el 27/10/2026: la lista muestra el estado existente de cobranza junto a cada alumno. MOROSO con historia entra sin motivo. Pendiente de verificación independiente. |
| Concepto de alumno nuevo en asistencia | Sin un primer pago de cuota registrado (`Pago::conCuota`) y fuera de `primera_carga.detalle.alumnos` de la carga TERMINADA. Una inscripción sola no termina esa condición. Corrección local27/10 tras devolución de Claude; pendiente su nueva verificación. |
| Contador de clases del alumno nuevo | Interno, calculado con presentes en clases anteriores no canceladas. No cuenta ausencias ni clases posteriores y no se envía en HTML/JSON. Desde la siguiente a las dos de margen, motivo obligatorio. |
| Justificación al dejar entrar a un DEUDOR con historia | Implementada localmente el 27/10/2026: motivo obligatorio y registro atómico antes de admitir una nueva marca presente. Reenviar marcas sin cambios no crea decisiones retroactivas. Pendiente de verificación independiente. |
| Exceso de plan | Anotación EXTRA/RECUPERA; fuera de B3 por DU-34. No se agrega motivo libre. |
| Justificación en cobro parcial y al dejar meses anteriores impagos | Implementada localmente el 27/10/2026: control de servidor, motivo obligatorio y registro atómico común. Pruebas propias verdes; pendiente de verificación independiente. |
| Aviso al dueño por las cuatro excepciones | Implementado localmente el 27/10/2026: historial paginado en Inicio y resumen diario de las últimas 24 horas por los canales existentes, sin mensajes individuales. Pruebas propias verdes; pendiente de verificación independiente. |
| Movimientos visibles para todos | ✅ **Corregido el 23/09/2026.** El historial ya lo hacía; `/movimientos` filtraba por caja propia y ahora filtra por rubro, como manda PERMISOS-ROLES. Lo cubre `MovimientosVisibilidadPorRubroTest` |
| Caja restringida a su dueño | ✅ Implementado correctamente |

**Registro común B3:** `excepciones_cobranza` conserva tipo, motivo, autor, fecha,
alumno y registro de origen. La corrección de una lista y el cambio de profesor
conservan sus motivos propios; EXTRA/RECUPERA queda fuera de B3. Los dos casos de
asistencia están incluidos en la segunda entrega. Se conservan las decisiones
anteriores si se vuelve a admitir tras una ausencia; no se duplica al reenviar una
marca sin cambios. La primera carga tiene historia por la enmienda del06/09;
su identificación por ids está corregida localmente el27/10. Quedan abiertas las
preguntas del corte financiero histórico y los avisos de orígenes anulados/corregidos;
no se inventan esas políticas. [Entrega dinero](../06-pruebas/PRU-04/IMPLEMENTACION-T88-B3-DINERO.md)
· [Entrega asistencia](../06-pruebas/PRU-04/IMPLEMENTACION-B3-ASISTENCIA.md).

---

## 12. Reglas relacionadas, ya acordadas

Definidas en la misma conversación, se registran acá para que no se pierdan:

- **Descuento de primera cuota:** aplica **solo a la primera cuota**. Si se pagan varios meses juntos, los demás van completos.
- **Comisiones a profesores:** se cierran **por mes**.

---

## 13. Reglas Freeze

- Un alumno es nuevo únicamente hasta su primer pago registrado.
- El alumno nuevo tiene dos clases de margen; a partir de la tercera sin pagar, se justifica.
- Son cuatro estados: AL DÍA, EN PLAZO, MOROSO y DEUDOR. Se evalúan en orden y DEUDOR gana sobre todos.
- AL DÍA, EN PLAZO y MOROSO no requieren justificación para entrar a clase; DEUDOR sí.
- Ninguna de las cuatro excepciones bloquea la operación: todas permiten, exigen motivo y notifican al admin.
- Los días de gracia son configuración, nunca un valor fijo en el código.
- Ver movimientos ajenos es legítimo; ver la caja ajena no.

Cualquier cambio futuro requiere versión V2 explícita.

---

## 14. Estado del documento

🔒 **CERRADO como acuerdo de negocio.** Define qué debe hacer el sistema.

**No cerrado como implementación:** la mayor parte de §2 a §9 no está construida. Al implementar cada punto, actualizar la tabla de §11 con una de estas cuatro salidas, nunca otra:

**implementado y verificado** · **pendiente** · **implementado sin verificar (y por qué)** · **descartado (y por qué)**
