# PRU-02 — Día 1: el primer día del club con Wings

Jueves. El club deja el cuaderno. Hay 60 alumnos cargados, la deuda vieja declarada y nada
más: ni una clase, ni una caja, ni un movimiento.

El día está escrito desde el club. **El club abre a las 16.** La mañana es de Vanina sola,
desde su casa: ella no mira la caja en vivo, no tiene tiempo. Lo que pasa en el mostrador
lo controla al otro día, con un café.

Cada escena dice qué se hace, qué tiene que encontrar quien lo hace, y —cuando corresponde—
**dónde Wings no tiene lugar para lo que el club necesita**. Eso último no es una falla del
día: es la mitad del resultado de esta prueba.

---

## Parte 1 — La mañana de Vanina, desde su casa (09:00 a 12:00)

### 1.1 Lo primero: que existan las clases

El club vive de las clases. Y no es una frase: **sin clases cargadas no hay asistencia, sin
asistencia no hay qué liquidarle al profesor, y el mes que viene el sistema no le genera la
cuota a nadie** —manda a todos a revisión de cobranza, porque no puede saber quién sigue
viniendo—. Por eso el horario no es un paso de configuración: es lo primero.

Vanina entra con su usuario de dueña, cambia su contraseña y arranca por acá.

**Los profesores primero, porque una clase necesita uno.** Los cuatro, con su forma de cobro
y su tarifa. **Vanina también da clases**: se carga como profesora con **costo hora $0**,
así sus clases se liquidan sin generarle un pago a sí misma.

**Hueco:** para pagarle a un profesor hace falta su CBU o su alias, y no hay dónde
guardarlo. Ese dato sigue en el teléfono de Vanina.

**Después el horario completo**, como está en
[CRONOGRAMA-SEMANAL.md](CRONOGRAMA-SEMANAL.md): clases repetidas, **un grupo por vez**, del
24/09 al 31/10, mirando el listado entre uno y otro. Son seis cargas, una por grupo.

Dos intentos a propósito:

- Una clase para una profesora en un horario que ya tiene ocupado: tiene que frenarla.
- **Una clase de 17:30 a 18:30.** Wings la acepta sin decir nada. Para el club son **dos
  horas de cancha**, porque se paga por bloque de reloj ocupado, no por duración. Anotarlo
  y borrarla.

**El hueco más caro del día:** Wings no sabe qué es una cancha. No hay dónde decir en cuál
se juega, cuál es la tarifa de esa hora, ni cuánto va a pagar el club este mes. Está
definido en el plan del 21/09 y sin implementar. Hoy ese gasto —el más grande del club—
vive afuera del sistema.

### 1.2 Que cada alumno esté donde entrena

Con el horario cargado, revisa el padrón que le dejaron: si cada uno está en **su grupo y
con su plan** —una clase por semana o dos—, porque de eso depende a qué clase entra y
cuánto paga. Después, si están los **celulares y los tutores** para poder llamar, si las
**fechas de ingreso** son reales o son el día en que alguien los cargó, y si los 20 que
deben, deben lo que dice el cuaderno.

**Qué mirar:** cuántas pantallas distintas necesitó para contestarse eso.

### 1.3 Los números que gobiernan la plata

En Configuración: inscripción de $5.000 desde el 23/09, diez días de gracia, generación de
cuotas el día 1, los descuentos de la primera cuota según el día de ingreso, y el correo y
el Telegram adonde le llegan los avisos.

Que confirme que esos son los números del club, no los que trajo el sistema.

### 1.4 Por dónde entra y por dónde sale la plata

Vanina entra a Rubros. Encuentra cargado:

| Entra por | Sale por |
|---|---|
| Cuotas, Inscripciones, Torneos, Indumentaria, Intereses | Sueldos (uno por profesor y por operativa), Servicios, Gastos Operativos, Alquileres (San Carlos, Centenera, Eventos) |

**Lo que hace hoy:**

1. Confirma que los **Alquileres** son sus canchas, con esos nombres.
2. **Crea lo que falta**, porque hoy van a aparecer dos cosas que no están: el arreglo de
   una baranda —un egreso de mantenimiento— y **las clases particulares**, que son
   habituales y no tienen ningún lugar propio. Crea el rubro y el subrubro, decidiendo en
   cada uno **quién lo puede usar**: si lo marca para el mostrador, el mostrador lo va a
   poder cargar y ver; si lo deja para ella, no lo ve nadie más.
3. **Intenta tocar los reservados:** Cuotas e Inscripciones no se dejan borrar ni renombrar.

**Hueco:** el club **vende** indumentaria, pero **comprar** esa mercadería no tiene rubro de
egreso. Si va a "Gastos Operativos", el día que quiera saber si gana plata con la ropa no
va a poder.

