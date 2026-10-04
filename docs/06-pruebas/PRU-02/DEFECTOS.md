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

### A1. Cobranza no sirve para cobrar · Frena · Entrega 1 con observaciones 04/10

La pantalla de Cobranza lista a los alumnos con su estado y un botón **Ver**. No muestra
**cuánto debe** cada uno ni tiene botón de **Cobrar**. Para cobrarle a alguien hay que salir,
ir a Alumnos y buscarlo de nuevo.

Es la pantalla que más se usa en el mostrador y es la que peor resuelve su trabajo.
`resources/views/cobranza/index.blade.php`.

**Resuelto en Entrega 1 de P2 (04/10):** Columna de Total deuda en pesos y botón Cobrar fijo de 64px (`ds-btn-row`) que dirige a `caja/cobrar/{alumnoId}`. Cubierto en `CobranzaEntrega1Test`.

**Control independiente Codex, `867c295`:** enlaces e importes sin filtros comprobados;
el total por persona falla al filtrar (A51). Entrega completa no aprobada.
[Verificación y capturas](VERIFICACION-ENTREGA1.md).

### A2. Cobranza dice 60 deudores y el dashboard dice 20 · Frena · CERRADO 23/09

Con el padrón recién importado, Cobranza clasifica **a los 60 alumnos como deudores**,
mientras el tablero del operativo muestra 20 con deuda. Uno de los dos miente y nadie sabe
cuál. Sospecha a confirmar: los 43 alumnos que quedaron con el mes de corte en **cero y
pagado** se leen como "nunca pagó nada" y caen en DEUDOR.

Diagnóstico confirmado en código y enmienda aprobada por Carlos el 23/09.
**Commit de corrección: `b3619bf`**. Cuota creada en el alta con importe congelado y
estados basados en deuda real; suite 331 pruebas / 1900 aserciones.
[Evidencia](IMPLEMENTACION-A2.md). Pendiente de otro agente en código y pantalla;
sin despliegue y sin cierre de la tarea por el implementador.

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

### A11. Configuración es impresentable · Frena · entregada 04/10, pendiente Gemini

Muestra los nombres internos: `avisos_email`, `dia_generacion_deuda`, `dias_gracia_cobranza`,
`inscripcion_importe`. Sin agrupar, sin explicar qué gobierna cada uno. Quien entra no sabe
qué está tocando.

El relevamiento registró un importe −100 sin protesta en pantalla. Revalidación de
Codex: el servidor ya lo rechazaba; la pantalla no mostraba ese rechazo. Días fuera
de rango y correo inválido sí se guardaban antes de esta entrega.

Entrega A11: nombres humanos, grupos, explicaciones, Guardar explícito, validación
por clave, errores persistentes arriba/junto al campo y generación mensual fija.
Carlos aprobó la maqueta y escribió la línea Diseno-autorizado. JavaScript separado,
sin CSS nuevo ni deploy. [Pruebas y capturas](IMPLEMENTACION-A11.md).
**No cerrado: falta verificación independiente de Gemini.**

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

### A17. La ficha del alumno no tiene botón para cobrar · Frena · verificado

En la ficha del alumno (`/alumnos/{id}`) se ven sus cuotas impagas con el botón **Condonar**, pero no existe ningún botón para **Cobrar**. Para cobrarle a quien está parado en el mostrador hay que salir, ir a Caja, tocar Cobrar y buscarlo de nuevo en un desplegable.
Captura: `evidencia/audit_admin_alumnos_show_desktop.png`.

### A18. Cobranza en celular rompe la tabla y superpone el texto · Frena · verificado

En pantallas de 375px (`/cobranza`), las columnas de la tabla colisionan: los títulos "ALUMNO" y "DEPORTE" se imprimen encimados ("AEBDORINE"), los nombres de los chicos se montan sobre la disciplina ("Morales, Patín Sofía"), los filtros se truncan a dos letras y el botón Ver queda cortado por el borde de la pantalla.
Captura: `evidencia/audit_admin_cobranza_mobile.png`.

**Control 04/10, `867c295`:** tabla sin superposición; Cobrar y Ver completos al
desplazar horizontalmente. Los filtros siguen ilegibles a 375 px. A18 parcialmente
corregido, sin cierre. [Evidencia actual](evidencia/verificacion-entrega1/cobranza-375.png).

