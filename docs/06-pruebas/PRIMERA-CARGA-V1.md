# Primera carga — cómo se arma el club desde cero

> Definido por Carlos el 05/09/2026. **Especificación, sin implementar.**
> Reemplaza el enfoque de `DATASET-SEEDER-V1.md`: no es un seeder que inventa un
> club, es **reproducir la carga inicial real tal como la haría una persona**.
>
> ## ESTO NO ES LLENAR UNA BASE. ES UNA PRUEBA INTEGRAL DEL SISTEMA.
>
> Cada alta cargada a mano es **un caso de prueba con resultado esperado**, no un
> registro más. Si algo se carga y nadie comprobó que quedó bien, no se hizo nada.

## Punto de partida: la base vacía

Se borra **toda** la base. Queda únicamente lo que **no se puede crear desde la
aplicación**:

| Qué queda | Detalle |
|---|---|
| Usuario ADMIN | `admin@wings.com` / `wings2026` |
| ~~Usuario SUPERADMIN~~ | **Omitido en esta etapa** (decidido el 06/09). Importa cuando se cargue el servidor, no para probar en local |
| Rubro y subrubro de cuotas | El que cobra las clases — el código lo busca por nombre exacto |
| Rubro Sueldos | **Sin subrubros**: se crean solos al dar de alta cada profesor |
| Tabla `configuraciones` | **Agregado el 06/09.** Ver abajo: se borró y no hay forma de recrearla desde la aplicación |

### Hallazgo del 06/09: hay un valor que el sistema necesita y nadie puede crear

**Verificado, no inferido.** La limpieza dejó `configuraciones` en **0 filas**. Ahí
vivía `dias_gracia_cobranza`, y:

- Lo crea **la migración** `2026_05_29_003356_create_configuraciones_table.php`, no
  un seeder. Una base ya migrada que se vacía **no lo recupera**.
- La pantalla de configuración solo tiene `PATCH configuraciones/{clave}`: **edita
  claves que existen, no las crea.** No hay ninguna ruta que dé de alta una clave.
- `Configuracion::set()` usa `update()`: sobre una fila que no existe **no hace nada
  y no avisa**.
- `CobranzaEstadoService::diasGracia()` (línea 258) **tira `LogicException`** si el
  valor falta.

**Consecuencia concreta:** apenas haya alumnos, cualquier pantalla que calcule estado
de cobranza revienta con error 500. No es un problema de este ambiente: **le pasa
igual a cualquier instalación nueva que se vacíe**, y es exactamente el tipo de
agujero que esta prueba tiene que encontrar.

**Qué hacer ahora para no quedar trabados:** restaurar la fila corriendo de nuevo esa
migración sobre la base descartable, o insertarla a mano. Dejar registrado que se
hizo por fuera de la aplicación, porque desde la aplicación no se puede.

**Qué hacer después, como tarea aparte:** decidir si esas claves se siembran en un
seeder de arranque o si la pantalla de configuración pasa a poder crearlas. No se
resuelve dentro de esta carga.

#### Las dos claves originales

La migración inserta **dos**, y se restauran las dos con estos valores exactos:

| Clave | Valor | Tipo | Para qué |
|---|---:|---|---|
| `dias_gracia_cobranza` | 10 | integer | Días del mes en que una cuota corriente impaga sigue contando como *En plazo* |
| `dia_generacion_deuda` | 1 | integer | Día en que se genera la deuda mensual |

#### Segundo hallazgo: hay una configuración que no hace nada

**Verificado el 06/09.** `dia_generacion_deuda` **aparece únicamente en la
migración**. Ningún archivo de `app/`, `routes/` ni `resources/` la lee.

La generación mensual está programada en `routes/console.php:12` con
`->monthlyOn(1, '06:00')`: **el día 1 está escrito en el código**. La pantalla de
configuración lista todas las claves y deja editarlas, así que el administrador
puede cambiar ese valor a 5, guardarlo, verlo guardado — **y la deuda se va a seguir
generando el día 1**. Una configuración que miente.

No se arregla dentro de esta carga. Queda anotado porque **importa para punitorios
por mora**: ahí también se pidió un día y un porcentaje configurables, y ya sabemos
que en este sistema existe el patrón de guardar un valor que después nadie consulta.

> Las contraseñas de arriba son de prueba y así se usan a propósito. No aplica acá
> la discusión de claves fuertes: esta base es descartable.

Todo lo demás **lo carga una persona desde las pantallas**. Ese es el punto: si algo
no se puede cargar desde la aplicación, eso mismo es el hallazgo.