### 1.5 Los medios de cobro

Efectivo, Mercado Pago y tres cuentas de banco. Que confirme que son los suyos y **cree el
que falte**.

**Lo que va a aparecer hoy mismo:** la plata que entró por Mercado Pago después se
transfiere al banco. No es plata nueva, es la misma cambiando de lugar. Ver cómo se
registra eso sin que el club aparezca cobrando dos veces.

---

## Parte 2 — El club abre (16:00 a 20:00)

Sandra abre su caja con el cambio del cajón. A partir de acá, en el mostrador y en la
cancha pasan cosas al mismo tiempo.

### 2.1 Cobrar cuotas

Tres cobros, porque el club cobra de tres formas: una madre paga **en efectivo** el mes; otra
paga **por Mercado Pago** dos meses juntos; un padre paga **una parte** de lo que debe. En
los tres tiene que salir el recibo y la caja tiene que reflejar el medio correcto.

### 2.2 El alumno nuevo, con la familia esperando

Una mamá con una nena de siete años. Sandra la carga con la fecha de ingreso de hoy: Wings
avisa que corresponde inscripción por $5.000 **antes de guardar**. La cuota del mes sale
proporcional al día. Paga todo junto y el recibo muestra los dos conceptos separados.

Atrás viene **el hermano**, de diez, para fútbol.

**Lo que el club se pregunta hoy:** ¿el segundo hermano paga la inscripción completa? ¿hay
descuento por hermano? Hoy Wings cobra los $5.000 igual y **no conoce el concepto de
familia**: son dos alumnos sueltos que comparten apellido.

### 2.3 La plata que entra y no es una cuota

- Sandra **vende dos pares de patines y tres remeras**.
- Cobra la **inscripción a un torneo** de tres alumnas.
- Entra una **transferencia de $40.000 sin aviso**: no se sabe de quién es.

**Qué mirar:** si puede registrar una venta que no está atada a ningún alumno; si sale algún
comprobante para quien compró; y qué hace con la transferencia sin dueño, sabiendo que
mañana va a aparecer la madre diciendo que era de ella.

**Hueco:** no hay stock. El sistema registra la venta, no lo que queda en el armario.

### 2.4 La clase particular, que es habitual

Una mamá pide una clase particular para su hija el sábado a las 12, con Lucía.

Wings no tiene clases particulares: no existe el precio por clase ni la deuda de esa clase.
**Así se hace hoy, y esto es lo que se pierde:**

1. Vanina crea una **clase normal del grupo de la alumna**, sábado 12:00 a 13:00, con Lucía.
2. En la asistencia se marca presente **solo a ella**. Con eso la clase se liquida: a Lucía
   se le paga la hora completa, igual que cualquier otra.
3. El cobro se carga **a mano**, como un movimiento de la caja con el subrubro *Clases
   particulares* que Vanina creó a la mañana.

Lo que no se puede hacer: que quede una **deuda** si no paga en el momento, que salga un
**recibo** de esa clase, y saber si al club le dejó plata, porque **la hora de cancha del
sábado no está en ningún lado**. Además esa clase le cuenta a la alumna como una asistencia
más de la semana, así que va a aparecer como que vino de más.

Se juega igual, para medir cuánto duele.

### 2.5 La plata que sale

- Se **paga el arreglo de la baranda**, en efectivo y en el momento, con el rubro nuevo.
- Vanina le da un **adelanto a un profesor**, a cuenta de fin de mes.

**La trampa del adelanto:** cuando se liquide a ese profesor, el adelanto tiene que
descontarse. Si el sistema no los relaciona, el club le paga dos veces. Anotar cómo queda
registrado hoy.

### 2.6 El cambio de turno

A las 18:00 Sandra se va y entra **Pablo**. Abre su propia caja.

**Lo que hay que comprobar, porque es el problema diario del mostrador:** Pablo tiene que
**ver los cobros de cuota que hizo Sandra**. Si mañana viene la madre a preguntar por el
pago que hizo a la tarde, el que atiende tiene que encontrarlo. Lo que no tiene que ver es
la caja de Sandra, que es de ella hasta que la cierre.

### 2.7 Alguien carga algo mal

Porque va a pasar el primer día: un gasto en el rubro equivocado, un cobro de más, un
movimiento con la fecha del mes pasado. Lo que importa no es que Wings lo impida, sino **qué
queda registrado de la corrección**.

---

## Parte 3 — La cancha (16:00 a 20:00)

### 3.1 El nene que debe

Llega a entrenar un chico cuya familia debe dos meses. **Nadie lo echa.** Entra, entrena y
juega como todos.