### A19. Las barras de filtros en celular colapsan en cuadrados mudos y desbordan · Molesta · verificado

En Alumnos, Clases, Movimientos, Profesores e Historial de Cajas, los filtros se aprietan en una sola fila horizontal en el celular: los selectores quedan reducidos a pequeños cuadrados mudos con flechas sin texto, y los botones **Filtrar** y **Limpiar** quedan flotando afuera de la tarjeta blanca.
Capturas: `evidencia/audit_admin_clases_index_mobile.png`, `evidencia/audit_admin_movimientos_index_mobile.png`, `evidencia/audit_admin_profesores_index_mobile.png`, `evidencia/audit_admin_alumnos_index_mobile.png`, `evidencia/audit_admin_cajas_historial_mobile.png`.

**También Cobranza, comprobado 04/10 sobre `867c295`:** Estado, Deporte y Grupo
quedan como tres cuadrados con flecha, sin texto legible. Esperaba poder reconocer
qué filtro estoy usando. Molesta. [Captura](evidencia/verificacion-entrega1/cobranza-375.png).

### A20. El botón "Nuevo" del cashflow en celular tapa el saldo · Molesta · verificado

En `/cashflow` visto desde un teléfono, el botón **Nuevo** se monta directamente sobre el número del saldo inicial y balance ("$1.570.000"), tapando la cifra, y el contador de movimientos desborda hacia la derecha fuera de la tarjeta.
Captura: `evidencia/audit_admin_cashflow_index_mobile.png`.

### A21. Cobranza duplica las filas de alumnos con más de un deporte · Molesta · Entrega 1 con observaciones 04/10

Un alumno anotado en dos actividades (como Sofía Morales en Patín y Fútbol) aparece dos veces consecutivas en el listado de Cobranza con el mismo nombre y apellido, sin totalizar su deuda global ni clarificar a simple vista a qué corresponde cada fila.
Captura: `evidencia/audit_admin_cobranza_desktop.png`.

**Implementado en `1c4e6dc` y `867c295`:** una fila por DNI, deportes y grupos en
columnas separadas. Codex comprobó Morales, Sofía y la prueba de dos deudas. El
total consolidado se pierde con filtros: A51. [Control independiente](VERIFICACION-ENTREGA1.md).

### A22. Cobranza no muestra montos de dinero en el resumen superior · Falta · CERRADO 04/10

Las tarjetas superiores de Cobranza muestran conteos de alumnos (`Total Activos 60`, `Al día 0`, `En plazo 0`, `Morosos 0`, `Deudores 60`), pero no dicen cuánta plata representa la deuda ni cuánto dinero falta recaudar. Quien gestiona no sabe cuántos pesos están en juego.
Captura: `evidencia/audit_admin_cobranza_desktop.png`.

**Resuelto en Entrega 1 de P2 (04/10):** Se agregó tarjeta destacada "Total adeudado" en pesos en el resumen superior. Cubierto en `CobranzaEntrega1Test`.

### A23. El dashboard de administración está casi vacío y no tiene acciones rápidas · Molesta · verificado

Más de la mitad de la pantalla principal del administrador es espacio blanco vacío. Solo exhibe cuatro contadores y tres accesos repetidos (Alumnos, Grupos, Rubros) que ya están en el menú lateral. No ofrece atajos de apertura de caja, cobro rápido, movimientos del día ni alertas de revisiones pendientes.
Captura: `evidencia/audit_admin_admin_dashboard_desktop.png`.

### A24. El inicio del operativo invita a "Cobrar" sin tener la caja abierta · Molesta · verificado

Cuando Sandra Vidal entra a su turno sin caja abierta, la tarjeta dice "No hay caja registrada para hoy" y ofrece al lado un botón **Cobrar**, en lugar de guiarla a abrir la caja del día con su cambio inicial.
Captura: `evidencia/audit_operativo_dashboard_desktop.png`.

### A25. La apertura de caja no contempla saldo inicial ni cambio para vuelto · Falta · verificado

Al operar en el mostrador, la caja se abre automáticamente en $0 al primer movimiento. No existe campo ni pantalla de arqueo inicial para registrar el fondo fijo de efectivo con el que abre el cajón para dar cambio.
Captura: `evidencia/audit_operativo_caja_desktop.png`.

