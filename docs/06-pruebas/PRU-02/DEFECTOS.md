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

### A2. Cobranza dice 60 deudores y el dashboard dice 20 · Frena · implementado, pendiente de verificación

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

### A17. La ficha del alumno no tiene botón para cobrar · Frena · verificado

En la ficha del alumno (`/alumnos/{id}`) se ven sus cuotas impagas con el botón **Condonar**, pero no existe ningún botón para **Cobrar**. Para cobrarle a quien está parado en el mostrador hay que salir, ir a Caja, tocar Cobrar y buscarlo de nuevo en un desplegable.
Captura: `evidencia/audit_admin_alumnos_show_desktop.png`.

### A18. Cobranza en celular rompe la tabla y superpone el texto · Frena · verificado

En pantallas de 375px (`/cobranza`), las columnas de la tabla colisionan: los títulos "ALUMNO" y "DEPORTE" se imprimen encimados ("AEBDORINE"), los nombres de los chicos se montan sobre la disciplina ("Morales, Patín Sofía"), los filtros se truncan a dos letras y el botón Ver queda cortado por el borde de la pantalla.
Captura: `evidencia/audit_admin_cobranza_mobile.png`.

### A19. Las barras de filtros en celular colapsan en cuadrados mudos y desbordan · Molesta · verificado

En Alumnos, Clases, Movimientos, Profesores e Historial de Cajas, los filtros se aprietan en una sola fila horizontal en el celular: los selectores quedan reducidos a pequeños cuadrados mudos con flechas sin texto, y los botones **Filtrar** y **Limpiar** quedan flotando afuera de la tarjeta blanca.
Capturas: `evidencia/audit_admin_clases_index_mobile.png`, `evidencia/audit_admin_movimientos_index_mobile.png`, `evidencia/audit_admin_profesores_index_mobile.png`, `evidencia/audit_admin_alumnos_index_mobile.png`, `evidencia/audit_admin_cajas_historial_mobile.png`.

### A20. El botón "Nuevo" del cashflow en celular tapa el saldo · Molesta · verificado

En `/cashflow` visto desde un teléfono, el botón **Nuevo** se monta directamente sobre el número del saldo inicial y balance ("$1.570.000"), tapando la cifra, y el contador de movimientos desborda hacia la derecha fuera de la tarjeta.
Captura: `evidencia/audit_admin_cashflow_index_mobile.png`.

### A21. Cobranza duplica las filas de alumnos con más de un deporte · Molesta · verificado

Un alumno anotado en dos actividades (como Sofía Morales en Patín y Fútbol) aparece dos veces consecutivas en el listado de Cobranza con el mismo nombre y apellido, sin totalizar su deuda global ni clarificar a simple vista a qué corresponde cada fila.
Captura: `evidencia/audit_admin_cobranza_desktop.png`.

### A22. Cobranza no muestra montos de dinero en el resumen superior · Falta · verificado

Las tarjetas superiores de Cobranza muestran conteos de alumnos (`Total Activos 60`, `Al día 0`, `En plazo 0`, `Morosos 0`, `Deudores 60`), pero no dicen cuánta plata representa la deuda ni cuánto dinero falta recaudar. Quien gestiona no sabe cuántos pesos están en juego.
Captura: `evidencia/audit_admin_cobranza_desktop.png`.

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

### A43. El alumno antiguo cargado a mano recibe el descuento de bienvenida · Frena · verificado

**Regla decidida por Carlos el 26/09/2026.** El corte del sistema
(`inscripcion_fecha_corte`, el mismo parametro que ya usa la inscripcion) separa al
alumno viejo del nuevo, y la cuota que nace en el alta es la del **mes de la fecha de
ingreso**, no la del mes de la carga.

- **Ingreso anterior al corte:** el alta no genera cuota sola. La pantalla frena antes de
  guardar y pregunta si se le genera la cuota de este mes; decide la persona. Si dice que
  si, va el **mes completo**: ya uso el mes entero, no corresponde el descuento de
  bienvenida. Es el caso del que venia desde antes y no estaba en el padron, que se corre
  una sola vez.
- **Ingreso desde el corte:** se genera la cuota del mes de ingreso con el porcentaje
  segun el dia.
- **Ingreso futuro:** se acepta dentro del mes en curso y el siguiente; mas alla, no.
- Al no crear deuda para el alumno viejo, la importacion del padron deja de rechazar el
  archivo entero.

Criterios fijados en `CuotaAltaEstadoTest` (5 pruebas escritas el 26/09, **en rojo hasta
que Codex implemente**). Implementa Codex; verifica otro agente.

Salió de la verificación cruzada de la implementación de A2/B2 (Claude, 23/09).

`PagoCuotaService::crearCuotaAlta` crea la cuota del **mes en curso**, pero calcula el
porcentaje con **el día de la fecha de ingreso**, que puede ser de hace años. Un alumno que
entró al club el 20 de enero de 2020 y se carga hoy queda debiendo **el 65% de septiembre**,
por un mes que usó entero. El descuento de bienvenida existe para quien arranca a mitad de
mes, no para quien se carga tarde al sistema.

Está fijado en una prueba —`CuotaAltaEstadoTest::test_porcentaje_configurable_dia_de_ingreso_y_solo_mes_actual`—
así que hay que decidir la regla antes de tocarlo: **el porcentaje debería salir del día de
ingreso solo cuando el ingreso es de este mes; si es anterior, la cuota va completa.**

