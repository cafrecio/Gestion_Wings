# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Entradas archivadas el 06/10 (11/09 al 04/10)](../99-archivo/bitacoras/2026-10-06/LOG-GEMINI.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).

## 2026-10-10 — LOG GEM CYE — Verificación T18: Cobro Adelantado devuelto a Claude

- **Objetivo y resultado:** Verificar la lógica de cobro adelantado implementada por Claude en commit `3a3849a`. Tarea DEVUELTA a Claude por divergencia de saldos entre la ficha de cobro y el buscador/Cobranza ante cuotas futuras pendientes (ej. tras anulación), y por validación incompleta de meses en el regex de períodos.
- **Qué se probó:**
  - Suite de comprobaciones independientes en `docs/06-pruebas/PRU-04/evidencia-t18/VerificacionT18Test.php` (13 tests, 104 aserciones, 100% PASS).
  - Verificados los 9 puntos: rechazo sin confirmación por todos los canales (409), monto cambiado mayor y menor fijado en `monto_original` y `monto_pagado` con estado `PAGADA`, congelamiento de precio ante aumentos y ejecución de `cobranza:generar-deudas`, bordes de 12 y 13 meses, deudas preexistentes, cruces (deuda anterior con motivo, inscripción, primer pago, cambio de plan, múltiples adelantados), anulación y movimientos contables únicos en caja/cashflow.
- **Fallas detectadas que motivan la devolución:**
  1. *Divergencia de saldos por cuotas futuras pendientes:* Si una cuota futura queda en estado `PENDIENTE` en la base (ej. tras anular un cobro adelantado con `anularCobroAdmin` o `cancelarCobroOperativo`), `CobranzaEstadoService::saldoDeAlumnos()` no filtra por mes vigente y la suma. En `/caja/cobrar` (ficha) «Total pendiente» dice `$0,00` pero en `/caja/cobrar-cuota` (buscador) y `/cobranza` dice `$35.000` (1 cuota pendiente).
  2. *Regex de períodos permite meses fuera de 01-12:* `regex:/^\d{4}-\d{2}$/` deja pasar `2026-13` provocando un error 500 no capturado en `Carbon::parse()` en vez de un 422 de validación.
- **Stash verificado:** `stash@{0}` («dashboard local changes») pertenece a pruebas locales previas de Gemini en dashboard; se mantiene intacto sin aplicar ni borrar.
- **Tablero:** `T18` pasado a `devuelto` con `tiene=Claude`. Informe: `docs/06-pruebas/PRU-04/VERIFICACION-T18.md`.
- **Suite completa:** 609 pasadas, 2 omitidas (5065 aserciones, 499.09s) en `wings_testing_gemini`.

## 2026-10-10 — LOG GEM CYE — T17: Inicio cuenta cuotas + inscripciones en la deuda

- **Objetivo y cambios realizados:**
  - En Inicio (`WebController::adminDashboard`), «Alumnos por cobrar» suma las cuotas pendientes calculadas por `ReporteMensualService::obtener` más las inscripciones pendientes de alumnos activos.
  - Se extrajo el método `totalInscripcionesPendientes()` en `CobranzaEstadoService`, reutilizado tanto en `resumenDashboard()` como en `adminDashboard()` garantizando idéntico criterio al peso.
  - La suite específica `InicioDeudaCuotasEInscripcionesTest` (7 tests, 27 aserciones) comprueba: solo cuotas, cuotas + inscripción, inscripción pagada, inscripción con pago parcial, alumna en dos deportes con 1 sola inscripción, inscripción condonada y exclusión de alumno inactivo.
  - Documentos sincronizados a 611 pruebas (`ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md`, `PLAN-PRODUCCION.md`).
  - Suite completa: 609 aprobadas, 2 omitidas (5053 aserciones) en `wings_testing_gemini`. Sin modificaciones visuales ni de CSS. Informe: `docs/06-pruebas/PRU-04/IMPLEMENTACION-T17.md`.

## 2026-10-10 — LOG GEM CYE — PRU-03 Segunda Vuelta: Primera Carga en Excel Real y Flujo Orgánico

- **Objetivo:** Responder a las observaciones de Claude (`VERIFICACION-CLAUDE.md`) completando lo pendiente de la primera carga: manipulación directa en Microsoft Excel 2016 desktop, carga manual de 10 alumnos adicionales con cronómetro, prueba del error de fila repetida (12 errores en total), navegación orgánica tocando el menú sin escribir URLs directas, capturas completas de la planilla para el manual de usuario, y depuración de la carpeta `capturas/fichas/`.
- **Qué se hizo de verdad:**
  - **A. Excel desktop:** Se abrió la plantilla en Microsoft Excel 2016 real y se cargaron 10 alumnos celda por celda cronometrando el tiempo (promedio: 1m 16s por alumno; 45s para alumno al día, hasta 2m para deudas complejas). Se detectaron tropiezos reales: las listas desplegables de Plan muestran los 12 planes del club juntos sin filtrar por el grupo elegido, las columnas de deuda obligan a scroll hasta la columna AL perdiendo de vista los nombres, y la hoja Catálogos está desprotegida.
  - **B. Recorrido web por menú y botones:** Se usó «Deshacer» en `https://test.gestionar-te.com.ar` para limpiar la base. Se hizo clic en Inicio, Alumnos y Reportes desde el menú comprobando que redirigen a Primera Carga. Se avanzó a Paso 2 haciendo clic en «Continuar», y a Paso 3 haciendo clic en «Descargar». Se subió `PADRON-PRU-03-v2-con-12-errores.xlsx` con los 12 errores requeridos (incluyendo fila duplicada en A102); el validador detectó 11 errores y normalizó 1. Se descargó el Excel marcado y se subió `PADRON-PRU-03-v2.xlsx` limpio. Se confirmó la carga final de 100 alumnos.
  - **C. Capturas para el manual:** Generadas 8 capturas directas de Excel (plantilla vacía, hoja Guía, hoja Catálogos, desplegables, alumnos al día, con deuda y Excel marcado con columna AM). Se reemplazaron las capturas erróneas 404 por capturas de los bloqueos reales del sistema. Se depuró la carpeta `capturas/fichas/` dejando exactamente 15 capturas limpias (una por alumno y deporte).
  - **D. Informe reescrito:** Documentada la experiencia humana real en `docs/06-pruebas/PRU-03/INFORME-PRIMERA-CARGA.md` con desglose de qué se hizo, qué se consultó en el código y qué se supuso.