Lo que el club necesita es otra cosa: que alguien **se entere** para llamar a la familia
esta semana. Hoy Wings no pide ningún motivo ni avisa a nadie: la asistencia se carga igual
que la de cualquiera. Está identificado y pendiente.

### 3.2 La que viene de más y la que tiene que recuperar

Una nena que paga una clase por semana viene dos veces. Otra faltó la semana pasada y viene
a recuperar. Wings cuenta las asistencias de la semana contra el plan.

**Qué mirar:** qué ve la profesora en pantalla al cargar la asistencia, y si con eso el club
puede decidir: cobrarle la clase de más, perdonarla o tomarla como recuperación.

### 3.3 Llueve

A las 17:00 la cancha está mojada. **Se suspende la clase de las 18:00.** Vanina la cancela
con el motivo, desde el teléfono.

Las tres preguntas del club:

1. **La cuota no se toca:** es mensual, no por clase.
2. **Al profesor no se le paga** una clase que no dio, aunque había reservado la tarde.
3. **La recuperación cuesta**: si se agrega una clase el sábado, es otra hora de cancha.

Qué mirar: que la clase cancelada desaparezca de la liquidación, que las asistencias ya
cargadas queden como estaban, y que se pueda crear la clase de recuperación sin romper el
horario.

### 3.4 La que se enferma

Una mamá avisa que la nena tiene anginas y no viene en todo el mes. Pide que no le cobren.
**Vanina le condona la cuota**, con un motivo escrito.

Qué mirar: que quede registrado quién la perdonó y por qué, que lo condonado **no figure
como plata que entró**, y que el mes que viene se facture normal.

---

## Parte 4 — Cerrar (20:00) y controlar (mañana, 09:00)

### 4.1 A la noche, en el club

Pablo cuenta el efectivo y cierra su caja. La diferencia, si la hay, tiene que verse
**antes** de cerrar. Sandra ya cerró la suya al irse a las 18.

**Y lo que hay que probar aunque salga mal:** que alguien se vaya **sin cerrar**. Al día
siguiente Wings lo bloquea y no lo deja cobrar hasta que cierre la de ayer. Es el primer
viernes que alguien se va apurado.

### 4.2 A la mañana siguiente, Vanina con el café

Valida las cajas del día anterior y entra al cashflow. Cada peso que se movió ayer tiene que
estar ahí **una sola vez**, con su rubro y su fecha real.

### 4.3 Las cuatro preguntas

Terminado el control, Vanina tiene que poder contestar mirando la pantalla:

1. **¿Cuánto entró ayer, y por qué concepto?** Cuotas, inscripciones, ropa, torneo, particular.
2. **¿Cuánto salió, y por qué concepto?** Arreglo, adelanto.
3. **¿Cuánto le deben?**
4. **¿Cuánto debe?** Sueldos, alquiler de las canchas, la mercadería.

**Si no puede contestar las cuatro, el día falló aunque no se haya roto nada.** Esa es la
razón por la que el club deja el cuaderno.

---

## Lo que este día pone a prueba

| Lo que se prueba | Dónde |
|---|---|
| Que el club pueda cargar su horario, que es de lo que vive | 1.1 |
| Que la dueña termine de configurar su club sola, y a distancia | 1.2 a 1.5 |
| Que la plata que no es cuota tenga lugar | 2.3 y 2.5 |
| Que las clases particulares se puedan resolver de algún modo | 2.4 |
| Que el mostrador sepa lo que cobró el turno anterior | 2.6 |
| Que el error humano se corrija dejando rastro | 2.7 |
| Que el club siga siendo un club: nadie echa a un nene | 3.1 |
| Que el sistema aguante lo que trae la vida: lluvia, enfermedad, recuperación | 3.2 a 3.4 |
| Que el control funcione al otro día, no en vivo | 4.1 y 4.2 |
| Que al cerrar, los números sean los del día | 4.3 |

## Lo que ya sabemos que falta

Se anota igual cuando aparece, con la escena donde apareció:

- **Las canchas y su alquiler por hora.** El gasto más grande del club, afuera del sistema.
  Definido en el plan del 21/09, sin implementar.
- **Las clases particulares.** Contrato escrito, sin implementar: hoy se resuelven a mano y
  sin deuda ni recibo.
- **El stock de indumentaria**, y el rubro para comprar la mercadería que se vende.
- **El concepto de familia:** hermanos, descuentos, una cuenta por casa.
- **El aviso** cuando entra a entrenar alguien que debe.
- **El adelanto al profesor** atado a su liquidación.
- **Los datos para pagarle** a un profesor.
- **Los reportes:** las cuatro preguntas del cierre hoy se contestan mirando varias
  pantallas.
- **Los intereses por mora**, definidos y sin implementar.
