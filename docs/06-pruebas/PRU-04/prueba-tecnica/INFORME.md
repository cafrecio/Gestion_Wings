# Prueba técnica de los días 34 a 40 (12 al 18/11) — 30/10/2026 · Claude CAB

Sobre la base del sitio de prueba con el reloj en el 18/11, como `wingstest` y en solo lectura; seguridad en
`wings_testing_claude`; la comparación con la simulación, en dos copias locales descartables.

| Área | Resultado |
|---|---|
| Plata al peso contra la simulación | **0 diferencias en 11 tablas** (`comparar-con-simulacion-dias-34-40.txt`). La base del sitio después de la tanda, comparada por nombre, DNI, período y fecha con la copia donde Claude simuló los siete días antes de entregarlos: 213 cuotas, 52 recargos e inscripciones con lo cobrado de cada uno, 202 cobros, 110 alumnos, 113 planes, 16 precios, 34 cajas, 276 movimientos de caja, 305 de Cashflow, 148 clases con sus presentes y ausentes y 7 excepciones. Lo que Codex hizo por pantalla dejó exactamente lo que el guion esperaba |
| Funcionamiento interno | **44 de 44 bien** (`controles-internos-resultado-dias-34-40.json`). Ocho controles nuevos: Camila y Mora Benítez pagaron desde cada una de sus dos fichas, un cobro por ficha con su cuota y su recargo; Benicio pagó la cuota sola el 12/11 y el recargo solo el 16/11; Joaquín pagó $20.000 a cuenta con el motivo de Sandra y el resto ($15.000 + $3.500) sin motivo; Santino pagó octubre sin recargo y Guadalupe noviembre con su recargo perdonado entero; las únicas cuotas de diciembre son los tres adelantos (Joaquín Vega $48.000, Emma Fernández $45.000, Dante $52.000); Bautista y Emma Villalba entraron el 17/11 al 70% con inscripción de $5.000, Bautista pagó y fue sin motivo, Emma no; Lara cambió a Intermedias con el plan nuevo desde el 1/12 y noviembre en $44.000; la clase de Adultos del 19/11 cancelada con su motivo y sin lista; cada transferencia son dos renglones opuestos de traspaso; las seis cajas validadas por el dueño sin diferencia, la del sábado validada el domingo y la del 18/11 con los $7.000 como cambio. Tres controles de la tanda anterior describían el estado del día 33 y se pusieron al día (Iván pagó, Dante adelantó, se cobraron más recargos: $94.400). **Un control nuevo estaba mal escrito** (buscaba a Joaquín Rodríguez en vez de Joaquín Vega) y se corrigió. Probados contra una copia con cinco datos rotos a propósito: los cinco controles afectados se pusieron en rojo |
| Seguridad | **37 de 37.** Seis pruebas nuevas, forzando por fuera de la pantalla: el recargo no se cobra salteando la cuota, no se entrega más de lo que se debe (ni un peso más que el recargo) y la operativa no lo perdona; un adelanto exige confirmación, importe y hasta 12 meses, la profesora no cobra y no se cobra dos veces; el cambio de grupo exige una frecuencia del grupo nuevo y un grupo del mismo deporte, la profesora no lo hace y el mes pagado no se reescribe; la transferencia rechaza misma cuenta, cero, negativo, fecha futura y cuenta inactiva, y la misma clave no duplica ni cambia; cancelar pide motivo, la profesora no cancela y una clase con lista pide confirmar; el alta de la segunda quincena guarda el 70% que calcula Wings aunque el envío diga otro importe, y la profesora no da altas. Sin hallazgos. **SIS-006, verificado forzando:** el movimiento de la caja rechazada sin observación o con fecha futura ahora se rechaza en castellano («El campo observación es obligatorio.»; `caja-rechazada-avisos-despues-de-sis-006.txt`); el archivo del día 33 conserva los avisos en inglés del hallazgo |
| Velocidad con los datos de hoy | 44 pantallas, todas por debajo de 281 ms (`performance-sitio-dias-34-40.json`); nuevas: Cobranza con morosos 173 ms, ficha de quien tiene dos fichas 34 ms, alta 16 ms, edición con cambio de grupo 34 ms, Cobrar a un moroso con recargo 36 ms y a quien adelantó 37 ms, Transferir 5 ms, ficha de una clase cancelada 28 ms. Por primera vez se midió Cobrar: el script abre un turno dentro de la transacción que se revierte (comprobado: el sitio quedó sin cajas abiertas). La más lenta sigue siendo Reportes de Sueldos, 183 consultas |