---

# Etapa 1 · Armar el club a mano

## Catálogos

| Qué | Cuánto |
|---|---|
| **Deportes** | 2: Patín y Fútbol |
| **Niveles** | 4: Principiantes, Intermedias, Avanzadas, Federadas |
| | **Los niveles se comparten entre deportes.** Fútbol usa los mismos, no tiene versiones propias en masculino |
| **Grupos de Patín** | 4, uno por nivel |
| **Grupos de Fútbol** | 2: Principiantes y **Avanzadas** |
| **Tipos de caja** | 2: Efectivo y Mercado Pago |
| **Reglas de primer pago** | 3 tramos. **Agregado el 06/09**: también se borraron y sí se cargan por pantalla |

### Reglas de primer pago — se cargan por pantalla

La limpieza dejó `reglas_primer_pago` en **0 filas** (verificado). A diferencia de
`configuraciones`, esta sí tiene alta, edición y baja propias
(`configuraciones/primer-pago`), así que **se carga como todo lo demás: a mano, y
cada alta es un caso de prueba.**

| Días del mes | Porcentaje de la cuota |
|---|---:|
| 1 a 15 | 100% |
| 16 a 23 | 70% |
| 24 a 31 | 40% |

Sin estas tres filas **no se le puede cobrar la primera cuota a un alumno nuevo**,
que es justo el flujo que más importa probar.

Saldos iniciales definidos por Carlos el 06/09: **Efectivo 250.000** y
**Mercado Pago 1.320.000**. Se ingresan al crear estos tipos de caja nuevos,
no editando tipos existentes.

## Profesores

| Deporte | Cuántos | Cómo cobran |
|---|---|---|
| Patín | 3 | Por hora, **con valores distintos entre sí** |
| Fútbol | 1 | Por comisión |

## Usuarios

Dos **operativos** y cuatro **profesores**, uno por cada profesor cargado.

## Alumnos — 60 en total

| Deporte | Cuántos | Género |
|---|---|---|
| Patín | 40 | Mujeres |
| Fútbol | 20 | Varones, **con una excepción** |

> **Excepción autorizada por Carlos el 06/09.** La alumna anotada en los dos deportes
> —Sofía Morales, DNI 32123456— es **mujer y está en Fútbol**. Fútbol queda con 19
> varones y 1 mujer.
>
> Esta tabla decía "20 varones" sin excepción, y esa contradicción **frenó a Codex
> antes de escribir el seeder**: no podía cumplir las dos reglas a la vez. La decisión
> se tomó en el chat y el documento quedó sin corregir; se corrige acá.
>
> Es la consecuencia lógica de pedir la misma persona en dos deportes: si la dupla
> tiene que existir y Patín es de mujeres, esa persona es mujer en los dos.
>
> **Estado real verificado el 06/09:** 60 alumnos, Patín 40 / Fútbol 20, un único DNI
> repetido (el de la dupla), 0 deudas y 0 pagos.

**Todas las fechas de alta son anteriores a julio de 2026, y tiene que haber alumnos
desde 2025.** Precisado por Carlos el 06/09; antes decía solo "anteriores a hoy", que
era demasiado flojo.

Es una carga inicial: el club ya venía funcionando. Con fechas de julio o de agosto
quedarían alumnos que parecen recién llegados, y la prueba dejaría de representar lo
que va a pasar de verdad el día que se cargue el club real.

### Por qué la antigüedad tiene que estar repartida

**No es un detalle de ambientación: es lo que hace probable la etapa 2.** El script
que va a leer el Excel existe para una cosa concreta — **que Vanina nos pase el
estado de deuda REAL del club**. Si los 60 alumnos entran todos con la misma
antigüedad, ese script se prueba contra un caso único y el día que llegue la planilla
de verdad se va a encontrar con algo que nunca vio.

Un alumno de 2025 puede arrastrar más de un año de meses impagos. Uno de mayo de 2026
puede deber uno o dos. Y muchos no deben nada. Esas tres situaciones tienen que estar
en la base **antes** de escribir el script.

**Reparto propuesto** — el total y los tramos son propuesta mía; lo que fijó Carlos es
que haya alumnos desde 2025 y ninguno de julio en adelante:

| Fecha de alta | Cuántos | Para qué sirve |
|---|---:|---|
| Durante 2025, en meses distintos | 12 | Los que pueden arrastrar deuda vieja de muchos períodos |
| Enero a marzo de 2026 | 18 | Antigüedad media |
| Abril a junio de 2026 | 30 | Los más recientes, pero igual anteriores a julio |

