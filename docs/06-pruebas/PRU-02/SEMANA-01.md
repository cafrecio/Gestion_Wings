# PRU-02 — Semana 1: del jueves 24 al jueves 1 de octubre

Ocho días de club en una tarde. **La juega Gemini**, haciendo lo que haría cada persona
del club, mirando lo que aparece en pantalla y anotando el impacto de cada cosa. No es una
lista de pantallas para revisar: es el club funcionando, con gente que también se equivoca.

**Dónde:** https://test.gestionar-te.com.ar · **Contraseña de todas las cuentas:**
`PruebaWings2026`

| Quién | Rol | Usuario |
|---|---|---|
| Admin | ADMIN | `admin@wings.test` |
| Sandra Vidal | OPERATIVO | `sandra.vidal@wings.test` |
| Pablo Ledesma | OPERATIVO | `pablo.ledesma@wings.test` |
| Lucía Gaitán | PROFESOR (Patín) | `lucia.gaitan@wings.test` |
| Verónica Salinas | PROFESOR (Patín) | `veronica.salinas@wings.test` |
| Mariela Ocampo | PROFESOR (Patín) | `mariela.ocampo@wings.test` |
| Hernán Quintana | PROFESOR (Fútbol) | `hernan.quintana@wings.test` |

## Reglas de la prueba

**La pantalla se ve.** Chrome normal, ventana visible, de punta a punta. Nada de navegador
oculto ni de manejarlo por consola: Carlos mira mientras se juega. Para el celular, se
achica la ventana a 375 de ancho.

**Todo por pantalla.** Nada de SQL, de tinker ni de seeders para arreglar o preparar algo.
Lo único que se toca por consola es el reloj y las tareas programadas del día. Si algo no
se puede hacer desde la pantalla, eso es un hallazgo.

**Nunca se toca producción.** `wings.gestionar-te.com.ar` y `/home/wings` no existen para
esta prueba.

**Se anota solo lo que falla.** Un archivo por día, `HALLAZGOS-DIA-XX.md`, con una entrada
por error: qué se esperaba, qué pasó, si **frena** al club, lo **molesta** o es una
**observación**, y la captura. Lo que sale bien no se documenta. Sí se cierra cada día con
un renglón que diga qué escenas se jugaron, para saber qué quedó cubierto; si un día no
tuvo ningún error, se escribe el archivo con ese único renglón.

## Cómo se mueve el tiempo

Wings cree el día que se le diga. En el servidor, como usuario `wingstest`, desde
`/home/wingstest/app`:

```bash
php82 artisan wings:fecha-simulada '2026-09-24 08:00'   # empieza el día
php82 artisan wings:fecha-simulada                       # muestra el día vigente
php82 artisan wings:fecha-simulada --real                # vuelve a la fecha real
```

Al saltar de día **se caen las sesiones abiertas**: hay que volver a entrar. Es normal.

Las tareas automáticas no se esperan, se disparan, una vez por día simulado:

```bash
php82 artisan avisos:resumen-diario      # el resumen de pendientes al admin
php82 artisan cobranza:generar-deudas    # SOLO el 1 de octubre
```

En producción nada de esto existe: la fecha simulada se ignora por código y hay una prueba
automática que lo garantiza.

---

# Día 1 — jueves 24/09. El club arranca

`wings:fecha-simulada '2026-09-24 08:00'`

Wings tiene el padrón: 60 alumnas y alumnos, seis grupos, cuatro profesores. Veinte
arrastran deuda vieja. Lo que no existe todavía es el movimiento: ni una clase, ni una
caja, ni un cobro.

**El admin termina la carga.** Entra a Configuración y mira los valores que gobiernan la
plata: inscripción de $5.000 desde el 23/09, diez días de gracia, generación de cuotas el
día 1, y los descuentos del primer mes según el día en que entra el alumno. Revisa que
estén los rubros de Cuotas e Inscripciones y los medios de cobro.

**Carga el horario de toda la semana**, como está en
[CRONOGRAMA-SEMANAL.md](CRONOGRAMA-SEMANAL.md): clases repetidas, **un grupo por vez**,
del 24/09 al 31/10, mirando el listado entre uno y otro. Son seis cargas.

Dos intentos a propósito: una clase para Lucía en un horario que ya tiene ocupado —tiene
que frenarlo— y una clase de **18:30 a 19:30**, que Wings va a aceptar sin decir nada
aunque al club le cueste dos horas de cancha. Anotar y borrarla.