Para que Carlos decida (no es un error de esta tanda): un mes adelantado se cobra al importe que escriba quien
cobra y queda pagado entero, sin aviso al dueño. Es lo que Carlos decidió el 10/10 (T18: «el importe lo puede
cambiar quien cobra»). Leído en el código, no ejecutado: el único límite es un centavo, así que diciembre se puede
cobrar a $1. Un parcial o una deuda anterior sí dejan motivo y aviso.

# Prueba técnica de los días 29 a 33 (7 al 11/11) — 30/10/2026 · Claude CAB

Sobre la base del sitio de prueba con el reloj en el 11/11 (23:40), como `wingstest` y en solo lectura;
seguridad en `wings_testing_claude`.

| Área | Resultado |
|---|---|
| Funcionamiento interno | **36 de 36 bien** (`controles-internos-resultado-dias-29-33.json`). Nueve controles nuevos: los 32 recargos son el 10% del original de su cuota de noviembre, de activos, uno por cuota y de una sola corrida (11/11 06:15); Santino y Candela sin recargo; $115.500 = $40.300 cobrados + $5.000 condonados (por el dueño, con motivo) + $70.200 pendientes; la deuda perdida de Julieta ($77.000) la registró el dueño, está marcada en sus cuotas y no creó pago ni movimiento; Verónica inactiva sin clases desde el 7/11, con el usuario desactivado y sin sesiones, sus 2 clases de noviembre siguen siendo suyas y las 7 Intermedias quedaron con Lucía; Centenera en $125.000 con una sola corrección (antes, después, motivo y dueño); la caja #53 registra que la cerró el dueño; la #55 heredó $10.000, abrió y se rectificó con motivo y autora, se rechazó con motivo, lleva la resma y se validó con diferencia 0; la clase 255 reactivada conserva su motivo y el amistoso no existe; Principiantes del 11/11 con 22 presentes y 2 ausentes, excepción solo de Santino e Iván y los motivos del guion; Iván pagó solo la inscripción y su cuota sigue entera; Florencia pagó cuota y recargo en dos renglones; el plan de Fútbol Avanzadas vale $52.000 desde el día del cambio (T44) con noviembre y el diciembre de Joaquín en $48.000. **Un control mío estaba mal escrito** (esperaba el precio nuevo desde el 1/12) y se corrigió. Deuda $850.200 (83 Al día, 22 Morosos, 1 Deudor) y disponible $5.585.920,75: iguales a lo que leyó Codex |
| Seguridad | **31 de 31.** Cinco pruebas nuevas: el cierre por el dueño queda marcado y con qué dueño, una operativa no rectifica la caja de otra, y abrir con más del 50% de diferencia exige motivo, confirmación y el cierre visto, también forzando; el rechazo sin motivo lo frena el servidor con el texto del catálogo y la operativa recibe 403; en la caja rechazada, el movimiento sin observación o con fecha futura se rechaza en el servidor y otra operativa recibe 403; dar por perdida la deuda forzando: no de un activo, no con otro importe, no dos veces, y sin pago ni movimiento; reactivar con choque se rechaza forzando y dice con cuál, y la profesora y la operativa no reactivan; corregir Cashflow exige la versión vista en pantalla, deja el historial y la operativa recibe 403. **La prueba que retrataba MF12 se dio vuelta** (reparada el 29/10). **Hallazgo menor (SIS-006):** salteando el navegador, el servidor contesta en inglés al rechazar el movimiento de la caja rechazada (`caja-rechazada-avisos-dia-33.txt`: «The observaciones field is required.», «The fecha field must be a date before or equal to today.»); por pantalla Chrome frena antes, en castellano. Mismo origen que el hallazgo del día 17: no hay `lang/es/validation.php`. El motor del recargo, su configuración y su cobro ya tienen 37 pruebas permanentes (`PunitoriosMotorTest`, `PunitoriosCobroTest`, `PunitoriosConfiguracionTest`): repetir la corrida no duplica, no toca inactivos ni meses cerrados, 150 se rechaza, condonar es solo del dueño y con motivo |
| Velocidad con los datos de hoy | 36 pantallas, todas por debajo de 304 ms (`performance-sitio-dias-29-33.json`); nuevas: Configuración 8 ms, ficha de un moroso con recargo 80 ms (141 consultas), historial de una corrección de Cashflow 8 ms, caja rechazada corregida 18 ms. La más lenta sigue siendo Reportes de Sueldos del mes anterior, 183 consultas en 304 ms. Sin caja abierta, Cobrar redirige a la apertura y no se mide |