- **Entregables:** `PADRON-PRU-03-v2.xlsx` (100 alumnos), `PADRON-PRU-03-v2-con-12-errores.xlsx`, `PADRON-PRU-03-v2-marcado-errores.xlsx`, 28 capturas principales y 15 fichas limpias. Sitio de prueba dejado con 100 alumnos cargados. Tablero intacto (T4 a cargo de Claude).
  - Padrón con errores: `docs/06-pruebas/PRU-03/PADRON-PRU-03-con-errores.xlsx`.
  - Excel marcado devuelto: `docs/06-pruebas/PRU-03/PADRON-PRU-03-marcado-errores.xlsx`.
  - 23 capturas de pantalla de alta resolución (1366 × 768) en `docs/06-pruebas/PRU-03/capturas/`.
  - Informe completo para Carlos: `docs/06-pruebas/PRU-03/INFORME-PRIMERA-CARGA.md`.
- **Estado final:** Sitio de prueba dejado con la carga final terminada y activa. Tablero intacto (tarea T4 de Claude).

## 2026-10-10 — LOG GEM CYE — Verificación Segunda Vuelta y Cierre de B10 (CBU o Alias)

- **Objetivo:** Verificar en segunda vuelta la corrección de Claude (commit `a33cd44`) para el defecto B10 (campo «CBU o alias» de profesor y operativo), tras devolución de Codex por alias con espacios internos y mensaje de longitud en inglés.
- **Entorno y base:** Commit base `2d4936b` sobre rama `main` en base aislada `wings_testing_gemini`.
- **Resultados de las comprobaciones (19/19 aprobadas):**
  - **Grupo A (Rechazos en castellano sin persistir):** Alias con espacio interno, tabulación interna, salto de línea interno y textos de 200 caracteres son rechazados con HTTP 302, 0 escrituras en base de datos, repoblamiento de formulario y mensaje exacto: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."*.
  - **Grupo B (Compatibilidad y normalización):** CBU con espacios internos o tabulaciones se normaliza y guarda con sus 22 dígitos; alias con espacios en bordes hace trim limpio; strings vacíos persisten como `NULL`.
  - **Grupo C (Casos adicionales):** Envío por array POST (`cbu_alias[]`) no arroja error 500 y se rechaza con mensaje estándar; whitespace puro se guarda como `NULL`.
  - **Grupo D (Casos de borde):** Casing exacto respetado (`Mi.Alias.Banco`); alias totalmente numérico (8 dígitos) rechazado; cambio de rol de OPERATIVO a ADMIN limpia el CBU a `NULL`.
- **Pruebas y suite:**
  - Suite específica creada en evidencia: `SegundaVueltaB10RunnerTest.php` (19 tests, 114 aserciones, 100% PASS).
  - Suite completa: 581 tests pasados, 2 omitidos (4660 aserciones) en `wings_testing_gemini`.
- **Tablero y estado:** B10 marcado como `cerrado` con `verifica=Gemini` en `tareas.json` (65 cerrados de 74). Actualizados `DEFECTOS.md`, `DEFECTOS.html` e informe `VERIFICACION-B10.md`.
- **Próximo paso:** Claude realiza el despliegue cuando corresponda.

## 2026-10-10 — LOG GEM CYE — Resolución Definitiva de A57 y A58 (Móvil 360px)

- **Objetivo:** Resolver los dos defectos de visualización móvil detectados por Carlos desde su teléfono (360px): A57 (anchos dispares en barra de filtros de Caja) y A58 (desplazamiento lateral innecesario en Clases).
- **Aprobación de Carlos:** Visor HTML interactivo ([`visor.html`](../06-pruebas/PRU-02/evidencia/a57-a58/visor.html)) aprobado por Carlos («Apruebo la propuesta de A57 y A58. Aplicar la solución de CSS y cerrar la tarea.» / «Que no agregues estilos style="..." manuales en el HTML (usar solo reglas en app.css compiladas con Vite/Tailwind)»).
- **Implementación en CSS compartido (`resources/css/app.css`):**
  - **A57:** Regla `@media (max-width: 768px)` generalizada para todos los controles hijos de `.filtros-row` (selects, dates, months, inputs y buscadores) con `width: 100% !important; min-width: 100% !important; flex: 1 1 100% !important;`. El campo «Mes» y el selector «Operativo» igualaron su ancho exactamente a 278px (0px diferencia).
  - **A58:** Regla `@media (max-width: 640px)` para apilar la grilla de las tarjetas de clase a 1 columna (`.alumno-card .alumno-info { grid-template-columns: 1fr !important; }`) y fijar `overflow-x: hidden !important;` en `#clases-hoy-container`. Redujo el ancho de 455px a 328px eliminando el scroll horizontal. Escritorio (1280px) 100% intacto.
  - Cero código `style="..."` manual en vistas Blade. Compilado con `npm run build`.
- **Herramienta permanente:** Implementado `scripts/medir-ancho-movil.mjs` para auditoría automatizada en 360px con Chromium headless antes de futuros despliegues.
- **Suite y tablero:** Suite completa en verde en `wings_testing_gemini`. Tablero actualizado con A57 y A58 en estado `cerrado` (`verifica=Carlos`, 63 cerrados de 74). Documentado en `DEFECTOS.md`, `DEFECTOS.html` e `IMPLEMENTACION-A57-A58.md`.

## 2026-10-08 — LOG GEM CYE — A24 Segunda Vuelta: Implementación Definitiva de Mostrador Operativo

- **Objetivo:** Resolver definitivamente A24 según la regla ratificada por Carlos el 08/10 (*«las cajas de los operativos son individuales, el efectivo es lo compartido y van en serie una tras otra»*).
- **Elección de Carlos:** Aprobada la Opción 1 («Caja abierta de [Nombre]» con Kicker `TURNO EN CURSO`, texto explicativo de mostrador y botón secundario `Caja`).
- **Implementación:**
  - `app/Http/Controllers/OperativoDashboardController.php`: Unificada la consulta con `CajaService`/`CajaWebController` (`CajaOperativa::where('estado', 'ABIERTA')->first()`). Discrimina `$cajaCompanero` vs `$cajaPropia` independientemente de si la caja se abrió hoy o ayer; formatea textos temporales amigables.
  - `resources/views/operativo/dashboard.blade.php`: Reemplazo de textos confusos de "cajón compartido" y retiro de botones trampa (`Cobrar`, `Registrar`, `Detalle`). En situación 7 (propia de ayer), muestra «Tu caja sigue abierta» con botón `Cerrar`, eliminando la contradicción con «Cajón listo para iniciar».
