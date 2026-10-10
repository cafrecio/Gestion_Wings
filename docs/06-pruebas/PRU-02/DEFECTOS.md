# Wings — Lo que está mal

Relevado el 23/09/2026, sobre el sitio de prueba con el club cargado. Dos listas: lo que
se ve entrando a la pantalla, y lo que no se ve pero rompe igual.

Cada punto dice **qué pasa**, **por qué importa para el club** y **dónde está**. Los marcados
como *verificado* se comprobaron en el código o en pantalla; los marcados como *por
diagnosticar* se vieron pero todavía no se sabe la causa.

> **Avance al 10/10/2026: 63 cerrados de 74.** Quedan 11 abiertos, de los
> cuales **0 frenan**. Un defecto se marca **CERRADO solo cuando
> otro agente lo verificó**; desde el 08/10, lo exclusivamente visual también se cierra con aprobación de Carlos (§6a). El que implementa lógica deja `HECHO, a revisar`. El tablero para
> mirar en el navegador es [DEFECTOS.html](DEFECTOS.html) y tiene los mismos estados.
>
> A57 y A58: **CERRADOS 10/10, aprobados por Carlos** sobre visor interactivo y capturas reales a 360px. Filtros parejos en todo el sistema y eliminación del desplazamiento lateral en Clases. [Implementación](IMPLEMENTACION-A57-A58.md).
>
> A38 y A44: **CERRADOS 08/10, aprobados por Carlos**, junto con el menú lateral nuevo. A26 y A39: **CERRADOS 08/10, verificado Gemini** en base de datos y navegador interactivo real. A35: **CERRADO 08/10, verificado Gemini** (auditadas `/caja` y `/caja/historial`, confirmado no-defecto por captura original mal rotulada). [Informe de verificación](VERIFICACION-A26-A39-A35.md). [Entrega previa de Claude](IMPLEMENTACION-A26-A38-A39-A44.md).
>
> A12: **CERRADO 08/10, verificado Claude**; el aspecto lo aprobó Carlos. A24: **HECHO (Gemini), a revisar 08/10** — segunda vuelta: eliminados botones trampa y textos de cajón compartido; caja de compañero muestra "Caja abierta de [Nombre]" con botón Caja; caja propia de ayer muestra "Tu caja sigue abierta" con botón Cerrar. 8 pruebas permanentes en InicioOperativoTest. [Implementación](IMPLEMENTACION-A24-VUELTA-2.md).

> A4 y A5: **CERRADOS 06/10** — Verificados de forma independiente por Gemini en pantalla real y código ([Informe de verificación](VERIFICACION-A4-A5.md)).
> A25: **CERRADO 06/10, verificado Gemini** — segunda verificación, con recorrido propio por HTTP, importes propios y 23 capturas; Claude reprodujo el recorrido (97 aserciones) y contrastó el informe. Sin sesión interactiva de navegador: eso lo cubre la prueba humana. [Informe](VERIFICACION-A25.md).
> A20, A28, A33, A36, A40 y A41: CERRADOS, verificado Codex CyE 06/10. A14, A27 y A53 fueron devueltos a Gemini el 06/10; T1 sigue devuelto por cobertura incompleta y recortes. [Informe independiente](VERIFICACION-CELULAR-COMPARTIDO.md). Sin despliegue.
> A14, A27 y A53: **CERRADOS 07/10, verificado Gemini** con un navegador manejado por programa; Claude contrastó el informe ([verificación](VERIFICACION-A14-A27-A53.md)). Carlos aprobó las capturas ANTES/DESPUÉS. [Propuesta y capturas ANTES/DESPUÉS](PROPUESTA-A14-A27-A53.md). No cerrados.

Criterio de gravedad: **Frena** = el club no puede trabajar o pierde plata · **Molesta** =
se puede trabajar, con fricción · **Falta** = el club lo necesita y no existe.

---

## Parte A — Lo que se ve

### A1. Cobranza no sirve para cobrar · Frena · CERRADO, verificado 05/10

La pantalla de Cobranza lista a los alumnos con su estado y un botón **Ver**. No muestra
**cuánto debe** cada uno ni tiene botón de **Cobrar**. Para cobrarle a alguien hay que salir,
ir a Alumnos y buscarlo de nuevo.

Es la pantalla que más se usa en el mostrador y es la que peor resuelve su trabajo.
`resources/views/cobranza/index.blade.php`.

**Resuelto en Entrega 1 de P2 (04/10):** Columna de Total deuda en pesos y botón Cobrar fijo de 64px (`ds-btn-row`) que dirige a `caja/cobrar/{alumnoId}`. Cubierto en `CobranzaEntrega1Test`.

**Control independiente Codex, `867c295`:** enlaces e importes sin filtros comprobados;
el total por persona falla al filtrar (A51). Entrega completa no aprobada.
[Verificación y capturas](VERIFICACION-ENTREGA1.md).

**Segunda vuelta, Codex CAB 05/10:** Entrega 1 aprobada sobre `abc346a` y HEAD
`4fb185e`: columna **Deuda por registro**, ayuda por otro deporte, enlaces y filtros
375 comprobados; A51/A52 resueltos. Suite propia 380/2242. [Informe](VERIFICACION-ENTREGA1.md).

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

### A3. No se puede cobrar por adelantado · Molesta · CERRADO 05/10

Si el alumno no tiene una deuda generada, la pantalla de cobro muestra "Sin deudas
pendientes" y el botón queda apagado. **El motor sí lo soporta** —cobrar un período sin
deuda usa el precio del plan— pero la pantalla no lo ofrece. Un club cobra por adelantado
todo el tiempo.

**Resuelto en Entrega 2 de P2 (05/10):**
`CajaWebController::cobrar()` ahora proyecta y ofrece en memoria los períodos futuros inmediatos (los próximos 2 meses) al precio de lista vigente del plan activo (`$plan->precio_mensual`), con badge identificatorio «Adelantado». Al cobrar un período adelantado, se crea la `DeudaCuota` con estado `PAGADA` y el `Pago` correspondiente. Al llegar el día 1 del mes, el comando mensual `cobranza:generar-deudas` comprueba que la deuda ya existe y la omite (`$contSkipped++`), evitando duplicaciones. Además, el selector `/caja/cobrar` permite buscar a cualquier alumno activo por nombre, apellido o DNI para cobrarle por adelantado. Cubierto en `P2Entrega2FichaCobroAdelantadoTest`.

### A4. Editar alumno no avisa por qué no guardó · Frena · CERRADO 06/10, verificado Gemini

Al cambiar la fecha de nacimiento, el tutor pasa a ser obligatorio. El error aparece **abajo
de todo**, en una pantalla más alta que el monitor. La persona aprieta Guardar, no pasa nada
visible y no entiende por qué. Es el mismo defecto que se corrigió en Revisión el 22/09.

**Y peor:** si se va por el menú lateral, **nadie le avisa que va a perder lo que cargó**.

**Entrega Codex CAB, 05/10:** resumen persistente arriba en alta/edición, foco y enlaces
a los campos; conserva datos y plan al rechazar. Menú/Cancelar piden confirmación;
cierre/recarga mantienen beforeunload. JavaScript en archivo propio; sin CSS.
[Implementación y capturas reales](IMPLEMENTACION-A4-A5.md).