Revisado aparte: el recargo corrió **una sola vez**, a las 06:15 del 11/11 (los 32 cargos llevan esa hora). El
resumen diario no corrió en esta tanda porque cada día el reloj se fijó después de las 08:00; no es un error. El
registro del servidor tiene un error del 29/10 a las 00:31 UTC por una consulta hecha a mano (`precio` en
`grupo_planes`), no de una pantalla de Wings. El «today» de las reglas de validación es el día real de la
máquina, no el reloj simulado: en producción coinciden.

# Prueba técnica de los días 24 a 28 (2 al 6/11) — 29/10/2026 · Claude CAB

Sobre la base del sitio de prueba con el reloj en el 6/11, como `wingstest` y en solo lectura; seguridad en
`wings_testing_claude`.

| Área | Resultado |
|---|---|
| Funcionamiento interno | **27 de 27 bien** (`controles-internos-resultado-dias-24-28.json`). Seis controles nuevos: cada liquidación pagada tiene un único egreso en Cashflow, por su importe y con su fecha (8, $1.370.280); la constancia de noviembre sigue igual después de la segunda corrida; cada excepción de B3 tiene motivo, autor y su cobro (3); el diciembre adelantado existe solo pagado; ningún activo conserva fecha de baja (Julieta no la tiene: se dio de baja antes de T70); un DNI no se repite por deporte. Huérfanos ampliado a planes, cargos, excepciones, Revisión y profesoras de clase: lo eliminado (Lucas en Patín, la serie de sábados) no dejó nada. **ASI-2 se corrigió:** desde T62 una clase cancelada con la lista tomada conserva sus marcas (12, la de Avanzadas del 3/11); ahora el control mira que no haya marcas cargadas después de cancelar |
| Seguridad | **26 de 26.** Nuevas: el dueño forzando no elimina un alumno con cobros, una clase con lista ni una serie con una clase con lista (los rechazos son los textos del catálogo), y la operativa y la profesora reciben 403 al eliminar o eliminar serie; cobrarle a un inactivo sin decir si vuelve pregunta, y forzar «No» con el mes en curso se rechaza; la operativa no da por perdida la deuda de un inactivo. **Hallazgo MF12:** con un mes ya liquidado por comisión, «Nueva liquidación» sigue contando sus clases; generar ese mes otra vez sí se rechaza |
| Velocidad con los datos de hoy | 31 pantallas, todas por debajo de 277 ms (`performance-sitio-dias-24-28.json`); nuevas: Nueva liquidación con el mes anterior 13 ms, ficha de un inactivo 76 ms (132 consultas), caja abierta 16 ms. La más lenta sigue siendo Reportes de Sueldos del mes anterior, 183 consultas |

Revisado aparte: los recibos LIQ-2 y CUOTA-254 (los PDF del servidor) son correctos; el resumen diario del
5/11 corrió una vez a las 08:00:26 sin error. La consola de MySQL muestra la hora de la constancia en UTC
(09:02:31); Wings la guarda bien (06:02:31).

# Prueba técnica del día 23 (1/11) — 27/10/2026 · Claude CyE

Sobre la base del sitio de prueba con el reloj en el 1/11, como `wingstest` y en solo lectura; seguridad en
`wings_testing_claude`.

