# Wings — Lo que está mal

Relevado el 23/09/2026, sobre el sitio de prueba con el club cargado. Dos listas: lo que
se ve entrando a la pantalla, y lo que no se ve pero rompe igual.

Cada punto dice **qué pasa**, **por qué importa para el club** y **dónde está**. Los marcados
como *verificado* se comprobaron en el código o en pantalla; los marcados como *por
diagnosticar* se vieron pero todavía no se sabe la causa.

Criterio de gravedad: **Frena** = el club no puede trabajar o pierde plata · **Molesta** =
se puede trabajar, con fricción · **Falta** = el club lo necesita y no existe.

---

## Parte A — Lo que se ve

### A1. Cobranza no sirve para cobrar · Frena · verificado

La pantalla de Cobranza lista a los alumnos con su estado y un botón **Ver**. No muestra
**cuánto debe** cada uno ni tiene botón de **Cobrar**. Para cobrarle a alguien hay que salir,
ir a Alumnos y buscarlo de nuevo.

Es la pantalla que más se usa en el mostrador y es la que peor resuelve su trabajo.
`resources/views/cobranza/index.blade.php`.

### A2. Cobranza dice 60 deudores y el dashboard dice 20 · Frena · por diagnosticar

Con el padrón recién importado, Cobranza clasifica **a los 60 alumnos como deudores**,
mientras el tablero del operativo muestra 20 con deuda. Uno de los dos miente y nadie sabe
cuál. Sospecha a confirmar: los 43 alumnos que quedaron con el mes de corte en **cero y
pagado** se leen como "nunca pagó nada" y caen en DEUDOR.

### A3. No se puede cobrar por adelantado · Molesta · verificado

Si el alumno no tiene una deuda generada, la pantalla de cobro muestra "Sin deudas
pendientes" y el botón queda apagado. **El motor sí lo soporta** —cobrar un período sin
deuda usa el precio del plan— pero la pantalla no lo ofrece. Un club cobra por adelantado
todo el tiempo.

### A4. Editar alumno no avisa por qué no guardó · Frena · verificado

Al cambiar la fecha de nacimiento, el tutor pasa a ser obligatorio. El error aparece **abajo
de todo**, en una pantalla más alta que el monitor. La persona aprieta Guardar, no pasa nada
visible y no entiende por qué. Es el mismo defecto que se corrigió en Revisión el 22/09.

**Y peor:** si se va por el menú lateral, **nadie le avisa que va a perder lo que cargó**.

### A5. Los profesores se eligen sin saber de qué deporte es la clase · Frena · verificado

Una fila de casillas con todos los profesores del club, sin filtrar. En una clase de patín
se puede tildar al profesor de fútbol. El formulario no conoce el deporte de la clase.

### A6. "Modificar" donde en todo el resto dice "Editar" · Molesta

En la ficha de la clase el botón se llama **Modificar** y tiene otro formato. Rompe la regla
de un verbo por acción, igual en todas las pantallas.

### A7. El listado de alumnos desperdicia la pantalla · Molesta

Una tarjeta por alumno del ancho completo, con el nombre a la izquierda y el deporte a
treinta centímetros. El botón **Ver** desborda su lugar. El gris de la tarjeta no significa
nada. Para saber el plan o el celular de alguien hay que abrir su ficha, uno por uno.

### A8. Puntos grises que no dicen nada · Molesta

En grupos, niveles y profesores cada tarjeta tiene un punto de color. En alumnos el color es
el deporte; en el resto es gris siempre. O significa algo o no va.

### A9. El interruptor "Activo" en las tarjetas · Molesta

En grupos, niveles y profesores aparece un interruptor al lado de los botones, con otro
tamaño y otra lógica visual que el resto del sistema.

### A10. El botón "Nuevo" del cashflow · Molesta

Está suelto en la barra de totales y lleva a **Movimiento directo**. Nadie puede adivinar
qué crea. En el resto del sistema el botón Nuevo vive en su propia barra, con el contador al
lado.

### A11. Configuración es impresentable · Frena · verificado

Muestra los nombres internos: `avisos_email`, `dia_generacion_deuda`, `dias_gracia_cobranza`,
`inscripcion_importe`. Sin agrupar, sin explicar qué gobierna cada uno. Quien entra no sabe
qué está tocando.

Además **acepta valores inválidos sin decir nada**: se guarda un importe en −100 y la
pantalla no protesta.

### A12. El inicio del operativo no ayuda a trabajar · Molesta

Tres cuadros en cero, una tarjeta que dice "Sin caja hoy" y nada que diga por dónde empezar
el día.

### A13. El admin termina con caja propia · Frena · verificado

Cuando el dueño cobra una cuota, el sistema **le abre una caja a su nombre**
(`abrirCajaSiNoExiste`). Después tiene que cerrarla y validarse a sí mismo.

**Decisión de Carlos, 23/09:** el admin **no tiene caja**. No cierra ni valida lo suyo. Le
corresponde una pantalla de caja distinta: la del dueño que mira, no la del mostrador que
rinde.

### A14. Pantallas más altas que el monitor · Molesta

Formularios largos donde los botones y los errores quedan fuera de la vista, sin nada fijo
arriba ni abajo.

### A15. Una clase de 17:30 a 18:30 se acepta sin decir nada · Molesta · verificado

Para el club son **dos horas de cancha**, porque se paga por bloque de reloj ocupado. Wings
no lo sabe ni lo advierte. Ver también I6.

### A16. Cargar el horario obliga a repetir la carga · Molesta

Un grupo que entrena lunes a las 17:00 y viernes a las 16:00 no se puede cargar de una vez:
hay que hacer dos series separadas, porque la carga repetida usa un solo horario para todos
los días elegidos.

