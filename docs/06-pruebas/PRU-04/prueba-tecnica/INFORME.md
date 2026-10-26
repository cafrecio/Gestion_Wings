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