| Área | Resultado |
|---|---|
| Funcionamiento interno | **21 de 21 bien** (`controles-internos-resultado-dia-23.json`). Cinco controles nuevos: cada clase liquidada paga valor hora × minutos y es del mes, de la profesora, no cancelada y con presentes o validada; cada parte de comisión es el porcentaje de su cuota, sin noviembres adelantados, y cada atrasada tiene su decisión registrada; una liquidación por profesor y mes; lo cerrado sin pagar ($345.000) es el «por pagar» de Reportes y no hay movimientos de Cashflow; el reporte de octubre quedó igual que el 31/10. Deuda $3.783.000 (100 En plazo, 3 Al día) y disponible $4.959.900,75 |
| Seguridad | **22 de 22.** Nuevas: con la liquidación cerrada, ni la profesora ni el dueño cambian asistencias, horario, validación o profesoras, ni generan otra del mismo mes ni la recalculan; la operativa y la profesora no generan, cierran, recalculan, pagan, ajustan, cancelan ni borran una liquidación; una abierta no se paga forzando (con control positivo: cerrada, el mismo envío paga). **Hallazgo T90:** una clase de una liquidación cerrada se podía cancelar, también la operativa y desde la pantalla. Reparado el mismo día; la prueba se dio vuelta y ahora comprueba el rechazo (`cancelar-clase-liquidada.txt`) |
| Velocidad con los datos de hoy | 26 pantallas, todas por debajo de 271 ms (`performance-sitio-dia-23.json`); nuevas: Nueva liquidación 11 ms, detalle por hora 14 ms, por comisión 29 ms. La más lenta: Reportes de Sueldos de octubre, 159 consultas en 271 ms. La generación de las 100 cuotas tardó 1 s |

Revisado y descartado: las horas del detalle de liquidación parecían corridas 3 horas; es la consola de MySQL,
que muestra en UTC. Wings guarda bien.

# Prueba técnica de los días 19 a 22 — 27/10/2026 · Claude CyE

Sobre la base del sitio de prueba con el reloj en el 31/10, como `wingstest` y en solo lectura; seguridad en
`wings_testing_claude`.

| Área | Resultado |
|---|---|
| Funcionamiento interno | **16 de 16 bien** (`controles-internos-resultado-dias-19-22.json`): 19 cajas, 126 movimientos validados, 2 transferencias, 95 cobros y 104 cuotas cuadran. Deuda $0 (103 Al día); disponible $4.959.900,75, igual a lo que leyó Codex |
| Seguridad | **18 de 18.** Nuevas: la liquidación del mes no se cierra antes de fin de mes ni forzando el envío. Cinco pruebas retratan hallazgos y se van a poner en rojo cuando se corrijan: gasto mayor que el cajón (MOV-004), parcial sin motivo (COB-009), «Nueva liquidación» que cuenta otros meses, dos toques del interruptor (T85) y el sueldo de una operativa, que Cashflow no ofrece y que forzando también se rechaza (`sueldo-operativa-forzado.txt`) |
| Velocidad con los datos de hoy | 21 pantallas, todas por debajo de 192 ms (`performance-sitio-dias-19-22.json`). La más lenta sigue siendo Reportes de Sueldos, con 149 consultas |

# Prueba técnica de los días 17 y 18 — 27/10/2026 · Claude CyE

Sobre la base del sitio de prueba (`wingstest`, con el reloj en 27/10), como usuario `wingstest` y en
solo lectura. Por SSH desde CyE, por el puerto 2222.

| Área | Resultado |
|---|---|
| Funcionamiento interno | **16 de 16 bien** (`controles-internos-resultado-dias-17-18.json`): 15 cajas, 82 movimientos validados, 58 cobros y 70 cuotas cuadran; deuda $234.000 y disponible $3.483.700,75, iguales a lo que leyó Codex |
| Seguridad | **12 de 12** en `wings_testing_claude`. Nuevo: con un turno abierto, otra operativa no abre el suyo ni forzando el envío; sale el aviso de CAJ-009 y no se crea ninguna caja. El control previo deja configurado el medio de efectivo, así el rechazo viene de esa regla y no de otra |
| Velocidad con los datos de hoy | Todas las pantallas entre 2 y 211 ms (`performance-sitio-dias-17-18.json`). La más lenta es Cobranza del dueño. Reportes de Sueldos hace 149 consultas en 168 ms: no molesta hoy, pero conviene mirarla cuando crezca la historia |