---

## Parte B — Lo que no se ve

### B1. El admin está modelado como un operativo más · Frena · verificado

La pantalla de cobro manda siempre por el camino del mostrador
(`registrarPagoCuotaOperativo`), sin importar el rol, y por eso le abre caja al dueño. Existe
un camino directo a cashflow sin caja (`registrarPagoCuotaAdmin`) que **solo usa la API**.

El modelo de fondo es el problema: el dueño no es una mula del sistema.

### B2. El estado de cobranza no distingue "sin deuda" de "nunca pagó" · Frena · por diagnosticar

Es la causa probable de A2. Hay que leer el cálculo completo antes de tocar nada:
`app/Services/CobranzaEstadoService.php`.

### B3. Las excepciones del contrato no existen · Falta

El contrato de cobranza exige **motivo obligatorio y aviso al admin** en cuatro situaciones:
cobro parcial, cobro que deja impago un mes anterior, deudor que entra a clase, y alumno
nuevo desde la tercera clase. **Ninguna pide motivo hoy.** Tarea ENT-11.

### B4. El resumen diario no incluye las clases sin asistencia · Molesta · verificado

El aviso que llega por mail y Telegram cubre cajas, revisiones y liquidaciones. Una clase
sin lista tomada **traba el pago del profesor** y solo se ve como un contador en el menú de
quien entra.

### B5. Las canchas no existen · Falta

No hay dónde decir en qué cancha se juega, cuál es la tarifa de esa hora ni cuánto se paga
este mes. **Es el gasto más grande del club y vive afuera del sistema.** Está planificado al
detalle en `docs/07-evaluacion/PLAN-CANCHAS-LIQUIDACIONES-v2026-09-21.md` (POS-07).

### B6. Las clases particulares no existen · Falta

Son habituales en el club. No hay precio por clase, ni deuda de esa clase, ni recibo, ni
rentabilidad. Contrato escrito en `Wings-Contrato-Clases-Particulares-V1.md` (POS-06).

Hoy se resuelven a mano: una clase del grupo de la alumna con ella sola presente, y el cobro
cargado como movimiento suelto.

### B7. Se vende ropa pero no hay stock ni compra · Falta

Existe el rubro de ingreso por indumentaria. No existe el egreso por **comprar** la
mercadería, ni nada que diga cuántas remeras quedan. El club nunca va a saber si gana plata
con la ropa.

### B8. No existe la familia · Falta

Dos hermanos son dos alumnos sueltos que comparten apellido. No hay descuento por hermano ni
una cuenta por casa.

### B9. El adelanto al profesor no se descuenta solo · Falta

Se le puede pagar un adelanto como egreso, pero nada lo ata a su liquidación de fin de mes.
**Si nadie se acuerda, el club le paga dos veces.**

### B10. No hay dónde guardar el CBU del profesor · Falta

Para pagarle hace falta su alias o su cuenta. Ese dato vive en el teléfono de Vanina.

### B11. Los punitorios están definidos y sin motor · Falta

Contrato completo en `Wings-Contrato-Punitorios-Mora-V1.md`, con su configuración. Nada los
calcula (FIN-14).

### B12. No hay reportes · Falta

Al cerrar el día el dueño tiene que poder contestar cuatro preguntas: cuánto entró, cuánto
salió, cuánto le deben y cuánto debe. Hoy se contestan abriendo varias pantallas y sumando a
mano. La definición está cerrada (FIN-04); la implementación es POS-01.

### B13. Los datos de prueba no representan al club · Molesta · verificado

Los hizo Claude y están mal: **los 60 alumnos son adultos** —nacidos entre 1986 y 2005— en
una escuela de patín y fútbol infantil; sin tutores ni celulares cargados. El catálogo trae
**Servicios (Luz, Internet)** que este club no paga porque alquila las canchas. Y los saldos
iniciales de caja son inventados: los $1.570.000 del cashflow salen de ahí, no de plata real.

### B14. El despliegue no limpiaba la caché de rutas · Verificado y corregido

El servidor de prueba servía la lista de rutas del 13/09: las pantallas nuevas reventaban.
Se limpió el 23/09. **El script de despliegue todavía no lo hace solo.**

### B15. El SPF del subdominio anuló su comodín de DNS · Verificado y corregido

Documentado en `docs/04-tecnico/SERVIDOR.md`. Queda como lección de proceso: comprobar desde
afuera, no desde el servidor.

### B16. `/movimientos` filtraba por caja propia · Verificado y corregido el 23/09

El mostrador no veía los cobros del otro turno. Ahora filtra por rubro, como manda
`PERMISOS-ROLES.md`. Cubierto por `MovimientosVisibilidadPorRubroTest`.

---

## Qué mirar en la próxima prueba

Esto es lo que significa "hacer una prueba" en Wings, y vale para Claude, Codex y Gemini:

1. **¿La pantalla resuelve el trabajo de quien la usa, o solo muestra datos?** Cobranza
   muestra deudores y no deja cobrar: eso es un defecto aunque no dé ningún error.
2. **¿El usuario entiende qué pasó?** Si no guardó, tiene que saber por qué y dónde. Si va a
   perder lo que cargó, hay que avisarle.
3. **¿Los números coinciden entre pantallas?** 60 contra 20 es un defecto grave aunque las
   dos pantallas "anden".
4. **¿El sistema deja hacer algo que el club no puede permitir?** Un profesor de fútbol en
   una clase de patín; una clase que cuesta el doble de cancha.
5. **¿Cada rol hace lo suyo?** El dueño no cierra cajas ni se valida a sí mismo.
6. **¿Qué necesita el club y no tiene dónde anotarse?** Eso también se reporta.