**Ninguno con alta en julio, agosto ni septiembre de 2026.**

De los **diez que se cargan a mano**, al menos **dos tienen que ser de 2025**. Si los
diez manuales son todos recientes, el caso más viejo nunca se prueba a mano y queda
solo en manos del seeder.

Ojo con lo que esto deja al descubierto, y es a propósito: como ninguno tiene pagos
registrados en Wings, **el sistema los va a tratar a todos como alumnos nuevos** y les
va a ofrecer el descuento de primer pago calculado con el día de una fecha de alta
vieja. Está registrado como enmienda pendiente en
`Wings-contrato-estadosAlum-cobranza-asistencia-V1.md` §4. **No se arregla dentro de
esta carga**: si al cobrar aparece el descuento, se anota como comportamiento
observado y se sigue.

### Diez se cargan a mano, como lo haría una persona

De los 60, **diez se cargan uno por uno desde la pantalla**, no por seeder.

- **Algunos con el usuario admin y otros con un operativo**, para ejercitar los dos
  caminos.
- **Probando todas las validaciones**: campos obligatorios vacíos, DNI repetido,
  formatos inválidos, menor sin tutor.
- **Verificando que quedaron bien cargados**, no solo que la pantalla no dio error.

### Cada alta a mano se registra como un caso de prueba

**No alcanza con cargarlos: hay que documentar qué se intentó y qué pasó.**

Se entrega un documento con una fila por intento:

| Qué se intentó | Resultado esperado | Resultado obtenido |
|---|---|---|
| Alta completa y válida, como admin | Se crea, y queda con el plan, el grupo y la fecha de alta correctos | |
| Alta completa y válida, como operativo | Igual que la anterior | |
| DNI repetido en el **mismo** deporte | Rechazado con mensaje | |
| DNI repetido en **otro** deporte | **Se crea** — es la alumna en dos deportes | |
| Celular vacío | Rechazado con mensaje | |
| Menor de edad sin datos del tutor | Rechazado pidiendo el tutor | |
| Fecha de nacimiento futura | Rechazado | |
| Email con formato inválido | Rechazado | |
| Grupo que no corresponde al deporte elegido | Rechazado | |
| Fecha de alta anterior a hoy | Se acepta y queda esa fecha, no la de hoy | |

**Los intentos que fallan son parte de la prueba, no un error.** Si un rechazo no
funciona como se espera, ese es el hallazgo más valioso de toda la carga.

Y en cada rechazo hay que mirar una cosa más: **que no haya quedado nada escrito a
medias**. Un alta rechazada que igual dejó una fila es peor que una que no se rechazó.

**Distinguir siempre** un mensaje de validación de una pantalla de error. El primero
está bien; el segundo es un defecto.

El resultado va a `docs/06-pruebas/RESULTADO-PRIMERA-CARGA-V1.md`.

### Los otros cincuenta, por seeder — RECIÉN DESPUÉS

**El seeder no se escribe hasta que los diez a mano estén cargados y todo funcione.**

El orden no es un capricho: si el seeder se escribe antes, va a insertar cincuenta
filas esquivando las validaciones que todavía no sabemos si funcionan. Y si algo está
mal, lo vamos a descubrir con cincuenta registros mal cargados encima.

Cuando los diez pasen sin sorpresas, el seeder genera los cincuenta restantes:
**realistas, sin datos incompletos y sin repetidos.** Nombres, DNI, celulares y
correos que podrían ser de personas distintas de verdad.

### Una alumna en los dos deportes

Una misma persona anotada en Patín y en Fútbol.

**No es un alumno con dos deportes: son dos registros con el mismo DNI**, uno por
deporte, cada uno con su grupo, su plan y su cuota. Lo permite el contrato
`Wings-Contrato-ABM-Admin-V1.md` §9.c: el DNI es único **combinado con el deporte**.

---

## Planes: definidos el 05/09

**Todos los grupos tienen las dos mismas frecuencias: 1 clase por semana y 2 clases
por semana.** Los seis grupos, sin excepción.

### Precios, definidos el 05/09

| Deporte | Grupo | 1 vez/semana | 2 veces/semana |
|---|---|---:|---:|
| Patín | Principiantes | 30.000 | 40.000 |
| Patín | Intermedias | 33.000 | 43.000 |
| Patín | Avanzadas | 35.000 | 45.000 |
| Patín | Federadas | 40.000 | 50.000 |
| Fútbol | Principiantes | 28.000 | 35.000 |
| Fútbol | Avanzadas | 38.000 | 48.000 |