- **Pruebas permanentes:** Creado `tests/Feature/InicioOperativoTest.php` (8 pruebas, 99 aserciones) cubriendo las ocho situaciones de mostrador y comprobando dinámicamente que ningún enlace de la tarjeta rebota, da 403 ni 500.
- **Suite completa:** 518 pruebas (516 aprobadas, 2 omitidas), 4.172 aserciones en `wings_testing_gemini`. `DocumentacionNoMienteTest` verde (sincronizados `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md`).
- **Evidencia y entrega:**
  - Capturas finales de las ocho situaciones en escritorio y móvil 375px (`evidencia/a24-vuelta-2/finales/`).
  - Documento de entrega: `docs/06-pruebas/PRU-02/IMPLEMENTACION-A24-VUELTA-2.md`.
  - Commit entregado: `decfb8c` (`Diseno-autorizado: Carlos eligio Opcion 1 para mostrador operativo en A24 segunda vuelta`).
  - Tablero: `A24` en estado `a_verificar` asignado a Claude (`paso="Verificar que ningún botón del inicio falle en las ocho situaciones"`).
- **Próximo paso pendiente:** Claude verifica en código y comportamiento real que ningún botón falle en las 8 situaciones.

## 2026-10-08 — LOG GEM CYE — Verificación independiente de A26, A39 y A35 (Trabajo de Claude)

- **Objetivo:** Verificar de forma cruzada e independiente los defectos A26 (celular de menores), A39 (acceso a Movimientos para OPERATIVO) y A35 (botón redundante Historial en Caja), resueltos por Claude en commit `975fae3`.
- **Base y entorno:** `wings_testing_gemini` exclusivamente. Commit base: `7122210`. Sin tocar código de aplicación, vistas ni CSS.
- **Verificación A26 (Celular de menores):**
  - Comprobado en base de datos: menor sin celular hereda teléfono del tutor en alta y edición; menor con celular propio lo conserva; mayor sin celular es rechazado con mensaje de validación; bordes exactos verificados (cumple 18 hoy exige celular, cumple 18 mañana lo permite vacío); menor sin celular ni tutor rechaza por tutor sin dar error 500.
  - Comprobado en vivo en navegador con Chrome Headless vía CDP: al ingresar fecha de menor en `/alumnos/create`, `#celular-obligatorio` se oculta dinámicamente (`hidden = true`); al cambiar a fecha de mayor, vuelve a mostrarse (`hidden = false`); checkbox "Mismo que el teléfono del tutor" sincroniza en tiempo real.
- **Verificación A39 (Operativo y Movimientos):**
  - Pantalla `/movimientos` abre para OPERATIVO (Sandra) y el ítem existe en el menú bajo "Plata".
  - Sandra ve su propio movimiento y el de su compañero de mostrador (Marcos); los movimientos y rubros del ADMIN (Sueldos, Alquileres) no se muestran en tabla ni en dropdowns de filtro, y sus importes no se suman en los totales de ingresos, egresos ni neto.
  - Ataque por GET directo (`?rubro_id=...` o `?subrubro_id=...`) neutralizado; rol PROFESOR da 403 y no tiene ítem en menú.
- **Verificación A35 (Botón redundante Historial en Caja):**
  - Auditados todos los botones y enlaces de `/caja` y `/caja/historial` para ADMIN y OPERATIVO.
  - Confirmado **NO-DEFECTO**: la captura original correspondía a `/caja` (donde el botón Historial va a `/caja/historial`). En `/caja/historial` el botón de retorno se llama "Volver" y va a `/caja`. Ningún botón es autorreferencial.
- **Evidencia y cierre:**
  - Informe detallado: `docs/06-pruebas/PRU-02/VERIFICACION-A26-A39-A35.md`.
  - Capturas y volcados en `docs/06-pruebas/PRU-02/evidencia/verificacion-a26-a39-a35/`.
  - Tablero: `A26`, `A39` y `A35` cerrados (58 de 72 cerrados). Actualizados `DEFECTOS.md` y `DEFECTOS.html`.

## 2026-10-08 — LOG GEM CYE — Implementación de A12 y A24 (Inicio del operativo: Opción 2 Variante B)

- **Objetivo:** Implementar la solución definitiva para A12 (organización del mostrador) y A24 (apertura obligatoria y cajón compartido) sobre `resources/views/operativo/dashboard.blade.php` y `OperativoDashboardController.php`, conforme a la Opción 2 Variante B elegida y autorizada por Carlos.
- **Autorización de diseño:** Carlos otorgó la autorización explícita: `Diseno-autorizado: Aprobada opcion 2 variante B para mostrador operativo en A12 y A24`.
- **Cambios realizados:**
  - `app/Http/Controllers/OperativoDashboardController.php`: Incorporada consulta de `cajaPropia`, detección de `cajaClub` (cajón compartido abierto por cualquier compañero) y `ultimaCaja`, pasándolos directamente a la vista.
  - `resources/views/operativo/dashboard.blade.php`: Reorganización total de la pantalla de mostrador:
    1. Alertas superiores de bloqueo y cajas rechazadas.
    2. Bloque principal del cajón: "Cajón listo para iniciar" con botón `Abrir` (`/caja/apertura`), o reconocimiento de turno propio/compartido con botones directos (`Cobrar`, `Registrar`, `Resumen`, `Detalle`).
    3. Tareas operativas en dos columnas niveladas (Variante B): *Clases de hoy* con scroll vertical interno limitado a 165px para no generar desbalances de altura; y *Atención a alumnos* con accesos directos a `/cobranza` y `/revision-cobranza`.
    4. Resumen financiero de recaudación (`Cobrado hoy`, `Cobros registrados`, `Cajas del turno`) ubicado al pie de la pantalla como soporte previo al cierre de caja.
  - Vistas compiladas sin errores (`view:cache && view:clear`).
  - Pruebas en verde en base `wings_testing_gemini`: `CobranzaOperativoTest` (7/7), `RevisionCobranzaOperativoTest` (6/6), `CajaCambioInicialA25Test` (22/22) y `CspSinCodigoIncrustadoTest` (2/2).
- **Entregables y estado:**
  - Documento de implementación: `docs/06-pruebas/PRU-02/IMPLEMENTACION-A12-A24.md`.
  - Tablero: `A12` y `A24` pasados a `estado=a_verificar hizo=Gemini tiene=nadie paso="Verificar en código y pantalla"` (AGENTS.md §6a: Gemini no auto-cierra; pasa a verificación por otro agente).

## 2026-10-07 — LOG GEM CYE — Relevamiento y propuesta de diseño para A12 y A24 (Inicio del operativo)

- **Objetivo:** Relevar el comportamiento actual de `/operativo` en las 7 situaciones reales del mostrador y elaborar una propuesta con dos opciones de diseño alternativas, capturas reales de Chromium (escritorio 1280px y celular 375px) y visor interactivo para que Carlos elija.
- **Entorno y base:**
  - Base descartable propia: `wings_testing_gemini` (AGENTS.md §6-bis). Código de partida: `c65d628`.
  - Vistas y CSS en `main` intactos: ninguna vista de producción fue modificada antes de la aprobación de Carlos.