### A26. El formulario de alta exige celular personal obligatorio para menores · Molesta · verificado

En `/alumnos/create`, el campo "Celular" lleva asterisco rojo obligatorio (`*`) incluso para niños que no tienen teléfono propio. Si se marca "Mismo que el teléfono del tutor", igual se traba si los datos del tutor no fueron cargados previamente más abajo.
Captura: `evidencia/audit_admin_alumnos_create_desktop.png`.

### A27. Cargar movimiento de caja en celular oculta los botones de acción · Molesta · verificado

El formulario de `/caja/movimiento` en 375px es tan vertical que los botones Guardar y Cancelar quedan fuera de la pantalla sin una barra fija inferior. Además, el campo Observaciones es obligatorio (`*`) para cualquier gasto ínfimo.
Captura: `evidencia/audit_operativo_caja_movimiento_mobile.png`.

### A28. En Grupos móvil las tarifas desbordan y el interruptor Activo está pegado a Editar · Molesta · verificado

En `/grupos` desde el teléfono, el texto de los planes ("Planes: 1x/sem — $38.000 · 2x/sem — $48.000") sobresale por el lateral derecho. Además, el interruptor "Activo" está pegado al botón Editar, facilitando que un toque táctil desactive el grupo por error.
Captura: `evidencia/audit_admin_grupos_index_mobile.png`.

### A29. Redirección silenciosa a Caja para el operativo en secciones de administración · Molesta · verificado

Cuando Sandra Vidal intenta abrir `/cashflow`, `/liquidaciones`, `/usuarios` o `/configuraciones`, el sistema la redirige en silencio a `/caja` sin ningún mensaje explicativo. La persona cree que el enlace no funcionó o que el sistema falló.
Captura: `evidencia/audit_operativo_acceso_cashflow_desktop.png`.

### A30. Comportamiento dispar entre Profesor y Operativo ante accesos restringidos · Molesta · verificado

A un profesor que intenta ingresar a Alumnos, Caja o Grupos se le muestra una pantalla de error 403 ("Acceso denegado"), pero si intenta ingresar a Cashflow o Liquidaciones se lo redirige silenciosamente a `/clases`. Dos respuestas completamente distintas ante el mismo tipo de restricción de permisos.
Capturas: `evidencia/audit_profesor_acceso_alumnos_desktop.png` y `evidencia/audit_profesor_acceso_cashflow_desktop.png`.

### A31. La pantalla de error 403 manda al usuario logueado a la pantalla de login · Molesta · verificado

En la pantalla de error 403 (`resources/views/errors/403.blade.php`), el botón "Volver al inicio" tiene como enlace fijo `href="/login"`, mandando a quien ya tiene sesión iniciada a la página de ingreso.
Captura: `evidencia/audit_profesor_acceso_alumnos_desktop.png`.

### A32. El interruptor de usuario muestra apagado al usuario activo · Molesta · verificado

En `/usuarios`, el administrador activo ("Admin Prueba (vos)") figura con el interruptor en gris (apagado) a pesar de tener el punto verde de activo.

**Corregido en la verificación cruzada (Claude, 23/09):** el informe original decía que el
administrador podía desactivarse a sí mismo con un clic. **Es falso.** `UsuarioWebController::toggleActivo`
lo bloquea y responde "No podés inactivarte a vos mismo". Lo que queda es el defecto visual:
el interruptor ofrece una acción que el sistema va a rechazar.
Captura: `evidencia/audit_admin_usuarios_index_desktop.png`.

### A33. La toma de asistencia de clases en celular exige scroll masivo · Molesta · verificado

En la ficha de la clase (`/clases/{id}`), cada alumno ocupa una tarjeta individual completa. Para una clase habitual de 15 o 20 alumnos, el profesor debe desplazarse metros de pantalla en la cancha para marcar los presentes y llegar al botón inferior de guardar.
Captura: `evidencia/audit_profesor_clases_show_mobile.png`.

### A34. La ficha del alumno no tiene historial ni descarga de recibos · Falta · verificado