Son doce planes en total: dos por cada uno de los seis grupos.

### Regla nueva: un grupo debe conservar al menos una frecuencia

**Es un cambio de código, no solo una convención.** Regla de Carlos del 05/09,
extendida por su orden del 06/09 al alta, la edición y la eliminación individual.
Implementada localmente el 06/09 en `GrupoWebController`: `planes` es obligatorio
y debe contener al menos una frecuencia. Eliminar la última se rechaza con una
explicación. No se modificaron vistas ni JavaScript.

Y eso es justamente lo que produce la trampa: un grupo sin frecuencias hace que el
campo de plan **no aparezca** en el formulario de alumno, y la carga se traba pidiendo
algo que no se ve en pantalla.

**Al menos una frecuencia es obligatoria al crear y al editar un grupo; no se
puede eliminar la última.** Los grupos existentes sin frecuencias se cuentan y
reportan, sin borrarlos ni inventar precios. En `wings_test` hay **0** al 06/09.

Verificación: cinco regresiones aprobadas en MariaDB. Alta y edición sin
frecuencias rechazadas en navegador, con comparación de la base sin cambios.
El rechazo de eliminar la última quedó visible en la captura aportada por Carlos
el 06/09, luego del bloqueo de la herramienta de navegador. Una nueva comparación
de la base confirmó que tampoco hubo cambios en ese intento. El rechazo también
está cubierto por regresión HTTP.
Esto no ejecuta ni cierra la prueba de primera carga completa.

---

# Etapa 2 · Cargar la deuda desde un Excel

Un script que toma un Excel del cliente y carga la deuda que cada alumno arrastra.

## Esto no es un ejercicio: es la herramienta con la que entra la deuda real

**Precisado por Carlos el 06/09.** Este script es el que va a usar **Vanina para
pasarnos el estado de deuda REAL del club.** No es un paso de la prueba: es una
herramienta de la entrega, y lo que se cargue con él va a ser la plata que el club
efectivamente reclama.

Dos consecuencias directas:

1. **Se prueba contra los 60 alumnos con antigüedad repartida** de la etapa 1, no
   contra un caso cómodo. Alumnos de 2025 con más de un año de meses impagos, alumnos
   de mayo de 2026 con uno o dos, y alumnos sin deuda.
2. **Un error acá no es un error de prueba: es cobrarle de más o de menos a una
   persona.** Todo lo que el script no entienda tiene que frenar y avisar, nunca
   adivinar.

## Lo que ya está decidido del formato

**El Excel trae el monto de cada mes.** No se deriva del plan actual: si el precio
subió, derivarlo cobraría de más por meses viejos. Decidido antes del 06/09.

El período se guarda en `deuda_cuotas.periodo`, que es **`varchar(7)` con formato
`YYYY-MM`** (verificado contra la base). El Excel tiene que poder expresar meses desde
2025 en adelante, y el script tiene que rechazar cualquier período que no tenga esa
forma en lugar de interpretarlo.

## El formato, definido por Carlos el 06/09

Una fila por alumno, y **de la tercera columna en adelante van pares de monto y mes**,
tantos como meses deba:

```
DNI ; deporte ; monto ; mmYYYY ; monto ; mmYYYY ; ...
```

| Columna | Qué es |
|---|---|
| `DNI` | Documento del alumno |
| `deporte` | **Hace falta**: el mismo DNI puede estar anotado en dos deportes, y son dos alumnos distintos con dos deudas distintas |
| `monto` + `mmYYYY` | Lo que debe de ese mes. Se repite el par tantas veces como meses adeude |

### La regla que evita las distorsiones

**El que no está en la planilla está al día, con todo cobrado.** Decidido por Carlos
el 06/09.

No se reconstruye historia hacia atrás. No se calculan cuotas viejas, no se generan
deudas que después habría que dar por pagadas, no se inventan pagos. **Entra
solamente lo que Vanina reporta como adeudado, y nada más.**

Es la decisión que evita el problema de fondo: cualquier intento de reconstruir los
meses anteriores obligaría a derivar montos de precios que fueron cambiando, y el
resultado sería una deuda que no coincide con la que el club realmente reclama.

### Lo que el script tiene que respetar

- **El monto viene en la planilla, no se calcula.** Si el precio subió, derivarlo del
  plan actual cobraría de más por meses viejos.