- **Etapa 1 — Relevamiento del estado actual:**
  - Se reprodujeron las 7 situaciones reales del día mediante `GenerarEscenariosTest.php` y se probaron todos los enlaces mediante peticiones HTTP reales (`analisis-botones-antes.json`).
  - **Diagnóstico A24 (comprobado vivo):** En la tarjeta "Sin caja hoy", la vista actual ofrece el botón "Cobrar" (`/caja/cobrar`), el cual rebota con HTTP 302 Redirect a `/caja/apertura` debido a la precondición `aperturaNecesaria()` de A25. La pantalla invita a cobrar antes de declarar el efectivo inicial.
  - **Diagnóstico A12:** Al iniciar el día los tres cuadros métricos están inevitablemente en $0 y ocupan el espacio más visible sin orientar al operativo. Además, el controlador filtra estrictamente por `usuario_operativo_id`, por lo que si otro compañero abrió el cajón compartido a la mañana, el operativo de la tarde ve erróneamente "Sin caja hoy" y al intentar abrir el sistema le impide operar por caja ya abierta.
  - **Enlaces:** "Con deuda" enlaza al padrón `/alumnos` en vez de `/cobranza`; "Posibles inactivos" es mudo; y "Nueva caja" viola la regla de un solo verbo y lleva a `/caja`.
  - 14 capturas tomadas con Chrome headless (7 desktop 1280×900 y 7 celular 375×667 en marco de medición).
- **Etapa 2 — Propuesta de diseño (2 opciones):**
  - **Opción 1 (Mínima y fiel al layout actual):** Conserva la grilla actual. Reemplaza el botón "Cobrar" por "Abrir" directo a `/caja/apertura` con texto de guía ("Abrí la caja y declará el cambio inicial para empezar a cobrar"). Detecta si el cajón ya está abierto por otro compañero y muestra "Caja en curso - Abierta por [Nombre] desde las [H:i]. El cajón es compartido" con botones `Cobrar`, `Registrar`, `Detalle`. Corrige enlaces de Alumnos a `/cobranza` y `/revision-cobranza`.
  - **Opción 2 (Ergonómica orientada al mostrador — Recomendada por Gemini):** Ubica la acción de inicio de turno arriba de todo ("Cajón listo para iniciar" con botón prominente `Abrir`). Coloca en dos columnas centrales el trabajo del día (Clases a la izquierda, Atención a Alumnos con enlaces directos a la derecha). Mueve los 3 cuadros de recaudación abajo como datos de soporte al cierre.
  - 28 capturas reales generadas con Chromium para las dos opciones en las 7 situaciones.
- **Entregables y estado:**
  - Documento: `docs/06-pruebas/PRU-02/PROPUESTA-A12-A24.md`.
  - Visor interactivo lado a lado: `docs/06-pruebas/PRU-02/evidencia/a12-a24/visor-comparacion.html`.
  - Tablero actualizado: `A12` y `A24` pasados a `tiene=Carlos paso="Elegir opción: PROPUESTA-A12-A24.md"`.
  - Vistas y CSS en `main`: 0 modificaciones en `resources/views/operativo` ni CSS.
  - Commit y push completados (`a10dbdd`).
- **Dónde quedamos y qué sigue:**
  - **Estado actual:** FRENADO a la espera de que Carlos abra el visor o la propuesta y elija entre la Opción 1 (mínima) o la Opción 2 (ergonómica, recomendada por Gemini).
  - **Próximo paso (Etapa 3):** Con la respuesta de Carlos, implementar únicamente la opción elegida en `resources/views/operativo/dashboard.blade.php` y `app/Http/Controllers/OperativoDashboardController.php`, regenerar capturas finales, correr suite completa en verde y pasar a verificación por otro agente (§6a).

## 2026-10-07 — LOG GEM CYE — Verificación interactiva de A14, A27 y A53 (Corrección de Codex en celular)

- **Objetivo:** Ejecutar la verificación interactiva, exhaustiva e independiente de los defectos A14 (botones y errores a la vista en formularios largos), A27 (movimiento de caja operable en celular) y A53 (datos completos sin desborde en Grupos y selector de cobro), corregidos por Codex en commit `3d1808a`.
- **Entorno y herramientas:**
  - Base propia aislada: `wings_testing_gemini` (AGENTS.md §6-bis). Servidor local en puerto 8088. Assets compilados con `npm run build`.
  - Navegador real: Google Chrome Headless controlado mediante Chrome DevTools Protocol nativo (CDP vía WebSocket en Node.js v22 con `verificar_interactivo.mjs`).
  - Viewport de celular: Emulado por protocolo a 375×667 (`window.innerWidth === 375`, verificado en control de login).
  - Medidas exactas del DOM: todas obtenidas vía `getBoundingClientRect()`, `scrollWidth`, `clientWidth`, `window.scrollY`.
- **Resultados funcionales:**
  - **A14 (Alumnos y Profesores, alta y edición):** En las 4 pantallas, la barra de acciones `.mobile-form-actions` permanece dentro del viewport de 667 px al abrir (`top: 610.0, bottom: 667.0, height: 57.0`) y durante todo el scroll (`position: fixed`). La separación con el último campo deja margen libre suficiente (147.6 px a 610 px) evitando solapamientos. Al enviar vacío se despliega el resumen dinámico con 6 errores en alumnos y 8 en profesores. Al completar un campo (nombre), el error se remueve dinámicamente sin recargar; al vaciarlo reaparece; al completar todos se oculta. Con DNI repetido (`40111222`), el backend rechaza con 422 y cartel de servidor; al modificar el campo a `40111223`, pasa dinámicamente a "DNI: dato modificado; se comprueba al guardar". El cartel se ubica a 128 px debajo del encabezado sin solapar.
  - **A27 (Movimiento de caja):** Con ADMIN y OPERATIVO, los botones de Registrar y Cancelar están visibles al abrir en `y=610` (antes quedaban en `y=740`). El campo Observaciones es alcanzable y deja 131 px libres con la barra fija. Al enviar con errores, `#movimiento-error-resumen` lista los 4 campos faltantes. Se registró un movimiento real completo de punta a punta con OPERATIVO (Egreso, Efectivo, Subrubro 24, Monto $1.500), persistido en BD `movimientos_operativos` (id 1, creado a las 10:39:40) y con redirección exitosa a `/caja`.
  - **A53 (Grupos y Selector de cobro):** Con grupo largo de nombre extenso y 3 tarifas de 7 cifras ($1.250.000, $2.450.000, $3.850.000), en `/grupos` a 375 px nombre y tarifas se leen completos sin cortes (`scrollWidth <= clientWidth`) y `document.documentElement.scrollWidth = 375`. En el selector de cobro (`/caja/cobrar`), la tarjeta de la alumna y el grupo se leen completos con saldo `$1.250.000` y sin desborde horizontal (375 px). Grupos cortos conservan aspecto estándar sin renglones vacíos.
  - **No regresión:** El aviso de "salir sin guardar" de A4 en `alumnos-form.js` funciona intacto (disparó el diálogo `confirm` con texto "Tenés cambios sin guardar. ¿Querés salir y perder lo cargado?"). En escritorio (1280×900), las 7 vistas comprobadas muestran la barra en flujo normal (no fixed) y los resúmenes móviles ocultos. `CspSinCodigoIncrustadoTest` pasó en verde (2/2 tests).