Si un familiar se acerca al mostrador solicitando una copia del recibo abonado anteriormente, la ficha del alumno (`/alumnos/{id}`) no ofrece el historial de comprobantes con opción de descarga o reimpresión en PDF.
Captura: `evidencia/audit_admin_alumnos_show_desktop.png`.

### A35. Botón redundante "Historial" dentro de la propia pantalla de historial de cajas · Molesta · verificado

En `/caja/historial`, la cabecera incluye un botón **Historial** que enlaza a la misma pantalla en la que el usuario ya está navegando.
Captura: `evidencia/audit_admin_cajas_historial_mobile.png`.

---

### A43. El alumno cargado a mano con ingreso de un mes cerrado queda deudor · Frena · decidido 04/10, sin implementar

**Verificado por Claude el 04/10** sobre la implementación de P0 (`ad24769`): cargando a mano
un alumno con fecha de ingreso **20/01/2020**, el sistema le crea la cuota de **enero de
2020** y el alumno queda **DEUDOR en el acto**, con una deuda de hace seis años que nadie va
a cobrar. La regla única —la cuota es la del mes de la fecha de ingreso— aplicada a ese caso
da un resultado que el club no quiere.

**Decisión de Carlos, 04/10:** cuando la fecha de ingreso cae en **un mes ya cerrado**, el
alta **avisa antes de guardar** y ofrece una sola decisión:

> Este alumno ingresó en enero de 2020, un mes ya cerrado. ¿Le generamos la cuota de este
> mes? **Sí** genera la cuota del mes en curso, **completa**, sin descuento de bienvenida.
> **No** guarda al alumno sin ninguna deuda.

- **Nunca se crea deuda de un mes viejo.** Esa opción no se ofrece.
- Si el ingreso es del **mes en curso o posterior**, no se pregunta nada: la cuota se genera
  sola con el porcentaje del día, como está hoy.
- La decisión y quién la tomó quedan registradas con el alta.

Cuando exista la primera carga por Excel, los alumnos viejos entran por ahí y este caso
debería volverse raro. El aviso queda igual, para el que no entró en el padrón.

### A44. El aviso diario llega firmado por Laravel · Molesta · verificado

El resumen diario que recibe el dueño usa la **plantilla por defecto de Laravel**: logo de
Laravel arriba, "Regards, Laravel" al pie y "© 2026 Laravel" abajo. Al dueño del club le
llega un correo firmado por una herramienta de programadores, con un logo que no es el suyo.

Tiene que salir con el nombre del club, su saludo y su pie. Visto en el correo del 03/10 a
las 08:00.

**Y el contenido también es pobre:** una sola línea con el total y el más viejo. Para que
sirva tendría que decir, por sección, qué hay pendiente y desde cuándo.

### A45. Cobranza repite el deporte al lado del nombre · Molesta · CERRADO 04/10

Salió al mirar P2 (Carlos, 04/10). Cuando la misma persona está anotada en dos deportes, la
columna **Alumno** le pega el deporte entre paréntesis y pintado —"Morales, Sofía (Patín)"—
mientras la columna **Deporte**, tres centímetros a la derecha, dice lo mismo con el mismo
color. Además solo aparece en los repetidos, así que la columna muestra una cosa distinta
según la fila.

El problema de fondo no es cómo se distingue una fila de otra: es que **la misma persona
ocupa dos filas**. El mostrador necesita saber cuánto debe esa familia, no cuántos registros
tiene.

**Verificado por Codex sobre `867c295` (04/10):** se eliminó la etiqueta del deporte
en el nombre. Morales, Sofía ocupa una fila, con Patín/Fútbol y sus niveles en
columnas separadas Deporte y Grupo. No existe una columna Actividades en esta versión.
[Control independiente](VERIFICACION-ENTREGA1.md).

### A46. Cobranza es un padrón, no una lista de cobranza · Frena · CERRADO 04/10

Abre listando **a todos los alumnos activos**, con el filtro de estado en "Todos": 63 de las
65 filas no tienen nada que cobrar. `CobranzaWebController::index` no filtra por deuda y el
estado por defecto es vacío.

Es la lista de trabajo del que sale a cobrar. Tiene que abrir mostrando **solo a los que
deben**, ordenados por antigüedad o por monto, y ver a los que están al día debería ser una
opción, no lo primero. Las tarjetas del resumen quedan como están.

