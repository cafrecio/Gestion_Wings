# Primera carga por Excel — decisiones de Carlos del 26/09/2026

> **Decidido, no implementado.** Reemplaza el enfoque anterior de carga inicial.
> Nada de esto se programa antes de que Carlos apruebe la maqueta de la pantalla.

## Por qué se cambió el enfoque

Se estaban gastando horas en una excepción: cómo tratar al alumno antiguo que se carga a
mano (A43). Cada regla nueva abría dos casos de borde más — el que ingresó este mes antes
del corte, el que ingresa el mes próximo, el que no entró en el padrón, quién paga
inscripción. Carlos cortó: *"estamos pensando e implementando todo mal"*.

## La decisión

1. **La primera carga es un Excel único** con los alumnos completos **y su deuda**. Un solo
   script lo importa. Hoy los dos importadores que existen —`wings:importar-padron` y
   `wings:importar-deuda-inicial`— cargan deuda de alumnos que ya tienen que estar cargados
   a mano: **los dos se retiran**, para no dejar tres caminos de carga inicial.
2. **Lo que se carga a mano después sigue las reglas del sistema**, sin excepciones: cuota
   del mes de la fecha de ingreso con el descuento según el día, más inscripción.
3. **Se saca la fecha de corte** (`inscripcion_fecha_corte`): el parámetro, su pantalla y la
   prueba que lo cuida. Era la fuente del enredo.
4. **El importador no crea catálogos.** Un grupo o un plan que no existe se **rechaza**: si
   se creara sobre la marcha quedaría sin precio y las cuotas saldrían mal. Deportes,
   niveles, grupos y planes se cargan por pantalla antes, y ese orden es parte del manual.

## Cómo evitar los errores del Excel, que los va a tener

- **La plantilla la genera el sistema**, con las columnas exactas y, en una hoja aparte, las
  listas reales de deportes, grupos y planes como desplegables: el club elige en vez de
  escribir.
- **Una pasada de revisión que no escribe nada** y devuelve *todos* los errores juntos, con
  fila, columna y qué se esperaba. Un solo error y no se carga nada: o entra todo o nada.
- **El informe de errores vuelve como Excel**, el mismo archivo con una columna al final que
  dice qué está mal en cada fila. Una lista en la consola no le sirve al club.
- **Ensayo en test antes de la carga real**, mirando en pantalla si los alumnos quedaron
  como el club espera, con vuelta atrás si salió mal.
- **Manual de primera carga**, en criollo y con capturas: orden de los pasos, qué significa
  cada columna, ejemplos de lo que sí y lo que no, y los errores típicos ya conocidos (DNI
  con puntos, el período `092026` que Excel convierte en `92026`, el monto `52.000` que se
  leía como $52, los espacios de más al final).

## La pantalla (propuesta, pendiente de maqueta)

- **Dónde:** entrada "Primera carga" en la sección Sistema del menú del ADMIN. Aparece solo
  mientras la base no tiene alumnos y desaparece cuando terminó.
- **Cómo:** una sola pantalla con cuatro pasos apilados, cada uno en una tarjeta con su
  estado (pendiente / listo / con error):
  1. **Catálogos** — cuántos deportes, grupos y planes hay; listo cuando hay un plan con precio.
  2. **Plantilla** — `Descargar`.
  3. **Revisión** — se sube el archivo, no escribe nada, muestra los errores y deja
     `Descargar` el Excel marcado.
  4. **Carga** — se habilita con cero errores; muestra el resumen (alumnos, deudas, importe
     total) y pide confirmar. Después queda `Deshacer` mientras nadie haya cobrado.
- **Estética:** la que ya existe. Tarjetas y tabla estándar, colores de estado del design
  system, botones de un verbo (`Descargar`, `Revisar`, `Cargar`, `Deshacer`), pasos apilados
  en celular. Ver `docs/03-diseno-ui/wings-design/SKILL.md`.

## Lo que queda abierto

- **Falta una forma de cargar una deuda suelta a mano.** Hoy solo se puede condonar, no
  agregar. Con eso, cualquier excepción —el que quedó afuera del Excel, el que arrancó el
  mes pasado, el que pagó por fuera— se resuelve sin inventar reglas nuevas. Para Claude es
  lo más importante de esta lista.
- **¿Se permite dar de alta a mano con fecha de ingreso de un mes anterior?** Si la
  respuesta es no, A43 desaparece solo y no hace falta ninguna pregunta en pantalla.
- **¿Sigue el alta generando la cuota?** Es lo que implementó Codex en `b3619bf` (A2).
- **Las 5 pruebas de A43 en `CuotaAltaEstadoTest` quedan en rojo** y hay que reescribirlas
  con la regla que salga de esto. No tomarlas como criterio vigente.
- **El Excel real:** si Vanina ya tiene una planilla, el formato se copia de la de ella.
- **La base de test** tiene 60 alumnos cargados a mano: hay que vaciarla para ensayar la
  carga desde cero, que es lo que va a pasar en el club.
- **Los 58 defectos de PRU-02 no se mezclan con esto.** Muchos son de la misma familia
  (filtros que se rompen en el celular, pantallas más altas que el monitor) y se arreglan de
  una pasada con un criterio escrito, no defecto por defecto.
