# Primera carga por Excel — decisiones de Carlos del 26/09/2026

> **P1 aprobada e implementada el 05/10/2026; pendiente de verificación independiente por Gemini.**
> [Entrega, pruebas y capturas](../06-pruebas/PRU-02/P1-IMPLEMENTACION-2026-10-05.md).
> Sin despliegue ni limpieza de bases. Importadores antiguos conservados hasta aceptar P1.

## Aclaración de Carlos del 05/10/2026

La maqueta completa está aprobada. Carlos informó que producción no tiene alumnos,
deudas ni pagos; sí usuarios y catálogos reales que se conservan. Esta entrega no
inspecciona ni modifica producción. Se inicializa primera_carga como PENDIENTE,
independientemente del conteo de alumnos. No hace falta resolver migración de alumnos
existentes para Wings; no aplicar esta entrega por su cuenta a otro club ya operativo.
La plantilla generada está vacía: no exporta alumnos de ninguna base.
Las aprobaciones entonces pendientes en el antecedente del 04/10 quedan resueltas.

## Decisiones de Carlos del 04/10/2026

**A43, decisión posterior a P0:** el alta manual de ingreso en un mes cerrado
pregunta si genera la cuota corriente completa. Elegir «No» evita únicamente la
cuota; inscripción sigue su regla por DNI. No se crea cuota histórica. Mes corriente/futuro mantiene
porcentaje del día y no pregunta. Esta excepción no implementa ni altera P1.

- **Formato aprobado por Carlos:** una fila por alumno y deporte en Alumnos, con sus
  datos, Debe inscripción, Tiene deuda y **12 pares Período / Monto**. No hay hoja Deudas
  ni repetición de DNI/deporte para cada mes. Tiene deuda se refiere a cuotas mensuales;
  inscripción es independiente. Los meses pueden venir desordenados.
- **Instructivo visual obligatorio:** indicaciones visibles junto a cada paso, celdas de
  ejemplo y casos completos. Una única hoja para completar. Catálogos no se completa;
  Guía reproduce los ejemplos dentro del archivo. Guardar/revisar/corregir/confirmar se
  explican con el mismo vocabulario de los botones. Este acuerdo de formato e instructivo
  quedó completado con la aprobación de la maqueta completa del 05/10.
- **Ingreso automático y obligatorio:** la primera carga no es una opción que ADMIN
  deba encontrar o entender leyendo instrucciones. Al ingresar a un club pendiente de
  primera carga, se abre directamente el recorrido y cada paso habilita el siguiente.
- **Acceso aprobado el 05/10:** no decidir si se
  muestra contando alumnos. Un primer alumno creado por menú no puede saltear la carga.
  Guardar primera carga pendiente/terminada; mientras esté pendiente, alta individual
  desde menú o acceso directo vuelve a preparación. El servidor debe aplicar la misma
  regla, no alcanza esconder un botón. Catálogos/configuración necesarios siguen accesibles.

- **Inscripción:** los alumnos que entran por el Excel **no pagan inscripción**, salvo que el
  propio archivo lo aclare. El Excel lleva una columna para marcar quién la debe, fila por
  fila. Los $5.000 siguen corriendo normalmente para todo el que se carga a mano después.
- **La maqueta se mira en el navegador**, no en un documento: una página de prueba que no
  toca el sistema, con los pasos, los mensajes de error y cómo vuelve el Excel corregido.
  Aprobada el 05/10; las capturas de implementación están en la entrega enlazada arriba.
- **La base del sitio de prueba se rehace con este Excel** cuando esté listo. No hace falta
  conservar lo que hay cargado hoy.

## Por qué se cambió el enfoque

Se estaban gastando horas en una excepción: cómo tratar al alumno antiguo que se carga a
mano (A43). Cada regla nueva abría dos casos de borde más — el que ingresó este mes antes
del corte, el que ingresa el mes próximo, el que no entró en el padrón, quién paga
inscripción. Carlos cortó: *"estamos pensando e implementando todo mal"*.