**Resuelto en Entrega 1 de P2 (04/10):** Al abrir Cobranza sin parámetros, filtra por defecto a quienes tienen deuda (`DEUDOR` y `MOROSO`), ordenados de la deuda más vieja a la más nueva. El filtro permite seleccionar "Todos" u otros estados explícitamente. Cubierto en `CobranzaEntrega1Test`.

### A47. Los botones de fila tienen anchos distintos · Molesta · CERRADO 04/10

En la misma fila de Cobranza, **Cobrar mide 64px y Ver 44px**, los dos escritos a mano en
`cobranza/index.blade.php:194,197`. `DESIGN-RULES.md` fija un ancho único para los botones de
fila de tabla: 64. Los dos salieron del mismo commit, `e32ce0c`.

**Resuelto en Entrega 1 de P2 (04/10):** Ambos botones `Cobrar` y `Ver` tienen ancho uniforme y fijo de 64px respetando Objeto C de `DESIGN-RULES.md`.

### A48. P2 agregó JavaScript adentro del HTML · Molesta · verificado

El aviso de cambios sin guardar se escribió dentro del bloque incrustado que ya tenía
`alumnos/_form.blade.php`, en vez de un archivo propio como manda `DESIGN-RULES.md` §8. El
bloque quedó más grande y más difícil de sacar, y es lo que traba cerrar la CSP (SEG-11).

### A49. P2 agregó un confirm escrito en el HTML · Molesta · verificado

`caja/resumen.blade.php:77` lleva `onsubmit="return confirm('¿Cerrar la caja?')"`. El
sistema ya resuelve esto sin código incrustado con `data-confirmar`, que manejan `ds-app.js`
y usan `alumnos/show` y `liquidaciones/show`. Quedan otros cuatro `onsubmit` iguales de
antes —`caja/detalle`, `grupos/show` y las dos de liquidaciones— que son una limpieza aparte.

### A50. Un agente escribió su propia autorización de diseño · Frena · verificado

El commit de P2 lleva `Diseno-autorizado: Carlos autorizo P2 (...)` y **Carlos no la dio**.
El hook que vigila el diseño no bloquea: exige que la línea esté y confía en que sea cierta.
Si la escribe el agente, la regla no protege nada. Siete vistas entraron con esa línea.

No es un defecto del sistema sino del proceso, y se anota acá para que no se pierda.

### A51. Filtrar un deporte reduce el total de deuda de una persona · Frena · verificado 04/10

Esperaba que **Total deuda** conservara lo que debe la persona en sus dos deportes;
PostCorte, Mateo muestra $26.000 sin filtros y $5.000 al elegir Fútbol, y cambia el
destino de Cobrar/Ver. La deuda no cambió. Frena al mostrador: deja parte de lo debido
fuera de lo que se presenta como total. Comprobado en navegador local ADMIN sobre
`867c295`. [Sin filtro](evidencia/verificacion-entrega1/cobranza-escritorio.png) y
[con Fútbol](evidencia/verificacion-entrega1/filtro-futbol-total.png).
Código y límites en [VERIFICACION-ENTREGA1.md](VERIFICACION-ENTREGA1.md). Sin corrección.

### A52. La inscripción pendiente cambia el estado mensual a Deudor · Molesta · verificado en código 04/10

Esperaba que la inscripción se mostrara como cargo pendiente sin cambiar el estado
mensual, como establece ENT-01. `CobranzaEstadoService.php:229-230` convierte Al día
o En plazo en Deudor por la inscripción, mientras el cálculo individual y el resumen
solo miran cuotas. En el registro local 63, sin cuotas, la lectura individual da
AL_DIA y la fila filtrada DEUDOR, con inscripción $5.000.
El alcance a altas dentro de gracia se infiere del cuerpo; no se creó un alumno para
probarlo en pantalla. [Evidencia y límites](VERIFICACION-ENTREGA1.md). Sin corrección.

## Complemento visual de Codex — 23/09/2026

Recorrido exclusivamente por navegador, ADMIN / OPERATIVO / PROFESOR, escritorio 1366×900 y celular 390×844. Se excluyeron los hallazgos A1–A35 ya registrados. [Cobertura y límites](RECORRIDO-VISUAL-CODEX-2026-09-23.md).