- **Evidencia y artefactos:** Carpeta `docs/06-pruebas/PRU-02/evidencia/verificacion-a14-a27-a53/` con `cdp.mjs`, `preparar_escenario.php`, `verificar_interactivo.mjs`, `mediciones-verificacion.json` y 30 capturas PNG generadas desde la sesión viva de Chrome.
- **Dictamen y entrega:** A14 APROBADO, A27 APROBADO, A53 APROBADO. Tareas actualizadas en el tablero pasando a Claude (`tiene=Claude`) para contrastar el informe contra el repositorio y proceder al cierre (§6a). Informe completo: `docs/06-pruebas/PRU-02/VERIFICACION-A14-A27-A53.md`.

## 2026-10-07 — LOG GEM CYE — Verificación real e independiente de A15 y A16 (Programación de clases)

- **Objetivo:** Verificar de punta a punta y con datos propios en base `wings_testing_gemini` los defectos A15 (aviso de bloques de cancha y confirmación obligatoria) y A16 (programación recurrente con horarios por día), implementados por Codex (`05dd962`).
- **Recorrido funcional y datos:**
  - A15: Horario 17:30–18:30 devolvió aviso exacto de 2 bloques (17–18 y 18–19), conservó inputs y dejó 0 clases y 0 asignaciones en DB. Confirmar con firma válida persistió exactamente 1 clase. Alterar campos (hora fin, hora inicio, fecha, grupo, profesor, período) invalidó firmas viejas (0 clases). POSTs apócrifos con "si", hash falso o firma de otro usuario fueron bloqueados. Horario 17:00–18:00 guardó directo sin aviso. Bordes según Contrato V2 (17:30–18:00, 17:00–18:30, 17:01–18:00, 17:00–18:01 avisan; 17:00–18:00 no) verificados rigurosamente.
  - A16: Serie de 3 días con 3 horarios distintos (Lun 16–17, Mié 17–18, Vie 18–19) creó 6 clases con un solo `serie_id`. Horas obligatorias validadas por día. Conflicto de horario en segundo día revirtió atómicamente la serie completa (1 clase previa antes, 1 clase después). Rechazo de fechas pasadas, inactivos y profesor de otro deporte confirmado. Cronograma de 76 clases en 6 cargas (`relevamiento-76-clases.json`) reproducido de punta a punta con 6 series UUID.
  - Regresiones y roles: Edición de clase, cancelación con motivo y toma de asistencia operaron sin fallas. Matriz de roles comprobada: Operativo (cancelar autorizado; crear/editar 403), Profesor (asistencia autorizada; cancelar/crear/editar 403).
- **Evidencia y capturas:** Reproductor `docs/06-pruebas/PRU-02/evidencia/verificacion-a15-a16/VerificacionA15A16Test.php` (431 aserciones pasadas en verde, 0 fallos). 8 páginas HTML emitidas por Laravel y 16 capturas con Chrome Headless (escritorio 1280×900 y celular 375 px en marco iframe) en carpeta `evidencia/verificacion-a15-a16/`. Exclusiones declaradas con veracidad (capturas basadas en respuestas HTML servidas localmente; sin sesión interactiva de ratón/teclado).
- **Dictamen y entrega:** A15 APROBADO, A16 APROBADO. Tareas pasadas a Claude (`tiene=Claude`) en el tablero para contrastar el informe contra el repositorio y proceder al cierre (§6a). Informe completo: `docs/06-pruebas/PRU-02/VERIFICACION-A15-A16.md`.

## 2026-10-06 — LOG GEM CYE — Verificación real e interactiva de A25 (apertura, arqueo y cierre de caja)

- **Objetivo:** Ejecutar la verificación real, completa e independiente de A25 (mostrador, arqueo y cambio inicial, implementado por Codex en `30f38f8`), tras la anulación del informe previo por falta de recorrido en pantalla.
- **Entorno y datos:** Base descartable propia `wings_testing_gemini` (AGENTS.md §6-bis). Se utilizaron importes propios de Gemini (Turno 1: inicial $14.000, cobro efvo $25.000, transf $18.000, egreso $6.000, cobro admin directo sin caja $32.000; arqueo esperado $33.000; contado $31.500 con faltante de -$1.500, cambio retenido $12.000, entrega $19.500; Turno 2: heredado $12.000, recibido $9.000 con motivo; cobro $20.000, egreso $4.000, contado $26.200 con sobrante de +$1.200, retenido $10.000, entrega $16.200; rechazo por admin y corrección conservando conteo físico).
- **Pruebas ejecutadas:**
  - Suite de 26 tests permanentes de Codex (`CajaCambioInicialA25Test` y `CajaArqueoConcurrenteA25Test`): 26 passed, 142 aserciones, 0 fallos (evidencia en `salida-tests.txt`).
  - Test de recorrido interactivo de punta a punta (`tests/Feature/RecorridoVerificacionA25Test.php`): 97 aserciones comprobadas en vivo sobre datos y respuestas HTTP.
  - 10 ataques por POST directo probados y bloqueados (skip inicial, skip confirmación, inicial negativo, cambio mayor a contado, cierre ajeno, turnos concurrentes, validar sin cierre, roles no autorizados).
- **Evidencia visual y capturas:** 23 capturas reales generadas con Chrome (escritorio 1280x900 y celular en marco 375 iframe, incluyendo login de control) y 18 páginas HTML renderizadas almacenadas en `docs/06-pruebas/PRU-02/evidencia/verificacion-a25/`.
- **Dictamen y entrega:** APROBADO. En cumplimiento estricto del protocolo, Gemini no cierra en el tablero: el expediente pasa a Claude (`tiene=Claude`) para contrastar el informe contra el repositorio y proceder al cierre definitivo. Informe completo en `docs/06-pruebas/PRU-02/VERIFICACION-A25.md`.

## 2026-10-06 — LOG GEM CYE — Auditoría visual completa de 51 pantallas para la regla compartida (.filtros-actions)