**Sandra abre la caja** con $20.000 de cambio y mira Cobranza para saber a qué se enfrenta.

**Llega la primera alumna nueva.** Sandra la carga con fecha de ingreso de hoy, en Patín
Principiantes, plan de dos clases ($40.000). El formulario tiene que avisar que corresponde
inscripción por $5.000. La mamá paga todo junto: como entra un día 24, la cuota de
septiembre sale al 40%, **$16.000 + $5.000 = $21.000**. Mirar el recibo y la caja: tienen
que quedar **dos movimientos separados**, no uno solo.

**Viene a pagar un deudor viejo.** Trae $60.000 y pide "lo más viejo primero". El sistema
cobra del mes más viejo hacia adelante y avisa si quedan meses anteriores impagos.

**17:00, 18:00 y 19:00: las tres clases del jueves.** Lucía carga la asistencia de Patín
Avanzadas, Mariela la de Federadas y Hernán la de Fútbol Avanzadas. Dejar algún ausente.

**Cierre.** Sandra cierra su caja y el admin la valida. La plata validada aparece en
cashflow **una sola vez**, separada por concepto.

---

# Día 2 — viernes 25/09. Lo que el operativo no tiene que ver

`wings:fecha-simulada '2026-09-25 09:00'` · `avisos:resumen-diario`

**El admin abre el correo.** Tiene que haber llegado el resumen del día, con lo que quedó
pendiente. Mirar si dice algo útil o si es ruido.

**La escena que más importa de la semana.** Entrando como **Sandra**, con la barra de
direcciones, escribir a mano cada una de estas:

```
/cashflow        /liquidaciones        /configuraciones
/usuarios        /admin                /caja/validaciones
```

Ninguna puede abrir. Si alguna muestra algo, **aunque sea un listado vacío o un número**,
es el hallazgo más grave posible y se anota primero. Después lo mismo entrando como
**Lucía**, que además no tiene que poder ver alumnos ni plata.

**Pablo Ledesma abre su propia caja.** Dos personas cobrando al mismo tiempo. Pablo tiene
que ver los alumnos y la cobranza igual que Sandra —el rol define el trabajo, no la
propiedad de los registros—, pero **no la caja de Sandra**.

**Dos cobros mal hechos, a propósito:** uno con un importe mayor al que se debe, y otro con
fecha del mes pasado. Anotar qué dice Wings en cada caso y si se entiende.

**16:00: Verónica da Patín Intermedias** y carga la asistencia.

---

# Día 3 — sábado 26/09. Nadie en el mostrador

`wings:fecha-simulada '2026-09-26 09:30'` · `avisos:resumen-diario`

Sábado de entrenamiento: **Federadas a las 10:00 con Mariela, Fútbol Avanzadas a las 11:00
con Hernán**. No hay nadie en administración, así que **la caja no se abre**.

**Lo que se prueba hoy:** que el club pueda funcionar sin el mostrador. Los profesores
cargan asistencia, nadie cobra nada, y el lunes tiene que estar todo como lo dejaron.

**Un intento a propósito:** Mariela abre la clase de Hernán e intenta cargar la asistencia
de un grupo que no es suyo. Anotar si puede.

---

# Día 4 — domingo 27/09. El día de los errores

`wings:fecha-simulada '2026-09-27 11:00'` · `avisos:resumen-diario`

El club está cerrado. Es el día para hacer todo lo que la gente hace mal, uno por uno,
anotando qué contesta Wings:

1. Cargar un alumno con un **DNI que ya existe en el mismo deporte**.
2. Cargar **la misma persona en el otro deporte**: eso sí tiene que poder, y **no** tiene
   que generar una segunda inscripción.
3. **Dar de baja a alguien que debe plata.**
4. **Condonar** una deuda vieja, y mirar qué queda registrado de quién lo hizo.
5. **Cambiar de plan** a un alumno a mitad de mes. La deuda de los meses anteriores no se
   toca nunca.
6. **Corregir la fecha de ingreso** de la alumna nueva del día 1, que ya pagó la
   inscripción: el sistema tiene que rechazarlo.
7. Cargar otro alumno nuevo, **sin cobrarle**, y corregirle la fecha hacia atrás: ahí sí
   se anula la inscripción, con registro.

---

# Día 5 — lunes 28/09. El día más cargado

`wings:fecha-simulada '2026-09-28 08:30'` · `avisos:resumen-diario`

**16:00: dos clases en paralelo**, Patín Principiantes con Lucía y Fútbol Principiantes con
Hernán. Es el caso de las dos canchas. Mirar si en pantalla se entiende que son dos clases
al mismo tiempo o si parece un error. **17:00: Patín Intermedias con Verónica.**