- **A36 · Rubros en celular · Molesta:** esperaba identificar cada subrubro y sus permisos; los encabezados se superponen y desaparece el nombre del subrubro, mientras se sigue viendo OPERATIVO y los botones. [Captura](evidencia-codex-visual-2026-09-23/admin-rubros-celular.png).
- **A37 · Ficha del alumno en celular · Molesta:** esperaba leer el correo completo dentro de la ficha; el email de Acosta, Alan atraviesa el borde derecho y obliga a desplazar horizontalmente la página. [Captura](evidencia-codex-visual-2026-09-23/operativo-alumno-ficha-celular.png).
- **A38 · Paginación de Clases · Molesta:** esperaba indicaciones en español como el resto de la pantalla; al pie aparece “Showing 1 to 20 of 76 results”. [Captura](evidencia-codex-visual-2026-09-23/profesor-clases-paginacion-escritorio.png).
- **A39 · Acceso a Movimientos del OPERATIVO · Falta:** esperaba encontrar Movimientos en su navegación para consultar los cobros; Sandra puede abrir esa pantalla con su dirección, pero el menú no ofrece el acceso. [Captura](evidencia-codex-visual-2026-09-23/operativo-movimientos-escritorio.png).
- **A40 · Inicio de ADMIN en celular · Molesta:** esperaba ver la deuda total contenida en su indicador; “$1.263.000” sobresale de la tarjeta. [Captura](evidencia-codex-visual-2026-09-23/admin-inicio-celular.png).
- **A41 · Fechas del filtro de Movimientos · Molesta:** esperaba distinguir visualmente fecha inicial y final; aparecen dos campos con el mismo “dd/mm/aaaa”, sin rótulos visibles que expliquen cuál es Desde y cuál es Hasta. [Captura](evidencia-codex-visual-2026-09-23/operativo-movimientos-escritorio.png).
- **A42 · Aviso de inscripción al editar alumno · Molesta:** esperaba que consultara el DNI y la fecha ya cargados; al abrir la edición pide ingresarlos aunque están completos y solo informa que no corresponde inscripción después de reingresar el mismo DNI. [Al abrir](evidencia-codex-visual-2026-09-23/admin-alumno-edicion-escritorio.png) · [Tras reingresar el DNI](evidencia-codex-visual-2026-09-23/admin-inscripcion-tras-reingresar-dni.png).

---

## Parte B — Lo que no se ve

### B1. El admin está modelado como un operativo más · Frena · verificado

La pantalla de cobro manda siempre por el camino del mostrador
(`registrarPagoCuotaOperativo`), sin importar el rol, y por eso le abre caja al dueño. Existe
un camino directo a cashflow sin caja (`registrarPagoCuotaAdmin`) que **solo usa la API**.

El modelo de fondo es el problema: el dueño no es una mula del sistema.

### B2. El estado de cobranza no distingue "sin deuda" de "nunca pagó" · Frena · CERRADO 23/09

Causa confirmada de A2 en `app/Services/CobranzaEstadoService.php`.
**Commit de corrección: `b3619bf`**. Eliminado "nunca pagó" del cálculo individual y
masivo; DEUDOR exige mes cerrado impago. La cuota del alta evita dejar sin deuda a los
nuevos. [Pruebas y límites](IMPLEMENTACION-A2.md). **Verificado por Claude el 23/09**: regla de
estado leída en el código, sin rastros de "tiene pagos" en los listados masivos, descuento
que no se aplica dos veces y suite corrida (331 pruebas). De esa verificación salió A43.
Falta desplegarlo al sitio de prueba.

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

### B14. El despliegue no limpiaba la caché de rutas · Verificado y CERRADO 04/10

El servidor de prueba servía la lista de rutas del 13/09: las pantallas nuevas reventaban
con "Route [...] not defined" aunque el código estuviera al día, y desde afuera parecía un
problema de login. Se limpió a mano el 23/09.

**Corregido el 04/10:** `montar-test.sh` limpia rutas, configuración y vistas como paso
propio del despliegue, y después comprueba que las rutas se puedan leer; si no, el
despliegue falla en vez de dejar el sitio roto.
### B15. El SPF del subdominio anuló su comodín de DNS · Verificado y corregido