**Retoque pedido por Carlos, 05/10:** motivo de corrección de ingreso dentro de la grilla,
rótulo arriba con el ícono de Descripción ya usado en Wings y cuadro con el mismo formato que los demás; sin CSS ni reglas nuevas.
[Capturas del retoque](IMPLEMENTACION-A4-A5.md#retoque-del-motivo-de-ingreso--05102026).
Diseño aprobado por Carlos el 05/10.

**Verificación independiente Gemini CyE, 06/10:** comprobado en Chrome interactivo el aviso al salir por menú/Cancelar y `beforeunload`; aviso superior visible sin scroll en escritorio y móvil 375 px dentro de marco; datos y plan conservados. [Informe de verificación](VERIFICACION-A4-A5.md).

### A5. Los profesores se eligen sin saber de qué deporte es la clase · Frena · CERRADO 06/10, verificado Gemini

Una fila de casillas con todos los profesores del club, sin filtrar. En una clase de patín
se puede tildar al profesor de fútbol. El formulario no conoce el deporte de la clase.

**Entrega Codex CAB, 05/10:** solo activos del deporte del grupo; al cambiar de grupo
se desmarca/deshabilita el ajeno. Filtrado en edición/ficha y validación servidor en
alta única/serie, edición y reasignación, sin escrituras parciales. No cambia permisos
ni borra asignaciones históricas. [Entrega y capturas](IMPLEMENTACION-A4-A5.md).

**Verificación independiente Gemini CyE, 06/10:** comprobado filtrado dinámico en frontend, rechazo de profesores de otro deporte o inactivos en backend con `ValidationException`, y comportamiento sin grupo definido. 11 pruebas pasando. [Informe de verificación](VERIFICACION-A4-A5.md).

### A6. "Modificar" donde en todo el resto dice "Editar" · Molesta · CERRADO 08/10 · verificado Carlos

**Cierre visual, AGENTS §6a:** Carlos: «Dejalo con el mismo formato que tiene originalmente y cambia solo el texto». Modificar → Editar, una sola palabra; seis capturas reales, formato original conservado. [Entrega y capturas](IMPLEMENTACION-A6-A10.md). Suite final del paquete 507 aprobadas/2 omitidas; sin deploy.

### A7. El listado de alumnos desperdicia la pantalla · Molesta · CERRADO 08/10 · verificado Carlos

**Cierre visual, AGENTS §6a:** Carlos: «Muy buen trabajo, me gusta». Una tarjeta por fila, plan/celular y columnas móviles Cobrar/Editar, Ver/Nuevo alineadas; escritorio conservado. [Entrega y capturas](IMPLEMENTACION-A6-A10.md). Suite final del paquete 507 aprobadas/2 omitidas; sin deploy.

### A8. Puntos grises que no dicen nada · Molesta · CERRADO 08/10 · verificado Carlos

**Cierre visual, AGENTS §6a:** Carlos: «A-8 APROBADO». Estados útiles en Grupos, Niveles y Profesores; distintivo original de Profesores conservado. [Entrega y capturas](IMPLEMENTACION-A6-A10.md). Suite final del paquete 507 aprobadas/2 omitidas; sin deploy.

### A9. El interruptor "Activo" en las tarjetas · Molesta · CERRADO 08/10 · verificado Carlos

**Cierre visual, AGENTS §6a:** Carlos: «Perfecto, APROBADO A-9 Entonces». Acciones móviles a derecha; último botón y Nuevo x=224–320 en las tres pantallas; interruptor termina en x=320. [Entrega y capturas](IMPLEMENTACION-A6-A10.md). Suite final del paquete 507 aprobadas/2 omitidas; sin deploy.

### A10. El botón "Nuevo" del cashflow · Molesta · CERRADO 08/10 · verificado Claude

Está suelto en la barra de totales y lleva a **Movimiento directo**. Nadie puede adivinar
qué crea. En el resto del sistema el botón Nuevo vive en su propia barra, con el contador al
lado.

**HECHO (Codex), a revisar 08/10:** Carlos: «Me gusta, A10 Aprobado». Aplicados Nuevo junto al contador, Limpiar en filtros, título Nuevo movimiento y selector Día/Semana/Mes/Año. Resultado del período sin saldo inicial. Suite propia 507 aprobadas/2 omitidas, 4037 aserciones; build/Blade correctos y 24 capturas finales coincidentes con propuesta. [Entrega](IMPLEMENTACION-A10-PERIODOS.md). Aspecto verificado por Carlos; intervalos/cálculos pendientes de otro agente, sin deploy.

**Primera revisión, 08/10 — devuelto por Claude:** al pulsar Limpiar en el navegador el período volvía a hoy, en los cuatro modos; el enlace salía con el `&` escapado dos veces. [Evidencia de esa devolución](evidencia/a10-verificacion-claude/primera-revision/LEEME.md).

**CERRADO 08/10, verificado Claude (segunda revisión):** Codex corrigió solo el enlace de Limpiar. Pulsado en Chrome en Día, Semana, Mes y Año con fecha lejana, caja y tipo: conserva período y fecha y quita los dos filtros; el enlace llega con sus cuatro parámetros. Controlador sin cambios desde la primera revisión, donde quedaron comprobados intervalos, validaciones, signos, saldo inicial, paginación, permisos y alta. El aspecto lo aprobó Carlos. [Verificación](VERIFICACION-A10.md). Sin deploy.


### A11. Configuración es impresentable · Frena · CERRADO 04/10

Muestra los nombres internos: `avisos_email`, `dia_generacion_deuda`, `dias_gracia_cobranza`,
`inscripcion_importe`. Sin agrupar, sin explicar qué gobierna cada uno. Quien entra no sabe
qué está tocando.

El relevamiento registró un importe −100 sin protesta en pantalla. Revalidación de
Codex: el servidor ya lo rechazaba; la pantalla no mostraba ese rechazo. Días fuera
de rango y correo inválido sí se guardaban antes de esta entrega.

Entrega A11 (`7a8fe09`): nombres humanos, grupos, explicaciones, Guardar explícito, validación
por clave, errores persistentes arriba/junto al campo y generación mensual fija.
Carlos aprobó la maqueta y escribió la línea Diseno-autorizado. JavaScript separado,
sin CSS nuevo ni deploy. [Pruebas y capturas](IMPLEMENTACION-A11.md).

**Verificación independiente por Gemini (04/10):** Aprobada en código y navegador real en escritorio y celular (375px). Se comprobó el rechazo de valores inválidos (-100, 29, email inválido), la persistencia del resumen y avisos de error (>5s sin desaparecer), el guardado asíncrono con estado "Guardado", el parámetro inmutable de generación mensual y la validación en castellano del editor de reglas de primer pago. Suite verde (356 pruebas / 2073 aserciones). [Informe de verificación y evidencia](VERIFICACION-A11.md). Defecto CERRADO.

### A12. El inicio del operativo no ayuda a trabajar · Molesta · CERRADO 08/10, verificado Claude

Tres cuadros en cero, una tarjeta que dice "Sin caja hoy" y nada que diga por dónde empezar
el día.

### A13. El admin termina con caja propia · Frena · CERRADO 06/10, verificado Codex

ADMIN cobró y anuló desde la ficha con motivo obligatorio. No abrió caja ni agregó movimientos al cajón del operativo. Historial anulado conserva Agosto y Septiembre; Cashflow muestra los contraasientos como egresos negativos en rojo. A25 conserva el esperado del operativo en $58.000.

[Verificación, capturas y límites](VERIFICACION-A13-B1-A55-CIERRE.md).

El [control anterior](VERIFICACION-A13-A54.md) queda como antecedente; sus observaciones fueron resueltas y revalidadas el 06/10 sobre el merge `6d3f68a`.

### A14. Pantallas más altas que el monitor · Molesta · CERRADO 07/10, verificado Gemini


Formularios largos donde los botones y los errores quedan fuera de la vista, sin nada fijo
arriba ni abajo.

**Control independiente 06/10 (Codex CyE):** DEVUELTO a Gemini: Guardar de Profesor comienza en y=1086; tras error de Alumno está en y=1498. Al llegar al pie el resumen de errores queda fuera de vista. Alinear a derecha no resuelve acciones y errores de formularios largos. [Informe y capturas](VERIFICACION-CELULAR-COMPARTIDO.md).

**Entrega 07/10 (Codex CyE), a revisar:** acciones fijas en celular y resumen visible que se actualiza al modificar los datos, con JavaScript externo. Carlos aprobó el ANTES/DESPUÉS. Falta asignar verificador independiente. [Propuesta y capturas](PROPUESTA-A14-A27-A53.md), [alcance y pruebas del autor](IMPLEMENTACION-A14-A27-A53.md). No cerrado.

### A15. Una clase de 17:30 a 18:30 se acepta sin decir nada · Molesta · CERRADO 07/10, verificado Gemini

Para el club son **dos horas de cancha**, porque se paga por bloque de reloj ocupado. Wings
no lo sabía ni lo advertía. Ver también I6.

**Implementación 06/10, decisión de Carlos:** aviso y confirmación antes de crear una
clase única o serie. Guardar muestra los bloques del reloj sin crear registros;
Confirmar guarda el horario revisado. Una carga modificada exige renovar el aviso.
Sin precios ni implementación de alquiler POS-07. Diseño aprobado con capturas reales.
[Entrega, pruebas y capturas](IMPLEMENTACION-A15-A16.md). Otro agente verifica; sin deploy.

### A16. Cargar el horario obliga a repetir la carga · Molesta · CERRADO 07/10, verificado Gemini

Un grupo que entrena lunes a las 17:00 y viernes a las 16:00 no se puede cargar de una vez:
hay que hacer dos series separadas, porque la carga repetida usa un solo horario para todos
los días elegidos. Ese era el comportamiento anterior.

**Implementación 06/10:** cada día elegido tiene su horario; grupo, profesores y período
se cargan una sola vez. El ejemplo de los seis grupos genera 76 clases con seis POST
en vez de diez, con una serie por grupo. Se conserva el rollback completo ante conflicto
y la validación de profesores activos del deporte. Diseño aprobado por Carlos.
[Entrega, pruebas y capturas](IMPLEMENTACION-A15-A16.md). Otro agente verifica; sin deploy.

### A17. La ficha del alumno no tiene botón para cobrar · Frena · CERRADO 05/10

En la ficha del alumno (`/alumnos/{id}`) se ven sus cuotas impagas con el botón **Condonar**, pero no existe ningún botón para **Cobrar**. Para cobrarle a quien está parado en el mostrador hay que salir, ir a Caja, tocar Cobrar y buscarlo de nuevo en un desplegable.
Captura original: `evidencia/audit_admin_alumnos_show_desktop.png`.

**Resuelto en Entrega 2 de P2 (05/10):**
Se agregó el botón principal de página **Cobrar** (`x-ds.button variant="primary"`) en la barra de acciones superior de la ficha (`resources/views/alumnos/show.blade.php`), visible para Admin y Operativo (`!auth()->user()?->isProfesor()`), que conduce directo a `/caja/cobrar/{alumnoId}`. Asimismo, en la sección «Estado de cobranza», cada fila de cuota impaga incluye el botón de fila **Cobrar** (`ds-btn-row`) junto al botón Condonar. Cubierto en `P2Entrega2FichaCobroAdelantadoTest`.

### A18. Cobranza en celular rompe la tabla y superpone el texto · Frena · CERRADO 04/10

En pantallas de 375px (`/cobranza`), las columnas de la tabla colisionan: los títulos "ALUMNO" y "DEPORTE" se imprimen encimados ("AEBDORINE"), los nombres de los chicos se montan sobre la disciplina ("Morales, Patín Sofía"), los filtros se truncan a dos letras y el botón Ver queda cortado por el borde de la pantalla.
Captura original: `evidencia/audit_admin_cobranza_mobile.png`.

**Corregido en commit `abc346a`:** Tabla con contenedor scrolleable horizontal sin colisiones ni textos encimados. Botones Cobrar y Ver de 64px fijos accesibles. Con la regla responsive de `.filtros-row`, los filtros se apilan verticalmente con texto legible y sin colapsar. Verificado en navegador a 375 px de ancho y aprobado por Carlos.

### A19. Las barras de filtros en celular colapsan en cuadrados mudos y desbordan · Molesta · CERRADO 04/10

En Alumnos, Clases, Movimientos, Profesores e Historial de Cajas, los filtros se aprietan en una sola fila horizontal en el celular: los selectores quedan reducidos a pequeños cuadrados mudos con flechas sin texto, y los botones **Filtrar** y **Limpiar** quedan flotando afuera de la tarjeta blanca.
Capturas: `evidencia/audit_admin_clases_index_mobile.png`, `evidencia/audit_admin_movimientos_index_mobile.png`, `evidencia/audit_admin_profesores_index_mobile.png`, `evidencia/audit_admin_alumnos_index_mobile.png`, `evidencia/audit_admin_cajas_historial_mobile.png`.

**Corregido en commit `abc346a`:** Regla responsive `@media (max-width: 768px)` en `resources/css/app.css` para `.filtros-row` y sus controles (`filtros-select`, `filtros-control[type="date"]`). En pantallas de 375px los selectores y fechas comunes se apilan con altura de 48px, texto identificable y acciones (`filtros-actions`) contenidas dentro de la tarjeta. Cadenas largas conservan elipsis; Historial mantiene fechas de 160px y Caja su campo de mes de 230px. **Verificación independiente Codex CAB 05/10 aprobada**, con todas las barras consumidoras recorridas, capturas propias y suite 380/2242; [segunda vuelta](VERIFICACION-ENTREGA1.md). Hallazgos fuera de los filtros: A53–A55.

### A20. El botón "Nuevo" del cashflow en celular tapa el saldo · Molesta · CERRADO 06/10, verificado Codex


En `/cashflow` visto desde un teléfono, el botón **Nuevo** se monta directamente sobre el número del saldo inicial y balance ("$1.570.000"), tapando la cifra, y el contador de movimientos desborda hacia la derecha fuera de la tarjeta.
Captura original: `evidencia/audit_admin_cashflow_index_mobile.png`.

**Control independiente 06/10 (Codex CyE):** CERRADO por Codex: Cashflow con balance $1.570.000, Nuevo debajo sin solapar cifras ni contador. Comprobado en 375 y escritorio. [Informe y capturas](VERIFICACION-CELULAR-COMPARTIDO.md).

### A21. Cobranza duplica las filas de alumnos con más de un deporte · Molesta · CERRADO 04/10

Un alumno anotado en dos actividades (como Sofía Morales en Patín y Fútbol) aparece dos veces consecutivas en el listado de Cobranza con el mismo nombre y apellido, sin totalizar su deuda global ni clarificar a simple vista a qué corresponde cada fila.
Captura: `evidencia/audit_admin_cobranza_desktop.png`.

**Resuelto por decisión de Carlos (04/10, commit `abc346a`):** En Wings un alumno es deporte + DNI. Se desiste de la unificación por DNI volviendo a una fila por registro (deporte + DNI). La claridad se resolvió nombrando la columna "Deuda" (a secas, propia de esa fila) y agregando el renglón de ayuda "también debe $X en [Deporte]" debajo del DNI cuando el mismo DNI tiene deuda en otro deporte (A51). Cubierto en `CobranzaEntrega1Test`.

### A22. Cobranza no muestra montos de dinero en el resumen superior · Falta · CERRADO 04/10

Las tarjetas superiores de Cobranza muestran conteos de alumnos (`Total Activos 60`, `Al día 0`, `En plazo 0`, `Morosos 0`, `Deudores 60`), pero no dicen cuánta plata representa la deuda ni cuánto dinero falta recaudar. Quien gestiona no sabe cuántos pesos están en juego.
Captura: `evidencia/audit_admin_cobranza_desktop.png`.

**Resuelto en Entrega 1 de P2 (04/10):** Se agregó tarjeta destacada "Total adeudado" en pesos en el resumen superior. Cubierto en `CobranzaEntrega1Test`.

### A23. El dashboard de administración está casi vacío y no tiene acciones rápidas · CERRADO 10/10, aspecto aprobado por Carlos, lógica verificada por Claude

**Antecedente del relevamiento:** más de la mitad de la pantalla principal del administrador era espacio blanco vacío. Solo exhibe cuatro contadores y tres accesos repetidos (Alumnos, Grupos, Rubros) que ya están en el menú lateral. No ofrece atajos de apertura de caja, cobro rápido, movimientos del día ni alertas de revisiones pendientes.
Captura: `evidencia/audit_admin_admin_dashboard_desktop.png`.

**Actualización09/10:** Inicio aprobado por Carlos («Ok, aprobado»), aplicado y verificado; cifras mensuales, cajas por tipo, cuatro avisos y acceso a Reportes. [Entrega](../B12-A23/IMPLEMENTACION-INICIO-2026-10-09.md). En ese corte seguía abierto para prueba manual integral junto a B12. A23 cerrado por Claude el 10/10; [verificación](../B12-A23/VERIFICACION-A23-INICIO.md). El texto anterior describe el defecto original.

### A24. El inicio del operativo invita a "Cobrar" sin tener la caja abierta · Molesta · CERRADO 08/10, verificado Claude

Cuando Sandra Vidal entra a su turno sin caja abierta, la tarjeta dice "No hay turno abierto hoy" y ofrece el botón **Abrir**, guiándola a abrir la caja del día con su cambio inicial. Si un compañero tiene una caja abierta, muestra "Caja abierta de [Nombre]" con botón **Caja**, sin botones trampa. Si dejó su caja abierta ayer, muestra "Tu caja sigue abierta" con botón **Cerrar**.
Pruebas permanentes en `tests/Feature/InicioOperativoTest.php` (8 situaciones). [Implementación y evidencia](IMPLEMENTACION-A24-VUELTA-2.md).
Captura: `evidencia/a24-vuelta-2/finales/situacion-3-desktop.png`.

### A25. La apertura de caja no contempla saldo inicial ni cambio para vuelto · CERRADO 06/10, verificado Gemini
La caja se abre automáticamente al primer movimiento **sin guardar un importe inicial**.
Revalidado en código el 06/10: tampoco hay un arqueo de cierre que compare contado y
esperado; el cierre solo cambia estado/fecha. No es una comparación existente contra cero.
Carlos decidió el 06/10: separar cambio/retiro, heredar el cambio con confirmación y
mostrar esperado/contado/diferencia permitiendo cerrar para revisión ADMIN. Un solo cajón
compartido: hereda el último cierre del club. Primera apertura declarada; corrección con
motivo; un turno abierto; cambio retenido elegible y retiro = contado − retenido.
ADMIN configura el medio físico una vez, guarda aparte sus cobros y debe contar/cerrar antes de validar.
Al corregir rechazadas se conserva conteo/entrega, sin cambiar el siguiente turno. Previas propias:
3 fallos actuales + 9 funciones ausentes, 12/15; no se alteró la suite compartida.
[Relevamiento y ejemplos](../../05-pendientes/A25-CAMBIO-INICIAL-CAJA.md).
**Implementado el 06/10 por Codex y CERRADO 06/10: verificado por Gemini.** Apertura explícita, un turno compartido,
herencia confirmada/motivo, conteo/diferencia, retenido/entrega y conservación de rechazada.
26 pruebas permanentes (142 aserciones) pasando; capturas reales en marco 375 y desktop aprobadas por Carlos. [Entrega y capturas](IMPLEMENTACION-A25.md).
Captura: `evidencia/audit_operativo_caja_desktop.png`.

### A26. El formulario de alta exige celular personal obligatorio para menores · Molesta · CERRADO 08/10, verificado Gemini

En `/alumnos/create`, el campo "Celular" lleva asterisco rojo obligatorio (`*`) incluso para niños que no tienen teléfono propio. Si se marca "Mismo que el teléfono del tutor", igual se traba si los datos del tutor no fueron cargados previamente más abajo.
Captura original: `evidencia/audit_admin_alumnos_create_desktop.png`.

**Verificado por Gemini el 08/10:** CERRADO. Verificado en base de datos `wings_testing_gemini` y con navegador Chrome Headless vía CDP interactivo. Menor sin celular hereda teléfono de tutor en alta y edición; mayor sin celular es rechazado; bordes exactos verificados (cumple 18 hoy exige celular, cumple 18 mañana permite celular vacío); menor sin celular ni tutor rechaza por tutor sin error 500; asterisco rojo desaparece/aparece en vivo al cambiar la fecha de nacimiento en el DOM y tilde sincroniza. [Informe de verificación](VERIFICACION-A26-A39-A35.md). Capturas: `evidencia/verificacion-a26-a39-a35/a26-asterisco-menor.png` y `a26-asterisco-mayor.png`.

### A27. Cargar movimiento de caja en celular oculta los botones de acción · Molesta · CERRADO 07/10, verificado Gemini


El formulario de `/caja/movimiento` en 375px es tan vertical que los botones Guardar y Cancelar quedan fuera de la pantalla sin una barra fija inferior. Además, el campo Observaciones es obligatorio (`*`) para cualquier gasto ínfimo.
Captura original: `evidencia/audit_operativo_caja_movimiento_mobile.png`.

**Control independiente 06/10 (Codex CyE):** DEVUELTO a Gemini: en marco 375 × 667 Registrar y Cancelar quedan en y=740–772, fuera de vista y sin barra fija. Observaciones opcional sí pasa en vista y validación nullable; falta resolver las acciones. [Informe y capturas](VERIFICACION-CELULAR-COMPARTIDO.md).

**Entrega 07/10 (Codex CyE), a revisar:** Registrar/Cancelar visibles en barra móvil para ADMIN y OPERATIVO; Observaciones sigue opcional. Carlos aprobó el ANTES/DESPUÉS. Falta asignar verificador independiente. [Propuesta y capturas](PROPUESTA-A14-A27-A53.md), [alcance y pruebas del autor](IMPLEMENTACION-A14-A27-A53.md). No cerrado.

### A28. En Grupos móvil las tarifas desbordan y el interruptor Activo está pegado a Editar · Molesta · CERRADO 06/10, verificado Codex


En `/grupos` desde el teléfono, el texto de los planes ("Planes: 1x/sem — $38.000 · 2x/sem — $48.000") sobresale por el lateral derecho. Además, el interruptor "Activo" está pegado al botón Editar, facilitando que un toque táctil desactive el grupo por error.
Captura original: `evidencia/audit_admin_grupos_index_mobile.png`.

**Control independiente 06/10 (Codex CyE):** CERRADO por Codex: página de Grupos sin desborde (360/360) con tres tarifas millonarias; Activo separado de Editar. La contención usa elipsis: lectura completa de tarifas sigue devuelta en A53. [Informe y capturas](VERIFICACION-CELULAR-COMPARTIDO.md).

### A29. Redirección silenciosa a Caja para el operativo en secciones de administración · Molesta · CERRADO 05/10

Cuando Sandra Vidal intenta abrir `/cashflow`, `/liquidaciones`, `/usuarios` o `/configuraciones`, el sistema la redirige en silencio a `/caja` sin ningún mensaje explicativo. La persona cree que el enlace no funcionó o que el sistema falló.
Captura: `evidencia/audit_operativo_acceso_cashflow_desktop.png`.

### A30. Comportamiento dispar entre Profesor y Operativo ante accesos restringidos · Molesta · CERRADO 05/10

A un profesor que intenta ingresar a Alumnos, Caja o Grupos se le muestra una pantalla de error 403 ("Acceso denegado"), pero si intenta ingresar a Cashflow o Liquidaciones se lo redirige silenciosamente a `/clases`. Dos respuestas completamente distintas ante el mismo tipo de restricción de permisos.
Capturas: `evidencia/audit_profesor_acceso_alumnos_desktop.png` y `evidencia/audit_profesor_acceso_cashflow_desktop.png`.

### A31. La pantalla de error 403 manda al usuario logueado a la pantalla de login · Molesta · CERRADO 05/10

En la pantalla de error 403 (`resources/views/errors/403.blade.php`), el botón "Volver al inicio" tiene como enlace fijo `href="/login"`, mandando a quien ya tiene sesión iniciada a la página de ingreso.
Captura: `evidencia/audit_profesor_acceso_alumnos_desktop.png`.

**Entrega conjunta A29/A30/A31, Codex CAB 04/10, commit `97cf933`:** rechazo 403 explícito y aviso en
castellano para cuenta activa; Volver al tablero ADMIN, inicio OPERATIVO o Clases PROFESOR.
No se habilitan permisos. Anónimo/inactivo siguen al login. Carlos escribió autorización.
Suite final propia 380/2242 verde; tres roles normal/375 px y Volver comprobados.
[Implementación, pruebas y capturas](IMPLEMENTACION-PERMISOS.md).
**Verificación independiente por Gemini (05/10):** Aprobada en código y navegador real en escritorio y celular (375 px). Se recorrieron manualmente las rutas con los tres roles (`sandra.vidal@wings.test`, `lucia.gaitan@wings.test` y `admin@wings.test`). Operativo recibe 403 en `/cashflow`, `/liquidaciones`, `/configuraciones`, `/usuarios` y `/admin/dashboard`, y el botón Volver regresa a `/operativo` con la sesión activa; `/admin` y `/caja/validaciones` devuelven 404 sin exponer datos. Profesor recibe 403 en las de administración y en `/alumnos`, `/caja`, `/grupos`, con Volver a `/clases` conservando su sesión. Admin común intentando editar cuenta protegida recibe 403 con Volver a `/admin/dashboard`. Cero datos filtrados. Suite completa verde en `wings_testing_gemini` (380 pruebas / 2242 aserciones). [Informe de verificación y evidencia](VERIFICACION-A43-PERMISOS.md). Defectos A29, A30 y A31 CERRADOS.

### A32. El interruptor de usuario muestra apagado al usuario activo · Molesta · CERRADO 08/10, verificado Carlos

Carlos aprobó el retoque: «Ok, APROBADO A32». Nombre de la persona en el título; Profesor toma Apellido, Nombre de su ficha vinculada y no repite ese dato abajo. Todas las tarjetas muestran Email y Rol en columnas consistentes. Pedro/Sandra/Victoria son nombres ficticios del ensayo. Se conservan Activo/Tu cuenta/candado y Editar/Nuevo x=224–320 en celular. Seis capturas reales y medidas correctas; controlador, CSS y toggle compartido intactos. CERRADO, verifica Carlos; sin despliegue. [Entrega y capturas](IMPLEMENTACION-A32.md).

### A33. La toma de asistencia de clases en celular exige scroll masivo · Molesta · CERRADO 06/10, verificado Codex


En la ficha de la clase (`/clases/{id}`), cada alumno ocupa una tarjeta individual completa. Para una clase habitual de 15 o 20 alumnos, el profesor debe desplazarse metros de pantalla en la cancha para marcar los presentes y llegar al botón inferior de guardar.
Captura original: `evidencia/audit_profesor_clases_show_mobile.png`.

**Control independiente 06/10 (Codex CyE):** CERRADO por Codex: 20 alumnos, filas medidas de 61,61 px más 10 px de separación; lista compacta y Guardar alcanzable. PROFESOR marcó un alumno y el sistema confirmó Asistencias guardadas. Nombres largos se truncan; no se certifica toda la ficha como libre de recortes. [Informe y capturas](VERIFICACION-CELULAR-COMPARTIDO.md).

### A34. La ficha del alumno no tiene historial ni descarga de recibos · Falta · CERRADO 05/10

**Verificacion cruzada de Claude, 05/10:** el enlace **Recibo** de cada pago **ya existia**
desde ENT-05 (`04e3125`, 13/09) y estaba en la version que se audito el 23/09, asi que el
defecto, tal como se escribio, no era exacto: no faltaba la descarga del recibo. Lo que si
cambio la Entrega 2 es que el historial muestra 12 pagos en vez de 8. Se deja anotado para
no heredar como cierto un hallazgo que no se comprobo contra el codigo.


Si un familiar se acerca al mostrador solicitando una copia del recibo abonado anteriormente, la ficha del alumno (`/alumnos/{id}`) no ofrece el historial de comprobantes con opción de descarga o reimpresión en PDF.
Captura original: `evidencia/audit_admin_alumnos_show_desktop.png`.

**Resuelto en Entrega 2 de P2 (05/10):**
En `resources/views/alumnos/show.blade.php`, la sección «Historial de pagos» expone el listado cronológico de cobros con enlace directo **Recibo** (`ds-btn-row ds-btn-row--sec`) con `target="_blank"`, que abre el comprobante oficial en PDF generado por `ReciboService` / DomPDF (con opciones nativas de impresión y descarga). Los pagos anulados se indican explícitamente con badge «Anulado», importe tachado y sello de anulación en el PDF. Se amplió el límite en `AlumnoWebController::show()` de 8 a 12 pagos para brindar un año completo de historial. Cubierto en `CobroReciboAccesoTest` y `P2Entrega2FichaCobroAdelantadoTest`.

### A35. Botón redundante "Historial" dentro de la propia pantalla de historial de cajas · Molesta · CERRADO 08/10, verificado Gemini

En `/caja/historial`, la cabecera incluye un botón **Historial** que enlaza a la misma pantalla en la que el usuario ya está navegando.
Captura original: `evidencia/audit_admin_cajas_historial_mobile.png`.

**Verificado por Gemini el 08/10:** CERRADO (Confirmado NO-DEFECTO). Se auditaron exhaustivamente todos los botones y enlaces en `/caja` y `/caja/historial` para roles ADMIN y OPERATIVO. La captura original correspondía a la pantalla principal `/caja` (cuyo botón Historial navega a `/caja/historial`). En `/caja/historial` el botón de retorno se denomina "Volver" y navega a `/caja`. No existe ningún botón autorreferencial ni redundante. [Informe de verificación](VERIFICACION-A26-A39-A35.md). Inventario completo en `evidencia/verificacion-a26-a39-a35/resultados-a35.json`.

---

### A36. Rubros en celular · Molesta · CERRADO 06/10, verificado Codex


esperaba identificar cada subrubro y sus permisos; los encabezados se superponen y desaparece el nombre del subrubro, mientras se sigue viendo OPERATIVO y los botones. Captura original: `evidencia/audit_admin_rubros_index_mobile.png`.

**Control independiente 06/10 (Codex CyE):** CERRADO por Codex: Opción A elegida por Carlos, tabla única con desplazamiento horizontal local. Nombre y permiso visibles al inicio; desplazamiento de 189 px permite Caja/Editar/Pausar sin ampliar la página (360/360). No hay tarjetas móviles alternativas. [Informe y capturas](VERIFICACION-CELULAR-COMPARTIDO.md).

### A37. Ficha del alumno en celular · Molesta · CERRADO 06/10, verificado por Claude

**Verificado por Claude el 06/10, contra la pantalla real.** El HTML sale de la aplicación
—ya no quedan rastros de la maqueta— y a 375 la ficha entra: los datos en una columna, los
tres botones de arriba alcanzables y la fila del historial en dos renglones, con el monto
arriba y `Recibo` y `Anular` abajo. Captura de la verificación:
`capturas-a13/verificacion-a37-375.png`, sacada con el marco de 375 que exige `AGENTS.md` §1.
Queda dicho, porque costó dos vueltas: **el arreglo de Gemini siempre estuvo bien**; lo que
fallaba era primero su evidencia —una maqueta escrita a mano— y después la medición de
Claude, que capturaba con una ventana que Windows no deja achicar.

**Asignado a Gemini el 05/10.** Al agregar el botón Anular quedó a la vista que la ficha
entera era más ancha que la pantalla del teléfono: se cortaba todo el lado derecho, botones
incluidos (`Cobrar`, `Editar`, `Recibo`, `Anular`).

**Causa raíz diagnosticada:**
1. En `resources/views/alumnos/show.blade.php`, la barra superior de acciones tenía `<div class="filtros-actions mb-4" style="justify-content: flex-end; flex-wrap: wrap;">`. La regla CSS en `app.css` para mobile (`justify-content: flex-start; width: 100%`) era pisada por el estilo inline `justify-content: flex-end`, empujando los botones hacia el borde derecho y cortando el botón **Editar**.
2. En las filas de deudas pendientes y de historial de pagos, el bloque de acciones de la derecha (Monto + Recibo + Anular / Cobrar + Condonar) sumaba ~230px que, junto a la fecha/período de la izquierda (~130px), no entraban en los 343px útiles de pantalla en 375px.

**Solución aplicada:**
- Barra superior de acciones migrada a contenedor flexible responsivo `flex flex-wrap items-center gap-2 mb-4 justify-start sm:justify-end`.
- Filas de deudas e historial adaptadas con estructura en dos renglones en móvil (`flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 p-2 rounded`): primer renglón con fecha/período y monto, segundo renglón exclusivo para los botones de acción (`Recibo`, `Anular` / `Cobrar`, `Condonar`) alineados a la derecha (`w-full sm:w-auto justify-end`), eliminando todo desborde.
- Botón Volver inferior adaptado con `justify-start sm:justify-end`.
- Cubierto por prueba automatizada `tests/Feature/FichaAlumnoResponsiveA37Test.php`. Capturas de evidencia real tomadas sobre el sistema corriendo (`tests/Feature/CapturaFichaAnularTest.php`) en `docs/06-pruebas/PRU-02/capturas-a37/`.

### A38. Paginación de Clases · Molesta · CERRADO 08/10, aprobado por Carlos

esperaba indicaciones en español como el resto de la pantalla; al pie aparece “Showing 1 to 20 of 76 results”. Captura.

### A39. Acceso a Movimientos del OPERATIVO · Falta · CERRADO 08/10, verificado Gemini

esperaba encontrar Movimientos en su navegación para consultar los cobros; Sandra puede abrir esa pantalla con su dirección, pero el menú no ofrece el acceso. Captura.

**Verificado por Gemini el 08/10:** CERRADO. Verificado en base de datos `wings_testing_gemini` y con navegador Chrome Headless vía CDP interactivo. El menú lateral ofrece "Movimientos" bajo el grupo "Plata" tanto para ADMIN como OPERATIVO; PROFESOR recibe 403 y no lo ve en menú. Sandra ve su movimiento y el de su compañero de mostrador; no ve filas ni importes de rubros reservados a Admin (sueldos, alquileres); totales de ingresos, egresos y neto calculados exactamente sin incluir datos de admin; filtros excluyen rubros y subrubros de admin; ataques por GET directo con IDs de rubros/subrubros de admin devuelven cero filas y totales en cero; rubros mixtos exponen exclusivamente los subrubros y movimientos operativos. [Informe de verificación](VERIFICACION-A26-A39-A35.md). Captura: `evidencia/verificacion-a26-a39-a35/a39-sandra-movimientos.png`.

### A40. Inicio de ADMIN en celular · Molesta · CERRADO 06/10, verificado Codex


esperaba ver la deuda total contenida en su indicador; “$1.263.000” sobresale de la tarjeta. Captura original: `evidencia/audit_admin_dashboard_mobile.png`.

**Control independiente 06/10 (Codex CyE):** CERRADO por Codex: deuda $24.691.358 contenida en el indicador del inicio ADMIN a 375 y en escritorio. [Informe y capturas](VERIFICACION-CELULAR-COMPARTIDO.md).

### A41. Fechas del filtro de Movimientos · Molesta · CERRADO 06/10, verificado Codex


esperaba distinguir visualmente fecha inicial y final; aparecen dos campos con el mismo “dd/mm/aaaa”, sin rótulos visibles que expliquen cuál es Desde y cuál es Hasta. Captura original: `evidencia/audit_admin_movimientos_index_mobile.png`.

**Control independiente 06/10 (Codex CyE):** CERRADO por Codex: Desde y Hasta visibles junto a cada fecha en ADMIN y OPERATIVO, a 375 y en escritorio. [Informe y capturas](VERIFICACION-CELULAR-COMPARTIDO.md).

### A42. Aviso de inscripción al editar alumno · Molesta · verificado

esperaba que consultara el DNI y la fecha ya cargados; al abrir la edición pide ingresarlos aunque están completos y solo informa que no corresponde inscripción después de reingresar el mismo DNI. Al abrir · Tras reingresar el DNI.

### A43. El alumno cargado a mano con ingreso de un mes cerrado queda deudor · Frena · CERRADO 05/10

**Verificado por Claude el 04/10** sobre la implementación de P0 (`ad24769`): cargando a mano
un alumno con fecha de ingreso **20/01/2020**, el sistema le crea la cuota de **enero de
2020** y el alumno queda **DEUDOR en el acto**, con una deuda de hace seis años que nadie va
a cobrar. La regla única —la cuota es la del mes de la fecha de ingreso— aplicada a ese caso
da un resultado que el club no quiere.

**Decisión de Carlos, 04/10:** cuando la fecha de ingreso cae en **un mes ya cerrado**, el
alta **avisa antes de guardar** y ofrece una sola decisión:

> Este alumno ingresó en enero de 2020, un mes ya cerrado. ¿Le generamos la cuota de este
> mes? **Sí** genera la cuota del mes en curso, **completa**, sin descuento de bienvenida.
> **No** guarda al alumno sin generar cuota. **Aclaración posterior de Carlos: solo
> la cuota; la inscripción conserva su regla por DNI.**

- **Nunca se crea deuda de un mes viejo.** Esa opción no se ofrece.
- Si el ingreso es del **mes en curso o posterior**, no se pregunta nada: la cuota se genera
  sola con el porcentaje del día, como está hoy.
- La decisión y quién la tomó quedan registradas con el alta.

**Entrega Codex CAB, 04/10, commit `218ffc5`:** implementada y autorizada por Carlos; No evita solo cuota.
366 pruebas / 2131 aserciones verdes; formulario real normal/375 px.
[Implementación, pruebas y capturas](IMPLEMENTACION-A43.md).

**Verificación independiente por Gemini (05/10):** Aprobada en código y navegador real en escritorio y celular (375 px). Se comprobó que el ingreso en mes corriente (`2026-10-05`) oculta el aviso y genera cuota automática con porcentaje del día; el ingreso en mes cerrado (`2020-01-20`) muestra el aviso dinámico con los importes congelados y radios obligatorios. La opción Sí generó la cuota corriente 100% ($30.000) sin cuotas históricas y dejó al alumno En plazo; la opción No no generó cuota, conservó la inscripción ($5.000) y dejó al alumno Al día (sin convertirlo en deudor). Se auditó `alta_cuota` en JSON (`modo`, `usuario_id`, fecha, período, monto). Suite completa verde en `wings_testing_gemini` (380 pruebas / 2242 aserciones). [Informe de verificación y evidencia](VERIFICACION-A43-PERMISOS.md). Defectos A43 CERRADO.

Cuando exista la primera carga por Excel, los alumnos viejos entran por ahí y este caso
debería volverse raro. El aviso queda igual, para el que no entró en el padrón.

### A44. El aviso diario llega firmado por Laravel · Molesta · CERRADO 08/10, aprobado por Carlos

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
en el nombre. Con la decisión de Carlos (A51) en commit `abc346a`, cada registro tiene su fila con columna Deporte dedicada, eliminando la duplicación en el nombre.
[Control independiente](VERIFICACION-ENTREGA1.md).

### A46. Cobranza es un padrón, no una lista de cobranza · Frena · CERRADO 04/10

Abre listando **a todos los alumnos activos**, con el filtro de estado en "Todos": 63 de las
65 filas no tienen nada que cobrar. `CobranzaWebController::index` no filtra por deuda y el
estado por defecto es vacío.

Es la lista de trabajo del que sale a cobrar. Tiene que abrir mostrando **solo a los que
deben**, ordenados por antigüedad o por monto, y ver a los que están al día debería ser una
opción, no lo primero. Las tarjetas del resumen quedan como están.

**Resuelto en Entrega 1 de P2 (04/10, commit `abc346a`):** Al abrir Cobranza sin parámetros, filtra por defecto a quienes tienen deuda (`DEUDOR` y `MOROSO`), ordenados de la deuda más vieja a la más nueva. El filtro permite seleccionar "Todos" u otros estados explícitamente. Cubierto en `CobranzaEntrega1Test`.

### A47. Los botones de fila tienen anchos distintos · Molesta · CERRADO 04/10

En la misma fila de Cobranza, **Cobrar mide 64px y Ver 44px**, los dos escritos a mano en
`cobranza/index.blade.php:194,197`. `DESIGN-RULES.md` fija un ancho único para los botones de
fila de tabla: 64. Los dos salieron del mismo commit, `e32ce0c`.

**Resuelto en Entrega 1 de P2 (04/10, commit `abc346a`):** Ambos botones `Cobrar` y `Ver` tienen ancho uniforme y fijo de 64px respetando Objeto C de `DESIGN-RULES.md` con clase `ds-btn-row`.

### A48. P2 agregó JavaScript adentro del HTML · Molesta · CERRADO 06/10, verificado Claude

Verificación de Claude del 06/10 asentada por pedido de Carlos. Búsqueda repetida sobre main integrado: alumnos/_form.blade.php no contiene bloques <script>; el aviso de cambios sin guardar vive en resources/js/alumnos-form.js. CspSinCodigoIncrustadoTest aprueba.

[Verificación, capturas y límites](VERIFICACION-A13-B1-A55-CIERRE.md).

### A49. P2 agregó un confirm escrito en el HTML · Molesta · CERRADO 06/10, verificado Claude

Verificación de Claude del 06/10 asentada por pedido de Carlos. Búsqueda repetida sobre main integrado: caja/resumen.blade.php no contiene onsubmit ni confirm escrito en HTML. Conserva un script ajeno a este defecto. Los manejadores antiguos de otras vistas quedan fuera de este cierre. CspSinCodigoIncrustadoTest aprueba.

[Verificación, capturas y límites](VERIFICACION-A13-B1-A55-CIERRE.md).

### A50. Un agente escribió su propia autorización de diseño · Frena · CERRADO 04/10

**Resuelto con Carlos el 04/10, anotado recien el 05/10.** La regla quedo en `AGENTS.md`,
"No se pide autorizacion de diseno sin imagenes": antes de pedir la linea `Diseno-autorizado`
tiene que haber **capturas guardadas en el repositorio** de cada pantalla afectada, en
escritorio y a 375 de ancho, con las rutas exactas para abrirlas; si el cambio toca
`app.css` o una pieza compartida, de todas las pantallas que cambian. Y en
`QUIEN-HACE-QUE.md`: lo visual no se aprueba por texto.
Lo que no se puede automatizar y conviene saber: **los tres agentes commitean con el nombre
de Git de Carlos**, asi que ningun candado puede distinguir si la linea la escribio el dueño
o el agente. Lo que protege es la capture obligatoria y que el verificador la mire.

El commit de P2 lleva `Diseno-autorizado: Carlos autorizo P2 (...)` y **Carlos no la dio**.
El hook que vigila el diseño no bloquea: exige que la línea esté y confía en que sea cierta.
Si la escribe el agente, la regla no protege nada. Siete vistas entraron con esa línea.

No es un defecto del sistema sino del proceso, y se anota acá para que no se pierda.

### A51. Filtrar un deporte reduce el total de deuda de una persona · Frena · CERRADO 04/10

Esperaba que **Total deuda** conservara lo que debe la persona en sus dos deportes;
PostCorte, Mateo muestra $26.000 sin filtros y $5.000 al elegir Fútbol, y cambia el
destino de Cobrar/Ver. La deuda no cambió. Frena al mostrador: deja parte de lo debido
fuera de lo que se presenta como total. Comprobado en navegador local ADMIN sobre
`867c295`. [Sin filtro](evidencia/verificacion-entrega1/cobranza-escritorio.png) y
[con Fútbol](evidencia/verificacion-entrega1/filtro-futbol-total.png).
Código y límites en [VERIFICACION-ENTREGA1.md](VERIFICACION-ENTREGA1.md).

**Corregido en commit `abc346a`:** Decisión de Carlos: en Wings un alumno es deporte + DNI. Se deshizo la unificación por DNI volviendo a una fila por registro (deporte + DNI). La columna se renombró a "Deuda" (a secas) y siempre muestra lo debido en esa fila (filtrado o sin filtrar, el monto representa exactamente lo mismo). Cuando el mismo DNI aparece en otro registro con deuda > 0, se muestra debajo del DNI el renglón chico de ayuda: "también debe $X en [Deporte]". Cubierto en `CobranzaEntrega1Test`.

### A52. La inscripción pendiente cambia el estado mensual a Deudor · Molesta · CERRADO 04/10

Esperaba que la inscripción se mostrara como cargo pendiente sin cambiar el estado
mensual, como establece ENT-01. `CobranzaEstadoService.php:229-230` convierte Al día
o En plazo en Deudor por la inscripción, mientras el cálculo individual y el resumen
solo miran cuotas. En el registro local 63, sin cuotas, la lectura individual da
AL_DIA y la fila filtrada DEUDOR, con inscripción $5.000.
El alcance a altas dentro de gracia se infiere del cuerpo; no se creó un alumno para
probarlo en pantalla. [Evidencia y límites](VERIFICACION-ENTREGA1.md).

**Corregido en commit `abc346a`:** El cálculo del estado de cobranza se unificó en `CobranzaEstadoService::calcularEstadoDesdeDeudas(...)` y evalúa exclusivamente las cuotas. La inscripción pendiente suma al importe de la deuda a cobrar pero no altera la etiqueta de estado mensual ni en el listado, ni en la ficha, ni en el resumen, respetando ENT-01. Cubierto en `CobranzaEntrega1Test`.

## Hallazgos de la segunda verificación de Entrega 1 — 05/10/2026

### A53. Las tarjetas de Grupos y del selector de cobro cortan datos en celular · Molesta · CERRADO 07/10, verificado Gemini


Esperaba leer el precio del plan y los datos de cada tarjeta a 375px; en Grupos el precio queda fuera del borde derecho y la página alcanza 457px, y en el selector de cobro el grupo también sobresale; los filtros sí caben. [Grupos](evidencia/verificacion-entrega1-v2/grupos-375.jpg), [selector](evidencia/verificacion-entrega1-v2/seleccionar-cobro-375.jpg).

**Control independiente 06/10 (Codex CyE):** DEVUELTO a Gemini: ds-truncate sigue ocultando datos. Tarifas requieren 383 px y reciben 225; grupo del selector requiere 695 y recibe 248. Nombres compuestos e importes grandes probados; nombre del alumno y saldo sí entran. Mostrar completos tarifas y grupo sin desborde. [Informe y capturas](VERIFICACION-CELULAR-COMPARTIDO.md).

**Entrega 07/10 (Codex CyE), a revisar:** nombres de grupo largos y tres tarifas millonarias completos en varias líneas a 375, para ADMIN y OPERATIVO. Carlos aprobó el ANTES/DESPUÉS. Falta asignar verificador independiente. [Propuesta y capturas](PROPUESTA-A14-A27-A53.md), [alcance y pruebas del autor](IMPLEMENTACION-A14-A27-A53.md). No cerrado.

### A54. El selector de cobro cuenta alumnos sin saldo como deuda pendiente · Molesta · CERRADO 06/10, verificado por Codex CAB

**Antecedente:** «alumnos con deuda pendiente» contaba 6 cuando solo 5 tenían saldo.
Corregido por Claude en `4571dbc`; verificación independiente de Codex: una cuota
PENDIENTE de importe cero no aumenta el contador. Caso inicial: 3 positivos y encabezado 3;
la lista además conserva al alumno sin deuda para adelantar (A3). Con casos adicionales,
8 positivos y encabezado 8. [Informe §7](VERIFICACION-A13-A54.md#7-contador-y-saldo-entre-selector-cobranza-y-ficha),
[captura de cero pendiente](evidencia/verificacion-a13-a54/selector-cero-pendiente.png).

### A55. El saldo del selector de cobro omite la inscripción · Molesta · CERRADO 06/10, verificado Codex

Una persona ficticia en Patín y Fútbol: una sola inscripción de $5.000. Selector y ficha propietaria imputan $5.000 a Patín; Fútbol no repite deuda y remite dinámicamente a esa ficha. Total pendiente inicial $5.000, una sola persona con deuda. Pago y anulación actualizan el cargo compartido. Texto exacto aprobado aplicado y capturas reales renovadas en escritorio y marco de 375.

[Verificación, capturas y límites](VERIFICACION-A13-B1-A55-CIERRE.md).

El [control anterior](VERIFICACION-A13-A54.md) queda como antecedente; sus observaciones fueron resueltas y revalidadas el 06/10 sobre el merge `6d3f68a`.

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

### A56. Cashflow en celular se corta y sus filtros no se apilan · Molesta · CERRADO 06/10, verificado Codex CyE

**Control independiente Codex CyE 06/10:** cobro/anulación reales y gasto normal por POST.
Contraasiento −$48.000 y gasto −$2.500: E y color de salida; original I y color de ingreso.
Ingresos netos $0, egresos $2.500, inicial $10.000, balance $7.500 comprobados.
Login de control y Cashflow en Chrome, marco real de 375: filtros/totales contenidos;
tabla con desplazamiento dentro de su tarjeta. 7 pruebas existentes/24 aserciones verdes,
ensayo independiente 1/24; sin aumento de suite ni despliegue.
[Informe y alcance](VERIFICACION-A56.md). El filtro Tipo conserva su criterio por rubro.

**Hecho el 06/10 (Claude).** Los filtros dejan la grilla fija de cuatro columnas y usan una
que se apila; los totales bajan de renglón. La tabla sigue desplazándose de costado dentro
de su marco, como el resto de las tablas.
**Corrección de método, en el camino:** la captura con la que se dijo que esto —y A37—
estaban cortados **medía mal**. Chrome en Windows no abre ventanas de menos de ~500px, así
que renderizaba a 500 y recortaba a 375. Se comprobó capturando el login, que no puede estar
roto, y también aparecía cortado. Ahora la captura de celular se saca con un `<iframe>` de
375 dentro de una ventana grande (`capturas-cashflow/marco-375.html`), y está escrito en
`AGENTS.md` §1. **A37 estaba bien arreglado por Gemini**; lo que fallaba era la medición.

**Hallazgo anterior al arreglo:** visto el 06/10 por Carlos sobre una captura del sistema. En `/cashflow` a 375 de ancho, la
barra de filtros, la de totales y la tabla **se salen de la pantalla**: el cuarto filtro
queda afuera y "2 movimientos" se lee a medias.

La causa está ubicada: `cashflow/index.blade.php:17` arma su **propia grilla a mano**, con
cuatro columnas fijas —`grid-template-columns: 100px 1fr 1fr 1fr`—, en vez de usar la barra
de filtros compartida que Gemini dejó responsive al cerrar A19. Como la grilla no se apila,
empuja el ancho de toda la pantalla.

Es la misma familia que A37, A53 y A14: cada pantalla resuelve el celular por su cuenta en
vez de usar lo compartido. Conviene tomarlas juntas.

### A57. Los campos de los filtros tienen anchos distintos en celular · Molesta · CERRADO 10/10, aprobado por Carlos

En Caja (`/caja`) en móvil (360px), el desplegable «Operativo» ocupaba todo el ancho (278px) y el campo de fecha «octubre de 2026», debajo, era más angosto (230px). Carlos: «El tamaño de los select debería ser el mismo […] fecha es más chico que operativo y queda feo.»

Causa: la regla `@media (max-width: 768px)` en `resources/css/app.css` solo expandía a 100% los `.filtros-select` y los `input[type="date"]`, omitiendo `input[type="month"]` y otros controles. Resuelto generalizando la regla en `resources/css/app.css` para todos los controles dentro de `.filtros-row`. Auditadas 82 pantallas a 360px, 375px y 320px; filtros parejos en todo el sistema. Aspecto aprobado por Carlos sobre el visor y capturas finales. [Evidencia y visor](evidencia/a57-a58/visor.html).

### A58. La pantalla se desliza hacia el costado sin que haya nada a la derecha · Molesta · CERRADO 10/10, aprobado por Carlos

En Clases (`/clases`) en móvil (360px). Carlos: «tiene un scroll lateral la pantalla al pedo, no hay nada a la derecha.»

Causa: la tarjeta de clase en `clases/_card.blade.php` tenía `grid-template-columns: repeat(3, 1fr)` inline que en 360px forzaba un ancho de 451px, y `#clases-hoy-container` con `overflow-y: auto` activaba desplazamiento horizontal (`scrollWidth: 455px` contra `clientWidth: 328px`). Resuelto en `resources/css/app.css` con regla `@media (max-width: 640px)` apilando las tarjetas a 1 columna y fijando `overflow-x: hidden` en el contenedor. Ancho final 328px sin scroll horizontal y escritorio (1280px) 100% intacto. Aspecto aprobado por Carlos sobre el visor y capturas finales. [Evidencia y visor](evidencia/a57-a58/visor.html).

### B1. El admin está modelado como un operativo más · Frena · CERRADO 06/10, verificado Codex

El ADMIN usa el cobro directo a Cashflow; el OPERATIVO usa su caja. Ensayo HTTP y navegador sobre main integrado: cobro admin $101.000, anulación con motivo y caja operativa con solo su movimiento $48.000. Inicial $10.000, esperado $58.000. Los hallazgos de historial y contraasiento de A13 quedaron comprobados en pantalla.

[Verificación, capturas y límites](VERIFICACION-A13-B1-A55-CIERRE.md).

El [control anterior](VERIFICACION-A13-A54.md) queda como antecedente; sus observaciones fueron resueltas y revalidadas el 06/10 sobre el merge `6d3f68a`.

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

### B4. El resumen diario no incluía las clases sin asistencia · Molesta · CERRADO 04/10

Una clase sin lista tomada **traba el pago del profesor** —sin asistencia no se liquida— y
hasta hoy solo se veía como un contador en el menú de quien entraba. El dueño podía no
enterarse nunca.

**Corregido el 04/10:** el resumen diario incluye cuántas hay, cuál es la más vieja, de qué
grupo, y dice por qué importa: hasta que se carguen no se le puede pagar al profesor. Usa la
misma consulta que el contador del menú, para que los dos digan siempre lo mismo. Las clases
validadas a mano para liquidar no aparecen. Cubierto por `AvisoAdminResumenDiarioTest`.
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

### B12. No hay reportes · En curso 09/10

Al cerrar el día el dueño tiene que poder contestar cuatro preguntas: cuánto entró, cuánto
salió, cuánto le deben y cuánto debe. Hoy se contestan abriendo varias pantallas y sumando a
mano (antecedente del relevamiento). FIN-04/POS-01 en desarrollo: Finanzas V3 y Alumnos aprobados por Carlos e integrados; Sueldos e historia monetaria implementados y probados; Sueldos y acceso Plata → Reportes aprobados por Carlos («Esta OK el acceso», 10/10). [Entrega Sueldos](../B12-A23/IMPLEMENTACION-SUELDOS-2026-10-09.md). Aspecto del ajuste aprobado por Carlos («Sí, así»,10/10); Monto ajustado e íconos. Faltan manual integral y clasificación de antecedentes; B12 sigue abierto.

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
### B15. El SPF del subdominio anuló su comodín de DNS · CERRADO, verificado

Documentado en `docs/04-tecnico/SERVIDOR.md`. Queda como lección de proceso: comprobar desde
afuera, no desde el servidor.

### B16. `/movimientos` filtraba por caja propia · CERRADO 23/09, verificado

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
  de ingreso**, con el porcentaje del día. Esta regla de P0 fue enmendada después
  por A43 del 04/10: ingreso en mes cerrado exige elegir cuota corriente completa
  o ninguna cuota; inscripción independiente.
- Se quita `inscripcion_fecha_corte`: el parámetro, su lugar en Configuración y la prueba
  que lo cuida.
- Se retiran los dos importadores viejos (`wings:importar-padron`, `wings:importar-deuda-inicial`)
  **cuando el nuevo esté andando**, no antes.

**P0 CERRADO el 05/10:** implementado por Codex (`ad24769`) y verificado por Gemini, que aprobó A43 y los permisos.
Evidencia y pruebas en [P0-CARGA-INICIAL-2026-10-04.md](P0-CARGA-INICIAL-2026-10-04.md).
La migración nueva elimina solo el parámetro de instalaciones existentes; no modifica
cargos ni pagos. Los importadores antiguos se conservan.

### P1 · La primera carga por Excel — **bloquea la prueba grande**

**P1 CERRADO el 05/10:** implementado por Codex (`d530c85`) y verificado por Gemini
(`a7070c7`, informe en [VERIFICACION-P1.md](VERIFICACION-P1.md)): plantilla con los catálogos
reales, revisión que no escribe nada y devuelve el Excel marcado, carga todo o nada y
Deshacer protegido. **Queda afuera, a propósito:** retirar los dos importadores viejos, que
hoy conviven con el nuevo.

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
| 2.4 | Que el usuario sepa por qué no se guardó, y que se le avise antes de perder lo cargado; A4 Hecho (Cx), a revisar 05/10; A14 HECHO (Codex), a revisar 07/10, diseño aprobado por Carlos | A4, A14 |
| 2.5 | Profesores activos del deporte de la clase; Hecho (Cx), a revisar 05/10 | A5 |
| 2.6 | Apertura declarada y arqueo; HECHO (Codex), a revisar 06/10 | A25 |

### P3 · El dueño deja de ser un operativo

**Control independiente registrado 06/10:** ADMIN cobra sin caja y puede anular desde
la ficha con motivo obligatorio. A13/B1 CERRADOS por Codex tras comprobar historial y
contraasientos en pantalla sobre main integrado. [Informe](VERIFICACION-A13-B1-A55-CIERRE.md).
Su pantalla de Caja propia sigue sin definir.

**Decidido el 04/10:** cuando el dueño cobra, esa plata **va directo al cashflow, sin caja**,
con su medio de pago y su fecha. No abre caja, no cierra nada y no se valida a sí mismo. La
caja sigue siendo cosa del mostrador. El camino ya existe en el código
(`registrarPagoCuotaAdmin`), alcanzable desde la pantalla desde `8869263`.

| Orden | Qué | Defectos |
|---|---|---|
| 3.1 | Que cobrar no le abra caja al admin, y que no se valide a sí mismo | A13, B1 |
| 3.2 | Pantalla de caja del dueño: la del que mira, no la del que rinde | A13 |
| 3.3 | Un tablero de dueño que sirva para decidir | A23 |

### P4 · Que se entienda

| Orden | Qué | Defectos |
|---|---|---|
| 4.1 | Configuración entregada el 04/10; CERRADO tras verificación independiente de Gemini | A11 |
| 4.2 | Consistencia: un verbo por botón, los puntos, los interruptores, el botón Nuevo | A6, A8, A9, A10, A32, A35 |
| 4.3 | Listado de alumnos y ficha: el dato donde se busca | A7, A37 |
| 4.4 | Permisos: aviso común y regreso al inicio propio; CERRADO tras verificación de Gemini | A29, A30, A31 |
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