Hallazgo menor: el rechazo de una clave corta sale en inglés si se saltea el formulario (ver revisión del 17.1).

# Prueba técnica de los días 7 a 16 — 26/10/2026 · Claude CyE

**Seguridad: 4 de 4 bien, sin hallazgos.** Base descartable `wings_testing_claude`; mismas pruebas de
`SeguridadDiasPruebaTest.php`, con 11/11 y 81 aserciones en verde.

| Prueba | Resultado |
|---|---|
| La operativa fuerza 10 acciones nuevas del dueño: rechazar caja, cambiar la gracia, crear usuario, ascenderse a ADMIN, desactivar a otra, alta de profesor, nivel, grupo, subrubro y liquidación | Las 10 dan 403 y no cambia ningún dato (`seguridad-operativa-dias-07-16.txt`) |
| El dueño intenta quitarse el rol o desactivarse por envío directo, sin pasar por la pantalla (día 16; Codex solo lo vio en pantalla) | Sigue siendo ADMIN y activo |
| Gracia «-3», «abc», 0, 29 y vacío; Cashflow con 0, negativo y fecha futura, por envío directo (días 14 y 16; Codex solo vio el aviso del navegador) | El servidor rechaza todo. El control con datos buenos sí se guarda |
| Caja rechazada: Sandra la vuelve a cerrar con otro contado; Pablo intenta cerrarla (día 13.8, que Codex no pudo probar) | Da el texto de CAJ-031 y lo contado no cambia; Pablo no la toca |

**Funcionamiento interno y performance sobre los datos del sitio: no se hicieron.** Necesitan el servidor, y
desde la red de CyE el puerto de `ssh vps` no responde, aunque la web sí (`/login` da 200). Quedan
para la próxima sesión con acceso. El volumen de dos años ya se midió después de T79.

# Prueba técnica de los días 1 a 6 — seguridad, performance y comportamiento interno

25/10/2026 · Claude CAB. Sobre las mismas operaciones de los días de la prueba humana.

**Dónde se probó.** Los controles de solo lectura corrieron en el sitio de prueba (`wingstest`) con lo
que dejaron los seis días. Lo que escribe datos corrió en la base descartable `wings_testing_claude`. La
copia de la base del sitio a la máquina local la frenó el control de permisos (manejaba credenciales del
servidor) y no se forzó.

## Resultado

| Área | Resultado | Para hacer |
|---|---|---|
| Comportamiento interno | **16 de 16 controles bien** sobre los datos reales | Nada |
| Seguridad | **7 de 7 bien**; un hallazgo: la política de contenido no bloquea | Pasarla a modo de bloqueo |
| Performance con los datos de hoy | Todas las pantallas entre 4 y 142 ms | Nada |
| Performance con dos años de datos | **Reportes tarda 22 s**; Cashflow 3 s; Inicio del dueño, Reportes de Alumnos, Sueldos y Cobranza entre 1,4 y 2,8 s | Corregir antes de que el club acumule historia |

## Comportamiento interno (`controles-internos.php`, resultado en `controles-internos-resultado.json`)

Reglas que la plata y la asistencia tienen que cumplir siempre, verificadas fila por fila en el sitio:
efectivo esperado de cada caja = inicial + movimientos en efectivo no cancelados; diferencia = contado −
esperado; retenido + retirado = contado; cada apertura hereda lo que retuvo la anterior; cada movimiento
de caja validada aparece una sola vez en Cashflow (los de antes del 12/10 enlazados a la caja entera); el
reflejo conserva importe, fecha y medio; la transferencia tiene dos patas iguales y opuestas; cada cobro
reparte exactamente su importe y entra a caja por ese importe; lo pagado de cada cuota es la suma de sus
imputaciones; una cuota por alumno y mes; deuda de Cobranza = deuda de Reportes ($1.612.000); vencidos +
en plazo = los que deben; nadie Al día con deuda; todo activo con plan; disponible de Reportes = saldos
iniciales + Cashflow + cajas sin validar ($2.148.300); una marca por alumno y clase, ninguna en clases
canceladas o futuras ni antes de llegar al grupo; toda clase con profesora; nada huérfano. **16/16.**

