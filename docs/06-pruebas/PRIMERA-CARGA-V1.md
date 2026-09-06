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
| Fútbol | 20 | Varones |

**Todas las fechas de alta son anteriores a julio de 2026.** Precisado por Carlos el
06/09; antes decía solo "anteriores a hoy", que era demasiado flojo.

Es una carga inicial: el club ya venía funcionando. Con fechas de julio o de agosto
quedarían alumnos que parecen recién llegados, y la prueba dejaría de representar lo
que va a pasar de verdad el día que se cargue el club real.

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

Pendiente de definir el formato. Ya se conversó que el Excel debe traer **el monto de
cada mes**, no calcularlo del plan actual: si el precio subió, derivarlo cobraría de
más por meses viejos.

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
