# Prueba técnica del día 23 (1/11) — 27/10/2026 · Claude CyE

Sobre la base del sitio de prueba con el reloj en el 1/11, como `wingstest` y en solo lectura; seguridad en
`wings_testing_claude`.

| Área | Resultado |
|---|---|
| Funcionamiento interno | **21 de 21 bien** (`controles-internos-resultado-dia-23.json`). Cinco controles nuevos: cada clase liquidada paga valor hora × minutos y es del mes, de la profesora, no cancelada y con presentes o validada; cada parte de comisión es el porcentaje de su cuota, sin noviembres adelantados, y cada atrasada tiene su decisión registrada; una liquidación por profesor y mes; lo cerrado sin pagar ($345.000) es el «por pagar» de Reportes y no hay movimientos de Cashflow; el reporte de octubre quedó igual que el 31/10. Deuda $3.783.000 (100 En plazo, 3 Al día) y disponible $4.959.900,75 |
| Seguridad | **22 de 22.** Nuevas: con la liquidación cerrada, ni la profesora ni el dueño cambian asistencias, horario, validación o profesoras, ni generan otra del mismo mes ni la recalculan; la operativa y la profesora no generan, cierran, recalculan, pagan, ajustan, cancelan ni borran una liquidación; una abierta no se paga forzando (con control positivo: cerrada, el mismo envío paga). **Hallazgo T90:** una clase de una liquidación cerrada se puede cancelar, también la operativa y desde la pantalla (`cancelar-clase-liquidada.txt`) |
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