Dos reglas mías estaban mal escritas en la primera corrida y se corrigieron: una cuota con pago a cuenta
sigue PENDIENTE con saldo (es el diseño), y la caja del día 1 se reflejó antes de que existiera el vínculo
por movimiento.

## Seguridad (`SeguridadDiasPruebaTest.php`)

| Prueba | Resultado |
|---|---|
| La operativa fuerza 12 acciones del dueño (Inicio del dueño, Cashflow, Reportes, Liquidaciones, Configuración, Transferir, gasto directo, validar caja, condonar, crear cuota pasada, anular cobro) | Todas 403; ningún dato cambió (`seguridad-operativa.txt`) |
| La profesora entra a Cobranza, Caja, Alumnos, una ficha, Cashflow y un recibo; intenta cobrar, cancelar y crear una clase | Todo rechazado, sin cambios. Cubrir la clase de otra profesora sí puede (suplencia, por diseño) y la clase sigue asignada a la titular |
| Pablo intenta cancelar un cobro, editar un movimiento y cerrar la caja abierta de Sandra | Rechazado, sin cambios |
| El mismo cobro enviado dos veces | Se registra una sola vez |
| `<script>` en el nombre, el tutor y la observación de un movimiento, mirado en 6 pantallas | Siempre se muestra como texto |
| Seis ingresos seguidos con clave equivocada | El sexto recibe 429 |
| Cabeceras | `nosniff`, `DENY` y HTTPS estricto están. **La política de contenido está en modo «solo informar»** (`SecurityHeaders`, línea 41): avisa pero no bloquea código inyectado. Desde T73 no queda JavaScript dentro de las vistas, así que se puede pasar a modo de bloqueo |

No probado por el arnés: el token contra envíos de otro sitio (las pruebas automáticas de Laravel lo
desactivan). Leído en el código: está activo para todo salvo `/csp-reporte`.

## Performance (`performance-pantallas.php`)

Cada pantalla se abre por dentro con el usuario de cada rol, tres veces; se informa la mediana.

**Con los datos de hoy del sitio** (101 alumnos, 17 cobros; `performance-sitio-prueba.json`): todas entre
4 y 142 ms. La más lenta es Cobranza.

**Con dos años de datos** (300 alumnos, 7.044 cuotas, 6.999 cobros, 532 cajas, 1.914 clases, 89.519
marcas; armados con `volumen-generar.php`; `performance-volumen-2-anios.json`):

| Pantalla | Hoy | Dos años |
|---|---|---|
| Reportes · Finanzas | 60 ms | **22.280 ms** |
| Cashflow | 75 ms | 2.976 ms |
| Inicio del dueño | 33 ms | 1.497 ms |
| Reportes · Alumnos | 40 ms | 2.162 ms |
| Reportes · Sueldos | 118 ms | 1.776 ms |
| Cobranza | 142 ms | 1.462 ms |
| Inicio de la operativa | 84 ms | 844 ms |
| Alumnos, fichas, Cajas, Clases, Movimientos, Liquidaciones | 4–62 ms | 17–87 ms |

**Causa de Reportes (medida):** el gráfico de evolución recalcula el mes completo seis veces, y cada vez
carga todos los movimientos de la historia (`ReporteMensualService::evolucion` → `obtener` →
`movimientos()`: 2,5 s por carga con 7.000 movimientos). Son 17 de los 22 segundos; el Inicio del dueño
usa el mismo cálculo una vez. La causa de Cashflow no se midió.

Límites: los datos de volumen no incluyen liquidaciones ni el historial de reportes; la máquina local
no es el servidor. Las proporciones (de decenas de milisegundos a segundos) son lo que importa.

## Después de las reparaciones (25/10, T79 y T80)

- **Performance (T79):** con los mismos dos años de datos, Reportes · Finanzas 22.280 → 2.613 ms y Cashflow
  2.976 → 101 ms, con los mismos números (comparación completa antes y después en `comparar-t79.php` y
  `comparar-cobranza-t79.php`). Medición en `performance-volumen-2-anios-t79.json`. [Informe](../IMPLEMENTACION-T79.md).
- **Seguridad (T80):** la política de contenido ahora bloquea; `SeguridadDiasPruebaTest` lo exige.
  [Informe](../IMPLEMENTACION-T80.md).