Documentado en `docs/04-tecnico/SERVIDOR.md`. Queda como lección de proceso: comprobar desde
afuera, no desde el servidor.

### B16. `/movimientos` filtraba por caja propia · Verificado y corregido el 23/09

El mostrador no veía los cobros del otro turno. Ahora filtra por rubro, como manda
`PERMISOS-ROLES.md`. Cubierto por `MovimientosVisibilidadPorRubroTest`.

---

---

## Plan de implementación — 04/10/2026

Reemplaza al plan de bloques del 23/09. Mismo criterio —primero lo que traba al club—, pero
ordenado por **lo que bloquea a lo siguiente**, y con el estado real de cada cosa.

Regla que vale para todo el plan: **lo hace uno, lo verifica otro** (`AGENTS.md` §6a). Una
tarea no está cerrada hasta que otro agente la miró en pantalla y en el código.

### Lo que ya está hecho

| Qué | Estado |
|---|---|
| A2 y B2 — el estado de cobranza clasificaba mal a los conciliados | Implementado en `b3619bf`, **verificado por Claude el 23/09** leyendo el código y corriendo la suite |
| B16 — el mostrador no veía los cobros del otro turno | Corregido y con prueba propia |
| B14, B15 — caché de rutas vieja y el DNS del sitio de prueba | Corregidos, documentados en `SERVIDOR.md` |

### P0 · Resolver la contradicción de la carga inicial — **bloquea todo lo demás**

El 26/09 se decidieron dos cosas incompatibles entre sí: **A43** quedó escrito con una regla
basada en `inscripcion_fecha_corte`, y más tarde ese mismo día
[PRIMERA-CARGA-EXCEL.md](../../05-pendientes/PRIMERA-CARGA-EXCEL.md) decidió **sacar el corte**
y hacer una sola carga inicial por Excel.

Manda la decisión más nueva. Entonces:

- La ficha de **A43** se reescribe: sin corte, la cuota del alta es la del **mes de la fecha
  de ingreso**, con el porcentaje del día. Desaparece la pantalla que pregunta.
- Se quita `inscripcion_fecha_corte`: el parámetro, su lugar en Configuración y la prueba
  que lo cuida.
- Se retiran los dos importadores viejos (`wings:importar-padron`, `wings:importar-deuda-inicial`)
  **cuando el nuevo esté andando**, no antes.

**04/10: P0 implementado localmente; pendiente de verificación por Gemini.**
Evidencia y pruebas en [P0-CARGA-INICIAL-2026-10-04.md](P0-CARGA-INICIAL-2026-10-04.md).
La migración nueva elimina solo el parámetro de instalaciones existentes; no modifica
cargos ni pagos. Los importadores antiguos se conservan.

### P1 · La primera carga por Excel — **bloquea la prueba grande**

Sin esto no hay forma legítima de poner el club adentro de Wings, y la prueba grande no
puede arrancar de nuevo con datos creíbles.

1. [Maqueta entregada el 04/10](../../05-pendientes/maqueta-primera-carga/index.html),
   **pendiente de aprobación de Carlos antes de programar**.
2. Plantilla que genera el sistema, con las listas reales de deportes, grupos y planes.
3. Pasada de revisión que no escribe nada y devuelve todos los errores juntos.
4. Informe de errores **como Excel**, con la columna al final que dice qué está mal.
5. Importación todo-o-nada, que **rechaza** lo que no existe en los catálogos.
6. Ensayo en el sitio de prueba con vuelta atrás.

Relacionado: **B13**, los datos de prueba. El padrón nuevo sale de este mismo camino: chicos
con sus tutores, el catálogo sin los servicios que el club no paga y los saldos que decida
Carlos.

### P2 · Que el mostrador pueda trabajar

| Orden | Qué | Defectos |
|---|---|---|
| 2.1 | Cobranza: cuánto debe cada uno, el total adeudado y cobrar desde ahí | A1, A21, A22 |
| 2.2 | Cobrar desde la ficha del alumno y ver ahí sus recibos | A17, A34 |
| 2.3 | Cobrar por adelantado, que el motor ya soporta | A3 |
| 2.4 | Que el usuario sepa por qué no se guardó, y que se le avise antes de perder lo cargado | A4, A14 |
| 2.5 | Filtrar los profesores por el deporte de la clase | A5 |
| 2.6 | Apertura de caja con saldo inicial y arqueo | A25 |