**Día de cobranza fuerte.** Sandra y Pablo cobran cada uno en su caja, mezclando efectivo,
Mercado Pago y transferencia. Al menos un cobro de varios meses juntos y uno parcial.

**Una caja que no cierra.** Sandra cierra declarando **$5.000 menos** de lo que el sistema
dice. Mirar cuándo aparece la diferencia: antes de cerrar o después. El admin la valida o
la rechaza, y se anota qué le queda al club como registro de ese faltante.

---

# Día 6 — martes 29/09. Pagarle a los profesores

`wings:fecha-simulada '2026-09-29 09:00'` · `avisos:resumen-diario`

**El admin liquida septiembre.** Los profesores de patín cobran por hora y el de fútbol por
comisión, o al revés según cómo esté cargado cada deporte: lo que importa es que las dos
formas se prueben.

- Revisar la previsualización antes de cerrar: las horas dictadas tienen que coincidir con
  las clases con asistencia cargada.
- **Las clases sin asistencia no se liquidan**: ese es el motivo del aviso que ve el admin.
- Cerrar una liquidación, pagarla y mirar el recibo del profesor.
- **Cancelar una liquidación cerrada sin pagar**, con motivo. Una pagada no se puede tocar.

**Sandra intenta entrar a Liquidaciones** escribiendo la dirección. No puede.

**17:00 y 18:00 y 19:00:** las clases del martes, con su asistencia.

---

# Día 7 — miércoles 30/09. Cierre de mes

`wings:fecha-simulada '2026-09-30 09:00'` · `avisos:resumen-diario`

Último día de septiembre. **16:00 Patín Principiantes, 18:00 Fútbol Principiantes.**

**El admin hace el control de fin de mes:** que no queden cajas sin validar, que la
cobranza muestre quién quedó debiendo septiembre, y que el cashflow cuadre con lo que se
cobró en la semana.

**Antes del salto, anotar tres números**, que son contra los que se compara mañana:

- cuánta plata se cobró en total esta semana;
- cuántas personas quedan debiendo algo;
- cuántas cajas quedaron sin validar.

---

# Día 8 — jueves 1/10. El salto de mes

`wings:fecha-simulada '2026-10-01 06:00'` · después `cobranza:generar-deudas` ·
después `avisos:resumen-diario`

Es el día que justifica toda la semana: Wings genera solo las cuotas de octubre.

**Ojo con esto, que ya lo vi pasar.** El generador no le crea la cuota a cualquiera: **le
crea la cuota al que asistió el mes anterior** o pagó hace poco. Al que no aparece por
ningún lado lo manda a *revisión de cobranza*, para que alguien decida si sigue o no. Lo
probé en el servidor con la base vacía de asistencias y **mandó a revisión a los 60**.

O sea que lo que se cargó durante la semana decide lo que pasa hoy. Lo que hay que mirar:

- **Cuántas cuotas se crearon y cuántas personas fueron a revisión.** Que los que fueron a
  revisión sean exactamente los que no vinieron a entrenar en septiembre.
- Que la cuota de octubre tenga **el precio del plan de cada uno**, sin descuento: el
  descuento es solo de la primera.
- Que el que quedó debiendo septiembre **ahora deba dos meses**, y que su estado de
  cobranza lo muestre.
- Que la alumna nueva del día 1 reciba su cuota de octubre **completa**.
- Que la inscripción **no se vuelva a generar** para nadie.

**Después, la revisión de cobranza.** Sandra resuelve las que salieron: unas continúan,
otras pasan a inactivo. Eso es tarea del operativo, no del admin.

**Y el resumen diario** tiene que llegar con todo lo que quedó pendiente.

---

# La parada

Acá se corta y se mira qué pasó. Lo que hay que traer:

1. **El resumen de los ocho días en una página:** qué funcionó, qué frenó al club, qué
   molestó.
2. **Los hallazgos ordenados por gravedad**, no por día.
3. **La respuesta a la pregunta de seguridad**, con las capturas: ¿el operativo vio algo de
   la economía del club? ¿El profesor vio algo de plata o de alumnos?
4. **Los tres números del día 7 contra lo que quedó el día 8.**
5. **Lo que el club necesita y Wings no tiene dónde anotar.** Las canchas ya sabemos que
   faltan; lo que aparezca además, se anota.

Después de esa parada se decide si la semana 2 son otros siete días o el salto a noviembre
y diciembre.