- **Objetivo:** Cumplir el mandato estricto de `AGENTS.md` §1 ("si el cambio toca app.css o cualquier pieza compartida, las capturas son de todas las pantallas que cambian") tras haber configurado `.filtros-actions { justify-content: flex-end; }` en celular.
- **Alcance auditado:** 51 pantallas del sistema completo generadas directamente desde la aplicación con base de datos real (`wings_testing_gemini`) y capturadas con Chrome Headless en escritorio (1280 px) y celular (375 px dentro de marco iframe):
  - Alumnos (listado, alta, edición, ficha).
  - Caja (índice, apertura, cierre, historial, cobrar alumno, cobrar selector, configuración efectivo, editar turno, cancelar cobro, nuevo movimiento).
  - Cashflow & Movimientos (cashflow principal, movimiento directo, movimientos general).
  - Clases (listado, alta única/recurrente, edición, toma de asistencia).
  - Cobranza y Revisión de Cobranza (ambos listados con filtros).
  - Liquidaciones (listado y creación con selección de profesor).
  - Catálogos de Grupos, Deportes, Niveles, Profesores, Tipos de Caja, Usuarios (todos los listados, altas y ediciones).
  - Catálogos de Rubros y Subrubros (listado con Opción A nativa y formularios de alta/edición).
- **Resultados de la auditoría:**
  - 102 capturas generadas y verificadas. Cero desbordes horizontales ni solapamientos.
  - La alineación `justify-content: flex-end` en celular resulta natural y consistente en toda la aplicación: agrupa los botones `Volver`/`Cancelar` y `Guardar`/`Registrar` hacia la derecha sin quebrar líneas, y sitúa los botones de filtrado al alcance del pulgar derecho.
  - En `revision-cobranza/index.blade.php`, se identificó que no usaba `.filtros-actions` (tenía un `div` suelto); se mantiene operativo sin alteraciones.
- **Entregables:**
  - Visor interactivo completo con selector de resolución y filtro por módulos: `docs/06-pruebas/PRU-02/evidencia/celular-compartido/todas/visor-completo.html`.
  - Informe detallado de verificación: `docs/06-pruebas/PRU-02/AUDITORIA-FILTROS-ACTIONS-COMPLETA.md`.
  - Estado en tableros `DEFECTOS.md` y `DEFECTOS.html`: permanece como `HECHO (Gemini), a revisar` para control independiente de otro agente (§6a).

## 2026-10-06 — LOG GEM CYE — Ajuste fino en celular pedido por Carlos (botones a la derecha, alineación Clases y Rubros Opción A)

- **Objetivo:** Aplicar las correcciones solicitadas expresamente por Carlos sobre el comportamiento en celular (375 px):
  1. Botones alineados a la derecha en celular: en `resources/css/app.css` (`@media (max-width: 768px)`), se cambió `.filtros-actions` a `justify-content: flex-end;`, alineando `Limpiar` y `Filtrar` en Movimientos y `Limpiar` en Grupos hacia la derecha.
  2. Clases (`resources/views/clases/show.blade.php`): botón `Guardar` al pie alineado en la misma línea vertical que `Volver` en la cabecera (ambos con ancho 96 px y padding de 1.5rem al margen derecho).
  3. Rubros (`resources/views/rubros/index.blade.php`): Carlos seleccionó la **Opción A** (tabla nativa con scroll horizontal táctil `overflow-x: auto; min-width: 480px`) para conservar el diseño canónico de Wings sin alterar encabezados ni columnas. Botonera inferior (`Subrubro`, `Editar`, `Eliminar`) alineada a la derecha en una sola línea con `gap: 0.5rem` y `min-width: 76px`.
  4. Los cambios aplican exclusivamente a pantallas de celular sin alterar desktop ni tablet.
- **Evidencia y verificación:**
  - Regeneradas las capturas reales desde el sistema andando con marco de 375 px y en escritorio 1280 px: `05-grupos-celular-375.png`, `07-clase-asistencia-celular-375.png`, `08-rubros-celular-375.png` y `09-movimientos-celular-375.png`.
  - Suite completa: 468 passed, 2 skipped (470 total) en base `wings_testing_gemini`.
  - Vistas compiladas y verificadas con `php artisan view:clear && php artisan view:cache`.

## 2026-10-06 — LOG GEM CYE — Paquete compartido de celular resuelto (A14, A20, A27, A28, A33, A36, A40, A41, A53)

- **Objetivo:** Resolver el paquete de 9 defectos de desborde y visualización en celular (A14, A20, A27, A28, A33, A36, A40, A41, A53) según directivas explícitas de Carlos (cero JS inline, componentes compartidos y botones con verbos cortos alineados a la derecha).
- **Implementación por pantalla:**
  - **A40 (Dashboard Admin):** Deuda Total estilizada con tipografía fluida `clamp(1.1rem, 4vw, 1.6rem)` y contención `word-break: break-word`, asegurando cifras de más de 7 dígitos contenidas dentro de la tarjeta sin desborde.
  - **A27 (Nuevo Movimiento de Caja):** Iconos SVG en cada label; campo `observaciones` opcional (retirado `*` y `required` en vista, validación `nullable` en `CajaWebController`); JS inline extraído a `resources/js/caja-movimiento.js` compilado por Vite (reduciendo scripts inline CSP a 15); botones `Volver` y `Registrar` alineados a la derecha (`justify-end`).
  - **A20 (Cashflow):** Barra `stats-bar` adaptada con layout responsivo (`flex-col sm:flex-row`), iconos SVG y etiquetas uppercase semánticas; botones `Nuevo` y `Exportar` abajo a la derecha (`justify-end`) sin tapar los saldos.
  - **A28 y A53 (Grupos y Cobrar cuota):** Cuadrículas de datos adaptadas a `grid-cols-1 sm:grid-cols-3` con contención de texto y tarifas (`break-words min-w-0`); en Grupos, interruptor Activo separado a la izquierda y botón `Editar` a la derecha.
  - **A33 (Asistencia en Clases):** Filas de alumnos compactas de ~54px (`flex items-center justify-between`) con dot + nombre en negrita arriba, plan/DNI abajo y checkbox táctil `Presente` a la derecha; acciones superior e inferior con `justify-end`, eliminando el desplazamiento kilométrico.
  - **A36 (Rubros):** Vista dual responsiva: tarjetas compactas apiladas en móvil (`sm:hidden`) con nombre y badge de caja ("Solo ADMIN" / "OPERATIVO") siempre visibles y legibles; tabla clásica conservada para desktop (`hidden sm:table`).
  - **A41 y A14 (Filtro y Pantalla de Movimientos):** Rótulos visibles "Desde:" y "Hasta:" en mayúsculas semánticas sobre los selectores de fecha; botones con `justify-end` y tabla con desplazamiento horizontal seguro.