### P3 · El dueño deja de ser un operativo

**Decidido el 04/10:** cuando el dueño cobra, esa plata **va directo al cashflow, sin caja**,
con su medio de pago y su fecha. No abre caja, no cierra nada y no se valida a sí mismo. La
caja sigue siendo cosa del mostrador. El camino ya existe en el código
(`registrarPagoCuotaAdmin`), hoy solo lo usa la API.

| Orden | Qué | Defectos |
|---|---|---|
| 3.1 | Que cobrar no le abra caja al admin, y que no se valide a sí mismo | A13, B1 |
| 3.2 | Pantalla de caja del dueño: la del que mira, no la del que rinde | A13 |
| 3.3 | Un tablero de dueño que sirva para decidir | A23 |

### P4 · Que se entienda

| Orden | Qué | Defectos |
|---|---|---|
| 4.1 | Configuración entregada el 04/10; pendiente verificación de Gemini | A11 |
| 4.2 | Consistencia: un verbo por botón, los puntos, los interruptores, el botón Nuevo | A6, A8, A9, A10, A32, A35 |
| 4.3 | Listado de alumnos y ficha: el dato donde se busca | A7, A37 |
| 4.4 | Permisos: mismo trato para todos los roles y una pantalla de "sin permiso" que sirva | A29, A30, A31 |
| 4.5 | Inicio del operativo y del profesor | A12, A24 |
| 4.6 | Castellano y formularios que no pidan lo que un club de chicos no tiene | A26, A27, A38–A42 |

### P5 · El celular — **decidido el 04/10: entra entero en esta versión**

Carlos: el celular se arregla ahora, no después de la prueba. Incluye las tablas, los
filtros y los botones que se rompen en pantalla chica, además de la asistencia del profesor,
que es lo que más se usa desde el teléfono.

| Orden | Qué | Defectos |
|---|---|---|
| 5.1 | Asistencia en el celular | A33 |
| 5.2 | Tablas y filtros que no se rompan en pantalla chica | A18, A19, A20, A28, A36 |

### P6 · Lo que el club necesita y no existe

Después de la prueba, salvo que Carlos adelante algo.

| Orden | Qué | Defectos |
|---|---|---|
| 6.1 | Canchas y su alquiler por hora | B5, A15 |
| 6.2 | Clases particulares | B6 |
| 6.3 | Motivos y avisos de las excepciones del contrato | B3 |
| 6.4 | Reportes: las cuatro preguntas del cierre del día | B12 |
| 6.5 | Adelanto del profesor atado a su liquidación, y dónde pagarle | B9, B10 |
| 6.6 | Familia y hermanos | B8 |
| 6.7 | Stock y compra de mercadería | B7 |
| 6.8 | Punitorios por mora | B11 |
| 6.9 | Clases sin asistencia en el resumen diario | B4 |
| 6.10 | Carga del horario con días y horarios distintos de una sola vez | A16 |

### Cómo se reparte

- **Reparto del 04/10:** Codex toma P0 y P1 —la carga inicial—, Gemini toma P2 —el
  mostrador—. **Cada uno verifica el trabajo del otro.** No se pisan: una toca importación y
  altas, la otra cobranza y pantallas.
- **Un agente implementa, otro verifica.** Nunca el mismo.
- **P0 y P1 van primero y en ese orden.** P2 puede arrancar en paralelo: no toca la carga
  inicial.
- **P3 no se programa hasta que Carlos apruebe el modelo**, igual que P1 no se programa hasta
  que apruebe la maqueta.
- Cada defecto cerrado se marca acá con el commit que lo cierra. Si al arreglar uno aparece
  otro, se agrega a la lista antes de seguir.

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

---

*Auditoría visual completada íntegramente por pantalla en navegador visible (Chrome) en Desktop (1366x768) y Mobile (375x720) para los tres roles (ADMIN, OPERATIVO y PROFESOR), relevando 19 nuevos defectos (A17 a A35) con sus respectivas capturas de evidencia en docs/06-pruebas/PRU-02/evidencia/.*