- **El mes viene como `mmYYYY` y en la base se guarda como `YYYY-MM`** —
  `deuda_cuotas.periodo` es `varchar(7)`, verificado. La conversión la hace el script,
  y **rechaza** cualquier valor que no tenga esa forma en lugar de interpretarlo.
- **Una fila que no coincide con ningún alumno frena y avisa.** No se crea el alumno,
  no se saltea la fila en silencio.
- **Correrlo dos veces no puede duplicar deuda.**

## Decisiones cerradas para la importación

- La primera fila es obligatoria y debe contener exactamente `DNI`, `deporte` y,
  desde la tercera columna, pares repetidos de `monto` y `mmYYYY`. La plantilla
  entregada por el sistema ya trae esos encabezados y deja la columna de período
  como texto para conservar el cero inicial de meses como `01`.
- Las filas completamente vacías se ignoran. Una fila parcialmente informada, sin
  deporte o sin al menos un par completo de monto y período, rechaza el archivo.
- La misma combinación de **DNI + deporte** no puede aparecer dos veces en el
  archivo. El mismo DNI en deportes distintos sí es válido y representa alumnos
  distintos.
- Un monto debe ser numérico y estrictamente mayor que cero. Los ceros y negativos
  rechazan el archivo completo.
- Si ya existe una deuda para el alumno y período informado, se rechaza el archivo
  completo. Nunca se sobrescribe ni se suma: la segunda ejecución del mismo Excel
  informa el conflicto de forma clara antes de intentar insertar.
- La validación recorre el archivo entero antes de escribir. Si hay errores, devuelve
  todos con su número de fila de Excel y no se crea ninguna deuda.

## Rehacer una carga sin tocar alumnos ni pagos

No se ejecutan `DELETE` manuales contra producción. El comando de importación incluye
una reversión con el mismo Excel: primero valida el archivo y confirma que cada deuda
coincide exactamente, sigue pendiente, no tiene monto pagado ni imputaciones. Solo
entonces elimina esas deudas dentro de una transacción. Si una sola deuda fue pagada,
condonada, ajustada, cambiada o falta, frena sin borrar ninguna.

El procedimiento es:

1. Conservar el Excel original que se importó.
2. Ejecutar la reversión del comando con ese archivo y revisar el listado de deudas
   que propone retirar.
3. El comando verifica que no quedan deudas de los períodos y alumnos del archivo.
4. Recién entonces ejecutar la importación de la planilla corregida.

La reversión no borra alumnos, planes, pagos, movimientos de caja ni imputaciones de
pagos. Si hay una imputación, la operación se rechaza y debe revisarse antes de decidir
cualquier corrección.

La guía operativa y los comandos están en
[`CARGA-DEUDA-INICIAL-EXCEL.md`](CARGA-DEUDA-INICIAL-EXCEL.md). Se entrega además
la plantilla vacía `PLANTILLA-CARGA-DEUDA-INICIAL.xlsx` y el caso documentado
`EJEMPLO-CARGA-DEUDA-INICIAL.xlsx` en esta misma carpeta.

## Lo que ya no queda abierto

- ~~Si viene como `.xlsx` o como `.csv`.~~ **`.xlsx`**, decidido por Carlos el 06/09:
  es el formato que la persona que arma la planilla sabe usar. El script lee `.xlsx`
  directamente; no se le pide a nadie que exporte ni convierta nada.
- ~~Qué hace si un alumno aparece dos veces en la planilla.~~ Rechaza el archivo si
  se repite la misma combinación DNI + deporte.

---

# Etapa 3 · Simulación de tres meses

Día por día, **a modo de cuento**: qué pasa cada día en el club y cómo lo cubre el
sistema. Con todas las situaciones que pueden darse en una jornada.

Reemplaza y absorbe lo especificado en `SIMULADOR-TRES-MESES-V1.md`, que queda como
referencia de qué verificar al cierre de cada mes.

---

# Para el final, después de todo lo anterior

- **Organizar el menú del administrador.**
- **Dashboards.**

Los dos van al final a propósito: los reportes agregan pantallas y el menú se
reordena igual, así que hacerlo antes obliga a hacerlo dos veces.

---

## Qué reemplaza este documento

`DATASET-SEEDER-V1.md` describía 15 alumnos inventados, cada uno para verificar un
caso. **Ese enfoque queda de lado.** Acá la prueba no es un catálogo de casos: es
**reproducir la carga real de un club**, y ver si el sistema la aguanta.