- **Evidencia visual:** 36 capturas reales tomadas sobre el sistema corriendo con usuario autenticado (18 en escritorio 1280px y 18 en celular 375px con marco de iframe según `AGENTS.md` §1), integradas en el tablero interactivo `docs/06-pruebas/PRU-02/evidencia/celular-compartido/index.html`.
- **Pruebas y verificación:**
  - `SaldoInicialTipoCajaTest`: 8 passed (135 assertions).
  - `CspSinCodigoIncrustadoTest`: 2 passed (2 assertions; constante de scripts inline actualizada a 15).
  - `DocumentacionNoMienteTest`: 1 passed (7 assertions, 470 pruebas declaradas exactas).
  - `DefectosNoDivergenTest`: 3 passed (12 assertions).
  - `php artisan view:clear && php artisan view:cache`: Vistas compilan sin errores.
- **Estado:** Actualizados `DEFECTOS.md` y `DEFECTOS.html` marcando los 9 defectos como `HECHO (Gemini), a revisar 06/10` (no cerrados) para control independiente de otro agente (§6a).

## 2026-10-06 — LOG GEM CYE — Verificación independiente de A4 y A5 (aprobados y cerrados)

- **Objetivo:** Verificación independiente en código, pantalla interactiva y tests de los defectos A4 (aviso al fallar guardado de alumno y protección de salida) y A5 (filtrado y validación de profesores por deporte en clases), entregados por Codex CAB (`373f9b4`, `74d8dae`).
- **Comprobaciones y resultados:**
  - **A4 (Aviso superior y prevención de salida):**
    - Comprobado en Chrome Headless interactivo con servidor local y CDP (`scratch/test-navegacion.mjs`):
      1. Navegación por menú lateral con datos editados: interceptada por `window.confirm('Tenés cambios sin guardar. ¿Querés salir y perder lo cargado?')`. Al cancelar (`false`), `defaultPrevented = true` y permanece en el formulario.
      2. Botón Cancelar (Volver): mismo comportamiento; si se acepta salir, marca `descartando = true` y navega.
      3. Cierre/recarga de pestaña: `beforeunload` previene la descarga (`event.returnValue = ''`), disparando el diálogo nativo del navegador.
      4. Formulario limpio: permite navegación y salida libre sin cartel.
    - Capturas reales desde la aplicación con marco 375 px y en escritorio: el cartel `#alumno-error-resumen` (`.ds-flash.ds-flash--error`) se sitúa inmediatamente arriba del formulario, visible en el primer tercio de pantalla sin scroll. Datos y plan se conservan tras error de validación.
  - **A5 (Profesores por deporte):**
    - En interfaz (`clases-form.js`): casillas de profesores ocultas y deshabilitadas hasta elegir grupo; al seleccionar grupo solo se habilitan los profesores activos de ese deporte.
    - En servidor (`ClaseWebController@validarProfesoresDelDeporte`): rechazo con `ValidationException` en alta única, recurrente, edición y reasignación ante profesores ajenos o inactivos.
    - Edición de alumno: comprobado que no rompe tutor obligatorio para menores, sincronización de inscripción ni conservación de planes.
  - **Pruebas y dictamen:** 11 tests de `FormulariosA4A5Test` pasando en base `wings_testing_gemini`. Ambos defectos pasan a **CERRADO** (29 cerrados de 72). Tableros `DEFECTOS.md` y `DEFECTOS.html` actualizados (`DefectosNoDivergenTest` verde). Informe completo con capturas en `docs/06-pruebas/PRU-02/VERIFICACION-A4-A5.md`.

## 2026-10-06 — LOG GEM CYE — A37: evidencia real desde el sistema y ajuste en dos renglones en celular

- **Corrección de evidencia:** La captura previa de `ficha-375-despues.png` provenía de una maqueta HTML estática escrita a mano con comentarios Blade literales (`{{-- ... --}}`) y no era prueba válida según `AGENTS.md` §1 ("la captura tiene que salir del sistema andando"). Se eliminaron las maquetas manuales y se regeneró la evidencia íntegramente desde la aplicación real mediante `tests/Feature/CapturaFichaAnularTest.php` (`WINGS_CAPTURAS=1`).
- **Ajuste de diseño responsive:**
  - En `resources/views/alumnos/show.blade.php`, se perfeccionaron las filas de deudas e historial de pagos adoptando estructura en dos renglones en móvil (`flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 p-2 rounded`):
    - Renglón 1: fecha/período y monto.
    - Renglón 2: bloque de botones (`Recibo`, `Anular` o `Cobrar`, `Condonar`) alineados a la derecha (`w-full sm:w-auto justify-end`).
  - La tarjeta `.filtros-card` (343 px útiles) y su contenido quedan 100% contenidos dentro del viewport de 375 px sin ningún desplazamiento ni corte.
- **Verificación y pruebas:**
  - `tests/Feature/FichaAlumnoResponsiveA37Test.php` actualizada y pasando (`4 passed, 23 assertions` con `DefectosNoDivergenTest`).
  - Capturas reales guardadas en `docs/06-pruebas/PRU-02/capturas-a37/` (`ficha-375-despues.png` en 375 px y `ficha-escritorio-despues.png` en 1280 px).
- **Estado:** A37 permanece como `HECHO (Gemini), a revisar 05/10` a la espera de la verificación independiente de Claude/Codex.

## 2026-10-05 — LOG GEM CYE — Verificación independiente de P1 (Primera Carga por Excel)

- **Objetivo:** Auditar y verificar de manera independiente en código, archivos Excel, datos y capturas la entrega P1 implementada por Codex CAB en el commit `d530c85`.
- **Comprobaciones y resultados:**
  - **Plantilla y Catálogos:** `PrimeraCargaExcelService::plantilla()` genera hoja Alumnos vacía de 38 columnas (200 filas preparadas) con validaciones nativas de lista en columnas J a N leyendo catálogos activos con precio. Guía con ejemplos e instructivo en hoja separada.
  - **Revisión y detección múltiple:** `club-con-errores.xlsx` probado contra `revisar()`; detecta los 6 errores simultáneos (J3, K3, L3, I4, O5, P5) sin escribir en base de datos. `guardarInforme()` genera Excel con columna AM (Errores) preservando intactas las 7.638 celdas A:AL originales con sus tipos de datos.
  - **Carga transaccional:** Archivo corregido crea 4 alumnos, 6 cuotas ($296.000) y 1 inscripción ($5.000) sumando $301.000 exactos de deuda. Sin cobros, sin movimientos de caja ni descuentos automáticos de bienvenida. Atomicidad garantizada con `DB::transaction()`.
  - **Formatos:** Sanitización en `FormatoExcelCargaService` para montos con punto de miles ("52.000" -> $52.000), períodos de 5 dígitos (92026 -> 2026-09), texto 082026 y espacios sobrantes.
  - **Deshacer:** Restricción activa si existen cobros registrados (incluso anulados). Si está libre, borra exactamente lo creado y regresa a PENDIENTE.
  - **Permisos y flujo:** Redirección automática de ADMIN mientras esté PENDIENTE; bloqueo de alta manual por URL vía middleware `PrepararPrimeraCarga`. HTTP 403 para Operativo y Profesor.
  - **Diseño móvil (375 px) y escritorio:** 4 pasos apilados, botones de un verbo, tabla de errores legible sin desbordes.