## La decisión

1. **La primera carga es un Excel único** con los alumnos completos **y su deuda**. Un solo
   script lo importa. Hoy los dos importadores que existen —`wings:importar-padron` y
   `wings:importar-deuda-inicial`— cargan deuda de alumnos que ya tienen que estar cargados
   a mano: **los dos se retiran cuando el nuevo funcione**, para no dejar tres caminos
   de carga inicial. Mientras tanto se conservan.
2. **Lo que se carga a mano después sigue las reglas del sistema:** para ingreso del
   mes corriente o futuro, cuota de ese mes con porcentaje del día, más inscripción.
   Para mes cerrado aplica la decisión posterior A43 del 04/10 explicada arriba.
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

## La pantalla aprobada e implementada

**Maqueta entregada el 04/10 y aprobada por Carlos el 05/10:**
[Abrir en navegador](maqueta-primera-carga/index.html). Cuatro pasos, ejemplos con todos
los errores por fila y Excel descargable con columna Errores. **Alumnos es la única hoja
que se completa**, con 12 pares de deuda en la misma fila. Catálogos alimenta desplegables;
Guía contiene el instructivo y ejemplos. [Formato y comprobaciones](maqueta-primera-carga/README.md).
P0 entregado en `ad24769`, suite 338/1932 verde; Gemini debe verificarlo antes de cerrar.

- **Dónde:** se abre automáticamente al ingresar como ADMIN a un club con primera carga
  pendiente. No depende del menú ni de que la base tenga cero alumnos. Solo completar
  correctamente la carga termina el recorrido. Una vez terminada, el ingreso habitual
  vuelve al Inicio. El menú no debe permitir saltear el recorrido dando un alta individual.
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
- **Alta manual:** P0 conserva fecha real y elimina el corte; A43 del 04/10 enmienda
  meses cerrados con elección de cuota corriente completa o ninguna cuota. Inscripción
  independiente por DNI. P1 no usa crearCuotaAlta ni aplica descuentos: carga solo lo declarado.
- **El Excel real:** si Vanina ya tiene una planilla, el formato se copia de la de ella.
- **Sitio de prueba:** Carlos pidió dejarlo como lo recibirá Vanina. Su limpieza y ensayo
  real se hacen en una operación separada y autorizada, no durante la implementación.
  La prueba de P1 usa únicamente la base descartable wings_testing_codex.
- **Los 58 defectos de PRU-02 no se mezclan con esto.** Muchos son de la misma familia
  (filtros que se rompen en el celular, pantallas más altas que el monitor) y se arreglan de
  una pasada con un criterio escrito, no defecto por defecto.

## Revisión del Excel con el formato aprobado

- Debe inscripción y Tiene deuda admiten Sí/No y son independientes. Inscripción queda
  en No por defecto para esta carga; se genera una vez por DNI si se marca Sí.
- Tiene deuda = No exige los 12 pares vacíos. Sí exige al menos un par completo.
- Cada par necesita período válido y monto pendiente mayor que cero. No se repite el
  mismo período para un alumno/deporte, aunque esté en otro par. No es el precio de hoy
  ni el importe original si hubo pago parcial: es lo que queda pendiente de ese mes.
- La plantilla tiene 38 columnas: 14 de datos/respuestas y 24 de los 12 pares. El informe
  agrega Errores como columna 39 (AM), al final de Alumnos; no altera los datos originales.
- DNI como texto; período mes/año (102026), compatible con cero inicial quitado por Excel.
  Montos 52000 o 52.000 significan $52.000. Los pares sin usar quedan realmente vacíos.
- Plantilla y ejemplos son distintos: la plantilla no contiene alumnos de muestra.
  Catálogos tiene listas del club; Guía no es una hoja a importar ni completar.