**Y arrastra un segundo problema:** desde ahora, cargar a mano un alumno antiguo le crea
deuda del mes en curso. Si después se importa el padrón con el saldo inicial de ese mismo
mes, la importación **rechaza el archivo entero** —"ya existe una deuda para ese alumno y
período"—. Los dos caminos de carga inicial chocan.

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

### B2. El estado de cobranza no distingue "sin deuda" de "nunca pagó" · Frena · implementado, pendiente de verificación

Causa confirmada de A2 en `app/Services/CobranzaEstadoService.php`.
**Commit de corrección: `b3619bf`**. Eliminado "nunca pagó" del cálculo individual y
masivo; DEUDOR exige mes cerrado impago. La cuota del alta evita dejar sin deuda a los
nuevos. [Pruebas y límites](IMPLEMENTACION-A2.md). Pendiente de verificación independiente;
no cerrado ni desplegado.

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

---

## Plan de acción

El orden no es por gravedad suelta: es por lo que traba al club primero. Cada bloque se
cierra con la verificación de otro agente, como manda `AGENTS.md` §6a.

### Bloque 1 — Que el mostrador pueda trabajar

Es lo único que frena plata todos los días. Nada del resto se toca hasta que esto esté.

| Orden | Qué | Defectos |
|---|---|---|
| 1.1 | Diagnóstico y enmienda implementados en `b3619bf`; **pendiente verificación independiente en código y pantalla**, sin deploy | A2, B2 |
| 1.2 | Cobranza: mostrar cuánto debe cada uno, el total adeudado, y poder cobrar desde ahí | A1, A21, A22 |
| 1.3 | Cobrar desde la ficha del alumno, y ver ahí sus recibos | A17, A34 |
| 1.4 | Poder cobrar por adelantado, que el motor ya soporta | A3 |
| 1.5 | Que el usuario sepa por qué no se guardó, y que se le avise antes de perder lo cargado | A4, A14 |
| 1.6 | Filtrar los profesores por el deporte de la clase | A5 |
| 1.7 | Apertura de caja con saldo inicial y arqueo | A25 |

### Bloque 2 — El dueño deja de ser un operativo

Cambio de modelo, no de pantalla: se diseña con Carlos antes de repartirlo.

| Orden | Qué | Defectos |
|---|---|---|
| 2.1 | Que cobrar no le abra caja al admin, y que no se valide a sí mismo | A13, B1 |
| 2.2 | Pantalla de caja propia del dueño: la del que mira, no la del que rinde | A13 |
| 2.3 | Un tablero de dueño que sirva para decidir | A23 |

### Bloque 3 — Que se entienda

| Orden | Qué | Defectos |
|---|---|---|
| 3.1 | Configuración con nombres humanos, agrupada, explicada y con validación real | A11 |
| 3.2 | Consistencia: un verbo por botón, los puntos, los interruptores, el botón Nuevo | A6, A8, A9, A10, A32, A35 |
| 3.3 | Listado de alumnos y ficha: que el dato esté donde se busca | A7, A37 |
| 3.4 | Permisos: mismo trato para todos los roles y una pantalla de "sin permiso" que sirva | A29, A30, A31 |
| 3.5 | Inicio del operativo y del profesor: que digan por dónde empezar el día | A12, A24 |
| 3.6 | Textos en castellano y formularios que no pidan lo que un club de chicos no tiene | A26, A27, A38 a A42 |

### Bloque 4 — El celular

**Decisión pendiente de Carlos:** si el celular entra en esta versión o después. Hoy el
mostrador trabaja en computadora, pero el profesor toma asistencia con el teléfono.

| Orden | Qué | Defectos |
|---|---|---|
| 4.1 | Asistencia en el celular, que es lo único que hoy se usa así | A33 |
| 4.2 | Tablas y filtros que no se rompan en pantalla chica | A18, A19, A20, A28, A36 |

### Bloque 5 — Los datos de prueba

Sin esto, ninguna prueba nueva significa nada. Los datos los armó Claude y están mal.

| Orden | Qué | Defectos |
|---|---|---|
| 5.1 | Alumnos que sean chicos, con sus tutores y sus teléfonos | B13 |
| 5.2 | Catálogo sin los servicios que el club no paga, con los rubros que sí usa | B13 |
| 5.3 | Saldos iniciales que decida Carlos, no inventados | B13 |

### Bloque 6 — Lo que el club necesita y no existe

Después de la prueba, salvo que Carlos decida adelantarlo.

| Orden | Qué | Defectos |
|---|---|---|
| 6.1 | Canchas y su alquiler por hora: el gasto más grande del club | B5, A15 |
| 6.2 | Clases particulares | B6 |
| 6.3 | Motivos y avisos de las excepciones del contrato | B3 |
| 6.4 | Reportes: las cuatro preguntas del cierre del día | B12 |
| 6.5 | Adelanto del profesor atado a su liquidación, y dónde pagarle | B9, B10 |
| 6.6 | Familia y hermanos | B8 |
| 6.7 | Stock y compra de mercadería | B7 |
| 6.8 | Punitorios por mora | B11 |
| 6.9 | Clases sin asistencia en el resumen diario | B4 |
| 6.10 | Carga del horario con días y horarios distintos en una sola vez | A16 |

### Cómo se trabaja cada bloque

1. Lo hace un agente y **lo verifica otro**, en pantalla y en el código.
2. Cada defecto cerrado se marca acá, con el commit que lo cierra.
3. Si al arreglar uno aparece otro, se agrega a la lista antes de seguir.

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