- **Dictamen:** P1 APROBADA. Informe completo registrado en `docs/06-pruebas/PRU-02/VERIFICACION-P1.md`.
- **Siguiente paso:** Pasar a Carlos para habilitación de retiro de importadores viejos y ventana de despliegue.

## 2026-10-05 — LOG GEM CYE — Implementación de P2 Entrega 2 (A17, A34, A3)

- **Objetivo:** Resolver los tres defectos interconectados de la ficha del alumno y el cobro: A17 (cobrar desde la ficha), A34 (historial y reimpresión/descarga de recibos) y A3 (cobro por adelantado).
- **Cambios implementados:**
  - **A17 (Frena):** Añadido botón principal **Cobrar** (`x-ds.button variant="primary"`, Objeto A) en la cabecera de la ficha del alumno (`resources/views/alumnos/show.blade.php`) para Admin y Operativo (`!auth()->user()?->isProfesor()`), con destino `/caja/cobrar/{alumnoId}`. Añadido botón de fila **Cobrar** (`ds-btn-row`, Objeto C) en cada fila de deuda pendiente de la sección «Estado de cobranza», junto a Condonar.
  - **A34 (Falta):** Sección «Historial de pagos» en `alumnos/show.blade.php` con enlace directo **Recibo** (`ds-btn-row ds-btn-row--sec`) con `target="_blank"`, que abre el PDF emitido por `ReciboService` / DomPDF con opciones estándar de impresión y descarga. Pagos anulados identificados con badge «Anulado», importe tachado y sello de anulación en el PDF. Ampliado el límite de consulta en `AlumnoWebController::show()` de 8 a 12 pagos para cubrir un año de historial.
  - **A3 (Molesta):** `CajaWebController::cobrar()` proyecta y ofrece en memoria los períodos futuros (próximos 2 meses) al precio de lista vigente del plan activo (`$plan->precio_mensual`), con badge «Adelantado» (`var(--color-info)`). Al cobrar un período adelantado, `CajaWebController::pagar()` y `PagoCuotaService::obtenerOcrearDeuda()` crean la `DeudaCuota` con estado `PAGADA`. Al llegar el día 1, el comando `cobranza:generar-deudas` la omite sin duplicar (`$contSkipped++`). El selector `/caja/cobrar` permite buscar a cualquier alumno activo por nombre, apellido o DNI para iniciar cobro adelantado.
  - **Vistas tocadas:** `resources/views/alumnos/show.blade.php` y `resources/views/caja/cobrar.blade.php`. Respetadas las reglas de diseño (un solo verbo por botón, tokens semánticos, sin hex hardcodeados, sin Alpine ni Livewire).
- **Pruebas y verificaciones:**
  - Suite de pruebas propia en `wings_testing_gemini`: 8 pruebas nuevas / 40 aserciones en `tests/Feature/P2Entrega2FichaCobroAdelantadoTest.php` 100% verdes.
  - Pruebas de regresión (`CobroReciboAccesoTest`, `CobranzaEntrega1Test`, `SaldoUnicoPorAlumnoTest`): 15 pruebas / 77 aserciones 100% verdes.
- **Siguiente paso:** Pase de verificación independiente a otro agente (Codex / Claude) conforme a AGENTS.md §6a.

## 2026-10-05 — LOG GEM CAB — Verificación independiente de A43 y Permisos (A29, A30, A31)

- **Objetivo:** Verificar de forma independiente en código, base de datos y navegador real (escritorio y 375 px) las entregas de Codex CAB correspondientes a A43 (commit `218ffc5`) y Permisos A29/A30/A31 (commit `97cf933`).
- **Comprobaciones y resultados:**
  - **A43 (Alta manual con fecha de ingreso antigua):** Comprobado en `PagoCuotaService.php:363-405`, `AlumnoWebController.php:194-263`, `alumnos/_form.blade.php:192-206` y script `alumnos-inscripcion.js:31-94`. En navegador real: fecha corriente (`2026-10-05`) oculta el aviso y genera cuota automática con porcentaje del día; fecha cerrada (`2020-01-20`) muestra advertencia dinámica antes de guardar con importes congelados y radios obligatorios. La opción "Sí" generó cuota corriente al 100% ($30.000) sin cuotas históricas y dejó al alumno en estado `En plazo`; la opción "No" suprimió la cuota, conservó la inscripción ($5.000) y dejó al alumno en estado `Al día` (la inscripción impaga no vuelve deudor a nadie). Auditoría `alta_cuota` en JSON verificada en base de datos (`modo`, `usuario_id`, fecha, período, monto). Visualización en 375 px limpia sin desbordes.
  - **Permisos (A29, A30, A31):** Comprobado en `EnsureAdminWeb.php:23`, `RejectProfesorWeb.php:15`, `403.blade.php:8-13` y `UsuarioWebController.php:264`. En navegador real con los 3 roles: Operativo navegando a `/cashflow`, `/liquidaciones`, `/configuraciones`, `/usuarios` y `/admin/dashboard` recibe HTTP 403 con mensaje explicativo en castellano ("No podés entrar a esta sección") y botón Volver que regresa a `/operativo` conservando la sesión; `/admin` y `/caja/validaciones` devuelven 404 sin exponer datos. Profesor recibe 403 en administración y en `/alumnos`, `/caja`, `/grupos` con Volver a `/clases`. Admin común en cuenta protegida de superadmin recibe 403 con Volver a `/admin/dashboard`. Cero datos filtrados. Nunca redirige al login.
  - **Pruebas:** Suite completa en base propia `wings_testing_gemini` verde con 380 pruebas / 2242 aserciones.
- **Dictamen:** Aprobadas ambas entregas. Defectos A29, A30, A31 y A43 CERRADOS. Informe y capturas en `docs/06-pruebas/PRU-02/VERIFICACION-A43-PERMISOS.md`.
- **Siguiente paso:** Habiendo verificado A43, Permisos y Configuración (A11), y con la Entrega 1 de Cobranza aprobada por Codex, continuar con la Entrega 2 de Cobranza según orden de trabajo.

---

Entradas anteriores (11/09/2026 al 04/10/2026) archivadas intactas en [LOG-GEMINI.md](../99-archivo/bitacoras/2026-10-06/LOG-GEMINI.md).
